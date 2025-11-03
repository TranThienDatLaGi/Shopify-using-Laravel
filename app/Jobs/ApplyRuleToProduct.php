<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RuleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batchable;

class ApplyRuleToProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected int $shopId;
    protected string $productId;
    protected float $discount;
    protected string $discountType;
    protected string $basedOn;
    protected int $ruleID;


    public function __construct(
        int $shopId,
        string $productId,
        float $discount,
        string $discountType,
        string $basedOn,
        int $ruleID,
    ) {
        $this->shopId = $shopId;
        $this->productId = $productId;
        $this->discount = $discount;
        $this->discountType = $discountType;
        $this->basedOn = $basedOn;
        $this->ruleID = $ruleID;
    }

    public function handle(RuleService $ruleService): void
    {
        $shop = User::find($this->shopId);
        // Log::info('Job ApplyRuleToProduct started', ['shopId' => $this->shopId, 'productId' => $this->productId]);
        if (!$shop) {
            Log::warning("Shop ID {$this->shopId} not found, skipping ApplyRuleToProduct job.");
            return;
        }

        // Log::info('ApplyRuleToProduct job started', [
        //     'shop' => $shop->name,
        //     'productId' => $this->productId,
        // ]);
        $shopDomain=$shop->name;
        $accessToken=$shop->access_token ?? $shop->password;
        $result = $ruleService->setRuleToProduct(
            $shopDomain,
            $accessToken,
            $this->productId,
            $this->discount,
            $this->discountType,
            $this->basedOn,
            $this->ruleID,
        );

        if (!$result['success']) {
            Log::error('ApplyRuleToProduct failed', [
                'shop' => $shop->name,
                'productId' => $this->productId,
                'errors' => $result['error'] ?? null,
            ]);
        } else {
            Log::info('ApplyRuleToProduct success', [
                'shop' => $shop->name,
                'productId' => $this->productId,
            ]);
        }
    }
}
