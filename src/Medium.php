<?php

namespace Atriatech\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Medium extends Model
{
    protected $guarded = [];

    public function getPathAttribute($value)
    {
        $media_url = trim((empty(config('atriatech_media.media_url'))) ? url('/') : config('atriatech_media.media_url'), '/');
        $path = url(trim(config('atriatech_media.url_prefix'), '/') . Storage::url($value));
        return str_replace(trim(url('/'), '/'), $media_url, $path);
    }

    public function scopePath($query, $paths = null)
    {
        if (!empty($paths)) {
            $newPaths = [];
            $media_url = trim((empty(config('atriatech_media.media_url'))) ? url('/') : config('atriatech_media.media_url'), '/');
            foreach($paths as $path) {
                $newPaths[] = str_replace($media_url . '/storage', 'public', $path['path']);
            }
            return $query->whereIn('path', $newPaths);
        }
        return $query;
    }

    public function getOptionsAttribute($value)
    {
        if (empty($value)) {
            return (object)[];
        }

        $options = (array)json_decode($value);

        $newOptions = [];
        $media_url = trim((empty(config('atriatech_media.media_url'))) ? url('/') : config('atriatech_media.media_url'), '/');
        if (!empty($options['subSizes'])) {
            $subSizes = (array)$options['subSizes'];
            foreach($subSizes as $key => $subSize) {
                $path = url(trim(config('atriatech_media.url_prefix'), '/') . Storage::url($subSize));
                $subSizes[$key] = str_replace(trim(url('/'), '/'), $media_url, $path);
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
