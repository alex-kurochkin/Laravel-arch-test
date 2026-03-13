<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalogue\Http\Controllers\CatalogueController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('catalogues', CatalogueController::class)->names('catalogue');
});
