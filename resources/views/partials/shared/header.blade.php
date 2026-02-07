{{-- partials/shared/header.blade.php --}}
<header class="sticky-top d-print-none">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a href="/" class="logo-container text-decoration-none">
                <img src="{{ asset("assets/img/logo.png") }}" alt="Easy Budget Logo" class="logo-img" role="img"
                    aria-label="Easy Budget Logo">
                <span class="logo-text">Easy Budget</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            @include( 'partials.shared.navigation' )

        </div>
    </nav>
</header>
