<?php

namespace Atriatech\Media\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool upload($file, $path = '')
 * @method static Medium medium_model()
 */

class AtriatechMedia extends Facade {
    protected static function getFacadeAccessor()
    {
        return 'atriatechmedia';
    }
}
