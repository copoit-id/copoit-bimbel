<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $tabs = [
            'all' => [
                'label' => 'Semua',
                'actions' => [],
            ],
            'login' => [
                'label' => 'Login',
                'actions' => ['login_success', 'login_failed', 'login_locked', 'logout'],
            ],
            'reset' => [
                'label' => 'Lupa Password',
                'actions' => ['reset_requested', 'reset_completed', 'reset_failed', 'reset_token_invalid'],
            ],
            'tryout' => [
                'label' => 'Tryout',
                'actions' => ['tryout_list_opened', 'tryout_lobby_opened', 'tryout_started', 'tryout_finished', 'tryout_result_viewed'],
            ],
            'kelas' => [
                'label' => 'Bimbel/Kelas',
                'actions' => ['class_list_opened', 'class_zoom_opened', 'class_material_opened'],
            ],
            'akun' => [
                'label' => 'Akun',
                'actions' => ['register', 'profile_updated', 'password_changed'],
            ],
            'pengaturan' => [
                'label' => 'Pengaturan',
                'actions' => ['settings_updated'],
            ],
        ];

        $actionLabels = [
            'login_success' => 'Login sukses',
            'login_failed' => 'Login gagal',
            'login_locked' => 'Login diblokir',
            'logout' => 'Logout',
            'reset_requested' => 'Request reset password',
            'reset_completed' => 'Reset password berhasil',
            'reset_failed' => 'Reset password gagal',
            'reset_token_invalid' => 'Token reset invalid',
            'tryout_list_opened' => 'Buka daftar tryout',
            'tryout_lobby_opened' => 'Buka lobby tryout',
            'tryout_started' => 'Mulai tryout',
            'tryout_finished' => 'Selesai tryout',
            'tryout_result_viewed' => 'Lihat hasil tryout',
            'class_list_opened' => 'Buka kelas/bimbel',
            'class_zoom_opened' => 'Akses zoom kelas',
            'class_material_opened' => 'Akses materi kelas',
            'register' => 'Registrasi',
            'profile_updated' => 'Update profil',
            'password_changed' => 'Ganti password',
            'settings_updated' => 'Update pengaturan',
        ];

        $tab = $request->input('tab', 'all');
        if (! array_key_exists($tab, $tabs)) {
            $tab = 'all';
        }

        $query = ActivityLog::query()
            ->with('user')
            ->latest();

        $tabActions = $tabs[$tab]['actions'];
        if (! empty($tabActions)) {
            $query->whereIn('action', $tabActions);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', '%'.$search.'%')
                    ->orWhere('ip', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $start = $request->input('start');
        $end = $request->input('end');
        if ($start || $end) {
            $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
            $endDate = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $logs = $query->paginate(\App\Support\Pagination::perPage(20))->withQueryString();

        $routeName = $request->route()?->getName() ?? '';
        $layout = str_starts_with($routeName, 'super-admin.')
            ? 'super-admin.layouts.app'
            : 'admin.layout.admin';

        return view('activity.index', [
            'layout' => $layout,
            'tabs' => $tabs,
            'tab' => $tab,
            'logs' => $logs,
            'actionLabels' => $actionLabels,
        ]);
    }
}
