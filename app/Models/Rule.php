<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'based_on',
        'discount_value',
        'discount_type',
        'applies_to',
        'applies_to_value',
        'status',
        'start_at',
        'end_at',
        'shop_id',
        'add_tag', 
    ];

    protected $casts = [
        'based_on'          => 'string',   // 'price' | 'compareAtPrice'
        'discount_type'     => 'string',   // 'percent' | 'fixed'
        'applies_to'        => 'string',   // 'products' | 'tags' | 'vendors' | 'collections' | 'whole_store'
        'status'            => 'string',   // 'active' | 'inactive' | 'archived'
        'applies_to_value'  => 'array',
        'add_tag'           => 'string',    
        'start_at'          => 'datetime',
        'end_at'            => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(User::class, 'shop_id');
    }

    
}
