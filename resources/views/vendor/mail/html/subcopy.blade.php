<table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
{!! Illuminate\Mail\Markdown::parse(preg_replace('/If you\'re having trouble clicking the "([^"]+)" button, copy and paste the URL below into your web browser:/', 'Se você estiver tendo problemas para clicar no botão "$1", copie e cole a URL abaixo em seu navegador:', $slot)) !!}
</td>
</tr>
</table>
