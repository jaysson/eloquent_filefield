<?php

namespace Jaysson\EloquentFileField;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileFieldTrait
{
    /**
     * Listen to eloquent events.
     * Cleanup properly on update and delete
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function (Model $model) {
            $oldObject = $model->getOriginal();
            $fileFields = (array)$model->fileFields;

            if (count($fileFields) > 0) {
                foreach ($fileFields as $key => $options) {
                    if (empty($oldObject->$key))
                        continue;

                    // Delete old file
                    $oldObject[$key]->delete();
                }
            }
        });

        static::deleting(function (Model $model) {
            // Don't delete the file if you are doing a soft delete!
            if (!method_exists($model, 'restore') || $model->forceDeleting) {
                $fileFields = (array)$model->fileFields;

                if (count($fileFields) > 0) {
                    foreach ($fileFields as $key => $options) {
                        $model->$key->delete();
                    }
                }
            }
        });
    }

    /**
     * Instead of database column, return the FileField object.
     *
     * @param $key
     * @return FileField
     */
    public function getAttributeValue($key)
    {
        if (in_array($key, array_keys($this->fileFields))) {
            return new FileField($this, $key);
        }
        return parent::getAttributeValue($key);
    }

    /**
     * Determine if it is a URL upload or file upload.
     * Upload the file and set file name
     *
     * @param $key
     * @param $value
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, array_keys($this->fileFields)) && $value) {
            // If valid URL and file exists, download it
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $headers = @get_headers($value);
                if (strpos($headers[0], '200') || strpos($headers[0], '301') || strpos($headers[0], '302')) {
                    $fileName = pathinfo($value, PATHINFO_FILENAME);
                    $extension = pathinfo($value, PATHINFO_EXTENSION);
                    $fullFileName = join('.', [$fileName, $extension]);
                    $fileField = new FileField($this, $key, $fullFileName);
                    $this->attributes[$key] = $fileField->uploadRemoteFile($value);
                }
            } elseif ($value instanceof UploadedFile) {
                $fileName = str_slug(pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME));
                $extension = $value->getClientOriginalExtension();
                $fullFileName = join('.', [$fileName, $extension]);
                $fileField = new FileField($this, $key, $fullFileName);
                $this->attributes[$key] = $fileField->uploadFile($value);
            } elseif (is_string($value)) {
                $fileName = pathinfo($value, PATHINFO_FILENAME);
                $extension = pathinfo($value, PATHINFO_EXTENSION);
                $fullFileName = join('.', [$fileName, $extension]);
                $fileField = new FileField($this, $key, $fullFileName);
                $this->attributes[$key] = $fileField->copyLocal($value);
            }
        } else {
            parent::setAttribute($key, $value);
        }
    }
}