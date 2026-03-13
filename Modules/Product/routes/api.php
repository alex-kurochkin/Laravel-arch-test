<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Product\Http\Controllers\ProductImageController;

// API маршруты для продуктов
Route::prefix('products')->group(static function (): void {
    // CRUD операции
    Route::get('/', [ProductController::class, 'index']);          // GET /api/products
    Route::post('/', [ProductController::class, 'store']);         // POST /api/products
    Route::get('active', [ProductController::class, 'active']);    // GET /api/products/active
    Route::get('{id}', [ProductController::class, 'show']);        // GET /api/products/{id}
    Route::put('{id}', [ProductController::class, 'update']);      // PUT /api/products/{id}
    Route::delete('{id}', [ProductController::class, 'destroy']);  // DELETE /api/products/{id}

    // Дополнительные операции
    Route::patch('{id}/stock', [ProductController::class, 'stock']);          // PATCH /api/products/{id}/stock
    Route::post('{id}/duplicate', [ProductController::class, 'duplicate']);   // POST /api/products/{id}/duplicate

    // Маршруты для изображений продуктов
    Route::prefix('{productId}/images')->group(static function (): void {
        Route::get('/', [ProductImageController::class, 'index']);             // GET /api/products/{id}/images
        Route::post('/', [ProductImageController::class, 'store']);            // POST /api/products/{id}/images
        Route::post('/reorder', [ProductImageController::class, 'reorder']);   // POST /api/products/{id}/images/reorder
        Route::patch('{imageId}/main', [ProductImageController::class, 'setMain']); // PATCH /api/products/{id}/images/{imageId}/main
        Route::put('{imageId}', [ProductImageController::class, 'update']);    // PUT /api/products/{id}/images/{imageId}
        Route::delete('{imageId}', [ProductImageController::class, 'destroy']); // DELETE /api/products/{id}/images/{imageId}
    });
});
