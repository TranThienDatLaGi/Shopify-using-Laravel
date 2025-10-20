<?php

namespace App\Jobs;

use App\Services\ProductService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkProductActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected string $shopDomain;
    protected string $accessToken;
    protected string $action;
    protected array $productIds;
    protected array $payload;
    public function __construct(string $shopDomain, string $accessToken, string $action, array $productIds, array $payload = [])
    {
        $this->shopDomain  = $shopDomain;
        $this->accessToken = $accessToken;
        $this->action      = $action;
        $this->productIds  = $productIds;
        $this->payload     = $payload;
    }

    public function handle(ProductService $service): void
    {
        foreach ($this->productIds as $id) {
            switch ($this->action) {
                case 'status':
                    $service->updateProductStatus($this->shopDomain, $this->accessToken, $id, $this->payload['status']);
                    break;

                case 'add_tags':
                    $service->addTags($this->shopDomain, $this->accessToken, $id, $this->payload['tags']);
                    break;

                case 'remove_tags':
                    $service->removeTags($this->shopDomain, $this->accessToken, $id, $this->payload['tags']);
                    break;

                case 'add_collection':
                    $service->addToCollection($this->shopDomain, $this->accessToken, $this->payload['collection_id'], $id);
                    break;

                case 'remove_collection':
                    $service->removeFromCollection($this->shopDomain, $this->accessToken, $this->payload['collection_id'], $id);
                    break;
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("❌ BulkProductActionJob failed", [
            'shop'   => $this->shopDomain,
            'action' => $this->action,
            'error'  => $exception->getMessage(),
        ]);
    }
}
