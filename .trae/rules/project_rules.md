# Project Rules - Easy Budget Laravel

## Testes

- Comando: `composer test`
- Descrição: limpa config e executa `php artisan test`

## Lint (Código)

- Comando: `vendor/bin/pint --test`
- Descrição: verifica estilo sem alterar arquivos

## Análise Estática

- Comando: `vendor/bin/phpstan analyse app --no-progress --level=0`
- Descrição: análise estática mínima do diretório `app`

## Build Frontend (opcional)

- Comando: `npm run build`
- Descrição: gera assets com Vite

## Observações

- Executar testes e lint antes de marcar checklists como concluídos
- Em caso de falhas, priorizar correções e reexecutar comandos
