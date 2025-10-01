<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="container-fluid">
        <div class="py-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand Section -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <img src="{{ asset('assets/img/logo.png') }}" alt="Easy Budget" class="h-8 w-auto">
                        <span class="text-xl font-bold text-blue-600">Easy Budget</span>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">
                        Simplificando a gestão financeira para prestadores de serviços. 
                        Gerencie clientes, orçamentos, serviços e faturas de forma eficiente.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors">
                            <i class="bi bi-facebook text-lg"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors">
                            <i class="bi bi-twitter text-lg"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors">
                            <i class="bi bi-linkedin text-lg"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors">
                            <i class="bi bi-instagram text-lg"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">
                        Links Rápidos
                    </h3>
                    <ul class="space-y-2">
                        @auth
                            <li>
                                <a href="{{ route('home') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('customers.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                    Clientes
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('budgets.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                    Orçamentos
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('reports.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                    Relatórios
                                </a>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('login') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                    Entrar
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('register') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                    Cadastrar
                                </a>
                            </li>
                        @endauth
                        <li>
                            <a href="{{ route('about') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                Sobre
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('support') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                Suporte
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">
                        Legal
                    </h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="{{ route('privacy-policy') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                Política de Privacidade
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('terms-of-service') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                Termos de Serviço
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('cookies-policy') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                Política de Cookies
                            </a>
                        </li>
                        @auth
                            <li>
                                <a href="{{ route('settings.index') }}" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
                                    Configurações
                                </a>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div class="border-t border-gray-200 mt-8 pt-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-sm text-gray-500">
                        © {{ date('Y') }} Easy Budget. Todos os direitos reservados.
                    </div>
                    <div class="flex items-center space-x-4 mt-4 md:mt-0">
                        <span class="text-sm text-gray-500">
                            Versão {{ config('app.version', '1.0.0') }}
                        </span>
                        @if(app()->environment('local'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Desenvolvimento
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>