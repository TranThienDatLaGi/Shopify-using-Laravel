<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rule;
use App\Jobs\ApplyRuleToProduct;
use App\Jobs\ResetProductPrice;
use App\Services\RuleService;
use Illuminate\Support\Facades\Log;

class CheckRuleSchedule extends Command
{
    protected $signature = 'rules:check-schedule';
    protected $description = 'Kiểm tra và thực thi các rule theo thời gian start_at / end_at';
    protected $ruleService;

    public function __construct(RuleService $ruleService)
    {
        parent::__construct();
        $this->ruleService = $ruleService;
    }

    public function handle()
    {
        $now = now();
        $this->info("⏱ Đang kiểm tra rules lúc {$now}");

        $startRules = Rule::where('status', 'active')
            ->where('start_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>', $now);
            })
            ->get();

        foreach ($startRules as $rule) {
            $shop = $rule->shop;
            $shopDomain = $shop->name ?? null;
            $accessToken = $shop->access_token ?? $shop->password ?? null;
            $products = $this->ruleService
                ->getProductsByRule($shopDomain, $accessToken, $rule->applies_to, $rule->applies_to_value)['products'] ?? [];

            foreach ($products as $productId) {
                dispatch(new ApplyRuleToProduct(
                    $rule->shop_id,
                    $productId,
                    $rule->discount_value,
                    $rule->discount_type,
                    $rule->based_on,
                    $rule->id
                ));
            }

            $rule->update(['status' => 'active']);
            Log::info("⏰ Đã bắt đầu rule #{$rule->id}");
            $this->info("→ Bắt đầu rule #{$rule->id}");
        }

        $endRules = Rule::where('status', 'active')
            ->whereNotNull('end_at')
            ->where('end_at', '<=', $now)
            ->get();

        foreach ($endRules as $rule) {
            // $this->info("Rule: " . json_encode($rule->toArray()));

            $shop = $rule->shop;
            $shopDomain = $shop->name ?? null;
            $accessToken = $shop->access_token ?? $shop->password ?? null;
            $products = $this->ruleService
                ->getProductsByRule($shopDomain, $accessToken, $rule->applies_to, $rule->applies_to_value)['products'] ?? [];

            // $this->info("products: " . json_encode($products));

            foreach ($products as $productId) {
                dispatch(new ResetProductPrice($rule->shop_id, $productId, $rule->id));
            }

            Log::info("🕒 Đã kết thúc rule #{$rule->id}");
            $this->info("→ Kết thúc rule #{$rule->id}");
        }

        $this->info("✅ Hoàn thành kiểm tra rules.");
        return 0;
    }
}
