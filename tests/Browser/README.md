# Laravel Dusk - Testes Browser

Esta documentação explica como executar os testes browser do Easy Budget Laravel usando Laravel Dusk.

## 🚀 Instalação e Configuração

### Pré-requisitos

1. **Instalar Laravel Dusk:**

```bash
composer require --dev laravel/dusk
php artisan dusk:install
```

2. **Instalar Chrome/Chromium:**

   -  Windows: Baixar do site oficial do Google Chrome
   -  Linux: `sudo apt-get install google-chrome-stable`
   -  macOS: `brew install --cask google-chrome`

3. **Configurar variáveis de ambiente:**

```bash
# .env
APP_URL=http://localhost:8000
DUSK_DRIVER_URL=http://localhost:9515
DUSK_HEADLESS_DISABLED=false  # true para CI/CD
```

## 🏃‍♂️ Executando os Testes

### Comandos Básicos

```bash
# Executar todos os testes Dusk
php artisan dusk

# Executar teste específico
php artisan dusk tests/Browser/FormularioProviderTest.php

# Executar teste com modo visual (não headless)
DUSK_HEADLESS_DISABLED=true php artisan dusk tests/Browser/FormularioProviderTest.php

# Executar com screenshot automático em caso de erro
php artisan dusk --colors

# Executar com verbosidade
php artisan dusk --verbose
```

### Comandos Avançados

```bash
# Executar apenas testes de validação
php artisan dusk --filter="test_validacao_"

# Executar testes com timeout personalizado
php artisan dusk --timeout=60

# Executar testes e gerar relatório HTML
php artisan dusk --html

# Executar testes específicos por grupo
php artisan dusk --group=business
```

## 📋 Estrutura dos Testes

### Arquivos Criados

```
tests/Browser/
├── Pages/
│   ├── Page.php                     # Classe base para Page Objects
│   ├── HomePage.php                 # Página inicial
│   └── BusinessFormPage.php         # Formulário de business (NOVO)
├── Support/
│   └── TestDataHelper.php           # Helper com dados de teste (NOVO)
├── FormularioProviderTest.php       # Teste principal otimizado (NOVO)
├── ExampleTest.php                  # Teste de exemplo
└── README.md                        # Esta documentação
```

### Page Object Pattern

**BusinessFormPage** - Abstração do formulário de business:

```php
use Tests\Browser\Pages\BusinessFormPage;
use Tests\Browser\Support\TestDataHelper;

public function test_exemplo_uso()
{
    $browser->visit(new BusinessFormPage())
            ->on(new BusinessFormPage())
            ->fillCompleteForm($browser, TestDataHelper::validBusinessData())
            ->submitForm($browser)
            ->assertSee('Dados atualizados com sucesso');
}
```

### Helper de Dados de Teste

**TestDataHelper** - Dados predefinidos para testes:

```php
// Dados válidos completos
TestDataHelper::validBusinessData()

// Dados mínimos
TestDataHelper::minimalBusinessData()

// Dados inválidos (para teste de validação)
TestDataHelper::invalidBusinessData()

// Dados para atualização parcial
TestDataHelper::partialUpdateData()

// Gerar arquivo de teste para logo
TestDataHelper::generateTestLogo()

// Limpar arquivos de teste
TestDataHelper::cleanupTestFiles()
```

## 🧪 Cenários de Teste Implementados

### 1. Teste de Formulário Válido

**Arquivo:** `test_envio_formulario_com_dados_validos()`

-  Preenche formulário com dados válidos
-  Upload de logo
-  Verifica redirecionamento e mensagem de sucesso

### 2. Teste de Campos Mínimos

**Arquivo:** `test_formulario_com_campos_minimos()`

-  Testa formulário com dados básicos necessários
-  Verifica funcionamento com informações essenciais

### 3. Teste de Validação

**Arquivo:** `test_validacao_formulario_com_dados_invalidos()`

-  Envia dados inválidos propositalmente
-  Verifica mensagens de erro específicas
-  Testa validação de email, telefone, CPF, CNPJ, etc.

### 4. Teste de Atualização Parcial

**Arquivo:** `test_atualizacao_parcial_dados()`

-  Testa atualização de apenas alguns campos
-  Verifica que dados não alterados permanecem intactos

### 5. Teste de Upload de Arquivo

**Arquivo:** `test_upload_logo_invalido()`

-  Tenta enviar arquivo inválido (texto)
-  Verifica validação de tipo de arquivo
-  Testa mensagens de erro de arquivo

### 6. Teste de Campos Obrigatórios

**Arquivo:** `test_campos_obrigatorios()`

-  Tenta submeter formulário vazio
-  Verifica que todos os campos obrigatórios são validados

### 7. Teste de Interações

**Arquivo:** `test_interacoes_campos_formulario()`

-  Testa interações específicas: focus, hover, clear
-  Verifica comportamento de dropdowns

### 8. Teste de Responsividade

**Arquivo:** `test_responsividade_mobile()`

-  Simula viewport mobile (375x667)
-  Verifica que elementos permanecem acessíveis

