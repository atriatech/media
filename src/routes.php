<?php
use Illuminate\Support\Facades\Route;

Route::prefix(config('atriatech_media.route_prefix'))->middleware((empty(config('atriatech_media.middleware'))) ? [] : config('atriatech_media.middleware'))->name('atriatech.media.')->group(function() {
    Route::get('/', 'Atriatech\Media\MediumController@index')->name('index');
    Route::post('/getDirectories', 'Atriatech\Media\MediumController@getDirectories')->name('getDirectories');
    Route::post('/getFiles', 'Atriatech\Media\MediumController@getFiles')->name('getFiles');
    Route::post('/newFolder', 'Atriatech\Media\MediumController@newFolder')->name('newFolder');
    Route::post('/deleteItem', 'Atriatech\Media\MediumController@deleteItem')->name('deleteItem');
    Route::post('/renameItem', 'Atriatech\Media\MediumController@renameItem')->name('renameItem');
    Route::post('/uploadFile', 'Atriatech\Media\MediumController@uploadFile')->name('uploadFile');
});

// Router
Route::get('js/atriatech_media_router.js', 'Atriatech\Media\MediumController@atriatech_media_router')->name('atriatech_media_router');
Route::get('js/atriatech_media_config.js', 'Atriatech\Media\MediumController@atriatech_media_config')->name('atriatech_media_config');
