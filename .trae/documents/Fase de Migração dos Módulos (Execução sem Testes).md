## Diretriz
- Priorizar implementação dos módulos e deixar todos os testes (unit/integration/e2e) para o encerramento da migração.
- Manter padrão MVC + Services + Repositories, rotas nomeadas e logs estruturados.

## Sequência de Migração
1) Relatórios (Reports)
- Consolidar geração PDF: usar mPDF para orçamentos/serviços e Dompdf para relatórios gerenciais, padronizar layouts.
- Migrar templates legados (Twig) para Blade, mantendo cabeçalhos/rodapés.
- Rotas: download/visualização e export por filtros.

2) Páginas Públicas (Planos)
- Página pública de status da assinatura do plano (por `plan_subscription_id`/hash).
- Rotas seguras e mensagens de pagamento aprovado/pendente/cancelado.

3) Catálogo de Produtos
- Migrar CRUD, imagens (uso de UploadController/FileUploadService), categorias/unidades.
- Rotas provider, busca AJAX, regras de validação.

4) Agenda/Agendamentos (Schedules)
- Migrar model, CRUD, calendário e regras de conflito.
- Notificações eventuais (placeholder) e filtros.

5) Inventário de Produtos
- Migrar movimentações (entrada/saída), saldos e auditoria.
- Serviços e repositórios para operações consistentes.

6) Provider Credenciais (UI)
- Painel de integração MP: estado, expiração e ações (refresh/disconnect) refinadas.
- Melhorias de UX e feedback.

7) Consolidação PDF + QR
- Garantir QR nativo mPDF em orçamentos/serviços/faturas.
- Padronizar hash e verificação pública.

8) Conversão de Legado
- Remover dependências Endroid/BaconQrCode do legado quando equivalentes existirem.
- Migrar utilitários úteis (ex.: geradores/formatadores) para Services.

## Critérios de Aceitação
- Relatórios: PDFs gerados com layouts padronizados e filtros funcionais.
- Planos: página pública apresenta status real e links de ação quando aplicáveis.
- Produtos: CRUD completo, upload/resize e busca AJAX.
- Agenda: criação/edição/cancelamento com prevenção de conflito.
- Inventário: movimentações refletem saldos e armazenam auditoria.
- Integração MP: UI exibe expiração amigável e permite ações com retorno claro.
- PDF+QR: todos os documentos possuem hash e QR funcional para `documents.verify`.

## Entregáveis
- Código migrado por módulo com rotas, controllers, services, repositories e views.
- Logs e configurações consistentes.
- Observações no plano mestre indicando módulo concluído e pendências.

## Observação
- Testes serão adicionados no final da migração de todos os módulos.

## Próxima Ação
- Iniciar migração de Relatórios (Reports): padronizar geração/rotas/views, converter templates legados e validar exportações por filtros.