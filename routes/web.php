<?php

use App\Http\Controllers\ProductGraphQLController;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Route;

Route::middleware(['verify.shopify'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('/products', [ProductGraphQLController::class, 'index'])
        ->name('product.index');

    Route::get('/orders', function () {
        return view('order');
    })->name('orders.index');

    Route::post('/products/bulk-action', [ProductGraphQLController::class, 'bulkAction'])
        ->name('products.bulk-action');
    
});
Route::get('/products/bulk-action/status/{batchId}', function ($batchId) {
    $batch = Bus::findBatch($batchId);
    if (!$batch) {
        return response()->json(['error' => 'Batch not found']);
    }

    logger("🔎 Batch progress: " . $batch->progress());

    return response()->json([
        'finished' => $batch->finished(),
        'progress' => $batch->progress(),
        'failed'   => $batch->hasFailures(),
    ]);
});
