@extends('admin.layout.admin')

@section('content')
<div x-data="dashboardCharts()" x-init="initCharts()">
    
    {{-- Upgrade Banner --}}
    @if($upgradeBanner['enabled'])
        <x-dashboard.upgrade-banner
            :title="$upgradeBanner['title']"
            :description="$upgradeBanner['description']"
            :button-text="$upgradeBanner['button_text']"
            :button-url="$upgradeBanner['button_url']"
            can-close="true"
        />
    @endif

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Overview</h1>
            <p class="text-sm text-gray-500 mt-1">Track your platform performance and metrics</p>
        </div>
        
        {{-- Period Selector --}}
        <div class="flex items-center gap-2 bg-white rounded-lg border border-gray-200 p-1">
            @foreach(['7d' => '7 Days', '30d' => '30 Days', '90d' => '3 Months', '1y' => '1 Year'] as $key => $label)
                <a href="{{ route('admin.dashboard', ['period' => $key]) }}" 
                   class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $period === $key ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @if($schoolDashboard ?? false)
            <x-dashboard.stat-card label="Paket Aktif" :value="number_format($summary['active_packages'])" icon="ri-shopping-bag-3-line" :trend="$packageTrend['direction']" :trend-value="$packageTrend['value']" trend-label="dari siswa rombel" color="green" />
        @else
            <x-dashboard.stat-card label="Total Revenue" value="Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}" icon="ri-money-dollar-circle-line" :trend="$revenueTrend['direction']" :trend-value="$revenueTrend['value']" trend-label="from last period" color="green" />
        @endif
        
        <x-dashboard.stat-card
            label="Active Users"
            :value="number_format($summary['total_users'])"
            icon="ri-user-3-line"
            :trend="$userTrend['direction']"
            :trend-value="$userTrend['value']"
            trend-label="from last period"
            color="primary"
        />
        
        <x-dashboard.stat-card
            label="Tryout Attempts"
            :value="number_format($recentTryouts->count())"
            icon="ri-draft-line"
            :trend="$tryoutTrend['direction']"
            :trend-value="$tryoutTrend['value']"
            trend-label="from last period"
            color="blue"
        />
        
        <x-dashboard.stat-card
            label="Packages Sold"
            :value="number_format($summary['active_packages'])"
            icon="ri-shopping-bag-3-line"
            :trend="$packageTrend['direction']"
            :trend-value="$packageTrend['value']"
            trend-label="from last period"
            color="orange"
        />
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Main Chart - Tryout Attempts --}}
        <div class="lg:col-span-2">
            <x-dashboard.chart-card
                title="Tryout Attempts"
                subtitle="Total tryout participants over time"
                :value="number_format($recentTryouts->count())"
                :trend="$tryoutTrend['direction']"
                :trend-value="$tryoutTrend['value']"
                chart-id="tryoutChart"
                chart-type="bar"
                height="280px"
                :selected-period="$period"
            />
        </div>
        
        {{-- Paket aktif untuk dashboard sekolah / Revenue untuk admin --}}
        <div class="lg:col-span-1">
            <x-dashboard.chart-card
                :title="($schoolDashboard ?? false) ? 'Paket Aktif' : 'Total Revenue'"
                :subtitle="($schoolDashboard ?? false) ? 'Akses paket siswa rombel' : 'Revenue trends'"
                :value="($schoolDashboard ?? false) ? number_format($summary['active_packages']) : 'Rp '.number_format($summary['total_revenue'], 0, ',', '.')"
                :trend="($schoolDashboard ?? false) ? $packageTrend['direction'] : $revenueTrend['direction']"
                :trend-value="($schoolDashboard ?? false) ? $packageTrend['value'] : $revenueTrend['value']"
                chart-id="revenueChart"
                chart-type="line"
                height="280px"
                :selected-period="$period"
            />
        </div>
    </div>

    {{-- Recent Activity Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Transactions --}}
        <x-dashboard.activity-table
            :title="($schoolDashboard ?? false) ? 'Aktivitas Tryout Terbaru' : 'Recent Transactions'"
            :columns="($schoolDashboard ?? false) ? ['Siswa', 'Tryout', 'Waktu Mulai', 'Status'] : ['Customer', 'Package', 'Date', 'Amount', 'Status']"
            :tabs="[
                ['id' => 'all', 'label' => 'All', 'count' => ($schoolDashboard ?? false) ? ($recentTryouts ?? collect())->count() : $payments->count()],
                ['id' => 'completed', 'label' => 'Completed'],
                ['id' => 'pending', 'label' => 'Pending'],
            ]"
            active-tab="all"
        >
            @if($schoolDashboard ?? false)
            @forelse(($recentTryouts ?? collect()) as $attempt)
                <tr class="hover:bg-gray-50/50 transition-colors"><td class="px-6 py-4"><p class="text-sm font-medium text-gray-900">{{ $attempt->user?->name ?? '-' }}</p><p class="text-xs text-gray-500">{{ $attempt->user?->email }}</p></td><td class="px-6 py-4 text-sm text-gray-700">{{ $attempt->tryout?->name ?? 'Tryout' }}</td><td class="px-6 py-4 text-sm text-gray-500">{{ $attempt->started_at?->format('d M Y H:i') }}</td><td class="px-6 py-4"><span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{{ ucfirst($attempt->status) }}</span></td></tr>
            @empty <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada aktivitas tryout.</td></tr>@endforelse
            @else @forelse($payments as $payment)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                                <span class="text-xs font-medium text-primary">
                                    {{ strtoupper(substr($payment->user->name ?? 'U', 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $payment->user->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->user->email ?? '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-700">{{ $payment->package->name ?? 'Package' }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-500">{{ $payment->created_at->format('d M Y') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-medium text-gray-900">Rp {{ number_format((float) $payment->total_amount, 0, ',', '.') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full border {{ $payment->status === 'success' ? 'bg-green/10 text-green border-green/20' : 'bg-yellow-100 text-yellow-700 border-yellow-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $payment->status === 'success' ? 'bg-green' : 'bg-yellow-500' }} mr-1.5"></span>
                            {{ ucfirst($payment->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button class="text-gray-400 hover:text-gray-600">
                            <i class="ri-more-2-fill text-lg"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="ri-inbox-line text-3xl text-gray-300"></i>
                            <p>No transactions found</p>
                        </div>
                    </td>
                </tr>
            @endforelse @endif
        </x-dashboard.activity-table>

        {{-- Recent Users --}}
        <x-dashboard.activity-table
            title="Recent Users"
            :columns="['User', 'Email', 'Joined Date', 'Status']"
            show-view-all="true"
            view-all-url="{{ route('admin.user.index') }}"
        >
            @forelse($users as $user)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary/60 flex items-center justify-center">
                                <span class="text-xs font-medium text-white">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-500">{{ $user->email }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-500">{{ $user->created_at->format('d M Y') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full border bg-green/10 text-green border-green/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-green mr-1.5"></span>
                            Active
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button class="text-gray-400 hover:text-gray-600">
                            <i class="ri-more-2-fill text-lg"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="ri-user-line text-3xl text-gray-300"></i>
                            <p>No users found</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-dashboard.activity-table>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function dashboardCharts() {
    return {
        tryoutChart: null,
        revenueChart: null,
        
        initCharts() {
            this.initTryoutChart();
            this.initRevenueChart();
            
            // Listen for period changes
            window.addEventListener('period-changed', (e) => {
                if (e.detail.chartId === 'tryoutChart') {
                    this.updateTryoutChart(e.detail.period);
                } else if (e.detail.chartId === 'revenueChart') {
                    this.updateRevenueChart(e.detail.period);
                }
            });
        },
        
        initTryoutChart() {
            const ctx = document.getElementById('tryoutChart').getContext('2d');
            const data = @json($tryoutChartData);
            
            this.tryoutChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Tryout Attempts',
                        data: data.data,
                        backgroundColor: 'rgba(28, 50, 89, 0.8)',
                        borderColor: 'rgba(28, 50, 89, 1)',
                        borderWidth: 0,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                font: {
                                    family: 'Poppins',
                                    size: 11
                                },
                                color: '#9ca3af'
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                font: {
                                    family: 'Poppins',
                                    size: 11
                                },
                                color: '#9ca3af'
                            }
                        }
                    }
                }
            });
        },
        
        initRevenueChart() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const data = @json($revenueChartData);
            
            // Create gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(42, 164, 113, 0.2)');
            gradient.addColorStop(1, 'rgba(42, 164, 113, 0)');
            
            this.revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data.data,
                        borderColor: '#2AA471',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#2AA471',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                font: {
                                    family: 'Poppins',
                                    size: 11
                                },
                                color: '#9ca3af',
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                font: {
                                    family: 'Poppins',
                                    size: 10
                                },
                                color: '#9ca3af',
                                maxTicksLimit: 6
                            }
                        }
                    }
                }
            });
        },
        
        updateTryoutChart(period) {
            // Fetch new data via AJAX or reload page
            // For now, we'll reload the page with new period
            window.location.href = '{{ route('admin.dashboard') }}?period=' + period;
        },
        
        updateRevenueChart(period) {
            window.location.href = '{{ route('admin.dashboard') }}?period=' + period;
        }
    }
}
</script>
@endpush
