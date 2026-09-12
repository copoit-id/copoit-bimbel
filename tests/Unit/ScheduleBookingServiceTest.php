<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Models\PackageBookingRule;
use App\Models\ScheduleBookingRequest;
use App\Models\Tentor;
use App\Models\User;
use App\Models\UserPackageAcces;
use App\Services\ScheduleBookingService;
use App\Services\TutorReviewService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScheduleBookingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-28 09:00:00');
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_approval_atomically_creates_a_package_schedule_and_session(): void
    {
        [$booking, $tutorUser] = $this->booking();

        $approved = app(ScheduleBookingService::class)->approve(
            $booking,
            $tutorUser,
            Carbon::parse('2026-07-30 13:00:00'),
            [
                'location' => 'Ruang 2',
                'meeting_url' => 'https://meet.example.test/booking',
            ],
        );

        $this->assertSame(ScheduleBookingRequest::STATUS_APPROVED, $approved->status);
        $this->assertNotNull($approved->class_schedule_id);
        $this->assertNotNull($approved->class_session_id);
        $this->assertDatabaseHas('class_sessions', [
            'id' => $approved->class_session_id,
            'tentor_id' => $booking->tentor_id,
            'location' => 'Ruang 2',
        ]);
        $this->assertDatabaseHas('detail_packages', [
            'package_id' => $booking->package_id,
            'detailable_type' => 'schedule',
            'detailable_id' => $approved->class_schedule_id,
        ]);
        $this->assertDatabaseHas('classes', [
            'class_id' => $booking->package->bookingRule->fresh()->class_id,
            'is_displayed' => false,
        ]);
    }

    public function test_approval_rejects_an_overlapping_tutor_slot(): void
    {
        [$firstBooking, $tutorUser, $package, $tentor] = $this->booking();
        $service = app(ScheduleBookingService::class);
        $service->approve(
            $firstBooking,
            $tutorUser,
            Carbon::parse('2026-07-30 13:00:00'),
        );

        $secondStudent = $this->user('second-student', 'user');
        $secondAccess = $this->access($secondStudent, $package);
        $secondBooking = $this->pendingBooking(
            $secondStudent,
            $secondAccess,
            $package,
            $tentor,
            Carbon::parse('2026-07-30 13:30:00'),
        );

        try {
            $service->approve(
                $secondBooking,
                $tutorUser,
                Carbon::parse('2026-07-30 13:30:00'),
            );
            $this->fail('Overlapping tutor slot should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('scheduled_start_at', $exception->errors());
        }

        $this->assertSame(
            ScheduleBookingRequest::STATUS_PENDING,
            $secondBooking->fresh()->status,
        );
        $this->assertDatabaseCount('class_sessions', 1);
    }

    public function test_approval_consumes_quota_only_once_per_package_access(): void
    {
        [$firstBooking, $tutorUser, $package, $tentor, $access, $student] = $this->booking();
        $service = app(ScheduleBookingService::class);
        $service->approve(
            $firstBooking,
            $tutorUser,
            Carbon::parse('2026-07-30 13:00:00'),
        );
        $secondBooking = $this->pendingBooking(
            $student,
            $access,
            $package,
            $tentor,
            Carbon::parse('2026-07-31 13:00:00'),
        );

        $this->expectException(ValidationException::class);

        $service->approve(
            $secondBooking,
            $tutorUser,
            Carbon::parse('2026-07-31 13:00:00'),
        );
    }

    public function test_student_can_review_a_finished_booking_only_once(): void
    {
        [$booking, $tutorUser, , , , $student] = $this->booking();
        $approved = app(ScheduleBookingService::class)->approve(
            $booking,
            $tutorUser,
            Carbon::parse('2026-07-30 13:00:00'),
        );
        Carbon::setTestNow('2026-07-30 15:00:00');
        $reviewService = app(TutorReviewService::class);

        $firstReview = $reviewService->save(
            $approved,
            $student,
            5,
            'Penjelasannya mudah dipahami.',
        );
        $updatedReview = $reviewService->save(
            $approved,
            $student,
            4,
            'Materinya jelas.',
        );

        $this->assertSame($firstReview->id, $updatedReview->id);
        $this->assertSame(4, $updatedReview->rating);
        $this->assertDatabaseCount('tutor_reviews', 1);
    }

    public function test_student_cannot_review_before_booking_session_ends(): void
    {
        [$booking, $tutorUser, , , , $student] = $this->booking();
        $approved = app(ScheduleBookingService::class)->approve(
            $booking,
            $tutorUser,
            Carbon::parse('2026-07-30 13:00:00'),
        );

        $this->expectException(ValidationException::class);

        app(TutorReviewService::class)->save(
            $approved,
            $student,
            5,
            null,
        );
    }

    public function test_booking_cannot_be_approved_after_package_access_expires(): void
    {
        [$booking, $tutorUser, , , $access] = $this->booking();
        $access->update(['end_date' => '2026-07-29 23:59:00']);

        $this->expectException(ValidationException::class);

        app(ScheduleBookingService::class)->approve(
            $booking,
            $tutorUser,
            Carbon::parse('2026-07-30 13:00:00'),
        );
    }

    public function test_per_session_booking_requires_and_stores_the_session_price(): void
    {
        [$booking, $tutorUser, $package] = $this->booking('per_session');
        $service = app(ScheduleBookingService::class);

        try {
            $service->approve($booking, $tutorUser, Carbon::parse('2026-07-30 13:00:00'));
            $this->fail('A per-session booking must require a session price.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('session_price', $exception->errors());
        }

        $approved = $service->approve(
            $booking->fresh(),
            $tutorUser,
            Carbon::parse('2026-07-30 13:00:00'),
            ['session_price' => 150000],
        );

        $this->assertSame(150000, $approved->session_price);
        $this->assertSame('awaiting_tutor_payment', $approved->tutor_payment_status);
        $this->assertSame($package->package_id, $approved->package_id);
    }

    /**
     * @return array{
     *   ScheduleBookingRequest,
     *   User,
     *   Package,
     *   Tentor,
     *   UserPackageAcces,
     *   User
     * }
     */
    private function booking(string $paymentModel = 'upfront'): array
    {
        $student = $this->user('student', 'user');
        $tutorUser = $this->user('tutor', 'tutor');
        $package = Package::query()->create([
            'name' => 'Paket Privat',
            'status' => 'active',
        ]);
        $tentor = Tentor::query()->create([
            'user_id' => $tutorUser->id,
            'name' => 'Tutor Satu',
            'email' => 'tentor@example.test',
            'is_active' => true,
        ]);
        PackageBookingRule::query()->create([
            'package_id' => $package->package_id,
            'is_enabled' => true,
            'session_quota' => 1,
            'duration_minutes' => 60,
            'min_notice_hours' => 1,
            'max_advance_days' => 30,
            'cancellation_hours' => 6,
            'allow_custom_time' => true,
            'allow_all_tutors' => true,
            'payment_model' => $paymentModel,
        ]);
        $access = $this->access($student, $package);
        $booking = $this->pendingBooking(
            $student,
            $access,
            $package,
            $tentor,
            Carbon::parse('2026-07-30 13:00:00'),
        );

        return [$booking, $tutorUser, $package, $tentor, $access, $student];
    }

    private function user(string $name, string $role): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => "{$name}@example.test",
            'role' => $role,
        ]);
    }

    private function access(User $user, Package $package): UserPackageAcces
    {
        return UserPackageAcces::query()->create([
            'user_id' => $user->id,
            'package_id' => $package->package_id,
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
        ]);
    }

    private function pendingBooking(
        User $student,
        UserPackageAcces $access,
        Package $package,
        Tentor $tentor,
        Carbon $startAt,
    ): ScheduleBookingRequest {
        return ScheduleBookingRequest::query()->create([
            'user_id' => $student->id,
            'package_id' => $package->package_id,
            'user_package_access_id' => $access->user_package_access_id,
            'tentor_id' => $tentor->id,
            'requested_start_at' => $startAt,
            'requested_end_at' => $startAt->copy()->addHour(),
            'status' => ScheduleBookingRequest::STATUS_PENDING,
        ]);
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->default('user');
            $table->timestamps();
        });
        Schema::create('packages', function (Blueprint $table): void {
            $table->id('package_id');
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('user_package_access', function (Blueprint $table): void {
            $table->id('user_package_access_id');
            $table->foreignId('user_id');
            $table->foreignId('package_id');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('tentors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('expertise')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->text('education')->nullable();
            $table->unsignedSmallInteger('experience_years')->nullable();
            $table->text('experience')->nullable();
            $table->text('certifications')->nullable();
            $table->text('teaching_method')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id('class_id');
            $table->dateTime('schedule_time');
            $table->string('title');
            $table->string('mentor')->nullable();
            $table->string('status')->default('upcoming');
            $table->boolean('is_for_sale')->default(false);
            $table->boolean('is_displayed')->default(true);
            $table->timestamps();
        });
        Schema::create('package_booking_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_id')->unique();
            $table->foreignId('class_id')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedSmallInteger('session_quota')->default(1);
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedSmallInteger('min_notice_hours')->default(12);
            $table->unsignedSmallInteger('max_advance_days')->default(30);
            $table->unsignedSmallInteger('cancellation_hours')->default(6);
            $table->boolean('allow_custom_time')->default(true);
            $table->boolean('allow_all_tutors')->default(true);
            $table->string('delivery_mode')->default('online');
            $table->string('learning_mode')->default('personal');
            $table->unsignedSmallInteger('min_participants')->default(1);
            $table->unsignedSmallInteger('max_participants')->default(1);
            $table->string('default_location')->nullable();
            $table->unsignedSmallInteger('payment_deadline_hours')->default(48);
            $table->string('payment_model', 20)->default('upfront');
            $table->timestamps();
        });
        Schema::create('class_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_id');
            $table->foreignId('study_group_id')->nullable();
            $table->foreignId('tentor_id')->nullable();
            $table->string('title');
            $table->string('schedule_type');
            $table->string('frequency')->nullable();
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('class_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_schedule_id');
            $table->foreignId('class_id');
            $table->foreignId('study_group_id')->nullable();
            $table->foreignId('tentor_id')->nullable();
            $table->date('session_date');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('status')->default('scheduled');
            $table->string('meeting_url')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
            $table->unique(
                ['class_schedule_id', 'session_date', 'start_at'],
                'test_session_unique',
            );
        });
        Schema::create('attendance_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_schedule_id');
            $table->string('mode')->default('button');
            $table->integer('open_minutes_before')->default(15);
            $table->integer('close_minutes_after')->default(30);
            $table->boolean('allow_admin_override')->default(true);
            $table->timestamps();
        });
        Schema::create('detail_packages', function (Blueprint $table): void {
            $table->id('detail_package_id');
            $table->foreignId('package_id');
            $table->string('detailable_type');
            $table->unsignedBigInteger('detailable_id');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
        Schema::create('schedule_booking_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('package_id');
            $table->unsignedBigInteger('user_package_access_id');
            $table->unsignedBigInteger('booking_cohort_id')->nullable();
            $table->unsignedBigInteger('study_group_id')->nullable();
            $table->foreignId('tentor_id');
            $table->dateTime('requested_start_at');
            $table->dateTime('requested_end_at');
            $table->dateTime('scheduled_start_at')->nullable();
            $table->dateTime('scheduled_end_at')->nullable();
            $table->unsignedBigInteger('session_price')->nullable();
            $table->string('tutor_payment_status', 30)->default('not_required');
            $table->timestamp('paid_to_tutor_at')->nullable();
            $table->timestamp('deposited_to_admin_at')->nullable();
            $table->foreignId('deposited_to_admin_by')->nullable();
            $table->string('status')->default('pending');
            $table->text('student_notes')->nullable();
            $table->text('tutor_notes')->nullable();
            $table->foreignId('class_schedule_id')->nullable();
            $table->foreignId('class_session_id')->nullable();
            $table->foreignId('responded_by')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('slot_key')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('tutor_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('schedule_booking_request_id')->unique();
            $table->foreignId('user_id');
            $table->foreignId('tentor_id');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }
}
