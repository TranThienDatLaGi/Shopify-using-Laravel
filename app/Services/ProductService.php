<?php

namespace App\Services;

use App\Models\ProductPriceBackup;
use Illuminate\Support\Facades\Log;

class ProductService
{
    protected $shopify;

    public function __construct(ShopifyService $shopify)
    {
        $this->shopify = $shopify;
    }
    public function getProductType(string $shopDomain, string $accessToken): array
    {
        $query = <<<GRAPHQL
        {
            shop {
                productTypes(first: 250) {
                    edges { node }
                }
            }
        }
        GRAPHQL;

        $data = $this->shopify->graphqlRequest(
            $shopDomain,
            $accessToken,
                $query);
        return data_get($data, 'shop.productTypes.edges', []);
    }
    public function getTags(string $shopDomain, string $accessToken): array
    {
        $query = <<<GRAPHQL
        {
            shop {
                productTags(first: 250) {
                    edges { node }
                }
            }
        }
        GRAPHQL;

        $data = $this->shopify->graphqlRequest(
                $shopDomain,
            $accessToken,
                $query);
        return data_get($data, 'shop.productTags.edges', []);
    }
    public function getVendors(string $shopDomain, string $accessToken): array
    {
        $query = <<<GRAPHQL
        {
            shop {
                productVendors(first: 250) {
                    edges { node }
                }
            }
        }
        GRAPHQL;

        $data = $this->shopify->graphqlRequest(
                $shopDomain,
            $accessToken,
                $query);
        return data_get($data, 'shop.productVendors.edges', []);
    }
    public function getCollections(string $shopDomain, string $accessToken): array
    {
        $query = <<<GRAPHQL
        {
            collections(first: 250) {
                edges {
                    node {
                        id
                        title
                        handle
                    }
                }
            }
        }
        GRAPHQL;

        $data = $this->shopify->graphqlRequest(
                $shopDomain,
            $accessToken,
                $query);
        return data_get($data, 'collections.edges', []);
    }
    public function getProducts(
        string $shopDomain,
        string $accessToken,
        int $limit = 20,
        ?string $after = null,
        ?string $before = null,
        ?string $searchQuery = null,
        ?string $sort = null,
        ?string $order = 'asc'
    ): ?array {

        // 🔁 Pagination Clause
        if ($after) {
            $paginationClause = "first: $limit, after: " . json_encode($after);
        } elseif ($before) {
            $paginationClause = "last: $limit, before: " . json_encode($before);
        } else {
            $paginationClause = "first: $limit";
        }

        // 🔍 Search Filter
        $queryClause = $searchQuery ? ', query: ' . json_encode($searchQuery) : '';

        // ↕ Sort Mapping
        $map = [
            'title'       => 'TITLE',
            'created'     => 'CREATED_AT',
            'updated'     => 'UPDATED_AT',
            'productType' => 'PRODUCT_TYPE',
            'vendor'      => 'VENDOR',
        ];

        $sortKey   = $sort && isset($map[$sort]) ? $map[$sort] : 'TITLE';
        $reverse   = $order === 'desc' ? 'true' : 'false';
        $sortClause = ", sortKey: $sortKey, reverse: $reverse";

        // 🧾 GraphQL Query
        $query = <<<GRAPHQL
                {
                products($paginationClause$queryClause$sortClause) {
                    edges {
                    cursor
                    node {
                        id
                        title
                        vendor
                        productType
                        tags
                        status
                        createdAt
                        updatedAt
                        totalInventory

                        collections(first: 10) {
                        edges {
                            node {
                            id
                            title
                            handle
                            }
                        }
                        }

                        variantsCount { count }

                        media(first: 1) {
                        edges {
                            node {
                            mediaContentType
                            ... on MediaImage {
                                image { url }
                            }
                            }
                        }
                        }
                        variants(first: 1) {
                        edges {
                            node {
                            id
                            title
                            price
                            compareAtPrice
                            }
                        }
                        }
                    }
                    }

                    pageInfo {
                    hasNextPage
                    hasPreviousPage
                    startCursor
                    endCursor
                    }
                }
                }
                GRAPHQL;

        // 🚀 Call API
        $data = $this->shopify->graphqlRequest($shopDomain, $accessToken, $query);

        // 📦 Return Unified Response
        return $data ? [
            'products' => data_get($data, 'products.edges', []),
            'pageInfo' => data_get($data, 'products.pageInfo', []),
            'error'    => null,
        ] : null;
    }

