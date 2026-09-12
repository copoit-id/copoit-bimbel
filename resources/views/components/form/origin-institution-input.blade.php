@props(['value' => '', 'id' => 'origin_institution', 'class' => ''])

@php
    $inputClasses = implode(' ', [
        'block w-full rounded-lg border bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-colors duration-200',
        'focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-0',
        $class,
    ]);
@endphp

<div class="relative z-40" data-origin-institution-input>
    <input id="{{ $id }}" name="origin_institution" type="text" value="{{ $value }}"
        placeholder="Contoh: SMA Negeri 1 Jakarta" autocomplete="off"
        class="{{ $inputClasses }}" data-origin-institution-field
        {{ $attributes->except('class') }}>
    <div class="absolute z-[70] mt-1 hidden w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg" data-origin-institution-options></div>
    <p class="mt-1 hidden text-xs text-gray-500" data-origin-institution-status></p>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    const lookupUrl = @json(route('origin-institutions.lookup'));
    document.querySelectorAll('[data-origin-institution-input]').forEach((wrapper) => {
        const field = wrapper.querySelector('[data-origin-institution-field]');
        const options = wrapper.querySelector('[data-origin-institution-options]');
        const status = wrapper.querySelector('[data-origin-institution-status]');
        let timer;
        let controller;

        const close = () => {
            options.classList.add('hidden');
            status.classList.add('hidden');
        };
        const render = (items) => {
            options.innerHTML = '';
            if (!items.length) return close();
            items.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-primary/5';
                button.textContent = item;
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    field.value = item;
                    close();
                });
                options.appendChild(button);
            });
            options.classList.remove('hidden');
            status.classList.add('hidden');
        };

        field.addEventListener('input', () => {
            clearTimeout(timer);
            const query = field.value.trim();
            if (query.length < 2) return close();
            timer = setTimeout(async () => {
                controller?.abort();
                controller = new AbortController();
                status.textContent = 'Mencari rekomendasi sekolah...';
                status.classList.remove('hidden');
                try {
                    const response = await fetch(`${lookupUrl}?q=${encodeURIComponent(query)}`, {
                        headers: { Accept: 'application/json' }, signal: controller.signal,
                    });
                    if (!response.ok) return close();
                    const payload = await response.json();
                    render(Array.isArray(payload.data) ? payload.data : []);
                } catch (error) {
                    if (error.name !== 'AbortError') close();
                }
            }, 300);
        });
        field.addEventListener('blur', () => setTimeout(close, 150));
    });
});
</script>
@endonce
