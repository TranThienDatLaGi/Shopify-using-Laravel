<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRuleRequest;
use App\Http\Requests\UpdateRuleRequest;
use App\Models\Rule;
use App\Services\ProductService;
use App\Services\ShopifyService;
use App\Services\RuleService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RuleController extends Controller
{
    protected ProductService $productService;
    protected ShopifyService $shopifyService;
    protected RuleService $ruleService;

    public function __construct(
        ProductService $productService,
        ShopifyService $shopifyService,
        RuleService $ruleService
    ) {
        $this->productService = $productService;
        $this->shopifyService = $shopifyService;
        $this->ruleService    = $ruleService;
    }

    /**
     * Hiển thị danh sách rule (có filter & sort)
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'main');
        $request->merge(
            $tab === 'archived'
                ? ['status' => 'archived']
                : ['exclude_status' => 'archived']
        );
        Log::debug('Request before getFilteredRules', [
            'all' => $request->all(),
            'query' => $request->query(),
            'input' => $request->input(),
            'tab' => $tab,
        ]);
        $rules = $this->ruleService->getFilteredRules($request);
        Log::debug('rules data', ['rules' => $rules->toArray()]);

        // Nếu là AJAX request
        if ($request->ajax() || $request->expectsJson()) {
            // Chọn partial view phù hợp theo tab
            $tableView = $tab === 'archived'
                ? 'rules.partials.archived_table_body'
                : 'rules.partials.table_body';

            return response()->json([
                'tbody' => view($tableView, compact('rules'))->render(),
                'pagination' => view('rules.partials.pagination', compact('rules'))->render(),
                'tab' => $tab,
            ]);
        }
        // Lần đầu load trang
        return view('rules.index', compact('rules', 'tab'));
    }
    /**
     * Form tạo rule mới
     */
    public function create()
    {

        return view('rules.create');
    }

    
    /**
     * Lưu rule mới vào DB
     */
    public function store(StoreRuleRequest $request)
    {
        $shopDomain = $request->input('shop');
        Log::info('Store Rule for shop: ' . $shopDomain);

        $status = $request->input('status');
        $selectedShop = $this->shopifyService->getShopByDomain($shopDomain);

        if ($status === 'inactive' || $status === 'archived') {
            // ✅ Tạo rule nội bộ, không push lên Shopify
            $rule = Rule::create(array_merge($request->validated(), [
                'shop_id' => $selectedShop->id,
            ]));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Rule created successfully.',
                    'rule_id' => $rule->id,
                ]);
            }
            return redirect()
                ->route('rules.index')
                ->with('success', 'Rule created successfully.');
        }

        // ✅ Ngược lại, tạo rule trên Shopify (active)
        $accessToken = $selectedShop->access_token ?? $selectedShop->password ?? null;
        $result = $this->ruleService->createRule($request->validated(), $shopDomain, $accessToken);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Rule created successfully.',
                'rule_id' => $result['rule'],
                'batch_id' => $result['batch_id'],
            ]);
        }
        return redirect()
            ->route('rules.index')
            ->with('success', 'Rule created successfully.');
    }
    /**
     * Hiển thị chi tiết rule
     */
    public function show(string $id)
    {
        $rule = Rule::findOrFail($id);
        return view('rules.show', compact('rule'));
    }

    /**
     * Form edit rule
     */
    public function edit(string $id)
    {
        $rule = Rule::findOrFail($id);
        $itemselect = '';
        if ($rule->applies_to === 'products' && $rule->shop) {
            $shopDomain = $rule->shop->name;
            $accessToken = $rule->shop->access_token ?? $rule->shop->password ?? null;

            $productIds = is_array($rule->applies_to_value)
                ? $rule->applies_to_value
                : json_decode($rule->applies_to_value, true);
            $itemselect = $this->productService->getProductByListID(
                $shopDomain,
                $accessToken,
                $productIds ?? []
            );
        } else if ($rule->applies_to === 'collections' && $rule->shop) {
            $shopDomain = $rule->shop->name;
            $accessToken = $rule->shop->access_token ?? $rule->shop->password ?? null;

            $collectionIds = is_array($rule->applies_to_value)
                ? $rule->applies_to_value
                : json_decode($rule->applies_to_value, true);
            $itemselect = $this->productService->getCollectionByListID(
                $shopDomain,
                $accessToken,
                $collectionIds ?? []
            );
        }
        return view('rules.edit', compact('rule', 'itemselect'));
    }
    /**
     * Cập nhật rule
     */
    public function update(UpdateRuleRequest $request, string $id)
    {
        $shopDomain = $this->shopifyService->getCurrentShopDomain($request)
            ?? $this->shopifyService->getFirstShop()?->name;
        $selectedShop = $this->shopifyService->getShopByDomain($shopDomain);
        $accessToken  = $selectedShop->access_token ?? $selectedShop->password ?? null;
        $status = $request->input('status');
        $validated = $request->validated();
        try {
            if ($status === 'inactive'|| $status === 'archived') {
                $rule = Rule::findOrFail($id);
                $rule->update(array_merge($validated, ['status' => $status]));
                return response()->json([
                    'message' => 'Rule updated successfully (inactive).',
                    'rule_id' => $rule->id,
                    'status'  => $rule->status,
                ]);
            }
            else if ($status === 'active') {
                $result = $this->ruleService->updateRule($id, $validated, $shopDomain, $accessToken);
                return response()->json([
                    'message'   => 'Rule updated successfully (active).',
                    'rule_id'   => $result['rule']['id'] ?? $result['rule'] ?? $id,
                    'batch_id'  => $result['batch_id'] ?? null,
                    'status'    => 'active',
                ]);
            }
            return response()->json([
                'error'   => 'Invalid status value.',
                'status'  => $status,
            ], 400);
        } catch (Exception $e) {
            Log::error('Update rule failed', [
                'id' => $id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error'   => 'Failed to update rule.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateStatus(Request $request, string $id)
    {
        $shopDomain = $this->shopifyService->getCurrentShopDomain($request)
            ?? $this->shopifyService->getFirstShop()?->name;
        $selectedShop = $this->shopifyService->getShopByDomain($shopDomain);
        $accessToken = $selectedShop->access_token ?? $selectedShop->password ?? null;
        $status = $request->input('status');
        $result = $this->ruleService->updateStatusRule($id,$status, $shopDomain, $accessToken);
        if ($request->expectsJson()) {
            return response()->json([
                'message'       => 'Rule status updated successfully.',
                'rule_id'       => $result['rule']->id,
                'batch_id'      => $result['batch_id'] ?? null,
                'rule_status'   => $status ?? null,
            ]);
        }
        return redirect()
            ->route('rules.index')
            ->with('success', 'Rule status updated successfully.');
    }
    public function duplicate(string $id)
    {
        $rule = Rule::findOrFail($id);
        $data = [
            'name'              => $rule->name . ' (Copy)',
            'based_on'          => $rule->based_on,
            'discount_value'    => $rule->discount_value,
            'discount_type'     => $rule->discount_type,
            'applies_to'        => $rule->applies_to,
            'applies_to_value'  => $rule->applies_to_value ?? [],
            'status'            => $rule->status,
            'start_at'          => $rule->start_at,
            'end_at'            => $rule->end_at,
            'add_tag'           => $rule->add_tag,
            'shop_id'           => $rule->shop_id,
        ];
        $ruleDuplicate = Rule::create(array_merge($data));
        // Nếu là request JSON (từ frontend JS)
            return response()->json([
                'status'=> 'success',
                'message' => 'Rule created successfully.',
                'data' => $ruleDuplicate,
            ]);
    }
    /**
     * Xóa rule
     */
    public function destroy(Request $request, string $id)
    {
        $shop= $request->input('shop');
        Log::info('Shop param: ' . $shop);
        try {
            $shopDomain = $this->shopifyService->getCurrentShopDomain($request)
                ?? $this->shopifyService->getFirstShop()?->name;

            $selectedShop = $this->shopifyService->getShopByDomain($shopDomain);
            $accessToken= $selectedShop->access_token ?? $selectedShop->password ?? null;
            Log::info('Delete Rule for shop: ' . $shopDomain);
            Log::info('Delete Rule ID: ' . $id);
            Log::info('Access Token: ' . $accessToken);
            // Gọi deleteRule và nhận batch (đã sửa trong service)
            $batch = $this->ruleService->deleteRule($id, $shopDomain, $accessToken);

            if ($request->expectsJson()) {
                return response()->json([
                    'message'  => 'Rule deleted successfully.',
                    'batch_id' => $batch?->id,
                ]);
            }

            return redirect()
                ->route('rules.index')
                ->with('success', 'Rule deleted successfully.');
        } catch (\Throwable $e) {
            Log::error("Error deleting rule {$id}: " . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unexpected error occurred while deleting rule.',
                ], 500);
            }

            return redirect()
                ->route('rules.index')
                ->with('error', 'Unexpected error occurred while deleting rule.');
        }
    }
}
