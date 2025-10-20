<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RuleService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResetProductPrice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected int $shopId;
    protected string $productId;
    protected int $ruleID;


    public function __construct(int $shopId, string $productId, int $ruleID)
    {
        $this->shopId = $shopId;
        $this->productId = $productId;
        $this->ruleID = $ruleID;
    }

    public function handle(RuleService $ruleService): void
    {
        $shop = User::find($this->shopId);

        if (!$shop) {
            Log::warning("Shop ID {$this->shopId} not found, skipping ResetProductPrice job.");
            return;
        }
        $shopDomain = $shop->name;
        $accessToken = $shop->access_token ?? $shop->password;
        $result = $ruleService->resetPriceForProduct(
            $shopDomain,
            $accessToken,
            $this->productId,
            $this->ruleID,
        );

        if (!$result['success']) {
            Log::error('ResetProductPrice failed', [
                'shop' => $shop->name,
                'productId' => $this->productId,
                'errors' => $result['error'] ?? null,
            ]);
        } else {
            Log::info('ResetProductPrice success', [
                'shop' => $shop->name,
                'productId' => $this->productId,
            ]);
        }
    }
}
