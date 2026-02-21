@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div>
        <h2 class="text-2xl font-bold">{{ __('Dashboard') }}</h2>
        <p class="text-gray-500">{{ __('Selamat datang di panel admin') }} {{ $clientBranding['name'] }}</p>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">{{ __('Total Users') }}</p>
                    <h3 class="text-2xl font-bold">{{ $count_user ?? 0 }}</h3>
                </div>
                <div class="bg-primary/10 p-3 rounded-lg">
                    <i class="ri-user-3-line text-primary text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">{{ __('Total Pendapatan') }}</p>
                    <h3 class="text-2xl font-bold">Rp {{ $count_amount ?? 0 }}</h3>
                </div>
                <div class="bg-primary/10 p-3 rounded-lg">
                    <i class="ri-money-dollar-circle-line text-primary text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">{{ __('Total Tryout') }}</p>
                    <h3 class="text-2xl font-bold">{{ $count_tryout ?? 0 }}</h3>
                </div>
                <div class="bg-primary/10 p-3 rounded-lg">
                    <i class="ri-draft-line text-primary text-2xl"></i>
                </div>
            </div>
        </div>

        @if($clientBranding['class_management_enabled'] ?? false)
        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">{{ __('Total Kelas') }}</p>
                    <h3 class="text-2xl font-bold">{{ $count_class ?? 0 }}</h3>
                </div>
                <div class="bg-primary/10 p-3 rounded-lg">
                    <i class="ri-book-open-line text-primary text-2xl"></i>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Recent Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Latest Users -->
        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ __('User Terbaru') }}</h3>
                <a href="{{ route('admin.user.index') }}" class="text-primary hover:underline">{{ __('Lihat Semua') }}</a>
            </div>
            <div class="space-y-4">
                @foreach ($users as $user)
                <div class="flex items-center justify-between py-2">
                    <div class="flex items-center space-x-3">
                        <img src="https://ui-avatars.com/api/?name=John+Doe" class="w-10 h-10 rounded-full">
                        <div>
                            <p class="font-medium">{{ $user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $user->email }}</p>
                        </div>
                    </div>
                    <span class="text-sm text-gray-500">
                        {{ $user->created_at->diffForHumans() }}
                    </span>
                </div>
                @endforeach
                @if(count($users) === 0)
                <div class="text-center text-gray-500 py-4">
                    {{ __('Tidak ada user terbaru') }}
                </div>
                @endif
            </div>
        </div>

        <!-- Latest Transactions -->
        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ __('Transaksi Terbaru') }}</h3>
                <a href="#" class="text-primary hover:underline">{{ __('Lihat Semua') }}</a>
            </div>
            <div class="space-y-4">
                @foreach ($payments as $payment)
                <div class="flex items-center justify-between py-2">
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 p-2 rounded">
                            <i class="ri-checkbox-circle-line text-green-500"></i>
                        </div>
                        <div>
                            <p class="font-medium">{{ __('Paket Premium') }}</p>
                            <p class="text-sm text-gray-500">John Doe</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-medium">Rp {{ $payment->amount }}</p>
                        <p class="text-sm text-gray-500">{{$payment->created_at->diffForHumans()}}</p>
                    </div>
                </div>
                @endforeach
                @if(count($users) === 0)
                <div class="text-center text-gray-500 py-4">
                    {{ __('Tidak ada transaksi terbaru') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    @if(!empty($clientBranding['dashboard_announcement_message'] ?? null))
    @php
        $rawBroadcastTitle = trim((string) ($clientBranding['dashboard_announcement_title'] ?? ''));
        $broadcastTitle = in_array(mb_strtolower($rawBroadcastTitle), ['pengumuman', 'announcement', 'broadcast'], true)
            ? __('Broadcast')
            : ($rawBroadcastTitle !== '' ? $rawBroadcastTitle : __('Broadcast'));
    @endphp
    <div class="bg-white p-6 rounded-lg border border-gray-200">
        <div class="flex items-start gap-3">
            <div class="bg-amber-100 text-amber-700 p-2 rounded-lg">
                <i class="ri-megaphone-line text-lg"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ $broadcastTitle }}
                </h3>
                <div class="text-sm text-gray-600 mt-1">
                    {!! html_entity_decode((string) $clientBranding['dashboard_announcement_message']) !!}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
