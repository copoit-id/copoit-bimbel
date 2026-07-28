<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\StudyGroup;
use App\Models\Tentor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudyGroupController extends Controller
{
    public function index(): View
    {
        $this->abortIfStudyGroupHidden();

        $studyGroups = StudyGroup::query()
            ->where(function ($query): void {
                $query->whereNull('package_id')
                    ->orWhere('status', StudyGroup::STATUS_ACTIVE);
            })
            ->with(['tentor:id,name', 'package:package_id,name'])
            ->withCount(['users', 'schedules'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.pages.study-group.index', compact('studyGroups'));
    }

    public function create(): View
    {
        $this->abortIfStudyGroupHidden();

        return view('admin.pages.study-group.create', [
            'studyGroup' => null,
            'tentors' => $this->tentorOptions(),
            'users' => $this->participantOptions(),
            'selectedUserIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->abortIfStudyGroupHidden();

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
        $this->abortIfStudyGroupHidden();
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
        $this->abortIfStudyGroupHidden();

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
        $this->abortIfStudyGroupHidden();

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

    private function abortIfStudyGroupHidden(): void
    {
        abort_unless((bool) config('client.branding.class_schedule_menu_enabled', false), 404);
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
