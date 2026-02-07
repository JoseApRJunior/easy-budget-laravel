<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Domain\SystemSettingsService;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    protected $systemSettingsService;

    public function __construct(SystemSettingsService $systemSettingsService)
    {
        $this->systemSettingsService = $systemSettingsService;
        $this->middleware(['auth', 'verified', 'role:admin']);
    }

    public function index()
    {
        $settings = $this->systemSettingsService->getAllSettings();
        $categories = $this->systemSettingsService->getSettingsCategories();
        $environment = $this->systemSettingsService->getEnvironmentInfo();

        return view('admin.settings.index', compact('settings', 'categories', 'environment'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'required|string|in:string,integer,boolean,array,json',
        ]);

        $updatedSettings = $this->systemSettingsService->updateSettings($validated['settings']);

        return response()->json([
            'success' => true,
            'message' => 'Configurações atualizadas com sucesso',
            'updated' => $updatedSettings,
        ]);
    }

    public function backup()
    {
        $backupPath = $this->systemSettingsService->createSettingsBackup();

        return response()->json([
            'success' => true,
            'message' => 'Backup criado com sucesso',
            'backup_path' => $backupPath,
        ]);
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'backup_file' => 'required|string',
        ]);

        $restored = $this->systemSettingsService->restoreSettingsBackup($validated['backup_file']);

        return response()->json([
            'success' => true,
            'message' => 'Configurações restauradas com sucesso',
            'restored' => $restored,
        ]);
    }

    public function resetToDefaults()
    {
        $resetSettings = $this->systemSettingsService->resetToDefaultSettings();

        return response()->json([
            'success' => true,
            'message' => 'Configurações redefinidas para valores padrão',
            'reset' => $resetSettings,
        ]);
    }

    public function getSettingValue($key)
    {
        $value = $this->systemSettingsService->getSettingValue($key);

        return response()->json([
            'success' => true,
            'key' => $key,
            'value' => $value,
        ]);
    }

    public function testConfiguration(Request $request)
    {
        $validated = $request->validate([
            'test_type' => 'required|string|in:email,database,payment,storage,queue',
        ]);

        $result = $this->systemSettingsService->testConfiguration($validated['test_type']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'details' => $result['details'] ?? null,
        ]);
    }
}
