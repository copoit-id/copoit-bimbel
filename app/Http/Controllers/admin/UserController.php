<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Question;
use App\Models\User;
use App\Models\UserAnswer;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    private array $maxScoreByDetail = [];

    private function roleOptions(): array
    {
        $roles = User::query()
            ->select('role')
            ->whereNotNull('role')
            ->distinct()
            ->orderBy('role')
            ->pluck('role')
            ->filter()
            ->values();

        return $roles
            ->prepend('admin')
            ->prepend('user')
            ->unique()
            ->mapWithKeys(function ($role) {
                return [$role => ucfirst(str_replace('_', ' ', $role))];
            })
            ->toArray();
    }

    public function index()
    {
        $users = User::query()
            ->latest()
            ->paginate(10);

        $roles = User::query()
            ->select('role')
            ->whereNotNull('role')
            ->distinct()
            ->orderBy('role')
            ->pluck('role');

        return view('admin.pages.user.index', compact('users', 'roles'));
    }

    public function create()
    {
        return view('admin.pages.user.create', [
            'user' => null,
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'status' => 'required|in:aktif,nonaktif',
            'role' => 'required|string|max:100',
            'is_devisadia_student' => 'boolean',
            'is_premium_member' => 'boolean',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'] ?? 'aktif',
            'role' => $validated['role'],
            'is_devisadia_student' => $request->boolean('is_devisadia_student', false),
            'is_premium_member' => $request->boolean('is_premium_member', false),
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User created successfully.');
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.pages.user.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.pages.user.create', [
            'user' => $user,
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:aktif,nonaktif',
            'role' => 'required|string|max:100',
            'is_devisadia_student' => 'boolean',
            'is_premium_member' => 'boolean',
        ]);

        $user = User::findOrFail($id);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'status' => $validated['status'],
            'role' => $validated['role'],
            'is_devisadia_student' => $request->boolean('is_devisadia_student', false),
            'is_premium_member' => $request->boolean('is_premium_member', false),
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil diperbarui');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || count($ids) === 0) {
            return redirect()->route('admin.user.index')
                ->with('error', 'Pilih minimal satu user untuk dihapus.');
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return redirect()->route('admin.user.index')
                ->with('error', 'Data user tidak valid.');
        }

        $deleted = User::where('role', 'user')
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

    /**
     * Remove every tryout attempt belonging to a participant so they can start over.
     * User answer details are removed by the database cascade.
     */
    public function resetTryoutAttempts($id)
    {
        $user = User::findOrFail($id);

        if ($user->role !== 'user') {
            return redirect()->route('admin.user.index')
                ->with('error', 'Reset attempt hanya dapat dilakukan untuk peserta.');
        }

        $attempts = UserAnswer::where('user_id', $user->id)
            ->select('tryout_id', 'attempt_token')
            ->distinct()
            ->get()
            ->count();

        if ($attempts === 0) {
            return redirect()->route('admin.user.index')
                ->with('error', "{$user->name} belum memiliki data attempt tryout.");
        }

        DB::transaction(function () use ($user) {
            UserAnswer::where('user_id', $user->id)->delete();
        });

        return redirect()->route('admin.user.index')
            ->with('success', "{$attempts} attempt tryout milik {$user->name} berhasil direset.");
    }

    public function report($id)
    {
        return view('admin.pages.user.report', $this->reportData($id));
    }

    public function downloadReport($id)
    {
        $data = $this->reportData($id);
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.pages.user.report-pdf', $data)->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf(
            'laporan-aktivitas-%s-%s.pdf',
            Str::slug($data['user']->name),
            now()->format('Ymd-His')
        );

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadReportExcel($id)
    {
        $data = $this->reportData($id);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Laporan Aktivitas ' . $data['user']->name);

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Ringkasan');
        $summarySheet->fromArray([
            ['LAPORAN AKTIVITAS PENGGUNA'],
            ['Nama', $data['user']->name],
            ['Email', $data['user']->email],
            ['Bergabung', $data['user']->created_at->format('d M Y')],
            ['Dibuat pada', now('Asia/Jakarta')->format('d M Y, H:i') . ' WIB'],
            [],
            ['Total Tryout', 'Rata-rata Nilai', 'Sertifikat', 'Waktu Belajar (jam)'],
            [
                $data['statistics']['total_tryouts'],
                $data['statistics']['avg_score'],
                $data['statistics']['total_certificates'],
                $data['statistics']['study_hours'],
            ],
        ], null, 'A1');
        $summarySheet->mergeCells('A1:D1');
        $summarySheet->getStyle('A1:D1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
        $summarySheet->getStyle('A1:D1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
        $summarySheet->getStyle('A7:D7')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $summarySheet->getStyle('A7:D7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('312E81');
        $summarySheet->getStyle('A1:D8')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach (range('A', 'D') as $column) {
            $summarySheet->getColumnDimension($column)->setAutoSize(true);
        }

        $historySheet = $spreadsheet->createSheet();
        $historySheet->setTitle('Riwayat Tryout');
        $historySheet->fromArray([
            ['Tanggal Selesai', 'Tryout', 'Subtest', 'Nilai', 'Jawaban Benar', 'Total Soal', 'Durasi (menit)', 'Status'],
        ], null, 'A1');
        foreach ($data['tryoutHistory'] as $tryout) {
            $historySheet->fromArray([[
                $tryout['date']->format('d M Y H:i') . ' WIB',
                $tryout['name'],
                $tryout['section'] ?? '-',
                $tryout['score'],
                $tryout['correct_answers'],
                $tryout['total_questions'],
                $tryout['duration'] ?? '-',
                $tryout['is_passed'] ? 'Lulus' : 'Belum lulus',
            ]], null, 'A' . ($historySheet->getHighestRow() + 1));
        }
        $this->styleExportSheet($historySheet, 'A1:H1');

        $certificateSheet = $spreadsheet->createSheet();
        $certificateSheet->setTitle('Sertifikat');
        $certificateSheet->fromArray([['Nama Sertifikat', 'Nomor Sertifikat', 'Tanggal Terbit', 'Status']], null, 'A1');
        foreach ($data['certificates'] as $certificate) {
            $certificateSheet->fromArray([[
                $certificate->certificate_name,
                $certificate->certificate_number,
                optional($certificate->issued_date)->format('d M Y') ?? '-',
                $certificate->status_text,
            ]], null, 'A' . ($certificateSheet->getHighestRow() + 1));
        }
        $this->styleExportSheet($certificateSheet, 'A1:D1');

        $timelineSheet = $spreadsheet->createSheet();
        $timelineSheet->setTitle('Timeline Aktivitas');
        $timelineSheet->fromArray([['Tanggal', 'Jenis Aktivitas', 'Aktivitas']], null, 'A1');
        foreach ($data['activities'] as $activity) {
            $timelineSheet->fromArray([[
                $activity['date']->format('d M Y H:i') . ' WIB',
                ucfirst($activity['type']),
                $activity['text'],
            ]], null, 'A' . ($timelineSheet->getHighestRow() + 1));
        }
        $this->styleExportSheet($timelineSheet, 'A1:C1');

        $filename = sprintf(
            'laporan-aktivitas-%s-%s.xlsx',
            Str::slug($data['user']->name),
            now()->format('Ymd-His')
        );
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function styleExportSheet($sheet, string $headerRange): void
    {
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($headerRange);

        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function reportData($id): array
    {
        $user = User::with([
            'userAnswers' => function ($query) {
                $query->where('status', 'completed')
                    ->with([
                        'tryout',
                        'tryoutDetail',
                        'userAnswerDetails.question',
                        'userAnswerDetails.questionOption',
                    ])
                    ->orderByDesc('finished_at')
                    ->orderByDesc('created_at');
            }
        ])->findOrFail($id);

        $completedTryouts = $user->userAnswers->where('status', 'completed');
        $totalTryouts = $completedTryouts->count();
        $avgScore = $completedTryouts->avg('score') ?? 0;

        $tryoutHistory = $completedTryouts->map(function ($answer) {
            $finishedAt = $answer->finished_at ?? $answer->created_at;
            $duration = $answer->started_at && $answer->finished_at
                ? (int) round($answer->started_at->diffInSeconds($answer->finished_at) / 60)
                : null;

            return [
                'id' => $answer->user_answer_id,
                'name' => $answer->tryout->name ?? 'Unknown Tryout',
                'score' => round($answer->score ?? 0, 1),
                'date' => Carbon::parse($finishedAt),
                'is_passed' => $this->isPassedByPassingGrade($answer),
                'duration' => $duration,
                'correct_answers' => $answer->correct_answers ?? 0,
                'total_questions' => $answer->userAnswerDetails->count(),
                'section' => $answer->tryoutDetail->name ?? $answer->tryoutDetail->title ?? null,
            ];
        });

        $totalStudyMinutes = $completedTryouts->sum(function ($answer) {
            if ($answer->started_at && $answer->finished_at) {
                return (int) round(Carbon::parse($answer->started_at)->diffInSeconds(Carbon::parse($answer->finished_at)) / 60);
            }

            return optional($answer->tryoutDetail)->duration ?? 60;
        });

        $totalStudyHours = (int) round($totalStudyMinutes / 60);

        // Certificate ownership is stored in metadata.user_id, not a certificates.user_id column.
        $certificates = Certificate::query()
            ->whereJsonContains('metadata->user_id', (string) $user->id)
            ->orderByDesc('issued_date')
            ->get();

        $activities = collect([[
            'type' => 'account',
            'text' => 'Bergabung dengan platform',
            'icon' => 'ri-user-add-line',
            'color' => 'indigo',
            'date' => Carbon::parse($user->created_at),
        ]]);

        foreach ($tryoutHistory as $tryout) {
            $activities->push([
                'type' => 'tryout',
                'text' => 'Menyelesaikan tryout ' . $tryout['name'] . ' dengan skor ' . $tryout['score'],
                'icon' => 'ri-file-list-line',
                'color' => 'blue',
                'date' => $tryout['date'],
            ]);
        }

        foreach ($certificates as $certificate) {
            $activities->push([
                'type' => 'certificate',
                'text' => 'Sertifikat "' . $certificate->certificate_name . '" diterbitkan',
                'icon' => 'ri-award-line',
                'color' => 'amber',
                'date' => Carbon::parse($certificate->issued_date ?? $certificate->created_at),
            ]);
        }

        $activities = $activities->sortByDesc('date')->values();

        $statistics = [
            'total_tryouts' => $totalTryouts,
            'avg_score' => round($avgScore, 1),
            'total_certificates' => $certificates->count(),
            'study_hours' => $totalStudyHours
        ];

        return compact(
            'user',
            'statistics',
            'tryoutHistory',
            'certificates',
            'activities'
        );
    }

    private function isPassedByPassingGrade($answer): bool
    {
        $tryout = $answer->tryout;
        if ($tryout?->requiresIrtScoring() || $tryout?->is_toefl) {
            return (bool) $answer->is_passed;
        }

        $type = $answer->tryoutDetail?->type_subtest;
        $rawScore = $this->calculateRawScore($answer, $type);
        $maxScore = $this->getMaxPossibleScoreForDetail($answer->tryout_detail_id, $type);
        $passingScore = $answer->tryoutDetail?->passing_score ?? $this->getDefaultPassingScore($type);

        if (($answer->tryoutDetail?->passing_type ?? 'score') === 'percentage') {
            return $maxScore > 0 && (($rawScore / $maxScore) * 100) >= $passingScore;
        }

        return $rawScore >= $passingScore;
    }

    private function calculateRawScore($answer, ?string $type): float
    {
        $totalScore = 0.0;

        foreach ($answer->userAnswerDetails as $detail) {
            $question = $detail->question;
            if (! $question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $pendingReview = (bool) ($answerMeta['pending_review'] ?? false);

            switch ($questionType) {
                case 'matching':
                    $weight = (float) ($question->default_weight ?? 1);
                    if ($weight <= 0) {
                        $pairs = isset($question->metadata['matching_pairs']) && is_array($question->metadata['matching_pairs'])
                            ? count($question->metadata['matching_pairs'])
                            : 1;
                        $weight = max(1, $pairs);
                    }
                    $totalScore += $detail->is_correct ? $weight : 0;
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        continue 2;
                    }
                    $weight = (float) ($question->default_weight ?? 1);
                    $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    break;

                case 'audio':
                    continue 2;

                default:
                    if (! $detail->questionOption) {
                        continue 2;
                    }

                    $weight = (float) ($detail->questionOption->weight ?? 0);
                    $fallbackWeight = match ($type) {
                        'twk', 'tiu' => 5,
                        'writing', 'reading', 'listening' => 10,
                        default => 1,
                    };
                    $totalScore += $weight > 0 ? $weight : ($type === 'tkp' ? 1 : ($detail->is_correct ? $fallbackWeight : 0));
                    break;
            }
        }

        return $totalScore;
    }

    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, ?string $type): float
    {
        if (isset($this->maxScoreByDetail[$tryoutDetailId])) {
            return $this->maxScoreByDetail[$tryoutDetailId];
        }

        $questions = Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();
        $total = 0.0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'matching':
                    $weight = (float) ($question->default_weight ?? 0);
                    if ($weight <= 0) {
                        $pairs = isset($question->metadata['matching_pairs']) && is_array($question->metadata['matching_pairs'])
                            ? count($question->metadata['matching_pairs'])
                            : 1;
                        $weight = max(1, $pairs);
                    }
                    $total += $weight;
                    break;

                case 'short_answer':
                case 'essay':
                    $weight = (float) ($question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'audio':
                    break;

                default:
                    $maxWeight = (float) ($question->questionOptions->max('weight') ?? 0);
                    $fallbackWeight = match ($type) {
                        'twk', 'tiu' => 5,
                        'writing', 'reading', 'listening' => 10,
                        default => 1,
                    };
                    $total += $maxWeight > 0 ? $maxWeight : $fallbackWeight;
                    break;
            }
        }

        return $this->maxScoreByDetail[$tryoutDetailId] = $total;
    }

    private function getDefaultPassingScore(?string $type): int
    {
        return match ($type) {
            'word', 'excel', 'ppt' => 70,
            'teknis', 'social culture', 'management', 'interview' => 65,
            default => 60,
        };
    }
}
