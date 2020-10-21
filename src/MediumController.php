<?php

namespace Atriatech\Media;

use App\Http\Controllers\Controller;
use Atriatech\Media\Facades\AtriatechMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $accept = $request->input('accept');
        if (empty($path)) {
            $path = 'public/' . config('atriatech_media.upload_folder');
        }
        $dirs = $this->getDirectories($path);

        $files = Medium::whereRaw("REPLACE(path, SUBSTRING_INDEX(path, '/', -1), '') = '" . $path . "/'")->limit($limit)->offset($offset)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get()->map(function ($item) {
            $item->visibility = $item->visibility;
            $item->size = $item->size;
            $item->basename = $item->basename;

            return $item;
        })->filter(function ($item) use ($accept) {
            if (!empty($accept)) {
                if (in_array('.' . pathinfo($item->path, PATHINFO_EXTENSION), explode(',', $accept)) || in_array($item->mime_type, explode(',', $accept))) {
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
        foreach ($breadcrumb as $index => $b) {
            if ($b == 'public') {
                $bbs[] = ['path' => '', 'name' => 'Public'];
            } else {
                $bb = $breadcrumb;
				$bb = array_splice($bb, 1, $index);
                $c_path = implode('/', $bb);
                $bbs[] = ['path' => $c_path, 'name' => $b];
            }
		}

		$breadcrumb = [];
        foreach($bbs as $index => $row) {
        	if ($index == 0) {
				$breadcrumb[] = $row;
			} else {
        		$bbs[$index]['path'] = 'public/' . config('atriatech_media.upload_folder') . '/' . $row['path'];
				$breadcrumb[] = $bbs[$index];
			}
		}

        return [
            'files' => ($offset == 0) ? (array_merge($dirs, (!empty($files[0])) ? $files : [])) : ((!empty($files[0])) ? $files : []),
            'breadcrumb' => $breadcrumb
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
        foreach ($request->input('items') as $item) {
            if (Storage::exists($item)) {
                $this->rrmdir(((!empty(config('atriatech_media.url_prefix'))) ? trim(config('atriatech_media.url_prefix'), '/') . '/' : '') . ltrim(Storage::url($item), '/'));
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
            foreach ($media as $medium) {
                $medium->update([
                    'path' => DB::raw("CONCAT('" . $newPath . "/', SUBSTRING_INDEX(path, '/', -1))")
                ]);
                if (strpos($medium->mime_type, 'image/') !== false) {
                    $attributes = $medium->getAttributes();
                    $options = json_decode($attributes['options']);
                    $subSizes = (array)$options->subSizes;
                    foreach ($subSizes as $index => $subSize) {
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
                foreach ($subSizes as $index => $subSize) {
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

    private function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
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
            'file' => 'required|max:' . $this->parseSize(ini_get('upload_max_filesize')) . '|mimes:' . str_replace('.', '', $accept),
        ], [
            'required' => 'You should select a file.',
            'max' => 'You Can not upload more than ' . ini_get('upload_max_filesize') . '.',
            'mimes' => 'Selected file not supported.',
        ]);

        $path = $request->input('path') . '/';
        $file = $request->file('file');

        return response(AtriatechMedia::upload($file, $path), 200);
    }

    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . '/' . $object) && !is_link($dir . "/" . $object)) {
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

    public function atriatech_media_router()
	{
		$routes_name = array_keys(app('router')->getRoutes()->getRoutesByName());

		$routes = [];
		foreach ($routes_name as $route) {
			$uri = app('router')->getRoutes()->getByName($route)->uri();

			if (strpos($route, 'atriatech.media.') !== false) {
				$routes[] = [
					'name' => $route,
					'uri' => urlencode($uri)
				];
			}
		}

		header('Content-Type: text/javascript');
		echo ("let allMediaRoutes = JSON.parse('" . json_encode($routes) . "');
			function mediaRoute(name, parameters = null) {
				const r = allMediaRoutes.find(x => x.name === name);
				if (parameters) {
					let uri = r.uri;
					for (const param of Object.keys(parameters)) {
						uri = uri.replace(new RegExp(encodeURIComponent('{' + param + '}'), 'g'), parameters[param]);
						uri = uri.replace(new RegExp(encodeURIComponent('{' + param + '?}'), 'g'), parameters[param]);
					}
					return '" . url('/') . "' + '/' + decodeURIComponent(uri);
				} else {
					return '" . url('/') . "' + '/' + decodeURIComponent(r.uri);
				}
			}");
		exit();
	}

	public function atriatech_media_config()
	{
		$config = config('atriatech_media');

		if (empty($config['url_prefix'])) {
			$config['url_prefix'] = '';
		}

		header('Content-Type: text/javascript');
		echo ("
			const asset = '" . asset('') . "';
			const config = JSON.parse('" . json_encode($config) . "');
		");
		exit();
	}
}
