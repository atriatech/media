<?php
use Illuminate\Support\Facades\Route;

Route::prefix('media')->name('atriatech.media.')->group(function() {
    Route::get('/', 'Atriatech\Media\MediumController@index')->name('index');
    Route::post('/getDirectories', 'Atriatech\Media\MediumController@getDirectories')->name('getDirectories');
    Route::post('/getFiles', 'Atriatech\Media\MediumController@getFiles')->name('getFiles');
    Route::post('/newFolder', 'Atriatech\Media\MediumController@newFolder')->name('newFolder');
    Route::post('/deleteItem', 'Atriatech\Media\MediumController@deleteItem')->name('deleteItem');
    Route::post('/renameItem', 'Atriatech\Media\MediumController@renameItem')->name('renameItem');
    Route::post('/uploadFile', 'Atriatech\Media\MediumController@uploadFile')->name('uploadFile');
});

// Router
Route::get('/js/atriatech_media_router.js', function () {
    $routes_name = array_keys(app('router')->getRoutes()->getRoutesByName());

    $routes = [];
    foreach ($routes_name as $route) {
        $uri = app('router')->getRoutes()->getByName($route)->uri();

        if (strpos($route, 'atriatech.media.') !== false) {
            $routes[] = [
                'name' => $route,
                'uri' => urlencode($uri)
            ];
        }
    }

    header('Content-Type: text/javascript');
    echo ("let allRoutes = JSON.parse('" . json_encode($routes) . "');
    function route(name, parameters = null) {
        const r = allRoutes.find(x => x.name === name);
        if (parameters) {
            let uri = r.uri;
            for (const param of Object.keys(parameters)) {
                uri = uri.replace(new RegExp(encodeURIComponent('{' + param + '}'), 'g'), parameters[param]);
                uri = uri.replace(new RegExp(encodeURIComponent('{' + param + '?}'), 'g'), parameters[param]);
            }
            return '/' + decodeURIComponent(uri);
        } else {
            return '/' + decodeURIComponent(r.uri);
        }
    }");
    exit();
})->name('atriatech_media_router');

// Router
Route::get('/js/atriatech_media_config.js', function () {
    $config = config('atriatech_media');

    header('Content-Type: text/javascript');
    echo ("
        const asset = '" . asset('') . "';
        const config = JSON.parse('" . json_encode($config) . "');
    ");
    exit();
})->name('atriatech_media_config');
