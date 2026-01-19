---
name: codemap
description: Analise a estrutura da base de código, dependências e alterações. Use quando o usuário perguntar sobre a estrutura do projeto, onde o código está localizado, como os arquivos se conectam, o que mudou, ou antes de iniciar qualquer tarefa de codificação. Fornece contexto arquitetural instantâneo
---

# Codemap
Codemap gives you instant architectural context about any codebase. Use it proactively before exploring or modifying code.

## Commands
1. **Dependency Check**: Read `composer.json` (or package.json) to identify frameworks (like Laravel), versions, and key libraries.
2. **Structure Scan**: List root directories and key subdirectories (e.g., `app/`, `routes/`, `src/`) to determine the architectural pattern (MVC, DDD, Modular).
3. **Entry Points**: Identify main route files or index entry points.
4. **Key Components**: Locate major Service Providers, Models, or Config files that define the system behavior.

## When to Use
- **Onboarding**: Immediately when opening a new or unfamiliar project.
- **Refactoring**: Before moving files or changing legacy logic (e.g., migrating from legacy PHP to Laravel).
- **Architecture Check**: When the user asks "Where is the logic for X?" or "How is the project organized?".

## Output Interpretation
- **Tree View**: Generate a concise file tree of the relevant directories (exclude vendor/node_modules).
- **Summary**: A bulleted list of the tech stack and architectural patterns detected (e.g., "Laravel 11 with Service Pattern").
- **Insights**: Note any non-standard structures (e.g., "Controllers are slim, logic is in Services").

## Examples
- "Run codemap to analyze the current folder structure."
- "Give me a codemap of the 'app/Services' directory."
- "Based on the codemap, where should I add a new DTO?"