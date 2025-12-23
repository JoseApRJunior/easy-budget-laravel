Essa é uma excelente ideia! Desativar o _auto-submit_ melhora a performance (evita requisições desnecessárias enquanto o usuário ainda está preenchendo os campos) e dá mais controle ao usuário, que decide exatamente quando quer processar os filtros.

Vou remover a função de submissão automática e garantir que, ao clicar em "Filtrar", os valores de moeda sejam normalizados corretamente antes do envio.

C:\laragon\www\easy-budget-laravel\resources\views\pages\product\index.blade.php
