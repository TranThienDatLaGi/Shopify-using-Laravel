<?php

namespace App\Services;

use App\Models\Rule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    public function graphqlRequest(string $shopDomain, string $accessToken, string $query, array $variables = []): ?array
    {
        $url = sprintf(
            'https://%s/admin/api/%s/graphql.json',
            $shopDomain,
            config('shopify-app.api_version')
        );

        try {
            $payload = ['query' => $query];
            if (!empty($variables)) {
                $payload['variables'] = $variables;
            }

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type'           => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
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
            ]);
        }

        return null;
    }

    public function getShopByDomain(string $shopDomain): ?User
    {
        return User::where('name', $shopDomain)->first();
    }
    public function getFirstShop(): ?User
    {
        return User::first();
    }
    public function getCurrentShopDomain(Request $request): string
    {
        if (session()->has('shopify_domain')) {
            return session('shopify_domain');
        }

        if (session()->has('shop')) {
            return session('shop');
        }

        if ($shop = $request->header('X-Shopify-Shop-Domain')) {
            return $shop;
        }

        if ($shop = $request->get('shop')) {
            return $shop;
        }

        if ($referer = $request->header('Referer')) {
            if (preg_match('/admin\.shopify\.com\/store\/([^\/]+)/', $referer, $m)) {
                return $m[1] . '.myshopify.com';
            }
        }
        return '';
    }
}
