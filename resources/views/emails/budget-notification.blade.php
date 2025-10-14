<x-mail::message>
# {{ $notificationType === 'created' ? 'Novo Orçamento Criado' : ($notificationType === 'updated' ? 'Orçamento Atualizado' : ($notificationType === 'approved' ? 'Orçamento Aprovado' : ($notificationType === 'rejected' ? 'Orçamento Rejeitado' : 'Notificação de Orçamento')) }}

@if($notificationType === 'created')
🎉 Um novo orçamento foi criado para você!
@elseif($notificationType === 'updated')
📝 Seu orçamento foi atualizado com novas informações.
@elseif($notificationType === 'approved')
✅ Seu orçamento foi aprovado!
@elseif($notificationType === 'rejected')
❌ Seu orçamento foi rejeitado.
@else
📋 Você recebeu uma notificação sobre seu orçamento.
@endif

---

## Detalhes do Orçamento

<x-mail::panel>
**Código:** {{ $budgetData['code'] }}
**Cliente:** {{ $budgetData['customer_name'] }}
**Valor Total:** R$ {{ $budgetData['total'] }}
@if($budgetData['discount'] !== '0,00')
**Desconto:** R$ {{ $budgetData['discount'] }}
@endif
@if($budgetData['due_date'])
**Validade:** {{ $budgetData['due_date'] }}
@endif
**Status:** {{ $budgetData['status'] }}

@if($budgetData['description'] && $budgetData['description'] !== 'Orçamento sem descrição')
**Descrição:**
{{ $budgetData['description'] }}
@endif
</x-mail::panel>

@if($customMessage)
<x-mail::panel>
**Mensagem Personalizada:**
{{ $customMessage }}
</x-mail::panel>
@endif

<x-mail::button :url="$budgetUrl" color="primary">
Ver Orçamento Completo
</x-mail::button>

Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:

[{{ $budgetUrl }}]({{ $budgetUrl }})

---

## Informações da Empresa

@if($company['company_name'])
**Empresa:** {{ $company['company_name'] }}

@if($company['email_business'] || $company['phone_business'])
**Contato:**
@endif
@if($company['email_business'])
- E-mail: [{{ $company['email_business'] }}](mailto:{{ $company['email_business'] }})
@endif
@if($company['phone_business'])
- Telefone: {{ $company['phone_business'] }}
@endif
@endif

---

**Precisa de ajuda?**
@if($supportEmail)
Entre em contato conosco: [{{ $supportEmail }}](mailto:{{ $supportEmail }})
@endif

Atenciosamente,
**Equipe {{ $appName }}**

<x-mail::subcopy>
Este é um e-mail automático sobre seu orçamento {{ $budgetData['code'] }}.
</x-mail::subcopy>
</x-mail::message>
