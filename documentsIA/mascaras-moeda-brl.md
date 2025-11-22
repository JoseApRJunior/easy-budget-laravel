# Máscaras de Moeda BRL (R$)

Implementação padronizada de máscara de moeda brasileira em campos de formulário, com conversão para decimal no envio e consistência visual durante a digitação.

Campos com máscara:
- `Produto`: `price` em `pages/product/create.blade.php` e `pages/product/edit.blade.php`
- `Plano`: `price` em `pages/plan/create.blade.php` e `pages/plan/edit.blade.php`
- `Serviço`: `discount` (máscara), `items[*].unit_value` (máscara e readonly) e `items[*].total` (visual)

Biblioteca:
- `public/assets/js/modules/vanilla-masks.js` com suporte ao tipo `currency`, funções globais `formatCurrencyBRL` e `parseCurrencyBRLToNumber`.

Inicialização:
- Exemplo: `new VanillaMask('price', 'currency')`.
- Conversão no submit: `input.value = parseCurrencyBRLToNumber(input.value).toFixed(2)`.

Regras de uso:
- Sempre exibir `R$` com separadores de milhares e vírgula como decimal.
- Converter para decimal antes de enviar ao backend.
- Em itens de serviço, manter `unit_value` readonly e calcular totais dinamicamente.

Validação:
- O backend recebe decimais puros; máscaras não interferem nas regras (`numeric`).

Observações:
- Para preenchimento programático, atribua o valor e dispare `input` para aplicar a máscara.
