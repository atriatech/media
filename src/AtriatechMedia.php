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
			$allMedia = Medium::path($paths)->get();
			foreach($allMedia as $medium) {
                $media[] = $medium->id;
            }
		}
		$this->media()->attach($media);
	}

	public function updateMedia($paths)
	{
		$media = [];
        if (!empty($paths)) {
            $allMedia = Medium::path($paths)->get();
            foreach($allMedia as $medium) {
                $media[] = $medium->id;
            }
        }
		$this->media()->sync($media);
	}

	public function getMedia()
    {
        return $this->media()->get();
    }

    public function getMedium($id = NULL)
    {
        return (!empty($id)) ? $this->media()->find($id) : $this->media()->first();
    }
}
