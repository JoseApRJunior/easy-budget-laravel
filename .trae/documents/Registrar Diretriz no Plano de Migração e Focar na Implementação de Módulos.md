## Onde salvar
- Arquivo: `C:\xampp\htdocs\easy-budget-laravel\.trae\documents\Plano de Migração do Sistema Legado para Laravel.md`
- Ação planejada: adicionar, ao final do arquivo, uma seção curta de diretriz operacional.

## Texto a adicionar no final
- Diretriz de execução: 
  "Decisão operacional: Todos os testes (unit/integration/e2e) serão concentrados ao final da migração. A partir deste ponto, o foco principal é implementar a migração dos módulos conforme priorização acordada, garantindo consistência de arquitetura e rotas."
- Lista de foco imediato (fase ativa):
  1. Uploads/Imagens com processamento (resize/watermark) e armazenamento
  2. Orçamentos: geração de PDF com hash e QR de verificação
  3. Faturas: criação parcial a partir de orçamento, PDF com QR e `public_hash`
  4. Serviços: impressão pública em PDF com hash e QR
  5. AJAX/CEP: filtros e busca; integração BrasilAPI
  6. Verificação de documentos: página pública e serviço de verificação por hash
  7. Mercado Pago: OAuth (troca/refresh), preferências de pagamento (faturas e planos)
  8. Webhooks: idempotência persistente, validação `X-Signature`, atualização de estados
  9. QR code nativo mPDF: priorizado em todos os PDFs
  10. Páginas públicas (fatura/serviço) com redirecionamento seguro

## Critérios de aceitação (por módulo)
- Uploads: endpoints e validações ativos; imagens processadas e persistidas com URLs acessíveis
- PDF + QR: hash `sha256` persistido e QR apontando para `documents.verify` (funcional)
- Faturas: criação parcial válida, cálculo correto e `public_hash` gerado; PDF com QR
- Serviços: PDF público com QR e verificação
- AJAX/CEP: rotas autenticadas respondem rapidamente e com estrutura JSON padronizada
- Verificação: página retorna status correto (found/not found) com metadados
- Mercado Pago: autoriza, renova tokens e cria preferências com `external_reference` completo
- Webhooks: idempotência por cache+DB, `X-Signature` validado, estados atualizados
- QR mPDF: sem chamadas externas; render local consistente
- Páginas públicas: acessíveis por hash/token, com respostas e fluxos corretos

## Entregáveis e rastreio
- Código migrado por módulos, seguindo MVC + Service + Repository
- Rotas nomeadas consistentes (`integrations.mercadopago.*`, `webhooks.mercadopago.*`, etc.)
- Logs estruturados nos pontos críticos (OAuth, webhooks, verificação)
- Observações no Plano: módulo migrado, pendências e integração com testes a serem feitos no final

## Próxima ação (após confirmação)
- Inserir a seção de diretriz no final do arquivo plano e iniciar a próxima fase de implementação de módulos conforme a lista de foco imediato.