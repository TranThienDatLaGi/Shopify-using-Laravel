<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceBackup extends Model
{
    protected $table = 'product_price_backups';
    protected $fillable = [
        'shop_name',
        'product_id',
        'variant_id',
        'original_price',
        'original_compare_at_price',
        'rule_id',
    ];
    public function scopeByShop($query, string $shopName)
    {
        return $query->where('shop_name', $shopName);
    }
    public function scopeByProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }
    public function scopeByVariant($query, string $variantId)
    {
        return $query->where('variant_id', $variantId);
    }
    public function scopeByRule($query, int $ruleId)
    {
        return $query->where('rule_id', $ruleId);
    }
}
