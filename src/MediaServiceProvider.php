<?php

namespace Atriatech\Media;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('atriatech_media', function ($file) {
            $file = trim($file, "'");
            if ($file == 'css') {
                return '<link href="' . asset('atriatech/media/css/media-selector.css') . '" rel="stylesheet" type="text/css"/>';
            } else if ($file == 'js') {
                return '
                <script src="' . route('atriatech_media_router') . '"></script>
                <script src="' . route('atriatech_media_config') . '"></script>
                <script src="' . asset('atriatech/media/js/media-selector.js') . '"></script>';
            } else {
                return '';
            }
        });
        Blade::directive('atriatech_media_start', function() {
            return '<div class="media-container">';
        });
        Blade::directive('atriatech_media_end', function() {
            return '</div>';
        });
        Blade::directive('atriatech_media_file', function($expression) {
            list($id, $options) = explode("', '", trim($expression, "'"));
            return '<div id="' . $id . '" data-plugin="media" data-options=\'' . $options . '\'></div>';
        });

        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadViewsFrom(__DIR__.'/views', 'atriatech_media');
//        if ($this->app->runningInConsole()) {
//            $this->commands([
//                AtriatechMediaCommand::class
//            ]);
//        }
        $this->publishes([
            __DIR__.'/config' => base_path('config'),
        ], 'atriatech-media-config');
        $this->publishes([
            __DIR__.'/../dist' => public_path('atriatech/media'),
        ], 'atriatech-media-public');
    }
}
