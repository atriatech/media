<?php

namespace Atriatech\Media\Helpers;

use Atriatech\Media\Medium;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MediumHelper {
    public static function upload($file, $path = '')
    {
        $path = 'public/' . config('atriatech_media.upload_folder') . '/' . str_replace('public/' . config('atriatech_media.upload_folder') . '/', '', $path);

        $counter = 2;
        $fileName = $file->getClientOriginalName();
        while (Storage::exists($path . $fileName)) {
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . ' (' . $counter . ').' . pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $counter++;
        }
        $file->storeAs($path, $fileName);
        $media = Medium::create([
            'path' => $path . $fileName,
            'mime_type' => $file->getMimeType(),
        ]);

        if (strpos($file->getMimeType(), 'image/') !== false) {
            $subSizes = config('atriatech_media.sub_sizes');

            $newPath = ltrim(trim(config('atriatech_media.url_prefix'), '/') . '/' . ltrim(Storage::url($path), '/'), '/');
            $sizes = [];
            $mediaSubSizes = [];
            foreach ($subSizes as $subSizeKey => $subSize) {
                $image = Image::make(file_get_contents($newPath . $fileName))->orientate();
                $width = $image->width();
                $height = $image->height();

                if ($subSize['width'] * $subSize['height'] < $width * $height) {
                    $pathInfo = pathinfo($fileName);
                    if ($width >= $height) {
                        if ($subSize['crop']) {
                            if (!in_array($subSize['width'] . 'x' . $subSize['height'], $sizes)) {
                                $sizes[] = $subSize['width'] . 'x' . $subSize['height'];
                                $image->fit($subSize['width'], $subSize['height'])->save($newPath . $pathInfo['filename'] . '-' . $subSize['width'] . 'x' . $subSize['height'] . '.' . $pathInfo['extension']);
                                $mediaSubSizes[$subSizeKey] = $path . $pathInfo['filename'] . '-' . $subSize['width'] . 'x' . $subSize['height'] . '.' . $pathInfo['extension'];
                            }
                        } else {
                            if ($subSize['width'] >= $subSize['height']) {
                                $newHeight = round(($height / $width) * $subSize['width']);
                                if (!in_array($subSize['width'] . 'x' . $newHeight, $sizes)) {
                                    $sizes[] = $subSize['width'] . 'x' . $newHeight;
                                    $image->resize($subSize['width'], $newHeight)->save($newPath . $pathInfo['filename'] . '-' . $subSize['width'] . 'x' . $newHeight . '.' . $pathInfo['extension']);
                                    $mediaSubSizes[$subSizeKey] = $path . $pathInfo['filename'] . '-' . $subSize['width'] . 'x' . $newHeight . '.' . $pathInfo['extension'];
                                }
                            } else {
                                $newWidth = round(($width / $height) * $subSize['height']);
                                if (!in_array($newWidth . 'x' . $subSize['height'], $sizes)) {
                                    $sizes[] = $newWidth . 'x' . $subSize['height'];
                                    $image->resize($newWidth, $subSize['height'])->save($newPath . $pathInfo['filename'] . '-' . $newWidth . 'x' . $subSize['height'] . '.' . $pathInfo['extension']);
                                    $mediaSubSizes[$subSizeKey] = $path . $pathInfo['filename'] . '-' . $newWidth . 'x' . $subSize['height'] . '.' . $pathInfo['extension'];
                                }
                            }
                        }
                    } else {
                        if ($subSize['crop']) {
                            if (!in_array($subSize['width'] . 'x' . $subSize['height'], $sizes)) {
                                $sizes[] = $subSize['width'] . 'x' . $subSize['height'];
                                $image->fit($subSize['width'], $subSize['height'])->save($newPath . $pathInfo['filename'] . '-' . $subSize['width'] . 'x' . $subSize['height'] . '.' . $pathInfo['extension']);
                                $mediaSubSizes[$subSizeKey] = $path . $pathInfo['filename'] . '-' . $subSize['width'] . 'x' . $subSize['height'] . '.' . $pathInfo['extension'];
                            }
                        } else {
                            if ($subSize['height'] >= $subSize['width']) {
                                $newWidth = round(($width / $height) * $subSize['height']);
                                if (!in_array($newWidth . 'x' . $subSize['height'], $sizes)) {
                                    $sizes[] = $newWidth . 'x' . $subSize['height'];
                                    $image->resize($newWidth, $subSize['height'])->save($newPath . $pathInfo['filename'] . '-' . $newWidth . 'x' . $subSize['height'] . '.' . $pathInfo['extension']);
                                    $mediaSubSizes[$subSizeKey] = $path . $pathInfo['filename'] . '-' . $newWidth . 'x' . $subSize['height'] . '.' . $pathInfo['extension'];
                                }
                            } else {
                                $newHeight = round(($height / $width) * $subSize['width']);
                                if (!in_array($subSize['width'] . 'x' . $newHeight, $sizes)) {
                                    $sizes[] = $subSize['width'] . 'x' . $newHeight;
                                    $image->resize($subSize['width'], $newHeight)->save($newPath . $pathInfo['filename'] . '-' . $subSize['width'] . 'x' . $newHeight . '.' . $pathInfo['extension']);
                                    $mediaSubSizes[$subSizeKey] = $path . $pathInfo['filename'] . '-' . $subSize['width'] . 'x' . $newHeight . '.' . $pathInfo['extension'];
                                }
                            }
                        }
                    }
                }
            }

            $media->update([
                'options' => json_encode([
                    'subSizes' => $mediaSubSizes,
                ])
            ]);

            return [
                'id' => $media->id,
                'path' => $media->path,
                'mime_type' => $media->mime_type,
                'options' => $media->options,
                'visibility' => $media->visibility,
                'size' => $media->size,
                'basename' => $media->basename,
                'created_at' => $media->created_at,
                'updated_at' => $media->updated_at,
            ];
        }

        return [
            'id' => $media->id,
            'path' => $media->path,
            'mime_type' => $media->mime_type,
            'options' => $media->options,
            'visibility' => $media->visibility,
            'size' => $media->size,
            'basename' => $media->basename,
            'created_at' => $media->created_at,
            'updated_at' => $media->updated_at,
        ];
    }
}
