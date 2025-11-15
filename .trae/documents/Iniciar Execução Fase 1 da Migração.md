## Objetivo Imediato
- Implementar entregas da Semana 1: Home (páginas públicas) e Upload (imagem com resize/watermark), preparando base para futuras etapas.

## Escopo Técnico
- Home:
  - Controller: `app/Http/Controllers/HomeController`.
  - Views: `resources/views/pages/home/*` seguindo padrões do memory-bank.
  - Rotas públicas em `routes/web.php`.
- Upload:
  - Controller: `app/Http/Controllers/UploadController`.
  - Service: `app/Services/Infrastructure/ImageProcessingService`.
  - Config: `config/upload.php` (watermark, caminhos, limites).
  - Rotas autenticadas com middleware `tenant`.

## Passos
1. Auditar estrutura existente (`app`, `routes`, `resources/views`) e dependências (`composer.json`).
2. Criar/ajustar controllers de Home e Upload conforme padrões (sem comentários desnecessários, nomes consistentes).
3. Implementar `ImageProcessingService` com resize e watermark, abstraindo filesystem.
4. Definir config `upload.php` com opções de processamento.
5. Adicionar rotas em `routes/web.php` com middlewares apropriados.
6. Validar com testes simples e verificação manual (onde aplicável), sem quebrar padrões do projeto.

## Critérios de Aceitação
- Home acessível publicamente com layout funcional.
- Upload funcional com resize 200px, watermark e armazenamento em `storage` público.
- Rotas corretas e protegidas quando necessário.
- Consistência com `architecture.md` e `brief.md`.

## Observações
- Evitar criação de arquivos desnecessários; aproveitar estrutura existente.
- Manter multi-tenant e segurança (validação de inputs, MIME real).