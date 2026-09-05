<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\ScheduleBookingRequest;
use App\Models\StudyGroup;
use App\Models\Tentor;
use App\Models\User;
use App\Models\UserPackageAcces;
use App\Services\ScheduleBookingService;
use App\Services\TutorReviewService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleBookingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $groupPackages = Package::query()
            ->select(['package_id', 'name', 'price'])
            ->active()
            ->where('is_displayed', true)
            ->whereHas('bookingRule', function ($query): void {
                $query->where('is_enabled', true)
                    ->whereIn('learning_mode', ['group', 'both']);
            })
            ->with([
                'bookingRule:id,package_id,min_participants,max_participants,payment_deadline_hours',
                'bookingRule.priceTiers:id,package_booking_rule_id,participant_count,price_per_person',
            ])
            ->orderBy('name')
            ->get();
        $studyGroups = StudyGroup::query()
            ->whereNotNull('package_id')
            ->with([
                'package:package_id,name',
                'members.user:id,name,email',
                'members.invoice:id,invoice_number,amount,status,due_date',
            ])
            ->whereHas('members', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->limit(20)
            ->get();
        $requestedPackageId = $request->integer('package_id');
        $accesses = UserPackageAcces::query()
            ->with([
                'package:package_id,name',
                'package.bookingRule.tutors:id,name,expertise',
                'package.bookingRule.priceTiers',
            ])
            ->where('user_id', $user->id)
            ->where(function ($query) use ($requestedPackageId): void {
                $query->where(function ($activeQuery): void {
                    $activeQuery->active();
                })->orWhere(function ($pendingQuery) use ($requestedPackageId): void {
                    $pendingQuery->where('status', 'pending')
                        ->where('requirement_status', 'pending')
                        ->when($requestedPackageId, fn ($query) => $query->where('package_id', $requestedPackageId))
                        ->whereHas('package', fn ($query) => $query->where('enrollment_mode', Package::ENROLLMENT_PROGRAM));
                });
            })
            ->whereHas('package.bookingRule', fn ($query) => $query->where('is_enabled', true))
            ->orderByDesc('created_at')
            ->get();
        $tutorDirectory = Tentor::query()
            ->active()
            ->whereHas('user', fn ($query) => $query->where('role', 'tutor'))
            ->select([
                'id',
                'name',
                'expertise',
                'bio',
                'profile_photo_path',
                'experience_years',
            ])
            ->withCount('visibleReviews')
            ->withAvg('visibleReviews', 'rating')
            ->orderBy('name')
            ->get()
            ->keyBy('id');
        $allTutors = $tutorDirectory->values();
        $quotaUsage = ScheduleBookingRequest::query()
            ->whereIn('user_package_access_id', $accesses->pluck('user_package_access_id'))
            ->whereNull('study_group_id')
            ->consumesQuota()
            ->selectRaw('user_package_access_id, COUNT(*) as total')
            ->groupBy('user_package_access_id')
            ->pluck('total', 'user_package_access_id');
        $groupQuotaUsage = ScheduleBookingRequest::query()
            ->whereIn('study_group_id', $studyGroups->pluck('id'))
            ->consumesQuota()
            ->selectRaw('study_group_id, COUNT(*) as total')
            ->groupBy('study_group_id')
            ->pluck('total', 'study_group_id');
        $accessOptions = $accesses->mapWithKeys(function (UserPackageAcces $access) use (
            $allTutors,
            $quotaUsage,
            $tutorDirectory,
            $studyGroups,
            $groupQuotaUsage
        ): array {
            $rule = $access->package->bookingRule;
            $tutors = $rule->allow_all_tutors
                ? $allTutors
                : $rule->tutors
                    ->map(fn (Tentor $tutor) => $tutorDirectory->get($tutor->id))
                    ->filter()
                    ->values();

            return [
                $access->user_package_access_id => [
                    'package_name' => $access->package->name,
                    'duration_minutes' => $rule->duration_minutes,
                    'min_notice_hours' => $rule->min_notice_hours,
                    'max_advance_days' => $rule->max_advance_days,
                    'quota' => $rule->session_quota,
                    'used' => (int) $quotaUsage->get($access->user_package_access_id, 0),
                    'learning_mode' => $rule->learning_mode,
                    'study_groups' => $studyGroups
                        ->where('package_id', $access->package_id)
                        ->where('organizer_user_id', $access->user_id)
                        ->where('status', StudyGroup::STATUS_ACTIVE)
                        ->map(fn (StudyGroup $group): array => [
                            'id' => $group->id,
                            'label' => $group->name,
                            'participants' => $group->target_participants,
                            'used' => (int) $groupQuotaUsage->get($group->id, 0),
                        ])->values()->all(),
                    'tutors' => $tutors->map(fn (Tentor $tutor): array => [
                        'id' => $tutor->id,
                        'name' => $tutor->name,
                        'expertise' => $tutor->expertise,
                        'bio' => $tutor->bio,
                        'photo_url' => $tutor->profile_photo_path
                            ? Storage::url($tutor->profile_photo_path)
                            : null,
                        'experience_years' => $tutor->experience_years,
                        'rating' => $tutor->visible_reviews_avg_rating
                            ? round((float) $tutor->visible_reviews_avg_rating, 1)
                            : null,
                        'review_count' => (int) $tutor->visible_reviews_count,
                        'profile_url' => route('user.booking.tutor.show', $tutor),
                    ])->values()->all(),
                ],
            ];
        });
        $requestedAccessId = $request->integer('access');
        $initialAccessId = $accesses->contains(
            'user_package_access_id',
            $requestedAccessId
        )
            ? $requestedAccessId
            : $accesses->first()?->user_package_access_id;
        $bookings = ScheduleBookingRequest::query()
            ->with([
                'package:package_id,name',
                'tentor:id,name,expertise',
                'session:id,start_at,end_at,status,location,meeting_url',
                'review:id,schedule_booking_request_id,rating,comment',
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(\App\Support\Pagination::perPage(10))
            ->withQueryString();

        return view('user.pages.booking.index', compact(
            'accesses',
            'accessOptions',
            'initialAccessId',
            'bookings',
            'groupPackages',
            'studyGroups',
        ));
    }

    public function showTutor(Request $request, Tentor $tentor): View
    {
        abort_unless(
            $tentor->is_active && $this->studentCanViewTutor($request, $tentor),
            404
        );

        $tentor->loadCount('visibleReviews')
            ->loadAvg('visibleReviews', 'rating');
        $reviews = $tentor->visibleReviews()
            ->with([
                'user:id,name',
                'booking.package:package_id,name',
            ])
            ->latest()
            ->paginate(\App\Support\Pagination::perPage(10))
            ->withQueryString();

        return view('user.pages.booking.tutor', compact('tentor', 'reviews'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_package_access_id' => ['required', 'integer', 'exists:user_package_access,user_package_access_id'],
            'tentor_id' => ['required', 'integer', 'exists:tentors,id'],
            'requested_start_at' => ['required', 'date'],
            'student_notes' => ['nullable', 'string', 'max:1000'],
            'study_group_id' => ['nullable', 'integer', 'exists:study_groups,id'],
        ]);
        $requestedStart = Carbon::parse($validated['requested_start_at'])->startOfMinute();

        DB::transaction(function () use ($request, $validated, $requestedStart): void {
            User::query()
                ->whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();
            $access = UserPackageAcces::query()
                ->with('package:package_id,name')
                ->whereKey($validated['user_package_access_id'])
                ->where('user_id', $request->user()->id)
                ->where(function ($query): void {
                    $query->where(function ($activeQuery): void {
                        $activeQuery->where('status', 'active')
                            ->where(function ($dateQuery): void {
                                $dateQuery->whereNull('end_date')->orWhere('end_date', '>', now());
                            });
                    })->orWhere(function ($pendingQuery): void {
                        $pendingQuery->where('status', 'pending')
                            ->where('requirement_status', 'pending')
                            ->whereHas('package', fn ($packageQuery) => $packageQuery->where('enrollment_mode', Package::ENROLLMENT_PROGRAM));
                    });
                })
                ->lockForUpdate()
                ->first();
            $rule = $access
                ? $access->package->bookingRule()
                    ->where('is_enabled', true)
                    ->lockForUpdate()
                    ->first()
                : null;

            if (! $access || ! $rule) {
                throw ValidationException::withMessages([
                    'user_package_access_id' => 'Paket tidak memiliki akses booking aktif.',
                ]);
            }

            $studyGroup = null;
            if (! empty($validated['study_group_id'])) {
                $studyGroup = StudyGroup::query()
                    ->whereKey($validated['study_group_id'])
                    ->where('package_id', $access->package_id)
                    ->where('organizer_user_id', $request->user()->id)
                    ->where('status', StudyGroup::STATUS_ACTIVE)
                    ->lockForUpdate()
                    ->first();
                if (! $studyGroup) {
                    throw ValidationException::withMessages([
                        'study_group_id' => 'Rombel tidak valid atau belum siap dijadwalkan.',
                    ]);
                }
            } elseif ($rule->learning_mode === 'group') {
                throw ValidationException::withMessages([
                    'study_group_id' => 'Pilih rombel yang sudah aktif untuk paket ini.',
                ]);
            }

            if ($access->end_date && $requestedStart->gt($access->end_date)) {
                throw ValidationException::withMessages([
                    'requested_start_at' => 'Waktu booking melewati masa aktif paket.',
                ]);
            }

            $tutorAllowed = $rule->allow_all_tutors
                ? Tentor::active()
                    ->whereHas('user', fn ($query) => $query->where('role', 'tutor'))
                    ->whereKey($validated['tentor_id'])
                    ->exists()
                : $rule->tutors()
                    ->where('tentors.is_active', true)
                    ->whereHas('user', fn ($query) => $query->where('role', 'tutor'))
                    ->whereKey($validated['tentor_id'])
                    ->exists();

            if (! $tutorAllowed) {
                throw ValidationException::withMessages([
                    'tentor_id' => 'Tutor tidak tersedia untuk paket ini.',
                ]);
            }

            $pendingCount = ScheduleBookingRequest::query()
                ->when(
                    $studyGroup,
                    fn ($query) => $query->where('study_group_id', $studyGroup->id),
                    fn ($query) => $query->where('user_package_access_id', $access->user_package_access_id)
                        ->whereNull('study_group_id')
                )
                ->awaitingResponse()
                ->lockForUpdate()
                ->count();

            if ($pendingCount >= 3) {
                throw ValidationException::withMessages([
                    'booking' => 'Maksimal tiga permintaan booking dapat menunggu persetujuan.',
                ]);
            }

            $usedQuota = ScheduleBookingRequest::query()
                ->when(
                    $studyGroup,
                    fn ($query) => $query->where('study_group_id', $studyGroup->id),
                    fn ($query) => $query->where('user_package_access_id', $access->user_package_access_id)
                        ->whereNull('study_group_id')
                )
                ->consumesQuota()
                ->count();

            if ($usedQuota >= $rule->session_quota) {
                throw ValidationException::withMessages([
                    'booking' => 'Kuota booking pada paket ini sudah habis.',
                ]);
            }

            $duplicateExists = ScheduleBookingRequest::query()
                ->where('user_id', $request->user()->id)
                ->where('tentor_id', $validated['tentor_id'])
                ->where('requested_start_at', $requestedStart)
                ->awaitingResponse()
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'requested_start_at' => 'Permintaan pada tutor dan waktu yang sama sudah ada.',
                ]);
            }

            ScheduleBookingRequest::query()->create([
                'user_id' => $request->user()->id,
                'package_id' => $access->package_id,
                'user_package_access_id' => $access->user_package_access_id,
                'study_group_id' => $studyGroup?->id,
                'tentor_id' => $validated['tentor_id'],
                'requested_start_at' => $requestedStart,
                'requested_end_at' => $requestedStart->copy()->addMinutes($rule->duration_minutes),
                'status' => ScheduleBookingRequest::STATUS_PENDING,
                'student_notes' => $validated['student_notes'] ?? null,
            ]);
        }, 3);

        return redirect()
            ->route('user.booking.index')
            ->with('success', 'Permintaan booking berhasil dikirim ke tutor.');
    }

    public function acceptCounter(
        Request $request,
        ScheduleBookingRequest $booking,
        ScheduleBookingService $bookingService
    ): RedirectResponse {
        $booking->loadMissing('responder');
        $bookingService->acceptCounter($booking, $request->user());

        return redirect()
            ->route('user.booking.index')
            ->with('success', 'Usulan waktu tutor diterima dan jadwal berhasil dibuat.');
    }

    public function cancel(
        Request $request,
        ScheduleBookingRequest $booking,
        ScheduleBookingService $bookingService
    ): RedirectResponse {
        $bookingService->cancel($booking, $request->user());

        return redirect()
            ->route('user.booking.index')
            ->with('success', 'Permintaan booking berhasil dibatalkan.');
    }

    public function storeReview(
        Request $request,
        ScheduleBookingRequest $booking,
        TutorReviewService $reviewService
    ): RedirectResponse {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $reviewService->save(
            $booking,
            $request->user(),
            (int) $validated['rating'],
            $validated['comment'] ?? null
        );

        return redirect()
            ->route('user.booking.index')
            ->with('success', 'Penilaian untuk Tutor berhasil disimpan.');
    }

    private function studentCanViewTutor(Request $request, Tentor $tentor): bool
    {
        $hasBookingHistory = ScheduleBookingRequest::query()
            ->where('user_id', $request->user()->id)
            ->where('tentor_id', $tentor->id)
            ->exists();

        if ($hasBookingHistory) {
            return true;
        }

        return UserPackageAcces::query()
            ->where('user_id', $request->user()->id)
            ->active()
            ->whereHas('package.bookingRule', function ($query) use ($tentor): void {
                $query->where('is_enabled', true)
                    ->where(function ($query) use ($tentor): void {
                        $query->where('allow_all_tutors', true)
                            ->orWhereHas(
                                'tutors',
                                fn ($query) => $query->whereKey($tentor->id)
                            );
                    });
            })
            ->exists();
    }
}
