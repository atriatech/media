<?php

namespace Atriatech\Media;

trait AtriatechMedia
{
	public function media()
	{
		return $this->morphToMany(Medium::class, 'media_item', 'medium_items', 'media_item_id', 'medium_id')->withPivot('name');
	}

	public function addMedia($paths)
	{
		$media = [];
		if (!empty($paths)) {
		    if (empty($paths[0])) {
                $paths = [$paths];
            }

            foreach ($paths as $medium) {
                if (!empty($medium['id'])) {
                    $media[$medium['id']] = ['name' => $medium['key']];
                }
            }
            $this->media()->attach($media);
		}
	}

	public function updateMedia($paths)
	{
		$media = [];
        if (!empty($paths)) {
            if (empty($paths[0])) {
                $paths = [$paths];
            }

            foreach ($paths as $medium) {
                if (!empty($medium['id'])) {
                    $media[$medium['id']] = ['name' => $medium['key']];
                }
            }
            $this->media()->detach($this->media->pluck('id'));
		    $this->media()->attach($media);
        }
	}

	public function removeMedia($name)
    {
        $media = [];

        if (is_array($name)) {
            $allMedia = $this->getMediumByName($name);
            foreach ($allMedia as $medium) {
                $media[] = $medium->id;
            }
        } else {
            $medium = $this->getMediumByName($name);
            if (!empty($medium)) {
                $media[] = $medium->id;
            }
        }

        $this->media()->detach($media);
    }

	public function getMedia()
    {
        return $this->media;
    }

    public function getMediaByName($pattern = null)
    {
        return $this->media->filter(function($item) use ($pattern) {
        	if (empty($pattern)) {
        		return true;
			}
        	return preg_match($pattern, $item->pivot->name);
		})->values();
    }

    public function getMedium($id = NULL)
    {
        return (!empty($id)) ? $this->media->where('id', $id)->first() : $this->media->first();
    }

    public function getMediumByName($name = NULL)
    {
        if (!empty($name)) {
            if (is_array($name)) {
                return $this->media->whereIn('pivot.name', $name);
            } else {
                return $this->media->where('pivot.name', $name)->first();
            }
        } else {
            return $this->media->first();
        }
    }
}
