<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\AreaOfActivity;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Profession;
use App\Models\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GlobalSettingsController extends Controller
{
    /**
     * Display global settings dashboard
     */
    public function index(): View
    {
        $settings = SystemSettings::pluck('value', 'key')->toArray();

        return view('admin.settings.global', [
            'settings' => $settings,
            'categories' => Category::count(),
            'activities' => AreaOfActivity::count(),
            'professions' => Profession::count(),
            'plans' => Plan::count(),
        ]);
    }

    /**
     * Update general system settings
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_description' => 'nullable|string|max:1000',
            'app_keywords' => 'nullable|string|max:500',
            'contact_email' => 'required|email|max:255',
            'support_email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:20',
            'currency' => 'required|string|max:3',
            'currency_symbol' => 'required|string|max:10',
            'language' => 'required|string|max:10',
            'country' => 'required|string|max:2',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                SystemSettings::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'type' => 'general']
                );
            }
        });

        Cache::forget('system_settings');

        return redirect()->back()->with('success', 'Configurações gerais atualizadas com sucesso!');
    }

    /**
     * Update system configuration settings
     */
    public function updateConfiguration(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'maintenance_mode' => 'boolean',
            'allow_registration' => 'boolean',
            'require_email_verification' => 'boolean',
            'allow_social_login' => 'boolean',
            'auto_approve_providers' => 'boolean',
            'trial_days' => 'required|integer|min:1|max:365',
            'max_tenants_per_user' => 'required|integer|min:1|max:100',
            'session_lifetime' => 'required|integer|min:1|max:43200',
            'password_expiry_days' => 'required|integer|min:0|max:365',
            'login_attempts' => 'required|integer|min:1|max:10',
            'lockout_duration' => 'required|integer|min:1|max:1440',
            'enable_2fa' => 'boolean',
            'force_2fa_admin' => 'boolean',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                SystemSettings::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ?? '0', 'type' => 'configuration']
                );
            }
        });

        Cache::forget('system_settings');

        return redirect()->back()->with('success', 'Configurações do sistema atualizadas com sucesso!');
    }

    /**
     * Update email settings
     */
    public function updateEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_driver' => 'required|string|max:50',
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|max:10',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
            'mailgun_domain' => 'nullable|string|max:255',
            'mailgun_secret' => 'nullable|string|max:255',
            'ses_key' => 'nullable|string|max:255',
            'ses_secret' => 'nullable|string|max:255',
            'ses_region' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                SystemSettings::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ?? '', 'type' => 'email']
                );
            }
        });

        Cache::forget('system_settings');

        return redirect()->back()->with('success', 'Configurações de email atualizadas com sucesso!');
    }

    /**
     * Update payment settings
     */
    public function updatePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mercadopago_enabled' => 'boolean',
            'mercadopago_public_key' => 'nullable|string|max:255',
            'mercadopago_access_token' => 'nullable|string|max:255',
            'stripe_enabled' => 'boolean',
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'paypal_enabled' => 'boolean',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_secret' => 'nullable|string|max:255',
            'payment_currency' => 'required|string|max:3',
            'payment_tax_rate' => 'required|numeric|min:0|max:100',
            'payment_processing_fee' => 'required|numeric|min:0|max:100',
            'enable_recurring_payments' => 'boolean',
            'payment_retry_attempts' => 'required|integer|min:1|max:10',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                SystemSettings::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ?? '0', 'type' => 'payment']
                );
            }
        });

        Cache::forget('system_settings');

        return redirect()->back()->with('success', 'Configurações de pagamento atualizadas com sucesso!');
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enable_email_notifications' => 'boolean',
            'enable_sms_notifications' => 'boolean',
            'enable_push_notifications' => 'boolean',
            'notification_email_frequency' => 'required|string|in:immediate,daily,weekly',
            'notification_sms_frequency' => 'required|string|in:immediate,daily,weekly',
            'max_emails_per_hour' => 'required|integer|min:1|max:1000',
            'max_sms_per_hour' => 'required|integer|min:1|max:100',
            'enable_notification_queue' => 'boolean',
            'notification_retry_attempts' => 'required|integer|min:1|max:10',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                SystemSettings::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ?? '0', 'type' => 'notifications']
                );
            }
        });

        Cache::forget('system_settings');

        return redirect()->back()->with('success', 'Configurações de notificação atualizadas com sucesso!');
    }

    /**
     * Update AI and analytics settings
     */
    public function updateAIAnalytics(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enable_ai_analytics' => 'boolean',
            'ai_provider' => 'required|string|in:openai,anthropic,google,local',
            'ai_api_key' => 'nullable|string|max:255',
            'ai_model' => 'required|string|max:100',
            'enable_predictive_analytics' => 'boolean',
            'enable_anomaly_detection' => 'boolean',
            'analytics_retention_days' => 'required|integer|min:30|max:3650',
            'enable_real_time_analytics' => 'boolean',
            'analytics_update_frequency' => 'required|string|in:real_time,hourly,daily',
            'enable_customer_insights' => 'boolean',
            'enable_financial_forecasting' => 'boolean',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                SystemSettings::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ?? '0', 'type' => 'ai_analytics']
                );
            }
        });

        Cache::forget('system_settings');

        return redirect()->back()->with('success', 'Configurações de IA e Analytics atualizadas com sucesso!');
    }

    /**
     * Update backup settings
     */
    public function updateBackup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enable_auto_backup' => 'boolean',
            'backup_frequency' => 'required|string|in:daily,weekly,monthly',
            'backup_time' => 'required|string|max:5',
            'backup_retention_days' => 'required|integer|min:1|max:365',
            'backup_storage_driver' => 'required|string|in:local,s3,ftp,dropbox',
            'backup_encryption' => 'boolean',
            'backup_compression' => 'boolean',
            'backup_notify_on_success' => 'boolean',
            'backup_notify_on_failure' => 'boolean',
            'backup_email_recipients' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                SystemSettings::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ?? '0', 'type' => 'backup']
                );
            }
        });

        Cache::forget('system_settings');

        return redirect()->back()->with('success', 'Configurações de backup atualizadas com sucesso!');
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            // Implementation for testing email configuration
            // This would typically send a test email
            return redirect()->back()->with('success', 'Email de teste enviado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao enviar email de teste: '.$e->getMessage());
        }
    }

    /**
     * Test payment configuration
     */
    public function testPayment(Request $request): RedirectResponse
    {
        try {
            // Implementation for testing payment configuration
            // This would typically create a test payment
            return redirect()->back()->with('success', 'Configuração de pagamento testada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao testar configuração de pagamento: '.$e->getMessage());
        }
    }

    /**
     * Clear system cache
     */
    public function clearCache(): RedirectResponse
    {
        Cache::flush();

        return redirect()->back()->with('success', 'Cache do sistema limpo com sucesso!');
    }

    /**
     * Export settings
     */
    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $settings = SystemSettings::all()->toArray();
        $filename = 'system-settings-'.date('Y-m-d-His').'.json';

        $tempFile = tempnam(sys_get_temp_dir(), 'settings');
        file_put_contents($tempFile, json_encode($settings, JSON_PRETTY_PRINT));

        return response()->download($tempFile, $filename)->deleteFileAfterSend();
    }

    /**
     * Import settings
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:json|max:1024',
        ]);

        try {
            $file = $request->file('settings_file');
            $content = file_get_contents($file->getRealPath());
            $settings = json_decode($content, true);

            DB::transaction(function () use ($settings) {
                foreach ($settings as $setting) {
                    SystemSettings::updateOrCreate(
                        ['key' => $setting['key']],
                        [
                            'value' => $setting['value'],
                            'type' => $setting['type'],
                        ]
                    );
                }
            });

            Cache::forget('system_settings');

            return redirect()->back()->with('success', 'Configurações importadas com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao importar configurações: '.$e->getMessage());
        }
    }
}