### 9. Teste de Persistência

**Arquivo:** `test_persistencia_dados_formulario()`

-  Testa que dados são preservados após reload
-  Verifica funcionalidade `old()` do Laravel

## 🛠️ Troubleshooting

### Problemas Comuns

#### 1. ChromeDriver não inicia

```bash
# Verificar se Chrome está instalado
google-chrome --version

# Iniciar ChromeDriver manualmente
google-chrome --headless --disable-gpu --remote-debugging-port=9222

# Ou usar Artisan
php artisan dusk:chrome-driver
```

#### 2. Timeout em testes

```php
// Aumentar timeout no teste
$browser->waitFor('#element', 10); // 10 segundos

// Ou configurar globalmente no .env
DUSK_TIMEOUT=30
```

#### 3. Elementos não encontrados

```php
// Usar waitFor antes de interagir
$browser->waitFor('@element')
       ->click('@element');

// Ou usar waitUntil
$browser->waitUntil('document.querySelector("@element")')
       ->click('@element');
```

#### 4. Problemas de resolução

```php
// Definir resolução específica
$browser->resize(1920, 1080);

// Ou para mobile
$browser->resize(375, 667);
```

### Debug e Screenshots

#### Gerar Screenshot Manualmente

```php
// No meio do teste
$browser->screenshot('nome-do-screenshot');

// Ou usar helper da classe base
$this->takeScreenshot('debug-step-1');
```

#### Screenshots Automáticos em Erro

Os screenshots são salvos automaticamente em:

```
storage/app/public/test-screenshots/
```

#### Console Logs

```php
// Verificar console do navegador
$browser->assertSeeInConsole('JavaScript error');

// Ou capturar logs
$browser->driver->manage()->getLog('browser');
```

## 🔧 Configuração Avançada

### DuskTestCase Customizado

A classe base `DuskTestCase` inclui funcionalidades extras:

```php
// Usar no teste
class MeuTeste extends DuskTestCase
{
    public function test_exemplo()
    {
        $browser = $this->browse(function ($browser) {
            // Usar screenshot helper
            $this->takeScreenshot('antes');

            // Usar helper para criar provider
            $provider = $this->createTestProvider();

            // Limpeza de dados
            $this->cleanUpTestData();
        });
    }
}
```

### Variáveis de Ambiente Úteis

```bash
# .env.testing ou .env.dusk
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Para CI/CD
DUSK_HEADLESS_DISABLED=true
DUSK_DRIVER_URL=http://chrome:4444/wd/hub

# Para debugging
DUSK_SCREENSHOT_PATH=storage/app/public/test-screenshots
DUSK_TIMEOUT=30
```

## 📊 Métricas e Relatórios

### Execução com Métricas

```bash
# Relatório de cobertura
php artisan dusk --coverage-html coverage

# Relatório detalhado
php artisan dusk --teamcity

# JUnit XML (para CI/CD)
php artisan dusk --junit results.xml
```

### Performance

```bash
# Testar performance específica
time php artisan dusk tests/Browser/FormularioProviderTest.php

# Monitor de recursos
php artisan dusk --profile
```

## 🚀 Integração com CI/CD

### GitHub Actions

```yaml
name: Browser Tests

on: [push, pull_request]

jobs:
   dusk:
      runs-on: ubuntu-latest
      services:
         mysql:
            image: mysql:8.0
            env:
               MYSQL_ROOT_PASSWORD: password
               MYSQL_DATABASE: easy_budget
            ports:
               - 3306

      steps:
         - uses: actions/checkout@v2

         - name: Setup PHP
           uses: shivammathur/setup-php@v2
           with:
              php-version: "8.3"
              extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, dom, filter, gd, iconv, json, mbstring, mysqlnd

         - name: Install dependencies
           run: composer install --prefer-dist --no-progress --no-scripts

         - name: Setup application
           run: |
              cp .env.example .env
              php artisan key:generate
              php artisan migrate --force

         - name: Install Chrome
           run: |
              sudo apt-get update
              sudo apt-get install -y google-chrome-stable

         - name: Run Dusk tests
           env:
              APP_ENV: testing
              DUSK_HEADLESS_DISABLED: true
           run: php artisan dusk --headless
```

## 📚 Recursos Adicionais

### Links Úteis

-  [Documentação Oficial Laravel Dusk](https://laravel.com/docs/dusk)
-  [Facebook WebDriver](https://facebook.github.io/php-webdriver/)
-  [Chrome DevTools Protocol](https://chromedevtools.github.io/devtools-protocol/)

### Comandos Artisan Úteis

```bash
# Limpar cache Dusk
php artisan dusk:clear

# Instalar ChromeDriver
php artisan dusk:chrome-driver

# Executar com modo GUI (Linux)
php artisan dusk --xvfb

# Executar teste específico
php artisan dusk --filter="nome_do_metodo"

# Executar grupo específico
php artisan dusk --group=business,validation
```

Esta documentação fornece tudo que você precisa para executar e manter os testes browser do Easy Budget Laravel de forma eficiente e confiável.
