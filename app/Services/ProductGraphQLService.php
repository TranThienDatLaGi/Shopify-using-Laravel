<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductGraphQLService
{
    /**
     * Summary of getGraphqlUrl
     * @param \App\Models\User $shop
     * @return string
     */
    private function getGraphqlUrl(User $shop): string
    {
        return sprintf(
            'https://%s/admin/api/%s/graphql.json',
            $shop->name,
            config('shopify-app.api_version')
        );
    }

    /**
     * Summary of graphqlRequest
     * @param \App\Models\User $shop
     * @param string $query
     */
    private function graphqlRequest(User $shop, string $query): ?array
    {
        $url   = $this->getGraphqlUrl($shop);
        $token = $shop->access_token ?? $shop->password; // Ưu tiên access_token, fallback password

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type'           => 'application/json',
            ])->post($url, ['query' => $query]);

            if ($response->successful()) {

                // Log::debug("🔎 Shopify GraphQL Raw Response", [
                //     'query'  => $query,
                //     'data' => $response->json('data'),
                //     'status' => $response->status(),
                //     'body'   => $response->body(),
                    
                // ]);
                return $response->json('data');
            }

            Log::error('GraphQL request failed', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('GraphQL request exception', [
                'url'     => $url,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    /**
     * Summary of getProductType
     * @param \App\Models\User $shop
     * @return array
     */

    public function getProductType(User $shop): array
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

        $data = $this->graphqlRequest($shop, $query);
        return data_get($data, 'shop.productTypes.edges', []);
    }
    /**
     * Summary of getTags
     * @param \App\Models\User $shop
     * @return array
     */
    public function getTags(User $shop): array
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

        $data = $this->graphqlRequest($shop, $query);
        return data_get($data, 'shop.productTags.edges', []);
    }
    /**
     * Summary of getVendors
     * @param \App\Models\User $shop
     * @return array
     */
    public function getVendors(User $shop): array
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

        $data = $this->graphqlRequest($shop, $query);
        return data_get($data, 'shop.productVendors.edges', []);
    }
    /**
     * Summary of getCollections
     * @param \App\Models\User $shop
     * @return array
     */
    public function getCollections(User $shop): array
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

        $data = $this->graphqlRequest($shop, $query);
        return data_get($data, 'collections.edges', []);
    }

    /**
     * Summary of getProducts
     * @param \App\Models\User $shop
     * @param int $first
     * @param mixed $after
     * @param mixed $searchQuery
     * @return array{error: null, pageInfo: mixed, products: mixed|null}
     */
    public function getProducts(
        User $shop,
        int $limit = 20,
        ?string $after = null,
        ?string $before = null,
        ?string $searchQuery = null,
        ?string $sort = null,   // title | created | updated | productType | vendor
        ?string $order = 'asc'  // asc | desc
    ): ?array {
        // 📌 paginationClause: ưu tiên after/before
        if ($after) {
            $paginationClause = "first: $limit, after: " . json_encode($after);
        } elseif ($before) {
            $paginationClause = "last: $limit, before: " . json_encode($before);
        } else {
            $paginationClause = "first: $limit";
        }

        // 📌 search
        $queryClause  = $searchQuery ? ', query: ' . json_encode($searchQuery) : '';

        // 📌 map sort key từ frontend sang Shopify
        $map = [
            'title'       => 'TITLE',
            'created'     => 'CREATED_AT',
            'updated'     => 'UPDATED_AT',
            'productType' => 'PRODUCT_TYPE',
            'vendor'      => 'VENDOR',
        ];

        $sortKey = $sort && isset($map[$sort]) ? $map[$sort] : 'TITLE';
        $reverse = $order === 'desc' ? 'true' : 'false';
        $sortClause = ", sortKey: $sortKey, reverse: $reverse";

        // 📌 GraphQL query
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

        $data = $this->graphqlRequest($shop, $query);

        return $data ? [
            'products' => data_get($data, 'products.edges', []),
            'pageInfo' => data_get($data, 'products.pageInfo', []),
            'error'    => null,
        ] : null;
    }
    /**
     * Summary of getProductsByCollection
     * @param \App\Models\User $shop
     * @param string $collectionId
     * @param int $first
     * @param mixed $after
     * @return array{error: null, pageInfo: mixed, products: mixed|null}
     */
    public function getProductsByCollection(
        User $shop,
        string $collectionId,
        int $limit = 20,
        ?string $after = null,
        ?string $before = null,
        ?string $sort = null,    // title | created | updated | productType | vendor
        ?string $order = 'asc'   // asc | desc
    ): ?array {
        Log::info("📥 Chạy nhầm tới getProductsbyCollection");
        // 📌 Pagination: ưu tiên after/before
        if ($after) {
            $paginationClause = "first: $limit, after: " . json_encode($after);
        } elseif ($before) {
            $paginationClause = "last: $limit, before: " . json_encode($before);
        } else {
            $paginationClause = "first: $limit";
        }

        // 📌 Sort mapping
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

        // 📌 GraphQL query
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

        $data = $this->graphqlRequest($shop, $query);

        return $data ? [
            'products' => data_get($data, 'collection.products.edges', []),
            'pageInfo' => data_get($data, 'collection.products.pageInfo', []),
            'error'    => null,
        ] : null;
    }
    /**
     * Summary of updateProductStatus
     * @param \App\Models\User $shop
     * @param string $productId
     * @param string $status
     * @return array|null
     */
    public function updateProductStatus(User $shop, string $productId, string $status): ?array
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

        return $this->graphqlRequest($shop, $query);
    }
    public function addTags(User $shop, string $productId, array $tags): ?array
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

        $result = $this->graphqlRequest($shop, $query);

        // Log::info("🟢 Shopify addTags Mutation", [
        //     'query' => $query,
        //     'productIds'   => [$productId],
        //     'result'       => $result
        // ]);

        return $result;
    }

    public function removeTags(User $shop, string $productId, array $tags): ?array
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

        return $this->graphqlRequest($shop, $query);
    }
    public function addToCollection(User $shop, string $collectionId, string $productId): ?array
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

        $url   = $this->getGraphqlUrl($shop);
        $token = $shop->access_token ?? $shop->password; // Ưu tiên access_token, fallback password

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type'           => 'application/json',
            ])->post($url, ['query' => $query]);

            Log::debug("🔎 Shopify GraphQL addToCollection Response", [
                'query'  => $query,
                'status' => $response->status(),
                'json'   => $response->json(),
                'body'   => $response->body(),
                // 'success' => $response->successful(),
                // 'data' => $response->json('data'),
            ]);
            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error("❌ Shopify GraphQL Request Failed", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error("🚨 Shopify GraphQL Exception", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        return null;
    }
    public function removeFromCollection(User $shop, string $collectionId, string $productId): ?array
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

        $url   = $this->getGraphqlUrl($shop);
        $token = $shop->access_token ?? $shop->password;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type'           => 'application/json',
            ])->post($url, ['query' => $query]);

            Log::debug("🔎 Shopify GraphQL removeFromCollection Response (Remove)", [
                'query'  => $query,
                'status' => $response->status(),
                'json'   => $response->json(),
                'body'   => $response->body(),
            ]);

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error("❌ Shopify GraphQL Request Failed (Remove)", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error("🚨 Shopify GraphQL Exception (Remove)", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    /**
     * Summary of getShopByDomain
     * @param string $shopDomain
     * @return User|null
     */
    public function getShopByDomain(string $shopDomain): ?User
    {
        return User::where('name', $shopDomain)->first();
    }
    /**
     * Summary of getFirstShop
     * @return User|null
     */
    public function getFirstShop(): ?User
    {
        return User::first();
    }
}
