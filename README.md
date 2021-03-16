![Media - Explore, Upload, Delete and Create new folder](https://raw.githubusercontent.com/atriatech/media/master/screenshot.png)

# Media - Explore, Upload, Delete and Create new folder

You can use this package to upload your media and attach the media to your models.

| Version | Laravel |
| ------- | ------- |
| ^1.0.0 | ^6.0.0 |
| ^2.0.0 | ^8.0.0 |

# Installation

1. `composer require atriatech/media`.
2. add `Atriatech\Media\MediaServiceProvider::class` to `providers` array inside `config/app.php`.
3. add `'AtriatechMedia' => Atriatech\Media\Facades\AtriatechMedia::class` to `aliases` array inside `config/app.php`.
4. add `"Atriatech\\Media\\": "vendor/atriatech/media/src/"` to `autoload => psr-4` object inside `composer.json` file, then run this command: `composer dump-autoload`.
5. If you haven't link your storage, please run this command `php artisan storage:link`.
6. run `php artisan vendor:publish --tag=atriatech-media-config` to copy the config file into `config` folder.
7. run `php artisan vendor:publish --tag=atriatech-media-public` to copy asset files into `public` folder, running this command with `--force` flag is recommended.
8. run `php artisan migrate` to create the tables.

# API

You can use these methods on your model:

| Method | Parameters | Description | Example |
| ------ | ---------- | ----------- | ------- |
| addMedia | $paths - (Single-Array) | Add media to your model | `User::findOrFail(1)->addMedia([$request->input('image')])` |
| updateMedia | $paths - (Array) | Update media for your model | `User::findOrFail(1)->updateMedia([$request->input('image')])` |
| removeMedia | $name - (Single-Array) | Remove media from your model | `User::findOrFail(1)->removeMedia('image')` |
| getMedia | - | Return all the media for your model | `$media = User::findOrFail(1)->getMedia()` |
| getMediaByName | $pattern (String) | Return all the media that has a name with the provided pattern | `$media = User::findOrFail(1)->getMediaByName('/(extra_images)/')` |
| getMedium | $id - (Integer) | Get a single medium of your model with an id, If id is empty it will return the first medium | `$medium = User::findOrFail(1)->getMedium(2)` |
| getMediumByName | $name - (Single-Array) | Get a single medium of your model with the name, If the name is empty it will return the first medium | `$medium = User::findOrFail(1)->getMediumByName('image')` |

There is a `getSubSize` method for a single medium which you can get a specific subSize (that you defined in the config file) of an image, using below code:

```php
$medium->getSubSize('thumbnail');
```

# Usage

First, take A look at the `atriatech_media.php` file in `config` folder.
 
Add `AtriatechMedia` to your model

```php
use Atriatech\Media\AtriatechMedia;

class Product extends Model
{
    ...
    use AtriatechMedia;
    ...
}
```

In your view you have to load the css and js files and load the media selector:

### Example:

```html
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Atriatech Media</title>
    <!-- load the css file -->
    @atriatech_media('css')
</head>
<body>
<form method="post">
    @csrf
    <!-- load the media selector -->
    @atriatech_media_start
        @atriatech_media_file('img', '{"name": "image", "placeholder": "Image", "file": ""}')
    @atriatech_media_end
    <!-- load the media selector -->
    <br>
    <button type="submit">Submit</button>
</form>

<!-- load the js files -->
@atriatech_media('js')
</body>
</html>
```

The `@atriatech_media_file` directive has two parameters:
1. ID - for the media selector
2. OPTIONS - A JSON object with these keys:
```json5
{
    "name": "(String)", // the key which you can get in $request object when the form submitted
    "placeholder": "(String)", // placeholder for the media selector
    "file": "(String)", // Current media path to show in media selector
    "id": "(Number)" // Current media id
}
```

## Upload from controller

To upload a file from controller simply use the `AtriatechMedia` facade.
```php
use Atriatech\Media\Facades\AtriatechMedia;

class HomeController
{
    function index()
    {
        $file = $request->file('file');
        AtriatechMedia::upload($file, 'path'); // path is optional
    }
}
```

## Load with JS

If you want to load the selector with javascript use the instruction below:

add this inside or outside the media selector blade directives.
```html
@atriatech_media_start
<div id="mp3"></div>
@atriatech_media_end

<!-- OR -->

<div id="mp3"></div>
```

then load the selector with this code
```js
AtriatechMedia.loadMediaSelectorWithJS('mp3', {name: 'mp3', placeholder: 'MP3', accept: '.mp3'});
```

The parameters of `loadMediaSelectorWithJS` method are exactly like `@atriatech_media_file` directive. It only has a third parameter that get `true` or `false`. you should pass `false` if you want to add that `div` element outside the media selector blade directives.

## Integrations

### CKEditor

In your view add a textarea:

```html
<textarea name="editor1"></textarea>
```

Use media as CKEditor file browser:

```javascript
CKEDITOR.replace( 'editor1', {
    filebrowserBrowseUrl: mediaRoute('atriatech.media.index'),
    filebrowserImageBrowseUrl: mediaRoute('atriatech.media.index') + '?accept={{ config('atriatech_media.mime_types.image/*') }}',
});
```
