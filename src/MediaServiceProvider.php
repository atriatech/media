<?php

namespace Atriatech\Media;

use Atriatech\Media\Helpers\MediumHelper;
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
        app()->bind('atriatechmedia', function() {
            return new MediumHelper;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        config()->set('atriatech_media.version', '2.1.6');
        Blade::directive('atriatech_media', function ($file) {
            $file = trim($file, "'");
            if ($file == 'css') {
                return '<?php $css_media_selector = asset(trim(config(\'atriatech_media.url_prefix\'), \'/\') . \'/\' . \'atriatech/media/css/media-selector.css\'); echo \'<link href="\' . $css_media_selector . \'?ver=\' . rand(10000000, 99999999) . \'" rel="stylesheet" type="text/css"/>\'; ?>';
            } else if ($file == 'js') {
                return '<?php $js_media_selector = asset(trim(config(\'atriatech_media.url_prefix\'), \'/\') . \'/\' . \'atriatech/media/js/media-selector.js\'); echo \'<script src="\' . route(\'atriatech_media_router\') . \'"></script><script src="\' . route(\'atriatech_media_config\') . \'"></script><script src="\' . $js_media_selector . \'?ver=\' . rand(10000000, 99999999) . \'"></script>\'; ?>';
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
        $this->loadViewsFrom(__DIR__.'/views', 'atriatech_media');
        
        $this->publishes([
            __DIR__.'/config' => base_path('config'),
        ], 'atriatech-media-config');

        $this->publishes([
            __DIR__.'/../dist' => public_path('atriatech/media'),
        ], 'atriatech-media-public');
        
        $this->publishes([
            __DIR__.'/migrations' => database_path('migrations'),
        ], 'atriatech-media-migrations');
    }
}
