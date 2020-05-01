![Media - Explore, Upload, Delete and Create new folder](https://raw.githubusercontent.com/atriatech/media/master/screenshot.png)

# Media - Explore, Upload, Delete and Create new folder

You can use this package to upload your media and attach the media to your models.

# Installation

1. `composer require atriatech/media`.
2. add `Atriatech\Media\MediaServiceProvider::class` to `providers` array inside `config/app.php`.
3. add `"Atriatech\\Media\\": "vendor/atriatech/media/src/"` to `autoload => psr-4` object inside `composer.json` file, then run this command: `composer dump-autoload`.
4. If you haven't link your storage, please run this command `php artisan storage:link`.
5. run `php artisan vendor:publish --tag=atriatech-media-config` to copy the config file into `config` folder.
6. run `php artisan vendor:publish --tag=atriatech-media-public` to copy asset files into `public` folder, running this command with `--force` flag is recommended.
7. run `php artisan migrate` to create the tables.

# API

You can use these methods on your model:

| Method | Parameters | Description | Example |
| ------ | ---------- | ----------- | ------- |
| addMedia | $paths - (Array) | Add media to your model | `User::findOrFail(1)->addMedia([$request->input('image')])` |
| updateMedia | $paths - (Array) | Update media for your mobile | `User::findOrFail(1)->updateMedia([$request->input('image')])` |
| getMedia | - | Return all the media for your model | `$media = User::findOrFail(1)->getMedia()` |
| getMedium | $id - (Integer) | Get a single medium of your model with an id, If id is empty it will return the first medium | `$medium = User::findOrFail(1)->getMedium(2)` |

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
        @atriatech_media_file('img', '{"name": "image", "placeholder": "Image", "file": "{{ $user->getMedium()->path }}"}')
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
    "file": "(String)" // Current media path to show in media selector
}
```
