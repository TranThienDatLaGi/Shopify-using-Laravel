<?php

namespace App\Services;

use App\Jobs\ApplyRuleToProduct;
use App\Jobs\BulkProductActionJob;
use App\Jobs\ResetProductPrice;
use App\Models\ProductPriceBackup;
use App\Models\Rule;
use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class RuleService
{
    protected ProductService $productService;
    protected ShopifyService $shopifyService;

    public function __construct(ProductService $productService, ShopifyService $shopifyService)
    {
        $this->productService  = $productService;
        $this->shopifyService  = $shopifyService;
    }
    public function setRuleToProduct(
        string $shopDomain,
        string $accessToken,
        string $productId,
        string $discount,
        string $discountType,
        string $basedOn,
        int $ruleID,
    ): ?array {
        // Lấy Variant
        $variantsData = $this->productService->getVariantsByProductID($shopDomain, $accessToken, $productId);
        $variants = $variantsData['variants'] ?? [];
        if (empty($variants)) {
            return [
                'success' => false,
                'message' => 'No variants found for this product',
                'error'   => null,
            ];
        }

        $variantInputs = [];
        // Ép giá trị discount sang float
        $discountValue = (float) $discount;

        foreach ($variants as $variantEdge) {
            $variantNode = $variantEdge['node'];
            $backupVariant = ProductPriceBackup::byShop($shopDomain)
                ->byProduct($productId)
                ->byVariant($variantNode['id'])
                ->first();
            // Chuyển đổi base on
            // Log::info('Backup variant', ['data' => $backupVariant]);
            $basedOn = match ($basedOn) {
                'current_price'    => 'price',
                'compare_at_price' => 'compareAtPrice',
                default            => 'price',
            };

            $oldPrice = $variantNode[$basedOn] ?? null;
            if ($basedOn == 'compareAtPrice' && $oldPrice === null) {
                $oldPrice = $variantNode['price'] ?? null;
            }
            if ($oldPrice === null) {
                continue;
            }
            $oldPrice = (float) $oldPrice;
            // Log::info('oldPrice', ['oldPrice' => $oldPrice]);
            $canApplyRule = false;
            if (!$backupVariant) {
                ProductPriceBackup::create([
                    'shop_name'                 => $shopDomain,
                    'product_id'                => $productId,
                    'variant_id'                => $variantNode['id'],
                    'original_price'            => isset($variantNode['price']) ? (float) $variantNode['price'] : null,
                    'original_compare_at_price' => isset($variantNode['compareAtPrice']) ? (float) $variantNode['compareAtPrice'] : null,
                    'rule_id'                   => $ruleID,
                ]);
                Log::info('Create Backup variant', ['backup' => $backupVariant]);
                $canApplyRule = true;
            } else {
                if (
                    ($backupVariant->rule_id === null) &&
                    (
                        ($basedOn === 'price' && $backupVariant->original_price == $oldPrice) ||
                        ($basedOn === 'compareAtPrice' && $backupVariant->original_compare_at_price == $oldPrice)
                    )
                ) {
                    $backupVariant->update(['rule_id'=>$ruleID]);
                    $canApplyRule = true;
                }
            }

            if ($canApplyRule) {
                // --- Tính giá mới ---
                if ($discountType === 'percent') {
                    $newPrice = $oldPrice * (1 - $discountValue / 100);
                } else { // fixed
                    $newPrice = max(0, $oldPrice - $discountValue);
                }
                $input = ['id' => $variantNode['id']];
                $currentPrice = isset($variantNode['price']) ? (float) $variantNode['price'] : null;
                $compareAtPrice = isset($variantNode['compareAtPrice']) ? (float) $variantNode['compareAtPrice'] : null;
                if ($basedOn === 'price') {
                    // Nếu giảm theo current_price:
                    // compareAtPrice = current_price gốc, price = giá mới sau giảm
                    $input['compareAtPrice'] = (string) round($currentPrice, 2);
                    $input['price'] = (string) round($newPrice, 2);
                } elseif ($basedOn === 'compareAtPrice') {
                    if ($compareAtPrice === null) {
                        // Nếu không có compare_at_price thì lấy current_price gốc làm compareAtPrice
                        $input['compareAtPrice'] = (string) round($currentPrice, 2);
                        $input['price'] = (string) round($newPrice, 2);
                    } else {
                        // Nếu có compare_at_price thì giảm giá dựa trên compareAtPrice, giữ nguyên compareAtPrice
                        $input['price'] = (string) round($newPrice, 2);
                        $input['compareAtPrice'] = (string) round($compareAtPrice, 2);
                    }
                }
                Log::info('Variant input', $input);
                $variantInputs[] = $input;
            }
        }

        if (empty($variantInputs)) {
            return [
                'success' => false,
                'message' => 'No valid variants to update',
                'error'   => null,
            ];
        }

        // --- Gửi mutation ---
        $mutation = <<<GRAPHQL
    mutation productVariantsBulkUpdate(\$productId: ID!, \$variants: [ProductVariantsBulkInput!]!) {
      productVariantsBulkUpdate(productId: \$productId, variants: \$variants) {
        product {
          id
          title
        }
        productVariants {
          id
          title
          price
          compareAtPrice
        }
        userErrors {
          field
          message
        }
      }
    }
    GRAPHQL;

        $variables = [
            'productId' => $productId,
            'variants'  => $variantInputs,
        ];

        $response = $this->shopifyService->graphqlRequest($shopDomain, $accessToken, $mutation, $variables);
        $errors = data_get($response, 'productVariantsBulkUpdate.userErrors', []);

        return [
            'success' => $response !== null && empty($errors),
            'data'    => data_get($response, 'productVariantsBulkUpdate', []),
            'error'   => $errors,
        ];
    }

    public function resetPriceForProduct(string $shopDomain, string $accessToken, string $productId, int $ruleID): ?array
    {
        $variantsData = $this->productService->getVariantsByProductID($shopDomain, $accessToken, $productId);
        $variants = $variantsData['variants'] ?? [];
        if (empty($variants)) {
            return [
                'success' => false,
                'message' => 'No variants found for this product',
                'error'   => null,
            ];
        }

        $variantInputs = [];

        foreach ($variants as $variantEdge) {
            $variantNode = $variantEdge['node'] ?? null;
            if (!$variantNode || empty($variantNode['id'])) {
                continue;
            }
        Log::info('shopDomain: ', ['shopDomain' => $shopDomain]);
        Log::info('productId: ', ['productId' => $productId]);
        Log::info('variantID: ', ['variantID' => $variantNode['id']]);
        Log::info('ruleID: ', ['ruleID' => $ruleID]);

            $backupVariant = ProductPriceBackup::byShop($shopDomain)
                ->byProduct($productId)
                ->byVariant($variantNode['id'])
                ->byRule($ruleID)
                ->first();
            Log::info('backupVariant: ', ['data'=> $backupVariant]);
            if ($backupVariant) {
                $input = ['id' => $variantNode['id']];
                $input['price'] = $backupVariant->original_price !== null
                    ? (string) round($backupVariant->original_price, 2)
                    : (string) $variantNode['price'];

                $input['compareAtPrice'] = $backupVariant->original_compare_at_price !== null
                    ? (string) round($backupVariant->original_compare_at_price, 2)
                    : ($variantNode['compareAtPrice'] ?? null);

                $variantInputs[] = $input;

                // Sửa lại chỗ này ✅
                $backupVariant->update(['rule_id' => null]);
            }
        }

        if (empty($variantInputs)) {
            return [
                'success' => false,
                'message' => 'No variants to reset',
                'error'   => null,
            ];
        }

        $mutation = <<<GRAPHQL
    mutation productVariantsBulkUpdate(\$productId: ID!, \$variants: [ProductVariantsBulkInput!]!) {
      productVariantsBulkUpdate(productId: \$productId, variants: \$variants) {
        product {
          id
          title
        }
        productVariants {
          id
          title
          price
          compareAtPrice
        }
        userErrors {
          field
          message
        }
      }
    }
    GRAPHQL;

        $variables = [
            'productId' => $productId,
            'variants'  => $variantInputs,
        ];

        try {
            $response = $this->shopifyService->graphqlRequest($shopDomain, $accessToken, $mutation, $variables);
            $errors = data_get($response, 'productVariantsBulkUpdate.userErrors', []);

            return [
                'success' => $response !== null && empty($errors),
                'data'    => data_get($response, 'productVariantsBulkUpdate', []),
                'error'   => $errors,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to reset product prices',
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function getProductsByRule(
        string $shopDomain,
        string $accessToken,
        string $appliesTo,
        array|string $appliesToValue
    ): ?array {
        $allProducts = [];
        switch ($appliesTo) {
            case 'products':
                $allProducts = (array) $appliesToValue;
                break;

            case 'tags':
                foreach ((array) $appliesToValue as $tag) {
                    $tagProducts = $this->productService->getAllProductIdByType($shopDomain, $accessToken, 'tag', $tag);
                    if (!empty($tagProducts['products'])) {
                        $allProducts = array_merge($allProducts, $tagProducts['products']);
                    }
                }
                break;

            case 'vendors':
                foreach ((array) $appliesToValue as $vendor) {
                    $vendorProducts = $this->productService->getAllProductIdByType($shopDomain, $accessToken, 'vendor', $vendor);
                    if (!empty($vendorProducts['products'])) {
                        $allProducts = array_merge($allProducts, $vendorProducts['products']);
                    }
                }
                break;

            case 'collections':
                foreach ((array) $appliesToValue as $collectionId) {
                    $collectionProducts = $this->productService->getAllProductIdByType($shopDomain, $accessToken, 'collection', $collectionId);
                    if (!empty($collectionProducts['products'])) {
                        $allProducts = array_merge($allProducts, $collectionProducts['products']);
                    }
                }
                break;

            case 'whole_store':
                $storeProducts = $this->productService->getAllProductIdByType($shopDomain, $accessToken, 'all');
                if (!empty($storeProducts['products'])) {
                    $allProducts = $storeProducts['products'];
                }
                break;
        }
        $unique = array_values(array_unique($allProducts));
        return [
            'products' => $unique,
            'count'    => count($unique),
            'error'    => null,
        ];
    }
    public function getFilteredRules($request)
    {
        $filters = $request->only([
            'search',
            'status',
            'exclude_status',
            'applies_to',
            'start_at',
            'end_at',
            'discount_type',
            'discount_min',
            'discount_max',
            'sort_field',
            'sort_order',
        ]);

        $query = Rule::query()->where('shop_id', $request->user()->id);
        Log::debug('User in getFilteredRules', ['user' => $request->user()]);

        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }


        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['exclude_status'])) {
            $query->where('status', '!=', $filters['exclude_status']);
        }

        // Nếu cả 2 đều rỗng → mặc định bỏ qua archived
        if (empty($filters['status']) && empty($filters['exclude_status'])) {
            $query->where('status', '!=', 'archived');
        }

        if (!empty($filters['applies_to'])) {
            $query->where('applies_to', $filters['applies_to']);
        }


        if (!empty($filters['start_at']) && !empty($filters['end_at'])) {
            $query->whereBetween('start_at', [$filters['start_at'], $filters['end_at']]);
        }

        if (!empty($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }


        if (!empty($filters['discount_min']) && !empty($filters['discount_max'])) {
            $query->whereBetween('discount_value', [
                $filters['discount_min'],
                $filters['discount_max']
            ]);
        }

        $sortField = '';
        if (!empty($filters['sort_field'])) {
            $sortField = match ($filters['sort_field']) {
                'sortCreateAt' => 'created_at',
                'sortName' => 'name',
                'sortDiscount' => 'discount_value',
                'sortTimeStart' => 'start_at',
                default => null,
            };
        }
        $sortOrder = '';
        if (!empty($filters['sort_order'])) {
            $sortOrder = match ($filters['sort_order']) {
                'orderOld' => 'asc',
                'orderNew' => 'desc',
                default => null,
            };
        }
        if ($sortField && $sortOrder) $query->orderBy($sortField, $sortOrder);
        if (empty($filters['sort_field']) && empty($filters['sort_order'])) {
            $query->orderByDesc('created_at');
        }

        return $query->paginate(5)->appends($filters);
    }

    public function createRule($data, $shopDomain, $accessToken)
    {
        if (!$shopDomain || !$accessToken) {
            Log::error('Shop not found when creating rule', ['data' => $data]);
            throw new Exception('Shop not found or not authenticated');
        }
        Log::info('Shop: ', ['shop' => $shopDomain]);
        if (empty($data["add_tag"])) {
            return [
                'rule' => null,
                'batch_id' => null,
                'error' => 'Tag is required',
            ];
        }
        $shop = $this->shopifyService->getShopByDomain($shopDomain);
        $rule = Rule::create(array_merge($data, ['shop_id' => $shop->id]));
        if (!$rule) {
            Log::error('Failed to create rule in DB', ['data' => $data]);
            throw new Exception('Failed to create rule');
        }
        $productsResp = $this->getProductsByRule($shopDomain, $accessToken, $rule->applies_to, $rule->applies_to_value);
        $products = $productsResp['products'] ?? [];
        Log::info('Rule: ' . $rule);
        $jobs = [];
        foreach ($products as $p) {
            $jobs[] = new BulkProductActionJob($shopDomain, $accessToken, 'add_tags', [$p], [
                'tags' => [$data['add_tag']]
            ]);
        }
        $applyBatch = $this->applyOrScheduleRule($rule, $shopDomain, $products);
        $ruleId = $rule->id;

        // Nếu $applyBatch là object, convert về dạng an toàn
        $applyBatchId = is_object($applyBatch) && property_exists($applyBatch, 'id')
            ? $applyBatch->id
            : (is_scalar($applyBatch) ? $applyBatch : null);

        $batch = Bus::batch($jobs)
            ->name("CreateRule #{$ruleId} - Add Tag")
            ->then(function () use ($ruleId, $applyBatchId) {
                Log::info("✅ Rule #{$ruleId} created successfully. ApplyBatch: {$applyBatchId}");
            })
            ->catch(function ($e) use ($ruleId) {
                Log::error("❌ Failed creating rule #{$ruleId}: " . $e->getMessage());
            })
            ->dispatch();

        Log::info('✅ Batch dispatched successfully', ['rule_id' => $ruleId, 'batch_id' => $batch->id]);

        return [
            'rule' => $rule,
            'batch_id' => $batch->id,
        ];
    }


    public function updateRule(string $id, $data, $shopDomain, $accessToken)
    {
        if (!$shopDomain || !$accessToken) {
            Log::error('Shop not found when updating rule', ['data' => $data]);
            throw new Exception('Shop not found or not authenticated');
        }
        $rule = Rule::findOrFail($id);
        $old = $rule->replicate();

        $oldTag = $old->add_tag ?? null;
        $newTag = $data['add_tag'] ?? null;

        $shop = $this->shopifyService->getShopByDomain($shopDomain);
        $rule->update(array_merge($data, ['shop_id' => $shop->id]));

        $oldProducts = $this->getProductsByRule($shopDomain, $accessToken, $old->applies_to, $old->applies_to_value);
        $oldProducts = $oldProducts['products'] ?? [];

        $newProductsResp = $this->getProductsByRule($shopDomain, $accessToken, $rule->applies_to, $rule->applies_to_value);
        $newProducts = $newProductsResp['products'] ?? [];

        $jobs = [];
        // ✅ Gỡ tag cũ nếu có
        if (!empty($oldTag)) {
            foreach ($oldProducts as $p) {
                $jobs[] = new BulkProductActionJob($shopDomain, $accessToken, 'remove_tags', [$p], [
                    'tags' => [$oldTag]
                ]);
            }
        }

        // ✅ Thêm tag mới nếu có
        if (!empty($newTag)) {
            foreach ($newProducts as $p) {
                $jobs[] = new BulkProductActionJob($shopDomain, $accessToken, 'add_tags', [$p], [
                    'tags' => [$newTag]
                ]);
            }
        } else {
            Log::warning('⚠️ No new tag provided when updating rule', ['rule_id' => $rule->id]);
        }

        // ✅ Áp dụng hoặc cập nhật rule
        $applyBatch = $this->applyOrScheduleRule($rule, $shopDomain, $newProducts);
        $batch = Bus::batch($jobs)
            ->name("UpdateRule #{$rule->id}")
            ->then(function () use ($rule) {
                Log::info("✅ Rule #{$rule->id} updated successfully.");
            })
            ->catch(function ($e) use ($rule) {
                Log::error("❌ Failed updating rule #{$rule->id}: " . $e->getMessage());
            })
            ->dispatch();
        Log::info('✅ Batch dispatched successfully', ['rule' => $rule, 'batch_id' => $batch->id]);
        return [
            'rule' => $rule,
            'batch_id' => $batch->id,
        ];
    }
    public function updateStatusRule(string $id, string $status, $shopDomain, $accessToken)
    {
        if (!$shopDomain || !$accessToken) {
            Log::error('Shop not found when updating rule', ['rule_id' => $id, 'status' => $status]);
            throw new Exception('Shop not found or not authenticated');
        }
        $rule = Rule::findOrFail($id);
        $shop = $this->shopifyService->getShopByDomain($shopDomain);
        $rule->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
        $productsResp = $this->getProductsByRule($shopDomain, $accessToken, $rule->applies_to, $rule->applies_to_value);
        $products = $productsResp['products'] ?? [];
        if (!is_array($products)) $products = [];
        $addTag = $data['add_tag'] ?? null;
        $jobs = [];
        if ($status === 'inactive') {
            foreach ($products as $p) {
                $jobs[] = new ResetProductPrice($shop->id, $p, $rule->id);
            }
            $batch = Bus::batch($jobs)
                ->name("InActive Rule #{$rule->id}")
                ->then(function () use ($rule) {
                    Log::info("✅ Rule #{$rule->id} marked inactive successfully.");
                })
                ->catch(function ($e) use ($rule) {
                    Log::error("❌ Failed deactivating rule #{$rule->id}: " . $e->getMessage());
                })
                ->dispatchAfterResponse(); // chạy ngầm
            return [
                'rule' => $rule,
                'batch_id' => $batch->id,
            ];
        }
        if ($status === 'archived') {
            return [
                'rule' => $rule,
            ];
        }
        if (!empty($addTag)) {
            foreach ($products as $p) {
                $jobs[] = new BulkProductActionJob($shopDomain, $accessToken, 'add_tags', [$p], [
                    'tags' => [$addTag]
                ]);
            }
        } else {
            Log::warning('⚠️ No new tag provided when updating rule', ['rule_id' => $rule->id]);
        }
        $applyBatch = $this->applyOrScheduleRule($rule, $shopDomain, $products);
        $batch = Bus::batch($jobs)
            ->name("Update status for Rule #{$rule->id}")
            ->then(function () use ($rule) {
                Log::info("✅ Rule #{$rule->id} updated successfully.");
            })
            ->catch(function ($e) use ($rule) {
                Log::error("❌ Failed updating rule #{$rule->id}: " . $e->getMessage());
            })
            ->dispatch();
        Log::info('✅ Batch dispatched successfully', ['rule' => $rule, 'batch_id' => $batch->id]);
        return [
            'rule' => $rule,
            'batch_id' => $batch->id,
        ];
    }
    public function deleteRule(string $id, $shopDomain, $accessToken)
    {
        try {
            $rule = Rule::findOrFail($id);
            $productsResp = $this->getProductsByRule($shopDomain, $accessToken, $rule->applies_to, $rule->applies_to_value);
            $products = $productsResp['products'] ?? [];

            $jobs = [];

            // ✅ Nếu có add_tag, gỡ tag ra trước khi reset giá
            if (!empty($rule->add_tag)) {
                foreach ($products as $p) {
                    $jobs[] = new BulkProductActionJob($shopDomain, $accessToken, 'remove_tags', [$p], [
                        'tags' => [$rule->add_tag]
                    ]);
                }
            }
            $shop = $this->shopifyService->getShopByDomain($shopDomain);
            // ✅ Reset giá sản phẩm
            foreach ($products as $p) {
                $jobs[] = new ResetProductPrice($shop->id, $p, $rule->id);
            }
            $batch = Bus::batch($jobs)
                ->name("DeleteRule #{$rule->id}")
                ->then(function () use ($rule) {
                    $rule->delete();
                    Log::info("✅ Rule #{$rule->id} deleted successfully.");
                })
                ->catch(function ($e) use ($rule) {
                    Log::error("❌ Failed deleting rule #{$rule->id}: " . $e->getMessage());
                })
                ->dispatch();

            return $batch;
        } catch (\Throwable $e) {
            Log::error("❌ Delete rule failed: " . $e->getMessage());
            return null;
        }
    }
    protected function applyOrScheduleRule($rule, $shopDomain, $products)
    {
        $jobs = [];
        $shop = $this->shopifyService->getShopByDomain($shopDomain);
        // Nếu rule có thời gian bắt đầu và kết thúc
        if ($rule->start_at && $rule->end_at) {
            if (now()->between($rule->start_at, $rule->end_at)) {
                // Thực thi ngay nếu đang trong khoảng thời gian hiệu lực
                foreach ($products as $p) {
                    $jobs[] = new ApplyRuleToProduct(
                        $shop->id,
                        $p,
                        $rule->discount_value,
                        $rule->discount_type,
                        $rule->based_on,
                        $rule->id
                    );
                }
            } elseif (now()->lessThan($rule->start_at)) {
                // Nếu chưa đến thời gian bắt đầu → schedule ApplyRuleToProduct
                foreach ($products as $p) {
                    $jobs[] = (new ApplyRuleToProduct(
                        $shop->id,
                        $p,
                        $rule->discount_value,
                        $rule->discount_type,
                        $rule->based_on,
                        $rule->id
                    ))->delay($rule->start_at);
                }
            }

            // Schedule reset khi đến end_at
            foreach ($products as $p) {
                $jobs[] = (new ResetProductPrice($shop->id, $p, $rule->id))->delay($rule->end_at);
            }
        }
        // Nếu không có end_at → apply rule ngay (không reset)
        else {
            foreach ($products as $p) {
                $jobs[] = new ApplyRuleToProduct(
                    $shop->id,
                    $p,
                    $rule->discount_value,
                    $rule->discount_type,
                    $rule->based_on,
                    $rule->id
                );
            }
        }

        if (empty($jobs)) {
            return null;
        }

        // 🟩 Tạo batch và dispatch toàn bộ job một lượt
        $batch = Bus::batch($jobs)
            ->name("ApplyRule #{$rule->id}")
            ->then(function () use ($rule) {
                $rule->update(['status' => 'active']);
            })
            ->catch(function ($e) use ($rule) {
                Log::error("Batch for rule {$rule->id} failed: " . $e->getMessage());
                $rule->update(['status' => 'failed']);
            })
            ->finally(function () use ($rule) {
                if ($rule->end_at && now()->greaterThanOrEqualTo($rule->end_at)) {
                    $rule->update(['status' => 'expired']);
                }
            })
            ->dispatch();

        return $batch;
    }
}
