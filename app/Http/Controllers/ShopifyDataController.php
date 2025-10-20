<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyDataController extends Controller
{
    protected ProductService $productService;
    protected ShopifyService $shopifyService;

    public function __construct(ProductService $productService, ShopifyService $shopifyService)
    {
        $this->productService = $productService;
        $this->shopifyService = $shopifyService;
    }

    public function getData(Request $request)
    {
        $type = $request->input('type');
        $shop = $request->input('shop');
        $after = $request->input('after');   // ✅ con trỏ trang sau
        $before = $request->input('before'); // ✅ con trỏ trang trước
        $limit = $request->input('limit', 10); // ✅ mặc định 10 item mỗi lần
        $searchQuery = $request->input('searchQuery'); // ✅ từ khóa tìm kiếm (nếu có)

        $allowedTypes = ['products', 'collections', 'tags', 'vendors'];

        if (!in_array($type, $allowedTypes)) {
            return response()->json(['error' => "Type '{$type}' không hợp lệ."], 400);
        }

        // ✅ Lấy shop hiện tại
        $currentShopDomain = $this->shopifyService->getCurrentShopDomain($request)
            ?? $this->shopifyService->getFirstShop()?->name;

        $selectedShop = $this->shopifyService->getShopByDomain($currentShopDomain);
        $shopDomain= $currentShopDomain;
        $accessToken= $selectedShop->access_token ?? $selectedShop->password ?? null;
        if (!$selectedShop) {
            return response()->json(['error' => "Shop '{$currentShopDomain}' không tồn tại."], 404);
        }

        // ✅ Gọi đến service tương ứng, có truyền thêm searchQuery
        $data = match ($type) {
            'products'    => $this->productService->getProducts($shopDomain, $accessToken, $limit, $after, $before, $searchQuery),
            'collections' => $this->productService->getCollections($shopDomain, $accessToken),
            'tags'        => $this->productService->getTags($shopDomain, $accessToken),
            'vendors'     => $this->productService->getVendors($shopDomain, $accessToken),
        };

        return response()->json([
            'type' => $type,
            'data' => $data,
        ]);
    }
}
