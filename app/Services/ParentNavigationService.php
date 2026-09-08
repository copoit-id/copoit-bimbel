<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class ParentNavigationService
{
    public function __construct(private readonly PlanModuleService $planModules)
    {
    }

    /**
     * Return render-ready navigation items for the parent portal.
     *
     * @return array<int, array{route: string, icon: string, label: string, is_active: bool}>
     */
    public function items(): array
    {
        $items = [
            ['route' => 'parent.dashboard', 'icon' => 'ri-home-5-line', 'label' => 'Ringkasan'],
            ['route' => 'parent.attendance', 'icon' => 'ri-calendar-check-line', 'label' => 'Presensi'],
            ['route' => 'parent.packages', 'icon' => 'ri-bank-card-line', 'label' => 'Paket & Pembayaran'],
            ['route' => 'parent.assessments', 'icon' => 'ri-bar-chart-box-line', 'label' => 'Riwayat Ujian'],
            ['route' => 'parent.development', 'icon' => 'ri-line-chart-line', 'label' => 'Perkembangan'],
        ];

        if ((bool) config('client.branding.tutor_chat_enabled', false)
            && $this->planModules->allows('discussion')
            && Route::has('parent.chat.index')) {
            $items[] = ['route' => 'parent.chat.index', 'icon' => 'ri-chat-3-line', 'label' => 'Chat Tutor'];
        }

        $items[] = ['route' => 'parent.report', 'icon' => 'ri-file-chart-line', 'label' => 'Laporan Cetak'];

        return array_map(static fn (array $item): array => [
            ...$item,
            'is_active' => request()->routeIs($item['route']),
        ], $items);
    }
}
