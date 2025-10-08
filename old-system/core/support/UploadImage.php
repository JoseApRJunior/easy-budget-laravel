<?php

namespace core\support;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class UploadImage
{
    private ImageManager $manager;
    private Image        $image;
    private array        $upload;
    public function __construct()
    {
        $this->manager = new ImageManager(
            Driver::class,
        );
    }

    public function make(string $input_name = 'file')
    {
        [ 'name' => $name, 'tmp_name' => $tmp_name ] = $_FILES[ $input_name ];
        $this->upload = [
            'name' => $name,
        ];
        /** @phpstan-ignore-next-line */
        $this->image = $this->manager->read($tmp_name);

        return $this;
    }

    public function resize(int $width = null, int $height = null, bool $constraint = false)
    {
        if (!$constraint) {
            $this->image->resize($width, $height);
        } else {
            $this->image->scale($width, $height);
        }

        return $this;
    }

    public function watermark(string $watermark_image, string $position = 'top-left', int $x = 0, int $y = 0, int $width = 100, int $height = 100, $opacity = 70)
    {
        $watermark = $this->manager->read(PUBLIC_PATH . '/assets/img/' . $watermark_image)->resize($width, $height);

        $this->image->place($watermark, $position, $x, $y, $opacity);

        return $this;
    }

    public function crop(int $width, int $height, ?int $x = null, ?int $y = null)
    {
        $this->image->crop($width, $height, $x, $y);

        return $this;
    }

    public function execute()
    {
        $extension = pathinfo($this->upload[ 'name' ], PATHINFO_EXTENSION);
        $image_new_name = uniqid() . '.' . $extension;
        $this->upload[ 'path' ] = '/assets/img/' . $image_new_name;

        return $this->image->save(PUBLIC_PATH . $this->upload[ 'path' ], $this->upload[ 'quality)' ] ?? 70);
    }

    public function get_image_info()
    {
        return $this->upload;
    }

}
