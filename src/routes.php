<?php
use Illuminate\Support\Facades\Route;

Route::prefix(config('atriatech_media.route_prefix'))->middleware((empty(config('atriatech_media.middleware'))) ? [] : config('atriatech_media.middleware'))->name('atriatech.media.')->group(function() {
    Route::get('/', ['MediumController@index'])->name('index');
    Route::post('/getDirectories', ['MediumController@getDirectories'])->name('getDirectories');
    Route::post('/getFiles', ['MediumController@getFiles'])->name('getFiles');
    Route::post('/newFolder', ['MediumController@newFolder'])->name('newFolder');
    Route::post('/deleteItem', ['MediumController@deleteItem'])->name('deleteItem');
    Route::post('/renameItem', ['MediumController@renameItem'])->name('renameItem');
    Route::post('/uploadFile', ['MediumController@uploadFile'])->name('uploadFile');
});

// Router
Route::get('js/atriatech_media_router.js', ['MediumController@atriatech_media_router'])->name('atriatech_media_router');
Route::get('js/atriatech_media_config.js', ['MediumController@atriatech_media_config'])->name('atriatech_media_config');
