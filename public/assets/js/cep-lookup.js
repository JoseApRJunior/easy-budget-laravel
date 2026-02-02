/**
 * CEP Lookup - Busca automática de endereço via ViaCEP
 * Uso: Adicione data-cep-lookup aos campos de CEP
 */
(function() {
    'use strict';

    /**
     * Realiza a busca do CEP via XMLHttpRequest
     */
    function searchCEP(cep, fields, loader) {
        const cleanCEP = cep.replace(/\D/g, '');
        
        if (cleanCEP.length !== 8) return;

        // Mostrar loader se fornecido
        if (loader) loader.classList.remove('d-none');

        const xhr = new XMLHttpRequest();
        xhr.open('GET', `https://viacep.com.br/ws/${cleanCEP}/json/`, true);
        xhr.timeout = 5000; // 5 segundos de timeout

        xhr.onload = function() {
            if (loader) loader.classList.add('d-none');

            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);

                    if (!data.erro) {
                        if (fields.address) fields.address.value = data.logradouro || '';
                        if (fields.neighborhood) fields.neighborhood.value = data.bairro || '';
                        if (fields.city) fields.city.value = data.localidade || '';
                        if (fields.state) fields.state.value = data.uf || '';
                        
                        // Disparar evento de mudança para outros scripts saberem que os campos foram preenchidos
                        Object.values(fields).forEach(field => {
                            if (field) field.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    }
                } catch (e) {
                    // Silencioso em produção
                }
            }
        };

        xhr.onerror = function() {
            if (loader) loader.classList.add('d-none');
        };

        xhr.ontimeout = function() {
            if (loader) loader.classList.add('d-none');
        };

        xhr.send();
    }

    /**
     * Manipulador do evento de input
     */
    function handleCEPInput(e) {
        const input = e.target;
        if (!input.hasAttribute('data-cep-lookup')) return;

        const cep = input.value.replace(/\D/g, '');
        
        if (cep.length === 8) {
            // Busca o form ou container mais próximo para limitar o escopo dos campos
            const container = input.closest('form') || input.closest('.modal') || input.parentElement;
            
            // Busca o loader específico deste container ou o global como fallback
            const loader = container.querySelector('#cep-loader') || document.getElementById('cep-loader');
            
            // Mapeamento de campos com seletores mais flexíveis
            const fields = {
                address: container.querySelector('#address') || container.querySelector('[name="address"]') || container.querySelector('[name*="address"]'),
                neighborhood: container.querySelector('#neighborhood') || container.querySelector('[name="neighborhood"]') || container.querySelector('[name*="neighborhood"]'),
                city: container.querySelector('#city') || container.querySelector('[name="city"]') || container.querySelector('[name*="city"]'),
                state: container.querySelector('#state') || container.querySelector('[name="state"]') || container.querySelector('[name*="state"]')
            };
            
            searchCEP(cep, fields, loader);
        }
    }

    // Usar delegação de eventos no document para capturar qualquer input de CEP, inclusive dinâmicos
    document.addEventListener('input', handleCEPInput);
})();
