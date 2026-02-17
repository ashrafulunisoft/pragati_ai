@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm', 'style' => 'color: #0d5540;']) }}>
    {{ $value ?? $slot }}
</label>
