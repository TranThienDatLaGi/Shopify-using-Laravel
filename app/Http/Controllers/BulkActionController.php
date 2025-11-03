<?php

namespace App\Http\Controllers;

use App\Jobs\BulkProductActionJob;
use App\Models\User;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class BulkActionController extends Controller
{
    protected $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }
    public function product(Request $request)
    {
        $currentShopDomain = $this->shopifyService->getCurrentShopDomain($request);
        $shop = User::where('name', $currentShopDomain)->first();

        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $productIds = $request->input('product_ids', []);
        $action     = $request->input('action');
        $payload    = $request->input('payload', []);

        if (empty($productIds)) {
            return response()->json(['error' => 'No products selected'], 422);
        }

        // ✅ Chuẩn hóa dữ liệu theo action
        switch ($action) {
            case 'add_collection':
            case 'remove_collection':
                if (isset($payload['collection_id']) && is_array($payload['collection_id'])) {
                    $payload['collection_id'] = $payload['collection_id'][0] ?? null;
                }
                break;

            case 'add_tags':
            case 'remove_tags':
                if (isset($payload['tags']) && is_string($payload['tags'])) {
                    $payload['tags'] = array_map('trim', explode(',', $payload['tags']));
                }
                break;

            case 'status':
                $payload['status'] = $payload['status'] ?? 'active';
                break;
        }

        $jobs = [];
        $shopDomain=$shop->name;
        $accessToken=$shop->access_token ?? $shop->password;
        foreach ($productIds as $id) {
            $jobs[] = new BulkProductActionJob($shopDomain, $accessToken, $action, [$id], $payload);
        }

        $batch = Bus::batch($jobs)->dispatch();

        return response()->json([
            'batch_id' => $batch->id,
            'message'  => 'Bulk action queued'
        ]);
    }

    public function status($batchId)
    {
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return response()->json(['error' => 'Batch not found']);
        }

        // Tính số job đã hoàn thành (bao gồm thành công và thất bại)
        $processedJobs = $batch->processedJobs();
        $totalJobs = $batch->totalJobs;

        // Nếu muốn chỉ tính job thành công (loại trừ job fail):
        $successfulJobs = $processedJobs - $batch->failedJobs;

        return response()->json([
            'finished'       => $batch->finished(),
            'processed_jobs' => $processedJobs,
            'total_jobs'     => $totalJobs,
            'successful_jobs' => $successfulJobs,
            'failed_jobs'    => $batch->failedJobs,
            'progress_detail'=> "{$processedJobs}/{$totalJobs}",
            'progress' => $batch->progress(),
        ]);
    }
}
