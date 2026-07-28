<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\ScheduleBookingRequest;
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
        $accesses = UserPackageAcces::query()
            ->with([
                'package:package_id,name',
                'package.bookingRule.tutors:id,name,expertise',
            ])
            ->where('user_id', $user->id)
            ->active()
            ->whereHas('package.bookingRule', fn ($query) => $query->where('is_enabled', true))
            ->orderByDesc('created_at')
            ->get();
        $tutorDirectory = Tentor::query()
            ->active()
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
            ->consumesQuota()
            ->selectRaw('user_package_access_id, COUNT(*) as total')
            ->groupBy('user_package_access_id')
            ->pluck('total', 'user_package_access_id');
        $accessOptions = $accesses->mapWithKeys(function (UserPackageAcces $access) use (
            $allTutors,
            $quotaUsage,
            $tutorDirectory
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
        $bookings = ScheduleBookingRequest::query()
            ->with([
                'package:package_id,name',
                'tentor:id,name,expertise',
                'session:id,start_at,end_at,status,location,meeting_url',
                'review:id,schedule_booking_request_id,rating,comment',
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('user.pages.booking.index', compact(
            'accesses',
            'accessOptions',
            'bookings',
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
            ->paginate(10)
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
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->whereNull('end_date')->orWhere('end_date', '>', now());
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

            $minimumStart = now()->addHours($rule->min_notice_hours);
            $maximumStart = now()->addDays($rule->max_advance_days)->endOfDay();

            if ($requestedStart->lt($minimumStart) || $requestedStart->gt($maximumStart)) {
                throw ValidationException::withMessages([
                    'requested_start_at' => "Waktu booking harus antara {$rule->min_notice_hours} jam hingga {$rule->max_advance_days} hari dari sekarang.",
                ]);
            }
            if ($access->end_date && $requestedStart->gt($access->end_date)) {
                throw ValidationException::withMessages([
                    'requested_start_at' => 'Waktu booking melewati masa aktif paket.',
                ]);
            }

            $tutorAllowed = $rule->allow_all_tutors
                ? Tentor::active()->whereKey($validated['tentor_id'])->exists()
                : $rule->tutors()
                    ->where('tentors.is_active', true)
                    ->whereKey($validated['tentor_id'])
                    ->exists();

            if (! $tutorAllowed) {
                throw ValidationException::withMessages([
                    'tentor_id' => 'Tutor tidak tersedia untuk paket ini.',
                ]);
            }

            $pendingCount = ScheduleBookingRequest::query()
                ->where('user_package_access_id', $access->user_package_access_id)
                ->awaitingResponse()
                ->lockForUpdate()
                ->count();

            if ($pendingCount >= 3) {
                throw ValidationException::withMessages([
                    'booking' => 'Maksimal tiga permintaan booking dapat menunggu persetujuan.',
                ]);
            }

            $usedQuota = ScheduleBookingRequest::query()
                ->where('user_package_access_id', $access->user_package_access_id)
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