    public function getProductsByCollection(
        string $shopDomain, string $accessToken,
        string $collectionId,
        int $limit = 20,
        ?string $after = null,
        ?string $before = null,
        ?string $sort = null,
        ?string $order = 'asc'
    ): ?array {
        Log::info("📥 Chạy nhầm tới getProductsbyCollection");
        if ($after) {
            $paginationClause = "first: $limit, after: " . json_encode($after);
        } elseif ($before) {
            $paginationClause = "last: $limit, before: " . json_encode($before);
        } else {
            $paginationClause = "first: $limit";
        }

        $map = [
            'title'       => 'TITLE',
            'created'     => 'CREATED_AT',
            'updated'     => 'UPDATED_AT',
            'productType' => 'PRODUCT_TYPE',
            'vendor'      => 'VENDOR',
        ];
        $sortKey  = $sort && isset($map[$sort]) ? $map[$sort] : 'TITLE';
        $reverse  = $order === 'desc' ? 'true' : 'false';
        $sortClause = ", sortKey: $sortKey, reverse: $reverse";

        $query = <<<GRAPHQL
    {
        collection(id: "$collectionId") {
            products($paginationClause$sortClause) {
                edges {
                    cursor
                    node {
                        id
                        title
                        vendor
                        productType
                        tags
                        status
                        createdAt
                        updatedAt
                        totalInventory
                        collections(first: 10) {
                            edges {
                                node {
                                id
                                title
                                handle
                                }
                            }
                            }
                        variantsCount { count }
                        media(first: 1) {
                            edges {
                                node {
                                    mediaContentType
                                    ... on MediaImage {
                                        image { url }
                                    }
                                }
                            }
                        }
                    }
                }
                pageInfo {
                    hasNextPage
                    hasPreviousPage
                    startCursor
                    endCursor
                }
            }
        }
    }
    GRAPHQL;

        $data = $this->shopify->graphqlRequest(
                $shopDomain,
            $accessToken,
                $query);

        return $data ? [
            'products' => data_get($data, 'collection.products.edges', []),
            'pageInfo' => data_get($data, 'collection.products.pageInfo', []),
            'error'    => null,
        ] : null;
    }
    public function updateProductStatus(string $shopDomain, string $accessToken, string $productId, string $status): ?array
    {
        $query = <<<GRAPHQL
    mutation {
        productUpdate(input: {
            id: "$productId",
            status: $status
        }) {
            product {
                id
                title
                status
            }
            userErrors {
                field
                message
            }
        }
    }
    GRAPHQL;

        return $this->shopify->graphqlRequest(
            $shopDomain,
            $accessToken,
            $query
        );
    }
    public function addTags(string $shopDomain, string $accessToken, string $productId, array $tags): ?array
    {
        $tagsString = json_encode($tags);
        $query = <<<GRAPHQL
    mutation {
        tagsAdd(id: "$productId", tags: $tagsString) {
            node { id }
            userErrors { field message }
        }
    }
    GRAPHQL;
        $result = $this->shopify->graphqlRequest(
            $shopDomain,
            $accessToken,
            $query
        );
        return $result;
    }

