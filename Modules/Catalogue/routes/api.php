<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalogue\Http\Controllers\CatalogueController;
use Modules\Catalogue\Http\Controllers\CategoryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('catalogues', CatalogueController::class)->names('catalogue');
});

Route::prefix('categories')->group(static function (): void {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('active', [CategoryController::class, 'active']);
    Route::get('tree', [CategoryController::class, 'tree']);

//    Route::get('{id}', [CategoryController::class, 'show']);
//    Route::put('{id}', [CategoryController::class, 'update']);
    Route::delete('{id}', [CategoryController::class, 'destroy']);

    Route::get('{category}', [CategoryController::class, 'show']);
    Route::put('{category}', [CategoryController::class, 'update']);
//    Route::delete('{category}', [CategoryController::class, 'destroy']);
});
