@props(['title' => null,'icon' => null ,'route' => '#', 'color' => 'primary'])
<a href="{{ $route }}"
    class="bg-{{$color}} text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-2">
    @if ($icon)
    <i class="{{ $icon }}"></i>
    @endif
    {{ $title }}
</a>