    public function removeTags(string $shopDomain, string $accessToken, string $productId, array $tags): ?array
    {
        $tagsString = json_encode($tags);
        $query = <<<GRAPHQL
    mutation {
        tagsRemove(id: "$productId", tags: $tagsString) {
            node { id }
            userErrors { field message }
        }
    }
    GRAPHQL;

        return $this->shopify->graphqlRequest(
                $shopDomain,
            $accessToken,
                $query);
    }
    public function addToCollection(string $shopDomain, string $accessToken, string $collectionId, string $productId): ?array
    {
        $productIdsArray = json_encode([$productId], JSON_UNESCAPED_SLASHES);

        $query = <<<GRAPHQL
        mutation {
            collectionAddProducts(id: "$collectionId", productIds: $productIdsArray) {
                collection { id title }
                userErrors { field message }
            }
        }
    GRAPHQL;
        return $this->shopify->graphqlRequest(
                $shopDomain,
            $accessToken,
                $query);
    }
    public function removeFromCollection(string $shopDomain, string $accessToken, string $collectionId, string $productId): ?array
    {
        $productIdsArray = json_encode([$productId], JSON_UNESCAPED_SLASHES);

        $query = <<<GRAPHQL
        mutation {
            collectionRemoveProducts(id: "$collectionId", productIds: $productIdsArray) {
                job { id }
                userErrors { field message }
            }
        }
    GRAPHQL;
        return $this->shopify->graphqlRequest(
                $shopDomain,
            $accessToken,
                $query);
    }
    public function getAllProductIdByType(string $shopDomain, string $accessToken, string $type = 'all', ?string $value = null): ?array
    {
        $allProducts = [];
        $hasNextPage = true;
        $endCursor = null;

        while ($hasNextPage) {
            $after = $endCursor ? ", after: \"{$endCursor}\"" : "";
            // query body phụ thuộc vào type
            switch ($type) {
                case 'tag':
                    $query = <<<GRAPHQL
                {
                  products(first: 20, query: "tag:'$value'" $after) {
                    edges { node { id } }
                    pageInfo { hasNextPage endCursor }
                  }
                }
                GRAPHQL;
                    break;

                case 'vendor':
                    $query = <<<GRAPHQL
                {
                  products(first: 20, query: "vendor:'$value'" $after) {
                    edges { node { id } }
                    pageInfo { hasNextPage endCursor }
                  }
                }
                GRAPHQL;
                    break;

                case 'collection':
                    $query = <<<GRAPHQL
                {
                  collection(id: "$value") {
                    products(first: 20 $after) {
                      edges { node { id } }
                      pageInfo { hasNextPage endCursor }
                    }
                  }
                }
                GRAPHQL;
                    break;

                case 'all':
                default:
                    $query = <<<GRAPHQL
                {
                  products(first: 20 $after) {
                    edges { node { id } }
                    pageInfo { hasNextPage endCursor }
                  }
                }
                GRAPHQL;
                    break;
            }

            $data = $this->shopify->graphqlRequest(
                $shopDomain,
            $accessToken,
                $query
            );

            if ($type === 'collection') {
                $edges = data_get($data, 'collection.products.edges', []);
                $pageInfo = data_get($data, 'collection.products.pageInfo', []);
            } else {
                $edges = data_get($data, 'products.edges', []);
                $pageInfo = data_get($data, 'products.pageInfo', []);
            }

            // chỉ lấy id
            foreach ($edges as $edge) {
                if (!empty($edge['node']['id'])) {
                    $allProducts[] = $edge['node']['id'];
                }
            }

            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $endCursor   = $pageInfo['endCursor'] ?? null;
        }

        // loại bỏ trùng lặp ID
        $allProducts = array_values(array_unique($allProducts));

        return [
            'products' => $allProducts,
            'count'    => count($allProducts),
            'error'    => null,
        ];
    }
    public function getVariantsByProductID(string $shopDomain, string $accessToken, string $productId): ?array
    {
        $allVariants = [];
        $hasNextPage = true;
        $endCursor = null;

        while ($hasNextPage) {
            $after = $endCursor ? ", after: \"{$endCursor}\"" : "";

            $query = <<<GRAPHQL
        {
          product(id: "$productId") {
            id
            title
            variants(first: 50 $after) {
              edges {
                cursor
                node {
                  id
                  title
                  sku
                  price
                  compareAtPrice
                  inventoryQuantity
                  createdAt
                  updatedAt
                }
              }
              pageInfo {
                hasNextPage
                endCursor
              }
            }
          }
        }
        GRAPHQL;

            $data = $this->shopify->graphqlRequest(
                $shopDomain,
            $accessToken,
                $query);

            $edges = data_get($data, 'product.variants.edges', []);
            $pageInfo = data_get($data, 'product.variants.pageInfo', []);

            $allVariants = array_merge($allVariants, $edges);

            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $endCursor   = $pageInfo['endCursor'] ?? null;
        }

        return [
            'variants'   => $allVariants,
            'count'      => count($allVariants),
            'product_id' => $productId,
            'error'      => null,
        ];
    }    
    public function setRuletoProduct(string $shopDomain, string $accessToken, string $productId, string $discount, string $typePriceChange): ?array
    {
        // Lấy variants của product
        $variantsData = $this->getVariantsByProductID($shopDomain, $accessToken, $productId);
        $variants = $variantsData['product']['variants']['edges'] ?? [];

        if (empty($variants)) {
            return [
                'success' => false,
                'message' => 'No variants found for this product',
                'error'   => null,
            ];
        }
        $variantInputs = [];
        $isPercent = str_ends_with($discount, '%');
        $discountValue = $isPercent ? (float) rtrim($discount, '%') : (float) $discount;

        foreach ($variants as $variantEdge) {
            $variantNode = $variantEdge['node'];
            $backupVariant = ProductPriceBackup::byShop($shopDomain)
                ->byProduct($productId)
                ->byVariant($variantNode['id'])
                ->first();

            $oldPrice = $variantNode[$typePriceChange] ?? null;
            if ($oldPrice === null) {
                continue;
            }
            $oldPrice = (float) $oldPrice;
            $canApplyRule = false;
            if (!$backupVariant) {
                ProductPriceBackup::create([
                    'shop_name'                 => $shopDomain,
                    'product_id'                => $productId,
                    'variant_id'                => $variantNode['id'],
                    'original_price'            => isset($variantNode['price']) ? (float) $variantNode['price'] : null,
                    'original_compare_at_price' => isset($variantNode['compareAtPrice']) ? (float) $variantNode['compareAtPrice'] : null,
                ]);
                $canApplyRule = true;
            } else {
                if (
                    ($typePriceChange === 'price' && $backupVariant->original_price == $oldPrice) ||
                    ($typePriceChange === 'compareAtPrice' && $backupVariant->original_compare_at_price == $oldPrice)
                ) {
                    $canApplyRule = true;
                }
            }
            if ($canApplyRule) {
                $newPrice = $isPercent
                    ? $oldPrice * (1 - $discountValue / 100)
                    : max(0, $oldPrice - $discountValue);


                $input = ['id' => $variantNode['id']];
                if ($typePriceChange === 'price') {
                    $input['price'] = (string) round($newPrice, 2);
                } else {
                    $input['compareAtPrice'] = (string) round($newPrice, 2);
                }
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
        // Mutation update
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

        $response = $this->shopify->graphqlRequest($shopDomain, $accessToken, $mutation, $variables);

        $errors = data_get($response, 'productVariantsBulkUpdate.userErrors', []);
        return [
            'success' => $response !== null && empty($errors),
            'data'    => data_get($response, 'productVariantsBulkUpdate', []),
            'error'   => $errors,
        ];
    }
    public function resetPriceForProduct(string $shopDomain, string $accessToken, string $productId): ?array
    {
        // Lấy variants của product
        $variantsData = $this->getVariantsByProductID($shopDomain, $accessToken, $productId);
        $variants = $variantsData['product']['variants']['edges'] ?? [];
        if (empty($variants)) {
            return [
                'success' => false,
                'message' => 'No variants found for this product',
                'error'   => null,
            ];
        }

        $variantInputs = [];

        foreach ($variants as $variantEdge) {
            $variantNode = $variantEdge['node'];

            $backupVariant = ProductPriceBackup::byShop($shopDomain)
                ->byProduct($productId)
                ->byVariant($variantNode['id'])
                ->first();

            if ($backupVariant) {
                $input = ['id' => $variantNode['id']];
                $input['price'] = $backupVariant->original_price !== null
                    ? (string) round($backupVariant->original_price, 2)
                    : (string) $variantNode['price'];

                $input['compareAtPrice'] = $backupVariant->original_compare_at_price !== null
                    ? (string) round($backupVariant->original_compare_at_price, 2)
                    : ($variantNode['compareAtPrice'] ?? null);

                $variantInputs[] = $input;
                // $backupVariant->delete();
            }
        }

        if (empty($variantInputs)) {
            return [
                'success' => false,
                'message' => 'No variants to reset',
                'error'   => null,
            ];
        }

        // Mutation update
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

        $response = $this->shopify->graphqlRequest($shopDomain, $accessToken, $mutation, $variables);

        $errors = data_get($response, 'productVariantsBulkUpdate.userErrors', []);

        return [
            'success' => $response !== null && empty($errors),
            'data'    => data_get($response, 'productVariantsBulkUpdate', []),
            'error'   => $errors,
        ];
    }
    public function getProductByListID(
        string $shopDomain,
        string $accessToken,
        array $productIds,
    ): ?array {
        if (empty($productIds)) {
            return null;
        }

        // Ghép danh sách ID thành chuỗi GraphQL đúng dạng "gid://shopify/Product/12345"
        $idStrings = collect($productIds)
            ->map(function ($id) {
                if (str_starts_with($id, 'gid://shopify/Product/')) {
                    return "\"{$id}\"";
                }
                return "\"gid://shopify/Product/{$id}\"";
            })
            ->join(',');
        $query = <<<GRAPHQL
    {
        nodes(ids: [{$idStrings}]) {
            ... on Product {
                id
                title
                media(first: 1) {
                    edges {
                        node {
                            mediaContentType
                            ... on MediaImage {
                                image {
                                    url
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    GRAPHQL;
        $data = $this->shopify->graphqlRequest(
            $shopDomain,
            $accessToken,
            $query
        );
        Log::info('data', ['data' => $data]);
        if (!$data || !isset($data['nodes'])) {
            return null;
        }
        return [
            'products' => $data['nodes'],
            'error'    => null,
        ];
    }

    public function getCollectionByListID(
        string $shopDomain,
        string $accessToken,
        array $collectionIds,
    ): ?array {
        if (empty($collectionIds)) {
            return null;
        }

        // Ghép danh sách ID thành chuỗi GraphQL đúng dạng "gid://shopify/Collection/12345"
        $idStrings = collect($collectionIds)
            ->map(function ($id) {
                if (str_starts_with($id, 'gid://shopify/Collection/')) {
                    return "\"{$id}\"";
                }
                return "\"gid://shopify/Collection/{$id}\"";
            })
            ->join(',');

        $query = <<<GRAPHQL
    {
        nodes(ids: [{$idStrings}]) {
            ... on Collection {
                id
                title
                handle
                updatedAt
                productsCount {
                    count
                }
                image {
                    url
                }
            }
        }
    }
    GRAPHQL;

        $data = $this->shopify->graphqlRequest(
            $shopDomain,
            $accessToken,
            $query
        );
        Log::info('data', ['data' => $data]);

        if (!$data || !isset($data['nodes'])) {
            return null;
        }

        return [
            'collections' => $data['nodes'],
            'error'       => null,
        ];
    }
}