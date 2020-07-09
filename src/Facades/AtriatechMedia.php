<?php

namespace Atriatech\Media\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool upload($file, $path = '')
 */

class AtriatechMedia extends Facade {
    protected static function getFacadeAccessor()
    {
        return 'atriatechmedia';
    }
}
