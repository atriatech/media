<?php
use Illuminate\Support\Facades\Route;

Route::prefix(config('atriatech_media.route_prefix'))->middleware((empty(config('atriatech_media.middleware'))) ? [] : config('atriatech_media.middleware'))->name('atriatech.media.')->group(function() {
    Route::get('/', [Atriatech\Media\MediumController::class, 'index'])->name('index');
    Route::post('/getDirectories', [Atriatech\Media\MediumController::class, 'getDirectories'])->name('getDirectories');
    Route::post('/getFiles', [Atriatech\Media\MediumController::class, 'getFiles'])->name('getFiles');
    Route::post('/newFolder', [Atriatech\Media\MediumController::class, 'newFolder'])->name('newFolder');
    Route::post('/deleteItem', [Atriatech\Media\MediumController::class, 'deleteItem'])->name('deleteItem');
    Route::post('/renameItem', [Atriatech\Media\MediumController::class, 'renameItem'])->name('renameItem');
    Route::post('/uploadFile', [Atriatech\Media\MediumController::class, 'uploadFile'])->name('uploadFile');
});

// Router
Route::get('js/atriatech_media_router.js', [Atriatech\Media\MediumController::class, 'atriatech_media_router'])->name('atriatech_media_router');
Route::get('js/atriatech_media_config.js', [Atriatech\Media\MediumController::class, 'atriatech_media_config'])->name('atriatech_media_config');
