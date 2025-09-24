<!DOCTYPE html>
<html lang="pt-BR" class="h-100">

	@include( 'partials.shared.head' )

	<body class="d-flex flex-column h-100">
		@include( 'partials.shared.header' )

		<main class="flex-shrink-0">
			@include( 'partials.components.alerts' )
			
			@yield( 'content' )
		</main>

		@include( 'partials.shared.footer' )
	</body>

</html>
