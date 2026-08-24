<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\StudyGroup;
use App\Models\Tentor;
use App\Models\User;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudyGroupController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString();
        $bookingScheduleEnabled = (bool) config('client.branding.booking_schedule_enabled', false);
        $tab = in_array($tab, ['rombel', 'pengajuan'], true) && ($tab !== 'pengajuan' || $bookingScheduleEnabled)
            ? $tab
            : 'rombel';

        $studyGroups = null;
        if ($tab === 'rombel') {
            $studyGroups = StudyGroup::query()
                ->where(function ($query): void {
                    $query->whereNull('package_id')
                        ->orWhere('status', StudyGroup::STATUS_ACTIVE);
                })
                ->with(['tentor:id,name', 'package:package_id,name'])
                ->withCount(['users', 'schedules'])
                ->orderBy('name')
                ->paginate(Pagination::perPage(15), ['*'], 'rombel_page')
                ->withQueryString();
        }

        $applicationStatus = $request->string('status')->toString();
        $applicationStatus = in_array($applicationStatus, [
            StudyGroup::STATUS_PENDING_APPROVAL,
            StudyGroup::STATUS_PENDING_PAYMENT,
            StudyGroup::STATUS_ACTIVE,
            StudyGroup::STATUS_CANCELLED,
            StudyGroup::STATUS_EXPIRED,
        ], true) ? $applicationStatus : null;

        $groupApplications = null;
        if ($tab === 'pengajuan') {
            $groupApplications = StudyGroup::query()
                ->whereNotNull('package_id')
                ->with([
                    'package:package_id,name',
                    'organizer:id,name,email',
                    'members.user:id,name,email,phone',
                    'members.invoice.payments',
                ])
                ->withCount([
                    'members',
                    'members as paid_members_count' => fn ($query) => $query->where('status', 'paid'),
                ])
                ->when($applicationStatus, fn ($query) => $query->where('status', $applicationStatus))
                ->latest()
                ->paginate(Pagination::perPage(15), ['*'], 'pengajuan_page')
                ->withQueryString();
        }

        return view('admin.pages.study-group.index', compact(
            'studyGroups',
            'tab',
            'groupApplications',
            'applicationStatus',
            'bookingScheduleEnabled'
        ));
    }

    public function create(): View
    {
        return view('admin.pages.study-group.create', [
            'studyGroup' => null,
            'tentors' => $this->tentorOptions(),
            'users' => $this->participantOptions(),
            'selectedUserIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        DB::transaction(function () use ($request, $validated): void {
            $studyGroup = StudyGroup::create([
                ...$validated,
                'is_active' => $request->boolean('is_active', true),
                'status' => StudyGroup::STATUS_ACTIVE,
            ]);

            $studyGroup->users()->sync($request->input('user_ids', []));
        });

        return redirect()
            ->route('admin.study-groups.index')
            ->with('success', 'Rombel berhasil ditambahkan.');
    }

    public function edit(StudyGroup $studyGroup): View
    {
        abort_if($studyGroup->package_id, 404);

        $selectedUserIds = $studyGroup->users()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('admin.pages.study-group.edit', [
            'studyGroup' => $studyGroup,
            'tentors' => $this->tentorOptions($studyGroup->tentor_id),
            'users' => $this->participantOptions(),
            'selectedUserIds' => $selectedUserIds,
        ]);
    }

    public function update(Request $request, StudyGroup $studyGroup): RedirectResponse
    {
        if ($studyGroup->package_id) {
            return back()->with('error', 'Rombel dari pengajuan paket dikelola melalui paket dan jadwalnya.');
        }

        $validated = $this->validatedData($request);

        DB::transaction(function () use ($request, $studyGroup, $validated): void {
            $studyGroup->update([
                ...$validated,
                'is_active' => $request->boolean('is_active'),
            ]);

            $studyGroup->users()->sync($request->input('user_ids', []));
        });

        return redirect()
            ->route('admin.study-groups.index')
            ->with('success', 'Rombel berhasil diperbarui.');
    }

    public function destroy(StudyGroup $studyGroup): RedirectResponse
    {
        if ($studyGroup->package_id) {
            return back()->with('error', 'Rombel dari pengajuan paket tidak dapat dihapus dari halaman ini.');
        }

        if ($studyGroup->schedules()->exists()) {
            return back()->with('error', 'Rombel masih dipakai di jadwal kelas. Nonaktifkan rombel jika tidak digunakan lagi.');
        }

        $studyGroup->delete();

        return redirect()
            ->route('admin.study-groups.index')
            ->with('success', 'Rombel berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tentor_id' => ['nullable', 'exists:tentors,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);
    }

    private function tentorOptions(?int $currentTentorId = null)
    {
        return Tentor::query()
            ->where('is_active', true)
            ->when($currentTentorId, fn ($query) => $query->orWhere('id', $currentTentorId))
            ->orderBy('name')
            ->get(['id', 'name', 'expertise']);
    }

    private function participantOptions()
    {
        return User::query()
            ->where('role', 'user')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
