<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 transition ease-in-out duration-150', 'style' => 'background: linear-gradient(135deg, #0bd696 0%, #0d5540 100%);']) }}>
    {{ $slot }}
</button>
