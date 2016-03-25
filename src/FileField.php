<?php

namespace Jaysson\EloquentFileField;

use Exception;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileField implements JsonSerializable
{
    /**
     * @var Model The eloquent model this field is in
     */
    protected $model;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem The file system which handles files for this field
     */
    protected $fileSystem;

    /**
     * @var string Name of this field
     */
    protected $key;

    /**
     * @var array Disk and path config for this field
     */
    protected $options;

    /**
     * @var string path to the file on the disk
     */
    protected $path;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * FileField constructor.
     * @param Model $model
     * @param $key
     * @param null $fileName
     */
    public function __construct(Model $model, $key, $fileName = null)
    {
        // If filename wasn't given, take it from the model
        if ($model->exists) {
            $this->path = $model->getAttributes()[$key];
            if (!$fileName) {
                $fileName = pathinfo($this->path, PATHINFO_FILENAME);
            }
        }
        $this->model = $model;
        $this->key = $key;
        $this->options = array_merge(app('config')->get('eloquent_filefield'), $this->model->fileFields[$key]);
        $this->fileName = $fileName;
        $this->fileSystem = app('filesystem')->disk($this->options['disk']);
    }

    /**
     * Substitute placeholders and return the path for the file
     *
     * @return string
     */
    protected function getPathForUpload()
    {
        $search = [':extension', ':attribute', ':unique_id', ':class_slug', ':file_name'];
        $replace = [
            pathinfo($this->fileName, PATHINFO_EXTENSION),
            $this->key,
            uniqid(),
            str_slug(snake_case(str_plural(class_basename($this->model)))),
            $this->fileName
        ];
        return str_replace($search, $replace, $this->options['path']);
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function getFileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * Move the given file to appropriate directory
     *
     * @param UploadedFile $file
     * @return mixed
     */
    public function uploadFile(UploadedFile $file)
    {
        $path = $this->getPathForUpload();
        if ($this->fileSystem->put($path, fopen($file->getRealPath(), 'r+'))) {
            $this->path = $path;
            return $path;
        }
    }

    public function copyLocal($currentPath)
    {
        $path = $this->getPathForUpload();
        if ($this->fileSystem->copy($currentPath, $path)) {
            $this->path = $path;
            return $path;
        }
    }

    /**
     * Download and move the given file to appropriate directory
     *
     * @param string $url
     * @return mixed
     */
    public function uploadRemoteFile($url)
    {
        $path = $this->getPathForUpload();
        if ($this->fileSystem->put($path, fopen($url, 'r'))) {
            $this->path = $path;
            return $path;
        }
    }

    /**
     * Delete the file
     *
     * @return mixed
     */
    public function delete()
    {
        try {
            return $this->fileSystem->delete($this->path);
        } catch (Exception $e) {
            app('log')->error($e->getMessage());
        }
    }

    public function exists()
    {
        return !empty($this->path);
    }

    /**
     * Delegate properties to filesystem
     *
     * @param $name
     * @return null|string
     */
    public function __get($name)
    {
        switch ($name) {
            case 'path':
                return $this->path;
            case 'name':
                return $this->fileName;
            case 'disk':
                return $this->options['disk'];
            default:
                return $this->fileSystem->$name;
        }
    }

    /**
     * Delegate methods to filesystem
     *
     * @param $name
     * @param $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        // Prepend filename to the arguments.
        array_unshift($args, $this->path);
        return call_user_func_array([$this->fileSystem, $name], $args);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        if (!$this->exists()) {
            return ['error' => 'File does not exist!'];
        }
        try {
            return [
                'name' => $this->fileName,
                'path' => $this->path,
                'size' => $this->size(),
                'type' => $this->getMimetype(),
                'disk' => $this->options['disk']
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function __toString()
    {
        return $this->path ? $this->path : $this->options['default_path'];
    }
}
