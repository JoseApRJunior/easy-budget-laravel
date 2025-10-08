<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupHelper
{
    public function create()
    {
        Artisan::call( 'db:backup' );
        return $this->getLatestBackup();
    }

    public function restore( $filename )
    {
        $path = Storage::disk( 'backups' )->path( $filename );
        Artisan::call( 'db:restore', [ '--source' => $path ] );
    }

    public function getBackups()
    {
        return Storage::disk( 'backups' )->files();
    }

    public function getLatestBackup()
    {
        $files = $this->getBackups();
        return end( $files );
    }

}
