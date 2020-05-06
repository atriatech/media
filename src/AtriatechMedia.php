<?php

namespace Atriatech\Media;

trait AtriatechMedia
{
	public function media()
	{
		return $this->morphToMany(Medium::class, 'media_item', 'medium_items', 'media_item_id', 'medium_id');
	}

	public function addMedia($paths)
	{
		$media = [];
		if (!empty($paths)) {
		    if (empty($paths[0])) {
                $paths = [$paths];
            }

            $allMedia = Medium::path($paths)->get();

            foreach ($allMedia as $medium) {
                $index = array_search($medium->path, array_column($paths, 'path'));
                if ($index !== false) {
                    $media[$medium->id] = ['name' => $paths[$index]['key']];
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

            $allMedia = Medium::path($paths)->get();

            foreach ($allMedia as $medium) {
                $index = array_search($medium->path, array_column($paths, 'path'));
                if ($index !== false) {
                    $media[$medium->id] = ['name' => $paths[$index]['key']];
                }
            }
		    $this->media()->sync($media);
        }
	}

	public function getMedia()
    {
        return $this->media()->get();
    }

    public function getMedium($id = NULL)
    {
        return (!empty($id)) ? $this->media()->find($id) : $this->media()->first();
    }

    public function getMediumByName($name = NULL)
    {
        if (!empty($name)) {
            return $this->media()->wherePivot('name', '=', $name)->first();
        } else {
            return $this->media()->first();
        }
    }
}
