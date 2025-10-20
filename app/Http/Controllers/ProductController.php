<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
  protected ProductService $productService;
  protected ShopifyService $shopifyService;

  public function __construct(ProductService $productService, ShopifyService $shopifyService)
  {
    $this->productService  = $productService;
    $this->shopifyService  = $shopifyService;
  }
  public function index(Request $request)
  {
    $currentShopDomain = $this->shopifyService->getCurrentShopDomain($request);
    $defaultPage = 20; 

    if (empty($currentShopDomain)) {
      Log::warning('No shop domain found', [
        'session_keys' => array_keys(session()->all()),
        'referer'      => $request->header('Referer'),
      ]);
      $firstShop = $this->shopifyService->getFirstShop();
      $currentShopDomain = $firstShop?->name ?? '';
    }

    $selectedShop = $this->shopifyService->getShopByDomain($currentShopDomain);
    $shopDomain= $currentShopDomain;
    $accessToken = $selectedShop->access_token ?? $selectedShop->password ?? '';
    if (!$selectedShop) {
      return view('welcome', [
        'products'  => [],
        'pageInfo'  => null,
        'perPage'   => $defaultPage,
        'error'     => "Shop '{$currentShopDomain}' không tồn tại trong hệ thống.",
      ]);
    }
    $perPage = (int) $request->get('per_page', $defaultPage);
    if (!in_array($perPage, [20, 40, 60])) {
      $perPage = $defaultPage;
    }

    $after  = $request->get('after');
    $before = $request->get('before');
    $sort   = $request->get('sort', 'title');
    $order  = $request->get('order', 'asc');
    $collectionId = $request->get('collection');

    $products = [];
    $pageInfo = null;

    if ($collectionId) {
      $result = $this->productService->getProductsByCollection(
        $shopDomain,
        $accessToken,
        $collectionId,
        $perPage,
        $after,
        $before,
        $sort,
        $order
      );
    } else {
      $filters   = [];
      $title     = trim((string) $request->get('title', ''));
      $status    = (array) $request->get('status', []);
      $tag       = trim((string) $request->get('tag', ''));
      $vendors   = (array) $request->get('vendors', []);
      $types     = (array) $request->get('types', []);

      if ($title !== '') {
        $filters[] = 'title:*' . addcslashes($title, '"') . '*';
      }
      if (!empty($status)) {
        $statusFilters = array_map(fn($s) => 'status:' . strtoupper($s), $status);
        $filters[] = '(' . implode(' OR ', $statusFilters) . ')';
      }
      if ($tag !== '') {
        $filters[] = 'tag:"' . addcslashes($tag, '"') . '"';
      }
      if (!empty($vendors)) {
        $filters[] = '(' . implode(
          ' OR ',
          array_map(fn($v) => 'vendor:"' . addcslashes($v, '"') . '"', $vendors)
        ) . ')';
      }
      if (!empty($types)) {
        $filters[] = '(' . implode(
          ' OR ',
          array_map(fn($t) => 'product_type:"' . addcslashes($t, '"') . '"', $types)
        ) . ')';
      }

      $searchQuery = count($filters) ? implode(' ', $filters) : null;

      $result = $this->productService->getProducts(
        $shopDomain,
        $accessToken,
        $perPage,
        $after,
        $before,
        $searchQuery,
        $sort,
        $order
      );
    }

    $products = $result['products'] ?? [];
    $pageInfo = $result['pageInfo'] ?? null;
    // Get another data to view
    $productTags        = $this->productService->getTags($shopDomain, $accessToken);
    $productVendors     = $this->productService->getVendors($shopDomain, $accessToken);
    $productTypes       = $this->productService->getProductType($shopDomain, $accessToken);
    $productCollections = $this->productService->getCollections($shopDomain, $accessToken);

    // Ajax (partial reload)
    if ($request->ajax()) {
      return response()->json([
        'table'      => view('products.partials.table_body', compact('products'))->render(),
        'pagination' => view('products.partials.pagination', compact('pageInfo'))->render(),
      ]);
    }
    // Full page load
    return view('products.index', [
      'products'     => $products,
      'pageInfo'     => $pageInfo,
      'tags'         => $productTags,
      'vendors'      => $productVendors,
      'productTypes' => $productTypes,
      'collections'  => $productCollections,
      'perPage'      => $perPage,
      'sort'         => $sort,
      'order'        => $order,
    ]);
  }
}
