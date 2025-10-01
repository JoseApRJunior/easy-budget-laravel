import axios from 'axios';
window.axios = axios;

// Configurar token CSRF para todas as requisições
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurar token CSRF do meta tag
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Configurar interceptors para tratamento de erros
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response) {
            switch (error.response.status) {
                case 401:
                    // Não autorizado - redirecionar para login
                    window.location.href = '/login';
                    break;
                case 403:
                    // Proibido
                    if (window.EasyBudget && window.EasyBudget.alert) {
                        window.EasyBudget.alert.error('Você não tem permissão para realizar esta ação.');
                    }
                    break;
                case 419:
                    // Token CSRF expirado
                    if (window.EasyBudget && window.EasyBudget.alert) {
                        window.EasyBudget.alert.error('Sessão expirada. Recarregue a página.');
                    }
                    break;
                case 422:
                    // Erro de validação
                    if (error.response.data.errors && window.EasyBudget && window.EasyBudget.alert) {
                        const errors = Object.values(error.response.data.errors).flat();
                        window.EasyBudget.alert.error(errors.join('<br>'));
                    }
                    break;
                case 500:
                    // Erro interno do servidor
                    if (window.EasyBudget && window.EasyBudget.alert) {
                        window.EasyBudget.alert.error('Erro interno do servidor. Tente novamente mais tarde.');
                    }
                    break;
            }
        }
        return Promise.reject(error);
    }
);

// Configurar jQuery se estiver disponível (compatibilidade com sistema legado)
if (typeof $ !== 'undefined') {
    // Configurar CSRF token para jQuery AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Interceptor global para erros jQuery AJAX
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status === 401) {
            window.location.href = '/login';
        } else if (xhr.status === 419) {
            if (window.EasyBudget && window.EasyBudget.alert) {
                window.EasyBudget.alert.error('Sessão expirada. Recarregue a página.');
            }
        }
    });
}