@extends('user.layout.new-user')

@section('title', 'Hasil Kecermatan')

@section('content')
@php($primaryColor = $clientBranding['primary_color'] ?? '#10b981')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('user.kecermatan.index') }}" class="rounded-lg p-2 hover:bg-gray-100">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Hasil Kecermatan</h1>
        <p class="text-sm text-gray-500">{{ $kecermatan->name }}</p>
    </div>
</div>

<div class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase text-gray-400">Token</p>
            <p class="mt-1 truncate text-sm font-bold text-gray-900">{{ $token }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase text-gray-400">Benar</p>
            <p class="mt-1 text-2xl font-black text-gray-900">{{ $totalCorrect }}/{{ $totalQuestions }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase text-gray-400">Persentase</p>
            <p class="mt-1 text-2xl font-black text-gray-900">{{ number_format($percentage, 1) }}%</p>
        </div>
        <div class="rounded-xl p-4" style="background-color: {{ $primaryColor }}12">
            <p class="text-xs font-semibold uppercase" style="color: {{ $primaryColor }}">Kategori</p>
            <p class="mt-1 text-2xl font-black text-gray-900">{{ $category }}</p>
        </div>
    </div>
</div>

<div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
    <h2 class="mb-4 text-lg font-bold text-gray-800">Rincian Per Kolom</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kolom</th>
                    <th class="px-4 py-3 text-center">Benar</th>
                    <th class="px-4 py-3 text-center">Salah</th>
                    <th class="px-4 py-3 text-center">Kategori</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($rows as $row)
                <tr>
                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $row['column']->name }}</td>
                    <td class="px-4 py-3 text-center">{{ $row['correct'] }}</td>
                    <td class="px-4 py-3 text-center">{{ $row['wrong'] }}</td>
                    <td class="px-4 py-3 text-center">{{ $row['category'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
