# Eloquent File Field

Easily upload files to a directory and save the filename to database attribute.

It does not handle any image processing. Take a look at [Glide][http://glide.thephpleague.com] for that.

## Install

Via Composer

```bash
$ composer require jaysson/eloquent_filefield
```

## Usage

In your Eloquent model:

```php
use Jaysson\EloquentFileField\FileFieldTrait;

public $fileFields = [
  'image' => [],
  'poster' => [
  	'disk' => app('config')->get('filesystems.default'),
    'path' => 'uploads/:class_slug/:attribute/:unique_id-:file_name',
    'default_path' => 'uploads/default.png'
  ]
];
```
Each field can have filesystem disk, path pattern and default path options. If you don't specify any of them, they will be loaded from default config.

## Publish default config

```bash
$ php artisan vendor:publish 
```

## Security

If you discover any security related issues, please email prabhakarbhat@live.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
