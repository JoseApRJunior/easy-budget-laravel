<?php

namespace App\Http\Controllers;

use App\Helpers\BackupHelper;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    protected $backupHelper;

    public function __construct(BackupHelper $backupHelper)
    {
        $this->backupHelper = $backupHelper;
    }

    public function index()
    {
        $backups = $this->backupHelper->getBackups();

        return view('admin.backups.index', compact('backups'));
    }

    public function create()
    {
        $this->backupHelper->create();

        return redirect()->route('admin.backups.index')->with('success', 'Backup criado com sucesso.');
    }

    public function restore(Request $request)
    {
        $request->validate(['filename' => 'required']);
        $this->backupHelper->restore($request->filename);

        return redirect()->route('admin.backups.index')->with('success', 'Backup restaurado com sucesso.');
    }

    public function destroy($filename)
    {
        $this->backupHelper->delete($filename);

        return redirect()->route('admin.backups.index')->with('success', 'Backup excluído com sucesso.');
    }
}
