<?php

namespace Atriatech\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Medium extends Model
{
    protected $guarded = [];

    public function getPathAttribute($value)
    {
        return url(trim(config('atriatech_media.url_prefix'), '/') . Storage::url($value));
    }

    public function scopePath($query, $paths = null)
    {
        if (!empty($paths)) {
            $newPaths = [];
            foreach($paths as $path) {
                $newPaths[] = str_replace(url(trim(config('atriatech_media.url_prefix'), '/') . '/storage'), 'public', $path['path']);
            }
            return $query->whereIn('path', $newPaths);
        }
        return $query;
    }

    public function getOptionsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        $options = (array)json_decode($value);

        $newOptions = [];
        if (!empty($options['subSizes'])) {
            $subSizes = (array)$options['subSizes'];
            foreach($subSizes as $key => $subSize) {
                $subSizes[$key] = url(trim(config('atriatech_media.url_prefix'), '/') . Storage::url($subSize));
            }
            $newOptions['subSizes'] = $subSizes;
        }

        return json_decode(json_encode($newOptions));
    }

    public function getVisibilityAttribute()
    {
        return Storage::getVisibility($this->attributes['path']);
    }

    public function getSizeAttribute()
    {
        return $this->formatSizeUnits(Storage::size($this->attributes['path']));
    }

    public function getBasenameAttribute()
    {
        return basename($this->attributes['path']);
    }

    public function getSubSize($key)
    {
        return (!empty($this->options) && !empty($this->options->subSizes) && !empty($this->options->subSizes->$key)) ? $this->options->subSizes->$key : '';
    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
