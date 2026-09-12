<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class ParentNavigationService
{
    public function __construct(private readonly PlanModuleService $planModules)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function items(): array
    {
        $academicItems = [
            $this->link('parent.attendance', 'ri-calendar-check-line', 'Presensi'),
            $this->link('parent.assessments', 'ri-bar-chart-box-line', 'Riwayat Ujian'),
            $this->link('parent.development', 'ri-line-chart-line', 'Perkembangan'),
            $this->link('parent.report', 'ri-file-chart-line', 'Laporan Anak'),
        ];

        $serviceItems = [
            $this->link('parent.catalog', 'ri-store-2-line', 'Katalog Belajar'),
            $this->link('parent.packages', 'ri-bank-card-line', 'Akses & Pembayaran'),
        ];

        if ((bool) config('client.branding.tutor_chat_enabled', false)
            && $this->planModules->allows('discussion')
            && Route::has('parent.chat.index')) {
            $serviceItems[] = $this->link('parent.chat.index', 'ri-chat-3-line', 'Chat Tutor');
        }

        return [
            ['type' => 'link', ...$this->link('parent.dashboard', 'ri-home-5-line', 'Ringkasan')],
            $this->group('academic', 'Akademik Anak', 'ri-graduation-cap-line', $academicItems),
            $this->group('services', 'Layanan Belajar', 'ri-service-line', $serviceItems),
        ];
    }

    /** @return array{route: string, icon: string, label: string, is_active: bool} */
    private function link(string $route, string $icon, string $label): array
    {
        return [
            'route' => $route,
            'icon' => $icon,
            'label' => $label,
            'is_active' => request()->routeIs($route.'*'),
        ];
    }

    /** @param array<int, array{route: string, icon: string, label: string, is_active: bool}> $items */
    private function group(string $id, string $label, string $icon, array $items): array
    {
        return [
            'type' => 'group',
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'is_active' => collect($items)->contains('is_active', true),
            'items' => $items,
        ];
    }
}
