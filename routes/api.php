<?php

use App\Http\Controllers\BulkActionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\ShopifyDataController;
use Illuminate\Support\Facades\Route;

Route::middleware(['verify.shopify'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('/products', [ProductController::class, 'index'])
        ->name('product.index');

    Route::prefix('bulk')->group(function () {
        // Bulk action cho product
        Route::post('/products', [BulkActionController::class, 'product'])
            ->name('bulk.products');
    });

    Route::get('/rules', [RuleController::class, 'index'])->name('rules.index');
    Route::get('/rules/create', [RuleController::class, 'create'])
        ->name('rules.create');
    Route::post('/rules', [RuleController::class, 'store'])->name('rules.store');
    Route::get('/rules/{id}/edit', [RuleController::class, 'edit'])->name('rules.edit');
    Route::put('/rules/{id}', [RuleController::class, 'update'])->name('rules.update');
    Route::put('/rules/{id}/status', [RuleController::class, 'updateStatus'])
        ->name('rules.updateStatus');
    Route::get('/rules/{id}', [RuleController::class, 'getRuleById'])
        ->name('rules.getRuleById');
    Route::post('/rules/{id}/duplicate', [RuleController::class, 'duplicate'])
        ->name('rules.duplicate');
    Route::delete('/rules/{id}', [RuleController::class, 'destroy'])
        ->name('rules.destroy');
    // routes/web.php
    Route::get('/shopify/data', [ShopifyDataController::class, 'getData'])->name('shopify.data');

    // Route::resource('rules', RuleController::class)->only(['index', 'create', 'store']);
});
Route::get('/bulk/status/{batchId}', [BulkActionController::class, 'status'])
    ->name('bulk.status');
