<?php

namespace app\controllers;

use core\library\Session;
use core\library\User;
use core\support\UploadImage;

class UploadController
{
    public function index(): void
    {

        $image = new UploadImage();
        $image->make()
            ->resize( 200, null, true )
            ->watermark( 'watermark.png', 'top-right', 2, 2, 30, 30, 70 )
            ->execute();
        $info = $image->get_image_info();

        remove_file( $user->findId( 5 )->image );

    }

}
