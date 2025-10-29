# Teste Manual - Atualização de Dados do Provider

## Credenciais de Teste
- **Email:** juniorklan.ju@gmail.com
- **Senha:** Password1@

## Status Atual do Banco
✅ Usuário configurado com:
- Provider ID: 4
- Common Data ID: 7
- Contact ID: 7
- Address ID: 7

## Dados Atuais no Banco
```
Nome: José Atualizado Silva Teste
Empresa: Empresa Teste LTDA
Email Business: contato@empresateste.com
Telefone Business: 11987654321
Website: https://empresateste.com
Endereço: Rua Nova Atualizada, 999
Bairro: Bairro Novo
Cidade: Rio de Janeiro/RJ
CEP: 20000-000
```

## Passo a Passo para Testar

### 1. Fazer Login
1. Acesse: `http://localhost/login` (ou sua URL local)
2. Entre com as credenciais acima
3. Você deve ser redirecionado para o dashboard

### 2. Acessar Página de Edição
1. Acesse: `http://localhost/provider/business/edit`
2. Você deve ver o formulário preenchido com os dados atuais

### 3. Atualizar os Dados
Preencha o formulário com novos dados de teste:

**Dados Pessoais:**
- Nome: `João`
- Sobrenome: `Santos`

**Dados Empresariais:**
- Nome da Empresa: `Minha Empresa Atualizada`
- CNPJ: `12.345.678/0001-90`
- Descrição: `Empresa de teste atualizada pelo navegador`

**Contato:**
- Email Empresarial: `novo@empresa.com`
- Telefone Empresarial: `(11) 98888-7777`
- Website: `https://novaempresa.com.br`

**Endereço:**
- Endereço: `Avenida Paulista`
- Número: `1000`
- Bairro: `Bela Vista`
- Cidade: `São Paulo`
- Estado: `SP`
- CEP: `01310-100`

### 4. Submeter o Formulário
1. Clique no botão "Salvar" ou "Atualizar"
2. Você deve ser redirecionado para `/settings`
3. Deve aparecer mensagem: "Dados empresariais atualizados com sucesso!"

### 5. Verificar no Banco de Dados
Execute este comando para verificar se os dados foram salvos:

```bash
php artisan tinker --execute="$user = App\Models\User::where('email', 'juniorklan.ju@gmail.com')->first(); $provider = $user->provider; $provider->load(['commonData', 'contact', 'address']); echo 'Nome: ' . $provider->commonData->first_name . ' ' . $provider->commonData->last_name . PHP_EOL; echo 'Empresa: ' . $provider->commonData->company_name . PHP_EOL; echo 'Email: ' . $provider->contact->email_business . PHP_EOL; echo 'Endereço: ' . $provider->address->address . ', ' . $provider->address->city . PHP_EOL;"
```

## Possíveis Problemas e Soluções

### ❌ Erro: "Provider não encontrado"
**Solução:** O usuário não tem provider associado. Execute:
```bash
php artisan tinker --execute="$user = App\Models\User::where('email', 'juniorklan.ju@gmail.com')->first(); echo 'Has Provider: ' . ($user->provider ? 'Yes' : 'No');"
```

### ❌ Erro: "Dados comuns não configurados"
**Solução:** Falta common_data_id no provider. Execute:
```bash
php artisan tinker --execute="$user = App\Models\User::where('email', 'juniorklan.ju@gmail.com')->first(); echo 'Common Data ID: ' . ($user->provider->common_data_id ?? 'NULL');"
```

### ❌ Erro de Validação
**Solução:** Verifique se todos os campos obrigatórios estão preenchidos:
- first_name (obrigatório)
- last_name (obrigatório)
- address (obrigatório)
- neighborhood (obrigatório)
- city (obrigatório)
- state (obrigatório)
- cep (obrigatório)

### ❌ Página não carrega
**Solução:** Verifique se o servidor está rodando:
```bash
php artisan serve
```

## Teste de Upload de Logo (Opcional)

1. Na página de edição, procure o campo "Logo da Empresa"
2. Selecione uma imagem (JPG, PNG, GIF, WEBP - máx 2MB)
3. Submeta o formulário
4. Verifique se o logo foi salvo:
```bash
php artisan tinker --execute="$user = App\Models\User::where('email', 'juniorklan.ju@gmail.com')->first(); echo 'Logo: ' . ($user->logo ?? 'Nenhum logo');"
```

## Checklist de Validação

- [ ] Login realizado com sucesso
- [ ] Página de edição carrega com dados atuais
- [ ] Formulário permite edição de todos os campos
- [ ] Validação funciona (campos obrigatórios)
- [ ] Dados são salvos no banco corretamente
- [ ] Mensagem de sucesso aparece após salvar
- [ ] Redirecionamento para /settings funciona
- [ ] Upload de logo funciona (se testado)
- [ ] Dados persistem após logout/login

## Resultado Esperado

✅ **SUCESSO:** Todos os dados devem ser atualizados no banco e a mensagem de sucesso deve aparecer.

Se tudo funcionar, o sistema está pronto para produção! 🎉
