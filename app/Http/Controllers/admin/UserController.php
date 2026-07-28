<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ParticipantDestinationCategory;
use App\Models\Role;
use App\Models\User;
use App\Rules\SafeName;
use App\Services\ParticipantDestinationSelectionService;
use App\Services\PlanQuotaService;
use App\Services\TutorProfileService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $roleOptions = $this->getRoleOptions();
        $activeRole = $request->input('role', array_key_exists('user', $roleOptions) ? 'user' : array_key_first($roleOptions));

        if (! array_key_exists((string) $activeRole, $roleOptions)) {
            $activeRole = array_key_exists('user', $roleOptions) ? 'user' : array_key_first($roleOptions);
        }

        $users = User::query()
            ->with([
                'participantDestinationCategory.parent',
                'studyGroups:id,name',
                'userPackageAccess' => fn ($query) => $query
                    ->select(['user_package_access_id', 'user_id', 'package_id', 'status', 'end_date'])
                    ->with('package:package_id,name'),
            ])
            ->withCount([
                'userPackageAccess as active_package_access_count' => fn ($query) => $query
                    ->where('status', 'active')
                    ->where(fn ($access) => $access->whereNull('end_date')->orWhere('end_date', '>', now())),
                'classAttendances as attendance_record_count',
                'classAttendances as attendance_present_count' => fn ($query) => $query
                    ->whereIn('status', ['present', 'late']),
            ])
            ->where('role', '!=', 'super_admin')
            ->when($activeRole, fn ($query) => $query->where('role', $activeRole))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pages.user.index', compact('users', 'roleOptions', 'activeRole'));
    }

    public function exportExcel(): BinaryFileResponse
    {
        $filename = 'data-user-lengkap-'.now()->format('Ymd_His').'.xlsx';
        $userColumns = collect(Schema::getColumnListing('users'))
            ->reject(fn (string $column) => in_array($column, [
                'id',
                'password',
                'remember_token',
                'reset_token',
                'reset_token_expires',
                'password_reset_token',
                'password_reset_expires_at',
                'referred_by_user_id',
                'participant_destination_category_id',
                'participant_destination_source',
                'participant_destination_external_id',
            ], true))
            ->values()
            ->all();
        $roleOptions = $this->getRoleOptions();
        $packageColumns = $this->exportPackageColumns();
        $fields = $this->exportUserFields($userColumns, $packageColumns);
        $tempFile = tempnam(sys_get_temp_dir(), 'users-export-');

        $this->writeUsersXlsx($tempFile, $fields, $userColumns, $roleOptions);

        return response()
            ->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache',
            ])
            ->deleteFileAfterSend(true);
    }

    public function loginAsPage()
    {
        $users = User::where('role', 'user')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.pages.user.login-as', compact('users'));
    }

    public function create()
    {
        $roleOptions = $this->getRoleOptions();
        $destinationCategories = $this->getDestinationCategories();

        return view('admin.pages.user.create', [
            'user' => null,
            'roleOptions' => $roleOptions,
            'destinationCategories' => $destinationCategories,
        ]);
    }

    public function store(
        Request $request,
        ParticipantDestinationSelectionService $destinationSelectionService,
        TutorProfileService $tutorProfileService
    ) {
        $roleOptions = $this->getRoleOptions();
        $roleSlugs = array_keys($roleOptions);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', new SafeName],
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'status' => 'required|in:aktif,nonaktif',
            'role' => ['required', Rule::in($roleSlugs)],
        ]);

        // Kuota paket hanya berlaku untuk akun peserta, bukan akun operasional seperti Tutor.
        if ($validated['role'] === 'user') {
            $quotaCheck = PlanQuotaService::canRegisterUser();
            if (! $quotaCheck['allowed']) {
                return redirect()->route('admin.user.index')
                    ->with('error', $quotaCheck['reason']);
            }
        }
        $destinationPayload = $destinationSelectionService->validate(
            $request,
            $validated['role'] === 'user' && $destinationSelectionService->isRequired()
        );

        $user = DB::transaction(function () use ($validated, $destinationPayload, $tutorProfileService): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'] ?? 'aktif',
                'role' => $validated['role'],
                ...$destinationPayload,
            ]);
            $role = Role::where('slug', $user->role)->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
            $tutorProfileService->sync($user);

            return $user;
        });

        return redirect()->route('admin.user.index', ['role' => $user->role])->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $user->load([
            'participantDestinationCategory.parent',
            'referredBy:id,name,email',
            'studyGroups:id,name,description,is_active',
            'userPackageAccess' => fn ($query) => $query
                ->with([
                    'package:package_id,name,access_duration_value,access_duration_unit',
                    'createdBy:id,name',
                ])
                ->orderByDesc('created_at'),
            'payments' => fn ($query) => $query
                ->with([
                    'package:package_id,name',
                    'installments' => fn ($installments) => $installments->select([
                        'id', 'payment_id', 'amount', 'paid_at', 'payment_method',
                    ]),
                ])
                ->withCount('installments')
                ->withSum('installments', 'amount')
                ->orderByDesc('created_at'),
            'billInvoices' => fn ($query) => $query
                ->with([
                    'recurringBill:id,name',
                    'payments' => fn ($payments) => $payments->select([
                        'id', 'bill_invoice_id', 'amount', 'paid_at', 'payment_method', 'notes',
                    ]),
                ])
                ->withSum('payments', 'amount')
                ->orderByDesc('due_date'),
            'classAttendances' => fn ($query) => $query
                ->with([
                    'session.class:class_id,title',
                    'session.studyGroup:id,name',
                    'session.tentor:id,name',
                ])
                ->orderByDesc('check_in_at')
                ->orderByDesc('created_at'),
            'classAccess' => fn ($query) => $query
                ->with('class:class_id,title')
                ->orderByDesc('created_at'),
        ]);

        $attendanceSummary = [
            'total' => $user->classAttendances->count(),
            'present' => $user->classAttendances->whereIn('status', ['present', 'late'])->count(),
            'late' => $user->classAttendances->where('status', 'late')->count(),
            'absent' => $user->classAttendances->where('status', 'absent')->count(),
            'excused' => $user->classAttendances->where('status', 'excused')->count(),
        ];
        $attendanceSummary['rate'] = $attendanceSummary['total'] > 0
            ? round(($attendanceSummary['present'] / $attendanceSummary['total']) * 100)
            : null;

        $paymentSummary = [
            'paid' => (int) $user->payments
                ->where('status', Payment::STATUS_SUCCESS)
                ->sum(fn (Payment $payment) => $payment->paid_amount),
            'outstanding' => (int) $user->billInvoices
                ->whereIn('status', ['unpaid', 'overdue'])
                ->sum(fn ($invoice) => $invoice->remaining_amount),
        ];

        return view('admin.pages.user.show', compact('user', 'attendanceSummary', 'paymentSummary'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roleOptions = $this->getRoleOptions();
        $destinationCategories = $this->getDestinationCategories();

        return view('admin.pages.user.create', [
            'user' => $user,
            'roleOptions' => $roleOptions,
            'destinationCategories' => $destinationCategories,
        ]);
    }

    public function update(
        Request $request,
        $id,
        ParticipantDestinationSelectionService $destinationSelectionService,
        TutorProfileService $tutorProfileService
    ) {
        $roleOptions = $this->getRoleOptions();
        $roleSlugs = array_keys($roleOptions);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', new SafeName],
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'username' => 'required|string|max:255|unique:users,username,'.$id,
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:aktif,nonaktif',
            'role' => ['required', Rule::in($roleSlugs)],
        ]);
        $destinationPayload = $destinationSelectionService->validate(
            $request,
            $validated['role'] === 'user' && $destinationSelectionService->isRequired()
        );

        $user = DB::transaction(function () use ($id, $validated, $destinationPayload, $tutorProfileService): User {
            $user = User::findOrFail($id);
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'status' => $validated['status'],
                'role' => $validated['role'],
                ...$destinationPayload,
            ]);

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            $role = Role::where('slug', $user->role)->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }
            $tutorProfileService->sync($user);

            return $user;
        });

        return redirect()->route('admin.user.index', $request->query())
            ->with('success', 'User berhasil diperbarui');
    }

    private function exportUserFields(array $userColumns, array $packageColumns = []): array
    {
        $labels = [
            'name' => 'Nama Lengkap',
            'email' => 'Email',
            'email_verified_at' => 'Email Terverifikasi Pada',
            'username' => 'Username',
            'role' => 'Role',
            'status' => 'Status',
            'admin_expires_at' => 'Akses Admin Berakhir',
            'affiliate_code' => 'Kode Affiliate',
            'referred_at' => 'Direferensikan Pada',
            'participant_destination_institution_name' => 'Nama Institusi Tujuan',
            'participant_destination_program_name' => 'Nama Program Tujuan',
            'created_at' => 'Dibuat Pada',
            'updated_at' => 'Diperbarui Pada',
        ];

        $fields = collect($userColumns)
            ->map(fn (string $column) => [
                'key' => $column,
                'label' => $labels[$column] ?? str($column)->replace('_', ' ')->headline()->toString(),
                'type' => 'column',
            ]);

        $fields = $fields->merge([
            ['key' => 'role_label', 'label' => 'Label Role', 'type' => 'computed'],
            ['key' => 'participant_destination_display', 'label' => 'Tujuan Peserta', 'type' => 'computed'],
            ['key' => 'participant_destination_category_name', 'label' => 'Kategori Tujuan', 'type' => 'computed'],
            ['key' => 'participant_destination_parent_name', 'label' => 'Kategori Induk Tujuan', 'type' => 'computed'],
            ['key' => 'referred_by_name', 'label' => 'Nama Referrer', 'type' => 'computed'],
            ['key' => 'referred_by_email', 'label' => 'Email Referrer', 'type' => 'computed'],
        ]);

        foreach ($packageColumns as $package) {
            $fields->push([
                'key' => 'package_'.$package['id'],
                'label' => $package['name'],
                'type' => 'computed',
            ]);
        }

        return $fields->values()->all();
    }

    private function exportPackageColumns(): array
    {
        if (! Schema::hasTable('packages')) {
            return [];
        }

        return DB::table('packages')
            ->orderBy('name')
            ->get(['package_id', 'name'])
            ->map(fn ($package) => [
                'id' => (int) $package->package_id,
                'name' => (string) $package->name,
            ])
            ->all();
    }

    private function writeUsersXlsx(string $targetPath, array $fields, array $userColumns, array $roleOptions): void
    {
        $sheetPath = tempnam(sys_get_temp_dir(), 'users-sheet-');
        $sheet = fopen($sheetPath, 'w');

        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetData>');
        $this->writeXlsxRow($sheet, 1, array_column($fields, 'label'), true);

        $selects = array_map(
            fn (string $column) => 'users.'.$column.' as '.$column,
            $userColumns
        );

        $rowNumber = 2;
        User::query()
            ->select([
                'users.id as chunk_id',
                ...$selects,
                'destination_categories.name as participant_destination_category_name',
                'destination_parent_categories.name as participant_destination_parent_name',
                'referrers.name as referred_by_name',
                'referrers.email as referred_by_email',
            ])
            ->leftJoin('participant_destination_categories as destination_categories', 'destination_categories.id', '=', 'users.participant_destination_category_id')
            ->leftJoin('participant_destination_categories as destination_parent_categories', 'destination_parent_categories.id', '=', 'destination_categories.parent_id')
            ->leftJoin('users as referrers', 'referrers.id', '=', 'users.referred_by_user_id')
            ->where('users.role', '!=', 'super_admin')
            ->chunkById(1000, function ($users) use ($sheet, $fields, $roleOptions, &$rowNumber) {
                $packageIdsByUserId = $this->exportPackageIdsByUserId(
                    $users->pluck('chunk_id')->map(fn ($id) => (int) $id)->all()
                );

                foreach ($users as $user) {
                    $this->writeXlsxRow($sheet, $rowNumber, array_map(
                        fn (array $field) => $this->exportUserFieldValue($user, $field['key'], $roleOptions, $packageIdsByUserId),
                        $fields
                    ));
                    $rowNumber++;
                }
            }, 'users.id', 'chunk_id');

        fwrite($sheet, '</sheetData></worksheet>');
        fclose($sheet);

        $zip = new ZipArchive;
        $zip->open($targetPath, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypesXml());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
        $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
        $zip->close();

        @unlink($sheetPath);
    }

    private function exportPackageIdsByUserId(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return DB::table('user_package_access')
            ->whereIn('user_package_access.user_id', $userIds)
            ->orderBy('user_package_access.user_id')
            ->get([
                'user_package_access.user_id',
                'user_package_access.package_id',
            ])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('package_id')->map(fn ($id) => (int) $id)->flip()->all())
            ->all();
    }

    private function exportUserFieldValue(object $user, string $key, array $roleOptions, array $packageIdsByUserId = []): string
    {
        if (str_starts_with($key, 'package_')) {
            $packageId = (int) str_replace('package_', '', $key);
            $packageIds = $packageIdsByUserId[(int) $user->chunk_id] ?? [];

            return array_key_exists($packageId, $packageIds) ? 'Ya' : '';
        }

        return match ($key) {
            'role_label' => $roleOptions[$user->role] ?? str($user->role ?? '')->headline()->toString(),
            'participant_destination_display' => $this->exportDestinationDisplay($user),
            default => $this->exportCell($user->{$key} ?? null),
        };
    }

    private function exportDestinationDisplay(object $user): string
    {
        $categoryName = trim((string) ($user->participant_destination_category_name ?? ''));
        $parentName = trim((string) ($user->participant_destination_parent_name ?? ''));

        if ($categoryName !== '') {
            return $parentName !== '' ? $parentName.' - '.$categoryName : $categoryName;
        }

        $institutionName = trim((string) ($user->participant_destination_institution_name ?? ''));
        $programName = trim((string) ($user->participant_destination_program_name ?? ''));

        if ($institutionName === '' && $programName === '') {
            return '';
        }

        return $programName !== '' ? $institutionName.' - '.$programName : $institutionName;
    }

    private function exportCell(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        $value = (string) ($value ?? '');

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function writeXlsxRow($handle, int $rowNumber, array $values, bool $isHeader = false): void
    {
        fwrite($handle, '<row r="'.$rowNumber.'">');

        foreach (array_values($values) as $index => $value) {
            $cellReference = $this->excelColumnName($index + 1).$rowNumber;
            $style = $isHeader ? ' s="1"' : '';
            fwrite($handle, '<c r="'.$cellReference.'" t="inlineStr"'.$style.'><is><t>'.$this->escapeXml($this->exportCell($value)).'</t></is></c>');
        }

        fwrite($handle, '</row>');
    }

    private function excelColumnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xlsxContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function xlsxRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function xlsxWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Data User" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function xlsxWorkbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function xlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            .'</styleSheet>';
    }

    private function getRoleOptions(): array
    {
        return Role::query()
            ->whereNotIn('slug', ['super_admin'])
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->toArray();
    }

    private function getDestinationCategories()
    {
        return ParticipantDestinationCategory::query()
            ->root()
            ->active()
            ->with(['activeChildren'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || count($ids) === 0) {
            return redirect()->route('admin.user.index')
                ->with('error', 'Pilih minimal satu user untuk dihapus.');
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return redirect()->route('admin.user.index')
                ->with('error', 'Data user tidak valid.');
        }

        $deleted = User::where('role', '!=', 'super_admin')
            ->whereIn('id', $ids)
            ->delete();

        if ($deleted === 0) {
            return redirect()->route('admin.user.index')
                ->with('error', 'Tidak ada user yang dihapus.');
        }

        return redirect()->route('admin.user.index')
            ->with('success', "{$deleted} user berhasil dihapus.");
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil dihapus');
    }

    public function report($id)
    {
        $user = User::with([
            'userAnswers' => function ($query) {
                $query->where('status', 'completed')
                    ->with(['tryout', 'userAnswerDetails'])
                    ->orderBy('created_at', 'desc');
            },
        ])->findOrFail($id);

        $completedTryouts = $user->userAnswers->where('status', 'completed');
        $totalTryouts = $completedTryouts->count();
        $avgScore = $completedTryouts->avg('score') ?? 0;

        $recentTryouts = $completedTryouts->take(5)->map(function ($answer) {
            return [
                'name' => $answer->tryout->name ?? 'Unknown Tryout',
                'score' => round($answer->score ?? 0, 1),
                'date' => Carbon::parse($answer->finished_at ?? $answer->created_at),
                'is_passed' => $answer->is_passed ?? false,
            ];
        });

        $totalStudyMinutes = $completedTryouts->sum(function ($answer) {
            if ($answer->started_at && $answer->finished_at) {
                return Carbon::parse($answer->started_at)->diffInMinutes(Carbon::parse($answer->finished_at));
            }

            return optional($answer->tryoutDetail)->duration ?? 60;
        });

        $totalStudyHours = round($totalStudyMinutes / 60, 1);

        $certificates = collect();
        $activities = collect();

        foreach ($completedTryouts->take(4) as $answer) {
            $activities->push([
                'type' => 'tryout',
                'text' => 'Menyelesaikan tryout '.($answer->tryout->name ?? 'Unknown').' dengan skor '.round($answer->score ?? 0, 1),
                'icon' => 'ri-file-list-line',
                'color' => 'blue',
                'date' => Carbon::parse($answer->finished_at ?? $answer->created_at),
            ]);
        }

        $activities->push([
            'type' => 'login',
            'text' => 'Login ke sistem',
            'icon' => 'ri-login-box-line',
            'color' => 'green',
            'date' => Carbon::now()->subHours(2),
        ]);

        $activities = $activities->sortByDesc('date')->take(8);

        $statistics = [
            'total_tryouts' => $totalTryouts,
            'avg_score' => round($avgScore, 1),
            'total_certificates' => $certificates->count(),
            'study_hours' => $totalStudyHours,
        ];

        return view('admin.pages.user.report', compact(
            'user',
            'statistics',
            'recentTryouts',
            'certificates',
            'activities'
        ));
    }

    public function loginAs($id)
    {
        $user = User::findOrFail($id);

        // Pastikan yang login adalah admin
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('admin.user.index')
                ->with('error', 'Unauthorized access.');
        }

        // Simpan admin ID dan info ke session
        session([
            'admin_login_as' => Auth::id(),
            'admin_name' => Auth::user()->name,
            'login_as_user_id' => $user->id,
            'login_as_user_name' => $user->name,
            'login_as_user_email' => $user->email,
        ]);

        // Login sebagai user
        Auth::login($user);

        return redirect()->route('user.dashboard.index');
    }

    public function logoutAs()
    {
        $adminId = session('admin_login_as');

        if (! $adminId) {
            return redirect()->route('login');
        }

        $admin = User::find($adminId);

        if (! $admin) {
            session()->forget([
                'admin_login_as',
                'admin_name',
                'login_as_user_id',
                'login_as_user_name',
                'login_as_user_email',
            ]);

            return redirect()->route('login');
        }

        // Hapus session admin_login_as
        session()->forget([
            'admin_login_as',
            'admin_name',
            'login_as_user_id',
            'login_as_user_name',
            'login_as_user_email',
        ]);

        // Login kembali sebagai admin
        Auth::login($admin);

        return redirect()->route('admin.user.index')
            ->with('success', 'Anda kembali login sebagai admin.');
    }
}
