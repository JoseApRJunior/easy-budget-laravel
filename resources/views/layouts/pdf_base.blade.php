<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>@yield( 'title', 'Documento' )</title>
  {{-- CSS específico para cada tipo de documento --}}
  @yield( 'styles' )
</head>

<body>
  <div class="container">

    {{-- Conteúdo principal --}}
    <main>
      @yield( 'content' )
    </main>

    {{-- Rodapé do documento --}}
    <footer class="footer">
      @yield( 'footer' )
    </footer>
  </div>
</body>

</html>
