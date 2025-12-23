/**
 * CEP Lookup - Busca automática de endereço via ViaCEP
 * Uso: Adicione data-cep-lookup aos campos de CEP
 */
(function() {
    'use strict';

    function searchCEP(cep, fields) {
        const cleanCEP = cep.replace(/\D/g, '');
        
        if (cleanCEP.length !== 8) return;

        const xhr = new XMLHttpRequest();
        xhr.open('GET', `https://viacep.com.br/ws/${cleanCEP}/json/`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                if (!data.erro) {
                    if (fields.address) fields.address.value = data.logradouro || '';
                    if (fields.neighborhood) fields.neighborhood.value = data.bairro || '';
                    if (fields.city) fields.city.value = data.localidade || '';
                    if (fields.state) fields.state.value = data.uf || '';
                }
            }
        };
        xhr.send();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const cepInputs = document.querySelectorAll('[data-cep-lookup]');
        
        cepInputs.forEach(input => {
            input.addEventListener('blur', function() {
                const fields = {
                    address: document.getElementById('address'),
                    neighborhood: document.getElementById('neighborhood'),
                    city: document.getElementById('city'),
                    state: document.getElementById('state')
                };
                
                searchCEP(this.value, fields);
            });
        });
    });
})();
