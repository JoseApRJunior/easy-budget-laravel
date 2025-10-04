<button {{ $attributes->merge( [ 'type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-light-primary border border-transparent rounded-md font-semibold text-xs text-light-on-primary uppercase tracking-widest hover:bg-light-primary-dark focus:bg-light-primary-dark active:bg-light-primary-darker focus:outline-none focus:ring-2 focus:ring-light-secondary focus:ring-offset-2 transition ease-in-out duration-150' ] ) }}>
    {{ $slot }}
</button>
