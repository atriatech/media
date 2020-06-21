<?php

namespace Atriatech\Media;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MediumController extends Controller
{
    public function index()
    {
        if (!Storage::exists('public' . '/' . config('atriatech_media.upload_folder'))) {
            Storage::makeDirectory('public' . '/' . config('atriatech_media.upload_folder'));
        }
        return view('atriatech_media::index');
    }

    private function getDirectories($path)
    {
        $directories = Storage::directories($path);
        $dirs = [];
        foreach ($directories as $dir) {
            $dirs[] = [
                'mime_type' => Storage::getMimeType($dir),
                'path' => $dir,
                'basename' => basename($dir)
            ];
        }
        return $dirs;
    }

    public function getFiles(Request $request)
    {
        $path = $request->input('path');
        $accept = $request->input('accept');
        if (empty($path)) {
            $path = 'public/' . config('atriatech_media.upload_folder');
        }
        $dirs = $this->getDirectories($path);

        $files = Medium::whereRaw("REPLACE(path, SUBSTRING_INDEX(path, '/', -1), '') = '" . $path . "/'")->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get()->map(function($item) {
            $item->visibility = $item->visibility;
            $item->size = $item->size;
            $item->basename = $item->basename;

            return $item;
        })->filter(function($item) use($accept) {
            if (!empty($accept)) {
                if (in_array('.'.pathinfo($item->path, PATHINFO_EXTENSION), explode(',', $accept)) || in_array($item->mime_type, explode(',', $accept))) {
                    return $item;
                }
            } else {
                return $item;
            }
        });
        $files = array_values($files->toArray());

        $breadcrumb = [];
        if (!empty($path)) {
            $breadcrumb = array_merge($breadcrumb, explode('/', mb_substr($path, 7)));
        }
        $bbs = [];
        foreach($breadcrumb as $index => $b) {
            if ($b == 'public') {
                $bbs[] = ['path' => '', 'name' => 'Public'];
            } else {
                $bb = $breadcrumb;
                $bb = array_splice($bb, 1, $index);
                $c_path = implode('/', $bb);
                $bbs[] = ['path' => $c_path, 'name' => $b];
            }
        }

        return [
            'files' => array_merge($dirs, (!empty($files[0])) ? $files : []),
            'breadcrumb' => $bbs
        ];
    }

    public function newFolder(Request $request)
    {
        $request->validate(
            [
                'folder' => 'required',
                'currentDir' => 'required'
            ],
            [
                'folder.required' => 'Enter folder name!',
                'currentDir.required' => 'currentDir is required!'
            ]
        );
        $folder = $request->input('folder');
        $currentDir = $request->input('currentDir');

        if (Storage::exists($currentDir . '/' . $folder)) {
            return response(['err' => 'This folder already exists!'], 400);
        }

        Storage::makeDirectory($currentDir . '/' . $folder);

        return response([], 200);
    }

    public function deleteItem(Request $request)
    {
        $item_path = [];
        foreach($request->input('items') as $item) {
            if (Storage::exists($item)) {
                $this->rrmdir(trim(config('atriatech_media.url_prefix'), '/') . '/' . ltrim(Storage::url($item), '/'));
            } else {
                $media = Medium::path([['path' => $item]])->first();
                if (!empty($media)) {
                    $attributes = $media->getAttributes();
                    $item_path[] = $attributes['path'];
                    if (strpos($media->mime_type, 'image/') !== false) {
                        $options = json_decode($attributes['options']);
                        if (!empty($options)) {
                            $subSizes = (array)$options->subSizes;
                            if (!empty($subSizes)) {
                                foreach ($subSizes as $index => $subSize) {
                                    $item_path[] = $subSize;
                                }
                            }
                        }
                    }
                    $media->delete();
                }
            }
        }
        Storage::delete($item_path);

        return response([], 200);
    }

