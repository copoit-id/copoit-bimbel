@extends('parent.layout')

@section('title', 'Chat Tutor')

@section('content')
<div class="space-y-6">
    <x-layout.page-header
        title="Chat tutor"
        description="Hubungi tutor berdasarkan jadwal belajar rutin {{ $child?->name ?? 'anak' }}."
    />

    <x-ui.card variant="flat" class="overflow-hidden border border-gray-200" padding="none">
        <x-ui.card.header title="Daftar tutor" subtitle="Pilih jadwal belajar untuk membuka percakapan." class="px-5 pt-5 sm:px-6 sm:pt-6" />

        <div class="divide-y divide-gray-100">
            @forelse($contacts as $contact)
                <a href="{{ $contact['url'] }}" class="group flex items-center gap-3 px-5 py-4 transition-colors hover:bg-gray-50 sm:px-6">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 font-bold text-primary">
                        {{ strtoupper(mb_substr($contact['tutor_name'], 0, 1)) }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-gray-900">{{ $contact['tutor_name'] }}</span>
                        <span class="mt-0.5 block truncate text-xs text-gray-500">{{ $contact['child_name'] }} · {{ $contact['schedule_title'] }}</span>
                    </span>
                    <x-ui.badge variant="light">Buka chat <i class="ri-arrow-right-s-line ml-1"></i></x-ui.badge>
                </a>
            @empty
                <div class="px-5 py-14 text-center"><i class="ri-chat-1-line text-4xl text-gray-300"></i><p class="mt-3 font-semibold text-gray-800">Belum ada tutor yang dapat dihubungi</p><p class="mt-1 text-sm text-gray-500">Tutor akan tersedia setelah anak memiliki jadwal belajar rutin.</p></div>
            @endforelse
        </div>
    </x-ui.card>
</div>
@endsection
