<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\User;
use App\Services\Domain\ServiceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Abstracts\Controller;

class DebugTenantController extends Controller
{
    public function index()
    {
        $debug = [];
        
        // Verificar usuário autenticado
        if (Auth::check()) {
            $user = Auth::user();
            $debug['user_authenticated'] = true;
            $debug['user_id'] = $user->id;
            $debug['user_tenant_id'] = $user->tenant_id ?? null;
            $debug['user_name'] = $user->name;
            $debug['user_company'] = $user->company_name ?? null;
            $debug['user_email'] = $user->email;
            
            // Verificar tenant do usuário
            if ($user->tenant) {
                $debug['user_tenant_name'] = $user->tenant->name;
                $debug['user_tenant_cnpj'] = $user->tenant->cnpj;
            }
        } else {
            $debug['user_authenticated'] = false;
        }
        
        // Testar ServiceService diretamente
        try {
            $serviceService = app(ServiceService::class);
            $result = $serviceService->findByCode('ORC-20251112-0003-S003');
            
            $debug['service_service_test'] = [
                'success' => $result->isSuccess(),
                'message' => $result->getMessage(),
                'has_data' => $result->hasData(),
                'status' => $result->getStatus()->value ?? null,
            ];
            
            if ($result->isSuccess() && $result->hasData()) {
                $service = $result->getData();
                $debug['service_service_test']['service'] = [
                    'id' => $service->id,
                    'code' => $service->code,
                    'tenant_id' => $service->tenant_id,
                    'status' => $service->status->value ?? null,
                ];
            }
            
        } catch (\Exception $e) {
            $debug['service_service_test'] = [
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }
        
        // Buscar o serviço diretamente com query
        try {
            $service = Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();
            
            if ($service) {
                $debug['direct_query'] = [
                    'found' => true,
                    'id' => $service->id,
                    'tenant_id' => $service->tenant_id,
                    'code' => $service->code,
                    'status' => $service->status->value ?? null,
                    'description' => $service->description ?? null,
                ];
                
                // Verificar tenant do serviço
                if ($service->tenant) {
                    $debug['direct_query']['service_tenant'] = [
                        'id' => $service->tenant->id,
                        'name' => $service->tenant->name,
                        'cnpj' => $service->tenant->cnpj,
                    ];
                }
                
                // Verificar correspondência de tenants
                if (Auth::check()) {
                    $user = Auth::user();
                    $debug['direct_query']['tenant_match'] = $user->tenant_id === $service->tenant_id;
                    $debug['direct_query']['user_tenant_id'] = $user->tenant_id ?? null;
                    $debug['direct_query']['service_tenant_id'] = $service->tenant_id;
                }
            } else {
                $debug['direct_query'] = ['found' => false];
            }
        } catch (\Exception $e) {
            $debug['direct_query'] = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
        
        // Verificar todos os tenants disponíveis
        try {
            $debug['all_tenants'] = \App\Models\Tenant::all()->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'cnpj' => $tenant->cnpj
                ];
            });
        } catch (\Exception $e) {
            $debug['all_tenants'] = ['error' => $e->getMessage()];
        }
        
        return response()->json([
            'debug' => $debug,
            'explanation' => 'This debug shows detailed information about the service access issue',
            'timestamp' => now()->toDateTimeString()
        ], 200, [], JSON_PRETTY_PRINT);
    }
}