@extends('user.layout.user')
@section('title', 'Dashboard')
@section('content')
<section class="dashboard">
    <x-page-desc title="Dashboard" description="Selamat datang {{ Auth::user()->name }}"></x-page-desc>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-5 w-full">
        <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
            <i
                class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
            <div>
                <p class="text-[24px] font-bold">{{ $stats['total_packages'] }}</p>
                <p class="text-[12px] mt-[-6px] font-light">Paket Aktif</p>
            </div>
        </div>

        <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
            <i
                class="ri-file-list-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
            <div>
                <p class="text-[24px] font-bold">{{ $stats['total_attempts'] }}</p>
                <p class="text-[12px] mt-[-6px] font-light">Total Tryout</p>
            </div>
        </div>

        <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
            <i
                class="ri-trophy-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
            <div>
                <p class="text-[24px] font-bold">{{ $stats['completed_tryouts'] }}</p>
                <p class="text-[12px] mt-[-6px] font-light">Tryout Selesai</p>
            </div>
        </div>

        <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
            <i
                class="ri-percent-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
            <div>
                <p class="text-[24px] font-bold">{{ number_format($stats['average_score'], 1) }}%</p>
                <p class="text-[12px] mt-[-6px] font-light">Rata-rata Skor</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Active Packages -->
        <div class="lg:col-span-2 bg-white p-6 rounded-lg border border-border">
            <h3 class="text-lg font-bold mb-4">Paket Aktif</h3>
            @forelse($activePackages as $access)
            <div class="flex items-center justify-between p-4 border border-border rounded-lg mb-3 last:mb-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                        <i class="ri-book-line text-white"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold">{{ $access->package->name }}</h4>
                        <p class="text-sm text-gray-600">{{ ucfirst($access->package->type_package) }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium">Berakhir: {{ $access->end_date->format('d M Y') }}</p>
                    <p class="text-xs text-gray-500">{{ $access->end_date->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <div class="text-center py-8">
                <i class="ri-inbox-line text-4xl text-gray-400 mb-2"></i>
                <p class="text-gray-500">Belum ada paket aktif</p>
                <a href="{{ route('user.package.index') }}" class="text-primary hover:underline">Lihat Paket</a>
            </div>
            @endforelse
        </div>

        <!-- Recent Attempts -->
        <div class="bg-white p-6 rounded-lg border border-border">
            <h3 class="text-lg font-bold mb-4">Tryout Terbaru</h3>
            @forelse($recentAttempts as $attempt)
            <div class="flex items-center justify-between p-3 border-b border-border border-dashed last:border-b-0">
                <div>
                    <h4 class="font-medium text-sm">{{ $attempt->tryout->name ?? 'Tryout' }}</h4>
                    <p class="text-xs text-gray-500">{{ $attempt->created_at->format('d M Y H:i') }}</p>
                </div>
                <div class="text-right">
                    @if($attempt->status === 'completed')
                    <span
                        class="text-xs px-2 py-1 rounded-full {{ $attempt->is_passed ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                        {{ $attempt->is_passed ? 'Lulus' : 'Tidak Lulus' }}
                    </span>
                    @else
                    <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-600">
                        Belum Selesai
                    </span>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-8">
                <i class="ri-file-list-line text-4xl text-gray-400 mb-2"></i>
                <p class="text-gray-500 text-sm">Belum ada tryout</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Packages Expiring Soon Alert -->
    @if($expiringSoon->count() > 0)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
        <div class="flex items-start gap-3">
            <i class="ri-alarm-warning-line text-yellow-500 text-xl mt-0.5"></i>
            <div>
                <h4 class="font-semibold text-yellow-800">Paket Akan Berakhir</h4>
                <p class="text-yellow-700 text-sm mb-2">Beberapa paket Anda akan berakhir dalam 7 hari:</p>
                <ul class="space-y-1">
                    @foreach($expiringSoon as $access)
                    <li class="text-yellow-700 text-sm">
                        • {{ $access->package->name }} - berakhir {{ $access->end_date->format('d M Y') }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('user.package.index') }}" class="text-yellow-800 hover:underline text-sm font-medium">
                    Perpanjang Sekarang →
                </a>
            </div>
        </div>
    </div>
    @endif

</section>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Add any dashboard-specific interactions here
    console.log('Dashboard loaded successfully');

    // Simple animation for stats cards
    const cards = document.querySelectorAll('.dashboard .grid > div');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>
@endsection

@section('styles')
<style>
    .dashboard .grid>div {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .dashboard .grid>div:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Custom color classes */
    .bg-green {
        background-color: #10B981;
    }

    .text-green {
        color: #10B981;
    }

    .bg-green-100 {
        background-color: #D1FAE5;
    }

    .text-green-600 {
        color: #059669;
    }

    .bg-red-100 {
        background-color: #FEE2E2;
    }

    .text-red-600 {
        color: #DC2626;
    }

    .bg-yellow-100 {
        background-color: #FEF3C7;
    }

    .text-yellow-600 {
        color: #D97706;
    }
</style>
@endsection
