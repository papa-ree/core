@props(['label' => 'Button'])
<button
    {{ $attributes->merge(['type' => 'submit', 'class' => 'px-4 py-3 text-white tracking-wide text-center text-sm items-center flex transition-all duration-500 rounded-lg bg-gradient-to-tl from-red-500 via-pink-400 to-red-400 bg-size-200 bg-pos-0 hover:bg-pos-100 capitalize focus:outline-none focus:ring focus:ring-red-300 focus:shadow-lg focus:shadow-red-400/50']) }}>
    {{ $label }}
</button>
