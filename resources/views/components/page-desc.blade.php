@props(['title' => 'Title', 'description' => null, 'name_link' => null, 'url_link' => null, 'direction' => null])
<div class="flex flex-col {{ $direction == null ? 'items-start' : $direction }}">
    <h1 class="text-[24px] text-dark font-bold">{{ $title }}</h1>
    @if ($description)
    <p class="font-light text-[16px]">{{ $description }}</p>
    @endif
    @if ($name_link && $url_link)
    <a href="{{$url_link}}" class="flex justify-center bg-primary text-white px-6 py-1.5 font-light rounded-lg mt-4">{{
        $name_link }}</a>
    @endif

</div>