    public function renameItem(Request $request)
    {
        $item = $request->input('item');
        $newName = $request->input('newName');

        if (Storage::exists($item)) {
            $media = Medium::whereRaw("REPLACE(path, SUBSTRING_INDEX(path, '/', -1), '') = '" . $item . "/'")->get();
            $newPath = substr($item, 0, strpos($item, strrchr(rtrim($item, '/'), '/'))) . "/" . $newName;
            foreach($media as $medium) {
                $medium->update([
                    'path' => \DB::raw("CONCAT('" . $newPath . "/', SUBSTRING_INDEX(path, '/', -1))")
                ]);
                if (strpos($medium->mime_type, 'image/') !== false) {
                    $attributes = $medium->getAttributes();
                    $options = json_decode($attributes['options']);
                    $subSizes = (array)$options->subSizes;
                    foreach($subSizes as $index => $subSize) {
                        $newFile = $newPath . '/' . basename($subSize);
                        $subSizes[$index] = $newFile;
                    }
                    $options->subSizes = (object)$subSizes;
                    $medium->update([
                        'options' => json_encode($options)
                    ]);
                }
            }
            Storage::move($item, $newPath);
        } else {
            $medium = Medium::path([$item])->first();

            $attributes = $medium->getAttributes();
            $newPath = str_replace(pathinfo($attributes['path'], PATHINFO_FILENAME), $newName, $attributes['path']);
            $oldPath = $attributes['path'];
            $medium->update([
                'path' => $newPath
            ]);

            if (strpos($medium->mime_type, 'image/') !== false) {
                $options = json_decode($attributes['options']);
                $subSizes = (array)$options->subSizes;
                foreach($subSizes as $index => $subSize) {
                    $newPath = str_replace(pathinfo($item, PATHINFO_FILENAME), $newName, $subSize);
                    $subSizes[$index] = $newPath;

                    Storage::move($subSize, $newPath);
                }
                $options->subSizes = (object)$subSizes;
                $medium->update([
                    'options' => json_encode($options)
                ]);
            }

            Storage::move($oldPath, $attributes['path']);
        }
    }

    private function parseSize($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        else {
            return round($size);
        }
    }

    public function uploadFile(Request $request)
    {
        $accept = (!empty($request->input('accept')) ? $request->input('accept') : implode(',', array_values(config('atriatech_media.mime_types'))));
        if (empty(array_intersect(explode(',', $accept), explode(',', implode(',', array_values(config('atriatech_media.mime_types'))))))) {
            abort(500, 'Selected file not supported.');
        }
        $request->validate([
            'file' => 'required|max:'.$this->parseSize(ini_get('upload_max_filesize')).'|mimes:'.str_replace('.', '', $accept),
        ], [
            'required' => 'You should select a file.',
            'max' => 'You Can not upload more than ' . ini_get('upload_max_filesize') . '.',
            'mimes' => 'Selected file not supported.',
        ]);

        $path = $request->input('path') . '/';
        $file = $request->file('file');

        $counter = 2;
        $fileName = $file->getClientOriginalName();
        while(Storage::exists($path . $fileName)) {
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
            foreach($subSizes as $subSizeKey => $subSize) {
                $image = Image::make(file_get_contents($newPath . $fileName))->orientate();
                $width = $image->width();
                $height = $image->height();

                if ($subSize['width'] * $subSize['height'] < $width * $height) {
                    $pathInfo = pathinfo($fileName);
                    if ($width >= $height) {
                        if ($subSize['crop']) {
                            if (!in_array($subSize['width'].'x'.$subSize['height'], $sizes)) {
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

            return response([
                'id' => $media->id,
                'path' => $media->path,
                'mime_type' => $media->mime_type,
                'options' => $media->options,
                'visibility' => $media->visibility,
                'size' => $media->size,
                'basename' => $media->basename,
                'created_at' => $media->created_at,
                'updated_at' => $media->updated_at,
            ], 200);
        }

        return response([
            'id' => $media->id,
            'path' => $media->path,
            'mime_type' => $media->mime_type,
            'options' => $media->options,
            'visibility' => $media->visibility,
            'size' => $media->size,
            'basename' => $media->basename,
            'created_at' => $media->created_at,
            'updated_at' => $media->updated_at,
        ], 200);
    }

    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir. '/' .$object) && !is_link($dir."/".$object)) {
                        $this->rrmdir($dir . '/' . $object);
                    } else {
                        Medium::where('path', 'public' . mb_substr(ltrim($dir . '/' . $object, './'), 7))->delete();
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
