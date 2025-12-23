<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Admin\SystemSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
            $settings = $this->systemSettingsService->getAllSettings();
            $categories = $this->systemSettingsService->getSettingsCategories();
            $environment = $this->systemSettingsService->getEnvironmentInfo();

            return view('admin.settings.index', compact('settings', 'categories', 'environment'));
        } catch (\Exception $e) {
            Log::error('Error loading system settings: '.$e->getMessage());

            return view('admin.settings.index', [
                'settings' => [],
                'categories' => [],
                'environment' => [],
            ]);
        }
    }

    public function update(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error updating system settings: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações: '.$e->getMessage(),
            ], 500);
        }
    }

    public function backup()
    {
        try {
            $backupPath = $this->systemSettingsService->createSettingsBackup();

            return response()->json([
                'success' => true,
                'message' => 'Backup criado com sucesso',
                'backup_path' => $backupPath,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating settings backup: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar backup',
            ], 500);
        }
    }

    public function restore(Request $request)
    {
        try {
            $validated = $request->validate([
                'backup_file' => 'required|string',
            ]);

            $restored = $this->systemSettingsService->restoreSettingsBackup($validated['backup_file']);

            return response()->json([
                'success' => true,
                'message' => 'Configurações restauradas com sucesso',
                'restored' => $restored,
            ]);
        } catch (\Exception $e) {
            Log::error('Error restoring settings backup: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao restaurar backup',
            ], 500);
        }
    }

    public function resetToDefaults()
    {
        try {
            $resetSettings = $this->systemSettingsService->resetToDefaultSettings();

            return response()->json([
                'success' => true,
                'message' => 'Configurações redefinidas para valores padrão',
                'reset' => $resetSettings,
            ]);
        } catch (\Exception $e) {
            Log::error('Error resetting to default settings: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao redefinir configurações',
            ], 500);
        }
    }

    public function getSettingValue($key)
    {
        try {
            $value = $this->systemSettingsService->getSettingValue($key);

            return response()->json([
                'success' => true,
                'key' => $key,
                'value' => $value,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting setting value: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter valor da configuração',
            ], 500);
        }
    }

    public function testConfiguration(Request $request)
    {
        try {
            $validated = $request->validate([
                'test_type' => 'required|string|in:email,database,payment,storage,queue',
            ]);

            $result = $this->systemSettingsService->testConfiguration($validated['test_type']);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => $result['details'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error testing configuration: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar configuração',
            ], 500);
        }
    }
}
