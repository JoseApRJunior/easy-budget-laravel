{{-- resources/views/partials/components/theme-toggle.blade.php --}}
{{-- Botão de toggle para tema dark/light --}}

@props([
    'class' => '',
    'size' => 'md',
    'variant' => 'default'
])

@php
    $sizeClasses = [
        'sm' => 'p-2',
        'md' => 'p-3',
        'lg' => 'p-4'
    ];

    $iconSizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6'
    ];

    $buttonClasses = [
        'default' => 'text-gray-500 hover:text-primary-600 hover:bg-gray-100 rounded-full transition-all duration-200',
        'primary' => 'bg-primary-500 text-white hover:bg-primary-600 rounded-full shadow-lg hover:shadow-xl transition-all duration-200',
        'outline' => 'border-2 border-gray-300 text-gray-700 hover:border-primary-500 hover:text-primary-600 rounded-full transition-all duration-200'
    ];
@endphp

<button
    {{ $attributes->merge([
        'class' => "{$buttonClasses[$variant]} {$sizeClasses[$size]} {$class}",
        'onclick' => 'toggleTheme()',
        'title' => 'Alternar tema',
        'aria-label' => 'Alternar entre tema claro e escuro'
    ]) }}
    type="button"
>
    {{-- Ícone Sol (Light Mode) --}}
    <svg
        id="theme-light-icon"
        class="{{ $iconSizes[$size] }} theme-light-icon"
        fill="currentColor"
        viewBox="0 0 20 20"
        xmlns="http://www.w3.org/2000/svg"
    >
        <path
            fill-rule="evenodd"
            d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"
            clip-rule="evenodd"
        />
    </svg>

    {{-- Ícone Lua (Dark Mode) --}}
    <svg
        id="theme-dark-icon"
        class="{{ $iconSizes[$size] }} theme-dark-icon hidden"
        fill="currentColor"
        viewBox="0 0 20 20"
        xmlns="http://www.w3.org/2000/svg"
    >
        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
    </svg>
</button>

{{-- JavaScript para controle do tema --}}
@section('scripts')
<script>
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.classList.toggle('dark', savedTheme === 'dark');
    updateThemeIcons(savedTheme);

    // Dispatch event for other components
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: savedTheme }));
}

function toggleTheme() {
    const currentTheme = localStorage.getItem('theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';

    // Add transition class for smooth animation
    document.documentElement.classList.add('theme-transition');

    // Toggle theme
    document.documentElement.classList.toggle('dark');

    // Save preference
    localStorage.setItem('theme', newTheme);

    // Update all icons
    updateThemeIcons(newTheme);

    // Dispatch event
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: newTheme }));

    // Remove transition class after animation
    setTimeout(() => {
        document.documentElement.classList.remove('theme-transition');
    }, 300);
}

function updateThemeIcons(theme) {
    const lightIcons = document.querySelectorAll('.theme-light-icon');
    const darkIcons = document.querySelectorAll('.theme-dark-icon');

    if (theme === 'dark') {
        lightIcons.forEach(icon => icon.classList.add('hidden'));
        darkIcons.forEach(icon => icon.classList.remove('hidden'));
    } else {
        lightIcons.forEach(icon => icon.classList.remove('hidden'));
        darkIcons.forEach(icon => icon.classList.add('hidden'));
    }
}

// Initialize theme when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
});
</script>
@endsection

{{-- CSS para transições suaves --}}
@push('styles')
<style>
.theme-transition,
.theme-transition *,
.theme-transition *:before,
.theme-transition *:after {
    transition: background-color 300ms ease, border-color 300ms ease, color 300ms ease !important;
}
</style>
@endpush
