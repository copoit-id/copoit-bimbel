@extends('general.layout')

@section('title', $title)

@section('content')
<section class="mx-auto flex min-h-[60vh] max-w-7xl items-center px-4 py-16 sm:px-6 lg:px-8">
    <div class="w-full rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-primary">General</p>
        <h1 class="mt-3 text-3xl font-bold text-slate-950 sm:text-5xl">{{ $title }}</h1>
    </div>
</section>
@endsection
