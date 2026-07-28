<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\admin\AdminAssistantController;
use App\Http\Controllers\admin\AffiliateController as AdminAffiliateController;
use App\Http\Controllers\admin\AiQuestionGeneratorBillingController;
use App\Http\Controllers\admin\AksesController;
use App\Http\Controllers\admin\ArticleController as AdminArticleController;
use App\Http\Controllers\admin\CertificateController;
use App\Http\Controllers\admin\CertificationController;
use App\Http\Controllers\admin\ClassAttendanceController;
use App\Http\Controllers\admin\ClassController;
use App\Http\Controllers\admin\ClassScheduleController;
use App\Http\Controllers\admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\admin\DiscountController;
use App\Http\Controllers\admin\DiscussionController;
use App\Http\Controllers\admin\EssayReviewController;
use App\Http\Controllers\admin\ExpenseController;
use App\Http\Controllers\admin\FaqController;
use App\Http\Controllers\admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\admin\FinanceIncomeController;
use App\Http\Controllers\admin\GeneralPageController as AdminGeneralPageController;
use App\Http\Controllers\admin\GroupBookingController;
use App\Http\Controllers\admin\LaporanController;
use App\Http\Controllers\admin\LeaderboardController;
use App\Http\Controllers\admin\MaterialCategoryController;
use App\Http\Controllers\admin\MaterialManagementController;
use App\Http\Controllers\admin\PackageBookingRuleController;
use App\Http\Controllers\admin\PackageController as AdminPackageController;
use App\Http\Controllers\admin\ParticipantDestinationCategoryController;
use App\Http\Controllers\admin\PembayaranController;
use App\Http\Controllers\admin\ProfileController;
use App\Http\Controllers\admin\QuestionBankController;
use App\Http\Controllers\admin\QuestionController;
use App\Http\Controllers\admin\QuestionImportController;
use App\Http\Controllers\admin\RecurringBillController;
use App\Http\Controllers\admin\SettingController;
use App\Http\Controllers\admin\StudyGroupController;
use App\Http\Controllers\admin\TentorController;
use App\Http\Controllers\admin\TesKoranController as AdminTesKoranController;
use App\Http\Controllers\admin\TryoutController as AdminTryoutController;
use App\Http\Controllers\admin\TutorPayrollController;
use App\Http\Controllers\admin\UpdateNotificationController;
use App\Http\Controllers\admin\UserController;
use App\Http\Controllers\admin\UserImportController;
use App\Http\Controllers\Api\AiGatewayBillingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GeneralPageController;
use App\Http\Controllers\IndividualPurchaseController;
use App\Http\Controllers\ParticipantDestinationLookupController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\superadmin\AiGatewayPlanController;
use App\Http\Controllers\superadmin\AiUsageController;
use App\Http\Controllers\superadmin\GeneralSettingController;
use App\Http\Controllers\superadmin\PlanController;
use App\Http\Controllers\superadmin\PlanManagementController;
use App\Http\Controllers\superadmin\RoleController;
use App\Http\Controllers\superadmin\SuperAdminController;
use App\Http\Controllers\tutor\ScheduleBookingController as TutorScheduleBookingController;
use App\Http\Controllers\tutor\StudentDevelopmentController as TutorStudentDevelopmentController;
use App\Http\Controllers\tutor\TutorDashboardController;
use App\Http\Controllers\tutor\TutorProfileController;
use App\Http\Controllers\user\AffiliateController as UserAffiliateController;
use App\Http\Controllers\user\AiGatewaySubscriptionController;
use App\Http\Controllers\user\AiLearningToolController;
use App\Http\Controllers\user\CertificateValidationController;
use App\Http\Controllers\user\DashboardController;
use App\Http\Controllers\user\EventController;
use App\Http\Controllers\user\FeedbackController as UserFeedbackController;
use App\Http\Controllers\user\HelpController;
use App\Http\Controllers\user\MaterialController;
use App\Http\Controllers\user\PackageController;
use App\Http\Controllers\user\ScheduleBookingController as UserScheduleBookingController;
use App\Http\Controllers\user\StudentDevelopmentController as UserStudentDevelopmentController;
use App\Http\Controllers\user\StudyGroupBookingController as UserStudyGroupBookingController;
use App\Http\Controllers\user\TesKoranController as UserTesKoranController;
use App\Http\Controllers\user\TryoutController;
use App\Http\Controllers\user\UserBillingController;
use App\Http\Controllers\user\UserClassScheduleController;
use App\Http\Middleware\AdminMiddleware;
use App\Models\Tentor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [GeneralPageController::class, 'landing'])->name('landing');
Route::get('/statistik-ptn', [GeneralPageController::class, 'statistics'])->name('statistics');
Route::get('/statistik-ptn/data-ptn', [GeneralPageController::class, 'proxyPtnList'])->name('statistics.proxy.ptn');
Route::get('/statistik-ptn/data-prodi', [GeneralPageController::class, 'proxyProdiList'])->name('statistics.proxy.prodi');
Route::get('/statistik-ptn/snbt', [GeneralPageController::class, 'statisticsSnbt'])->name('statistics.snbt');
Route::get('/statistik-ptn/snbt/data-ptn', [GeneralPageController::class, 'proxyPtnListSnbt'])->name('statistics.snbt.proxy.ptn');
Route::get('/statistik-ptn/snbt/data-prodi', [GeneralPageController::class, 'proxyProdiListSnbt'])->name('statistics.snbt.proxy.prodi');
Route::get('/participant-destinations/official/institutions', [ParticipantDestinationLookupController::class, 'institutions'])->name('participant-destinations.official.institutions');
Route::get('/participant-destinations/official/programs', [ParticipantDestinationLookupController::class, 'programs'])->name('participant-destinations.official.programs');
Route::get('/artikel', [GeneralPageController::class, 'articles'])->name('articles.index');
Route::get('/artikel/{slug}', [GeneralPageController::class, 'showArticle'])->name('articles.show');
Route::get('/ai-gateway-payments/{externalId}/qris', [AiGatewayBillingController::class, 'showQrisPayment'])->name('ai-gateway-payments.qris.show');

Route::prefix('general')->name('general.')->group(function () {
    Route::get('/', [GeneralPageController::class, 'landing'])->name('index');
    Route::get('/landing-page', [GeneralPageController::class, 'landing'])->name('landing');
    Route::get('/statistik-ptn', [GeneralPageController::class, 'statistics'])->name('statistics');
    Route::get('/statistik-ptn/snbt', [GeneralPageController::class, 'statisticsSnbt'])->name('statistics.snbt');
    Route::get('/artikel', [GeneralPageController::class, 'articles'])->name('articles.index');
    Route::get('/blog', [GeneralPageController::class, 'articles'])->name('blog.index');
    Route::get('/artikel/{slug}', [GeneralPageController::class, 'showArticle'])->name('articles.show');
});

Route::get('/terms-and-conditions', [PublicPageController::class, 'terms'])->name('public.terms');
Route::get('/syarat-ketentuan', [PublicPageController::class, 'terms'])->name('public.terms.id');
Route::get('/payment-policy', [PublicPageController::class, 'paymentPolicy'])->name('public.payment-policy');
Route::get('/kebijakan-pembayaran', [PublicPageController::class, 'paymentPolicy'])->name('public.payment-policy.id');
Route::get('/refund-policy', [PublicPageController::class, 'refundPolicy'])->name('public.refund-policy');
Route::get('/kebijakan-refund', [PublicPageController::class, 'refundPolicy'])->name('public.refund-policy.id');

Route::get('/live-score/{tryout}', [LaporanController::class, 'publicLiveScore'])
    ->middleware('signed')
    ->name('laporan.live-score.public');

// Authentication routes

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:10,1')->name('login.authenticate');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Route untuk logout as (admin kembali ke akun admin)
Route::post('/logout-as', [UserController::class, 'logoutAs'])->middleware('auth')->name('logout-as');

// Public user routes (no auth required)
Route::prefix('user')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard.index');

    // Public package listing (berbayar & gratis)
    Route::get('/paket', [PackageController::class, 'index'])->name('user.package.index');

    // Public package detail (bisa diakses guest)
    Route::get('/paket/{package_id}/detail', [PackageController::class, 'detail'])->name('user.package.detail');

    // Public material listing (bisa diakses guest)
    Route::prefix('materi')->name('user.material.')->group(function () {
        Route::get('/', [MaterialController::class, 'index'])->name('index');
        Route::get('/video', [MaterialController::class, 'videos'])->name('videos');
        Route::get('/belajar', [MaterialController::class, 'documents'])->name('documents');
        Route::get('/live-session', [MaterialController::class, 'liveSessions'])->name('live-sessions');
        Route::get('/kategori/{category_id}', [MaterialController::class, 'byCategory'])->name('category');
    });

    // Public tryout listing (bisa diakses guest)
    Route::get('/tryout-list', [PackageController::class, 'listTryout'])->name('user.package.tryout.list');
});

// User routes (add auth middleware)
Route::prefix('user')->middleware('auth')->group(function () {

    Route::prefix('chat')->name('user.chat.')->group(function () {
        Route::get('kelas/{class}', [ChatController::class, 'studentShow'])->name('class.show');
        Route::get('conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('messages');
        Route::post('conversations/{conversation}/messages', [ChatController::class, 'store'])
            ->middleware('throttle:60,1')
            ->name('messages.store');
        Route::post('conversations/{conversation}/read', [ChatController::class, 'markRead'])
            ->middleware('throttle:120,1')
            ->name('read');
    });

    // Profile routes
    Route::get('/profile', [App\Http\Controllers\user\ProfileController::class, 'index'])->name('user.profile.index');
    Route::put('/profile', [App\Http\Controllers\user\ProfileController::class, 'update'])->name('user.profile.update');
    Route::put('/profile/password', [App\Http\Controllers\user\ProfileController::class, 'updatePassword'])->name('user.profile.password.update');

    Route::prefix('paket-pembelian')->group(function () {
        Route::post('/{package_id}/buy', [PackageController::class, 'buyPackage'])->name('user.package.buy');
        Route::post('/{package_id}/discount/preview', [PackageController::class, 'previewDiscount'])->name('user.package.discount.preview');
        Route::get('/payment/success', [PackageController::class, 'paymentSuccess'])->name('user.package.payment.success');
        Route::get('/payment/failed', [PackageController::class, 'paymentFailed'])->name('user.package.payment.failed');
        Route::get('/payment/{transactionId}/resume', [PackageController::class, 'resumeCombinedPayment'])->name('user.package.payment.resume');
        Route::get('/payment/qris/{transactionId}', [PackageController::class, 'showQrisPayment'])->name('user.package.payment.qris.show');
        Route::post('/payment/qris/{transactionId}/check', [PackageController::class, 'checkQrisPayment'])->name('user.package.payment.qris.check');
        Route::get('/riwayat-pembelian', [PackageController::class, 'riwayatPembelian'])->name('user.package.riwayatPembelian');
        Route::get('/riwayat-pembelian/paket-aktif', [PackageController::class, 'riwayatPembelianAktif'])->name('user.package.riwayatPembelianAktif');
        Route::get('/{id_package}/bimbel', [PackageController::class, 'indexBimbel'])->name('user.package.bimbel');
        Route::get('/{id_package}/tryout', [PackageController::class, 'indexTryout'])->name('user.package.tryout');
        Route::get('/{id_package}/tryout/{id_tryout}/riwayat', [PackageController::class, 'riwayatTryout'])->name('user.package.tryout.riwayat');
        Route::get('/{id_package}/tryout/{id_tryout}/ranking', [PackageController::class, 'rankingTryout'])->name('user.package.tryout.ranking');
        Route::get('/{id_package}/tryout/{id_tryout}/pembahasan/{token}', [PackageController::class, 'pembahasanTryout'])->name('user.package.tryout.pembahasan');
        Route::post('/{id_package}/tryout/{id_tryout}/pembahasan/{token}/ai-chat', [PackageController::class, 'chatPembahasanAi'])->middleware('throttle:12,1')->name('user.package.tryout.pembahasan.ai-chat');
        Route::get('/{id_package}/tryout/{id_tryout}/pembahasan/{token}/ai-tools/history', [AiLearningToolController::class, 'history'])->middleware('throttle:30,1')->name('user.package.tryout.pembahasan.ai-tools.history');
        Route::post('/{id_package}/tryout/{id_tryout}/pembahasan/{token}/ai-tools', [AiLearningToolController::class, 'generate'])->middleware('throttle:12,1')->name('user.package.tryout.pembahasan.ai-tools');
        Route::post('/{id_package}/tryout/{id_tryout}/pembahasan/{token}/ai-speech', [PackageController::class, 'speakPembahasanAi'])->middleware('throttle:5,1')->name('user.package.tryout.pembahasan.ai-speech');
    });

    Route::get('/affiliate', [UserAffiliateController::class, 'index'])->name('user.affiliate.index');

    Route::prefix('event')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('user.event.index');
        Route::post('/{package_id}/join', [EventController::class, 'joinEvent'])->name('user.event.join');
        Route::post('/tryout/{tryout_id}/join', [EventController::class, 'joinFreeTryout'])->name('user.event.tryout.join');
    });

    Route::get('/class/{class}/zoom', [PackageController::class, 'openClassZoom'])->name('user.class.zoom');
    Route::get('/class/{class}/material', [PackageController::class, 'openClassMaterial'])->name('user.class.material');

    Route::get('/bantuan', [HelpController::class, 'index'])->name('user.help.index');
    Route::get('/tagihan', [UserBillingController::class, 'index'])->name('user.billing.index');
    Route::get('/paket-ai', [AiGatewaySubscriptionController::class, 'index'])->name('user.ai-gateway.index');
    Route::post('/paket-ai/checkout', [AiGatewaySubscriptionController::class, 'checkout'])
        ->middleware('throttle:10,1')
        ->name('user.ai-gateway.checkout');
    Route::get('/ai-learning-tools', [AiLearningToolController::class, 'index'])->name('user.ai-learning.index');
    Route::post('/ai-learning-tools/onboarding/skip', [AiLearningToolController::class, 'skipOnboarding'])
        ->middleware('throttle:10,1')
        ->name('user.ai-learning.onboarding.skip');
    Route::post('/ai-learning-tools/generate', [AiLearningToolController::class, 'generateIndependent'])
        ->middleware('throttle:12,1')
        ->name('user.ai-learning.generate-independent');
    Route::post('/catatan-ai/{artifact}/expand', [AiLearningToolController::class, 'expandNote'])
        ->middleware('throttle:8,1')
        ->name('user.ai-learning.notes.expand');
    Route::get('/catatan-ai', [AiLearningToolController::class, 'notes'])->name('user.ai-learning.notes');
    Route::post('/catatan-ai/{artifact}/save', [AiLearningToolController::class, 'save'])->middleware('throttle:30,1')->name('user.ai-learning.notes.save');
    Route::get('/catatan-ai/{artifact}/pdf', [AiLearningToolController::class, 'exportPdf'])->middleware('throttle:10,1')->name('user.ai-learning.notes.pdf');
    Route::delete('/catatan-ai/{artifact}', [AiLearningToolController::class, 'destroy'])->name('user.ai-learning.notes.destroy');
    Route::get('/jadwal-kelas', [UserClassScheduleController::class, 'index'])->name('user.class-schedule.index');
    Route::get('/perkembangan-belajar', [UserStudentDevelopmentController::class, 'index'])
        ->name('user.development.index');
    Route::post('/jadwal-kelas/{session}/absen', [UserClassScheduleController::class, 'attend'])
        ->middleware('module:class')
        ->name('user.class-schedule.attend');
    Route::prefix('booking-jadwal')->name('user.booking.')->group(function () {
        Route::get('/', [UserScheduleBookingController::class, 'index'])->name('index');
        Route::post('/kelompok', [UserStudyGroupBookingController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('rombel.store');
        Route::post('/kelompok/gabung', [UserStudyGroupBookingController::class, 'join'])
            ->middleware('throttle:10,1')
            ->name('rombel.join');
        Route::get('/tutor/{tentor}', [UserScheduleBookingController::class, 'showTutor'])
            ->name('tutor.show');
        Route::post('/', [UserScheduleBookingController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store');
        Route::post('/{booking}/terima-usulan', [UserScheduleBookingController::class, 'acceptCounter'])
            ->middleware('throttle:10,1')
            ->name('accept-counter');
        Route::post('/{booking}/review', [UserScheduleBookingController::class, 'storeReview'])
            ->middleware('throttle:10,1')
            ->name('review.store');
        Route::delete('/{booking}', [UserScheduleBookingController::class, 'cancel'])
            ->name('cancel');
    });

    Route::prefix('tryout')->group(function () {
        Route::get('/{id_package}/{id_tryout}/lobby', [TryoutController::class, 'indexLobby'])->name('user.tryout.lobby');
        Route::get('/{id_package}/{id_tryout}/tryout/{number}', [TryoutController::class, 'indexTryout'])->name('user.tryout.index');
        Route::post('/{id_package}/{id_tryout}/tryout/{number}/save', [TryoutController::class, 'saveAnswer'])->name('user.tryout.save');
        Route::post('/{id_package}/{id_tryout}/subtest/flush', [TryoutController::class, 'flushSubtestAnswers'])->name('user.tryout.subtest.flush');
        Route::post('/{id_package}/{id_tryout}/track-tab-switch', [TryoutController::class, 'trackTabSwitch'])->name('user.tryout.track-tab-switch');
        Route::post('/{id_package}/{id_tryout}/proctoring-snapshot', [TryoutController::class, 'storeProctoringSnapshot'])->name('user.tryout.proctoring-snapshot');
        Route::post('/{id_package}/{id_tryout}/flag', [TryoutController::class, 'toggleFlag'])->name('user.tryout.flag');
        Route::post('/{id_package}/{id_tryout}/finish', [TryoutController::class, 'finishTryout'])->name('user.tryout.finish');
        Route::get('/{id_package}/{id_tryout}/hasil', [TryoutController::class, 'indexResult'])->name('user.tryout.result');
        Route::post('/{id_package}/{id_tryout}/feedback', [UserFeedbackController::class, 'store'])->name('user.tryout.feedback.store');
        Route::get('/check-essay-status', [TryoutController::class, 'checkEssayStatus'])->name('user.tryout.check-essay-status');
        Route::post(
            '/listening/mark-played/{id_package}/{id_tryout}/{question_id}',
            [TryoutController::class, 'markPlayed']
        )->name('user.tryout.markPlayed');
    });

    Route::prefix('package')->group(function () {
        Route::get('/sertifikasi-list', [PackageController::class, 'listSertifikasi'])->name('user.package.sertifikasi.list');
        Route::get('/sertifikasi/{id_package}', [PackageController::class, 'indexSertifikasi'])->name('user.package.sertifikasi');
        Route::get('/{id_package}/tryout/{id_tryout}/statistik', [PackageController::class, 'statistikTryout'])->name('user.package.tryout.statistik');
        // Note: user.package.buy, user.package.bimbel, user.package.tryout, user.package.tryout.riwayat,
        // user.package.tryout.ranking sudah didefinisikan di paket-pembelian prefix
        // user.package.tryout.list sudah didefinisikan di public routes
    });

    // Tes Koran Routes
    Route::prefix('tes-koran')->name('user.tes-koran.')->group(function () {
        Route::get('/', [UserTesKoranController::class, 'index'])->name('index');
        Route::get('/riwayat', [UserTesKoranController::class, 'history'])->name('history');
        Route::get('/{tesKoran}', [UserTesKoranController::class, 'show'])->name('show');
        Route::post('/{tesKoran}/start', [UserTesKoranController::class, 'start'])->name('start');
        Route::get('/{tesKoran}/result/{result}', [UserTesKoranController::class, 'result'])->name('result');
    });

    // My Packages (Step by Step)
    Route::get('/paket-saya', [PackageController::class, 'myPackages'])->name('user.package.my');
    Route::get('/paket-saya/{package_id}', [PackageController::class, 'showPackage'])->name('user.package.show');

    // Material Routes yang butuh auth (detail dan actions)
    Route::prefix('materi')->name('user.material.')->group(function () {
        Route::get('/{material_id}', [MaterialController::class, 'show'])->name('show');
        Route::post('/{material_id}/start', [MaterialController::class, 'start'])->name('start');
        Route::post('/{material_id}/progress', [MaterialController::class, 'updateProgress'])->name('progress');
        Route::post('/{material_id}/complete', [MaterialController::class, 'complete'])->name('complete');
    });

    // Certificate validation routes
    Route::prefix('sertifikat')->middleware('certificate.enabled')->group(function () {
        Route::get('/validasi', [CertificateValidationController::class, 'index'])->name('user.certificate.validation');
        Route::post('/validasi', [CertificateValidationController::class, 'validateCertificate'])
            ->middleware('throttle:20,1')
            ->name('user.certificate.validate');
        Route::post('/download', [CertificateValidationController::class, 'downloadCertificate'])->name('user.certificate.download.post');
        Route::get('/validasi/preview/{certificate_id}', [App\Http\Controllers\user\CertificateController::class, 'view'])
            ->middleware('signed')
            ->name('user.certificate.validation.preview');
        Route::get('/download/{certificate_id}', [CertificateValidationController::class, 'downloadById'])
            ->middleware('signed')
            ->name('user.certificate.validation.download');

        // Certificate generation routes
        Route::get('/preview/{package_id}/{tryout_id}/{token}', [App\Http\Controllers\user\CertificateController::class, 'preview'])->name('user.certificate.preview');
        Route::get('/view/{certificate_id}/{token}', [App\Http\Controllers\user\CertificateController::class, 'view'])->name('user.certificate.view');
        Route::get('/preview-with-data/{certificate_id}/{token}', [App\Http\Controllers\user\CertificateController::class, 'previewWithData'])->name('user.certificate.preview.with.data');
        Route::get('/download/{certificate_id}/{token}', [App\Http\Controllers\user\CertificateController::class, 'download'])->name('user.certificate.download.file');

        // Template preview route
        Route::get('/template/preview', [App\Http\Controllers\user\CertificateController::class, 'previewTemplate'])->name('user.certificate.template.preview');
        Route::get('/template/test', [App\Http\Controllers\user\CertificateController::class, 'testSertifikat'])->name('user.certificate.template.test');
    });

    // Individual purchase routes
    Route::prefix('pembelian')->name('user.individual-purchase.')->group(function () {
        Route::post('/buy', [IndividualPurchaseController::class, 'buy'])->name('buy');
        Route::get('/gateway/{type}/{id}', [IndividualPurchaseController::class, 'gatewayRedirect'])->name('gateway');
        Route::get('/history', [IndividualPurchaseController::class, 'history'])->name('history');
    });
});

// Keep the former short URL working for existing bookmarks.
Route::redirect('/tutor', '/tutor/jadwal-tutor');

// Tutor portal: a Tutor may only access sessions assigned to their linked tentor profile.
Route::prefix('tutor/jadwal-tutor')->name('tutor.')->middleware(['auth', 'tutor', 'no-cache'])->group(function () {
    Route::get('chat', [ChatController::class, 'tutorIndex'])->name('chat.index');
    Route::get('chat/{conversation}', [ChatController::class, 'tutorShow'])->name('chat.show');
    Route::get('chat/{conversation}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('chat/{conversation}/messages', [ChatController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('chat.messages.store');
    Route::post('chat/{conversation}/read', [ChatController::class, 'markRead'])
        ->middleware('throttle:120,1')
        ->name('chat.read');
    Route::get('/', [TutorDashboardController::class, 'index'])->name('schedule.index');
    Route::get('profile', [TutorProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [TutorProfileController::class, 'update'])->name('profile.update');
    Route::prefix('booking')->name('booking.')->group(function () {
        Route::get('/', [TutorScheduleBookingController::class, 'index'])->name('index');
        Route::post('/{booking}/setujui', [TutorScheduleBookingController::class, 'approve'])
            ->middleware('throttle:20,1')
            ->name('approve');
        Route::post('/{booking}/tolak', [TutorScheduleBookingController::class, 'reject'])
            ->middleware('throttle:20,1')
            ->name('reject');
        Route::post('/{booking}/usulkan-waktu', [TutorScheduleBookingController::class, 'propose'])
            ->middleware('throttle:20,1')
            ->name('propose');
    });
    Route::prefix('perkembangan')->name('development.')->group(function () {
        Route::get('/', [TutorStudentDevelopmentController::class, 'index'])->name('index');
        Route::post('/feedback', [TutorStudentDevelopmentController::class, 'storeFeedback'])
            ->middleware('throttle:30,1')
            ->name('feedback.store');
        Route::post('/progres', [TutorStudentDevelopmentController::class, 'storeProgress'])
            ->middleware('throttle:30,1')
            ->name('progress.store');
    });
    Route::middleware('module:class')->group(function () {
        Route::get('absensi', [TutorDashboardController::class, 'attendanceIndex'])->name('attendance.index');
        Route::get('absensi/jadwal/{classSchedule}', [TutorDashboardController::class, 'showAttendanceSchedule'])->name('attendance.schedule.show');
        Route::get('absensi/{session}', [TutorDashboardController::class, 'showSession'])->name('attendance.show');
        Route::post('absensi/{session}/saya', [TutorDashboardController::class, 'markOwnAttendance'])->name('attendance.mark');
        Route::post('absensi/{session}/siswa', [TutorDashboardController::class, 'markStudentAttendance'])->name('attendance.students.mark');
    });
});

// Webhook route (outside auth middleware) - make sure this is correct
Route::post('/webhook/xendit', [PackageController::class, 'xenditWebhook'])->middleware('throttle:120,1')->name('webhook.xendit');
Route::post('/webhook/midtrans', [PackageController::class, 'midtransWebhook'])->middleware('throttle:120,1')->name('webhook.midtrans');
Route::post('/webhook/ipaymu', [PackageController::class, 'ipaymuWebhook'])->middleware('throttle:120,1')->name('webhook.ipaymu');

// Add route for checking payment status (for debugging)
Route::get('/admin/payment/{paymentId}/check', [PackageController::class, 'checkPaymentStatus'])->middleware(['auth', AdminMiddleware::class, 'admin.expiry']);

// Add route for manual payment activation
Route::post('/admin/payment/{paymentId}/activate', [PackageController::class, 'manualActivatePayment'])->middleware(['auth', AdminMiddleware::class, 'admin.expiry']);

// Super Admin Routes
Route::prefix('super-admin')->name('super-admin.')->middleware(['auth', 'super-admin', 'no-cache'])->group(function () {
    Route::get('/admins', [SuperAdminController::class, 'index'])->name('admins.index');
    Route::post('/admins', [SuperAdminController::class, 'store'])->name('admins.store');
    Route::put('/admins/{admin}', [SuperAdminController::class, 'update'])->name('admins.update');
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions');
    Route::get('/general-settings', [GeneralSettingController::class, 'edit'])->name('general-settings.edit');
    Route::put('/general-settings', [GeneralSettingController::class, 'update'])->name('general-settings.update');
    Route::post('/general-settings/telegram/test', [GeneralSettingController::class, 'testTelegram'])->name('general-settings.telegram.test');
    Route::get('/ai-usage', [AiUsageController::class, 'index'])->name('ai-usage.index');
    Route::get('/ai-gateway-usage', [AiUsageController::class, 'gatewayIndex'])->name('ai-gateway-usage.index');
    Route::post('/ai-gateway-subscriptions/{subscription}/tokens', [AiUsageController::class, 'addGatewaySubscriptionTokens'])
        ->name('ai-gateway-subscriptions.tokens.store');
    Route::post('/ai-gateway-subscriptions/{subscription}/revoke', [AiUsageController::class, 'revokeGatewaySubscription'])
        ->name('ai-gateway-subscriptions.revoke');
    Route::get('/ai-gateway-payments', [AiUsageController::class, 'gatewayPayments'])->name('ai-gateway-payments.index');
    Route::post('/ai-gateway-payments/{transaction}/approve', [AiUsageController::class, 'approveGatewayPayment'])->name('ai-gateway-payments.approve');
    Route::post('/ai-gateway-payments/{transaction}/reconcile', [AiUsageController::class, 'reconcileGatewayPayment'])->name('ai-gateway-payments.reconcile');
    Route::post('/ai-gateway-payments/{transaction}/reject', [AiUsageController::class, 'rejectGatewayPayment'])->name('ai-gateway-payments.reject');
    Route::post('/ai-gateway-payments/{transaction}/reset-unverified', [AiUsageController::class, 'resetUnverifiedGatewayPayment'])->name('ai-gateway-payments.reset-unverified');
    Route::put('/ai-usage/quota', [AiUsageController::class, 'updateQuota'])->name('ai-usage.quota.update');
    Route::post('/ai-usage/projects', [AiUsageController::class, 'storeGatewayClient'])->name('ai-usage.projects.store');
    Route::put('/ai-usage/projects/{gatewayClient}', [AiUsageController::class, 'updateGatewayClient'])->name('ai-usage.projects.update');
    Route::delete('/ai-usage/projects/{gatewayClient}', [AiUsageController::class, 'destroyGatewayClient'])->name('ai-usage.projects.destroy');
    Route::resource('ai-gateway-plans', AiGatewayPlanController::class)->only(['index', 'store', 'update', 'destroy']);

    // Plan Master Data Routes (CRUD Plan templates)
    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

    // Plan Management Routes (Current project plan)
    Route::get('/plan-management', [PlanManagementController::class, 'index'])->name('plan-management.index');
    Route::get('/plan-management/change', [PlanManagementController::class, 'changeForm'])->name('plan-management.change');
    Route::post('/plan-management/assign', [PlanManagementController::class, 'assign'])->name('plan-management.assign');
    Route::put('/plan-management/subscriptions/{subscription}', [PlanManagementController::class, 'updateSubscription'])->name('plan-management.subscriptions.update');
    Route::post('/plan-management/reset-essay', [PlanManagementController::class, 'resetEssayCounter'])->name('plan-management.reset-essay');
});

// Admin Routes (add auth middleware)
Route::prefix('{portal}')
    ->where(['portal' => 'admin|tutor'])
    ->name('admin.')
    ->middleware(['auth', AdminMiddleware::class, 'panel.portal', 'admin.expiry', 'permission', 'no-cache'])
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/ai-question-generator/quota', [AiQuestionGeneratorBillingController::class, 'index'])->name('question-generator.quota.index');
        Route::post('/ai-question-generator/quota/checkout', [AiQuestionGeneratorBillingController::class, 'checkout'])->name('question-generator.quota.checkout');
        Route::post('/assistant/chat', [AdminAssistantController::class, 'chat'])->name('assistant.chat');
        Route::get('/csrf-token', [FaqController::class, 'csrfToken'])->name('csrf-token');
        Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
        Route::get('/update-notifications', [UpdateNotificationController::class, 'index'])->name('update-notifications.index');
        Route::get('/update-notifications/{updateNotification}', [UpdateNotificationController::class, 'show'])->name('update-notifications.show');
        Route::prefix('keuangan')->name('finance.')->group(function () {
            Route::get('/pemasukan', [FinanceIncomeController::class, 'index'])->name('income.index');
            Route::get('/pengeluaran', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('/pengeluaran/tambah', [ExpenseController::class, 'create'])->name('expenses.create');
            Route::post('/pengeluaran', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::delete('/pengeluaran/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        });
        Route::resource('tagihan-rutin', RecurringBillController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
            ->names('recurring-bills')
            ->parameters(['tagihan-rutin' => 'recurringBill']);
        Route::post('tagihan-rutin/{recurringBill}/generate', [RecurringBillController::class, 'generate'])->name('recurring-bills.generate');
        Route::get('tagihan-rutin/{recurringBill}/periode/{periodStart}', [RecurringBillController::class, 'showPeriod'])->name('recurring-bills.periods.show');
        Route::put('tagihan-rutin/invoice/{invoice}', [RecurringBillController::class, 'updateInvoice'])->name('recurring-bills.invoices.update');
        Route::delete('tagihan-rutin/invoice/{invoice}', [RecurringBillController::class, 'destroyInvoice'])->name('recurring-bills.invoices.destroy');
        Route::post('tagihan-rutin/invoice/{invoice}/payments', [RecurringBillController::class, 'recordPayment'])->name('recurring-bills.invoices.payments.store');

        Route::resource('general/artikel', AdminArticleController::class)
            ->except(['show'])
            ->names('artikel')
            ->parameters(['artikel' => 'artikel']);

        Route::get('/general/landing-page', [AdminGeneralPageController::class, 'editLanding'])->name('general-pages.landing.edit');
        Route::put('/general/landing-page', [AdminGeneralPageController::class, 'updateLanding'])->name('general-pages.landing.update');

        Route::prefix('affiliate')->name('affiliate.')->group(function () {
            Route::get('/', [AdminAffiliateController::class, 'index'])->name('index');
            Route::put('/settings', [AdminAffiliateController::class, 'updateSettings'])->name('settings.update');
            Route::post('/commissions/{commission}/approve', [AdminAffiliateController::class, 'approve'])->name('commissions.approve');
            Route::post('/commissions/{commission}/pay', [AdminAffiliateController::class, 'markPaid'])->name('commissions.pay');
            Route::post('/commissions/{commission}/cancel', [AdminAffiliateController::class, 'cancel'])->name('commissions.cancel');
        });

        // Profile routes
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Admin User Import Routes
        Route::get('/user/import', [UserImportController::class, 'showImportForm'])->name('user.import');
        Route::post('/user/import', [UserImportController::class, 'import'])->name('user.import.process');
        Route::get('/user/import/template', [UserImportController::class, 'downloadTemplate'])->name('user.import.template');
        Route::get('/user/import/status/{token}', function (string $token) {
            return response()->json([
                'progress' => cache()->get("import_users:{$token}:progress"),
                'done' => cache()->get("import_users:{$token}:done", false),
            ]);
        })->name('user.import.status');

        // Package Management - Gunakan AdminPackageController dengan alias
        Route::get('/paket', [AdminPackageController::class, 'index'])->name('package.index');
        Route::get('/paket/tambah', [AdminPackageController::class, 'create'])->name('package.create');
        Route::post('/paket/store', [AdminPackageController::class, 'store'])->name('package.store');
        Route::get('/paket/{package_id}/edit', [AdminPackageController::class, 'edit'])->name('package.edit');
        Route::put('/paket/{package_id}/update', [AdminPackageController::class, 'update'])->name('package.update');
        Route::delete('/paket/{package_id}/destroy', [AdminPackageController::class, 'destroy'])->name('package.destroy');
        Route::get('/paket/{package}/booking', [PackageBookingRuleController::class, 'edit'])
            ->name('package-booking.edit');
        Route::put('/paket/{package}/booking', [PackageBookingRuleController::class, 'update'])
            ->name('package-booking.update');
        Route::get('/paket-booking/kelompok', [GroupBookingController::class, 'index'])
            ->name('package-booking.cohorts.index');
        Route::post('/paket-booking/invoice/{invoice}/pembayaran', [GroupBookingController::class, 'recordPayment'])
            ->name('package-booking.cohorts.payments.store');
        Route::post('/paket-booking/rombel/{studyGroup}/setujui', [GroupBookingController::class, 'approve'])
            ->name('package-booking.cohorts.approve');
        Route::resource('diskon', DiscountController::class)
            ->except(['show'])
            ->names('discounts')
            ->parameters(['diskon' => 'discount']);

        // Package Tryout Management
        Route::get('/paket/{package_id}/tryout', [AdminPackageController::class, 'indexTryout'])->name('package.tryout.index');
        Route::get('/paket/{package_id}/tryout/tambah', [AdminPackageController::class, 'createTryout'])->name('package.tryout.create');
        Route::post('/paket/{package_id}/tryout/store', [AdminPackageController::class, 'storeTryout'])->name('package.tryout.store');
        Route::post('/paket/{package_id}/tryout/{tryout_id}/toggle', [AdminPackageController::class, 'toggleTryout'])->name('package.tryout.toggle');

        // Package Class Management
        Route::get('/paket/{package_id}/kelas', [AdminPackageController::class, 'indexClass'])->name('package.class.index');
        Route::get('/paket/{package_id}/kelas/tambah', [AdminPackageController::class, 'createClass'])->name('package.class.create');
        Route::post('/paket/{package_id}/kelas/store', [AdminPackageController::class, 'storeClass'])->name('package.class.store');
        Route::post('/paket/{package_id}/kelas/{class_id}/toggle', [AdminPackageController::class, 'toggleClass'])->name('package.class.toggle');
        Route::post('/paket/{package}/jadwal/{classSchedule}/toggle', [ClassScheduleController::class, 'togglePackage'])
            ->middleware('module:schedule')
            ->name('package.schedule.toggle');

        // Package Material Management
        Route::get('/paket/{package_id}/materi', [AdminPackageController::class, 'indexMaterial'])->name('package.material.index');
        Route::post('/paket/{package_id}/materi/{material_id}/toggle', [AdminPackageController::class, 'toggleMaterial'])->name('package.material.toggle');

        // Package Tes Koran Management
        Route::get('/paket/{package_id}/tes-koran', [AdminPackageController::class, 'indexTesKoran'])->name('package.tes-koran.index');
        Route::post('/paket/{package_id}/tes-koran/{tes_koran_id}/toggle', [AdminPackageController::class, 'toggleTesKoran'])->name('package.tes-koran.toggle');

        // Package Tryout Soal Management
        Route::get('/paket/{package_id}/tryout/{tryout_detail_id}/soal', [AdminPackageController::class, 'indexSoal'])->name('package.tryout.soal');
        Route::get('/paket/{package_id}/tryout/{tryout_detail_id}/soal/tambah', [AdminPackageController::class, 'createSoal'])->name('package.tryout.soal.create');
        Route::post('/paket/{package_id}/tryout/{tryout_detail_id}/soal/store', [AdminPackageController::class, 'storeSoal'])->name('package.tryout.soal.store');
        Route::get('/paket/{package_id}/tryout/{tryout_detail_id}/soal/{question_id}/edit', [AdminPackageController::class, 'editSoal'])->name('package.tryout.soal.edit');
        Route::put('/paket/{package_id}/tryout/{tryout_detail_id}/soal/{question_id}/update', [AdminPackageController::class, 'updateSoal'])->name('package.tryout.soal.update');

        // Question Management Routes
        Route::prefix('soal')->name('question.')->group(function () {
            Route::get('/{tryout_detail_id}', [QuestionController::class, 'index'])->name('index');
            Route::get('/{tryout_detail_id}/tambah', [QuestionController::class, 'create'])->name('create');
            Route::post('/{tryout_detail_id}/store', [QuestionController::class, 'store'])->name('store');
            Route::get('/{tryout_detail_id}/{question_id}/edit', [QuestionController::class, 'edit'])->name('edit');
            Route::put('/{tryout_detail_id}/{question_id}/update', [QuestionController::class, 'update'])->name('update');
            Route::delete('/{tryout_detail_id}/{question_id}/destroy', [QuestionController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('bank-soal')->name('question-bank.')->group(function () {
            Route::get('/', [QuestionBankController::class, 'index'])->name('index');
            Route::post('/', [QuestionBankController::class, 'store'])->name('store');

            // Specific routes first (questions create/edit/update/delete)
            Route::get('/{questionBank}/questions/create', [QuestionBankController::class, 'createQuestionForm'])->name('questions.create');
            Route::get('/{questionBank}/questions', [QuestionBankController::class, 'redirectToCreateQuestionForm']);
            Route::post('/{questionBank}/questions', [QuestionBankController::class, 'storeQuestion'])->name('questions.store');
            Route::get('/{questionBank}/questions/ai-generator', [QuestionBankController::class, 'aiGeneratorForm'])->name('questions.ai-generator');
            Route::post('/{questionBank}/questions/ai-generator/preview', [QuestionBankController::class, 'previewAiQuestions'])->name('questions.ai-generator.preview');
            Route::post('/{questionBank}/questions/ai-generator/store', [QuestionBankController::class, 'storeAiQuestions'])->name('questions.ai-generator.store');
            Route::post('/{questionBank}/questions/ai-generator/reset', [QuestionBankController::class, 'resetAiPreview'])->name('questions.ai-generator.reset');
            Route::get('/{questionBank}/questions/import-template', [QuestionBankController::class, 'downloadImportTemplate'])->name('questions.import-template');
            Route::post('/{questionBank}/questions/import', [QuestionBankController::class, 'importQuestions'])->name('questions.import');
            Route::post('/{questionBank}/questions/import-ppt/preview', [QuestionBankController::class, 'previewPptQuestions'])->name('questions.import-ppt.preview');
            Route::post('/{questionBank}/questions/import-ppt/store', [QuestionBankController::class, 'storePptQuestions'])->name('questions.import-ppt.store');
            Route::get('/questions/{question}/edit', [QuestionBankController::class, 'editQuestionForm'])->name('questions.edit');
            Route::post('/questions/bulk-clone', [QuestionBankController::class, 'bulkCloneToTryout'])->name('questions.bulk-clone');
            Route::post('/questions/bulk-move', [QuestionBankController::class, 'bulkMoveQuestions'])->name('questions.bulk-move');
            Route::delete('/questions/bulk-delete', [QuestionBankController::class, 'bulkDestroyQuestions'])->name('questions.bulk-delete');
            Route::put('/questions/{question}', [QuestionBankController::class, 'updateQuestion'])->name('questions.update');
            Route::delete('/questions/{question}', [QuestionBankController::class, 'destroyQuestion'])->name('questions.destroy');
            Route::post('/questions/{question}/clone', [QuestionBankController::class, 'cloneToTryout'])->name('questions.clone');

            // CRUD bank soal (show, update, delete)
            Route::get('/{questionBank}', [QuestionBankController::class, 'show'])->name('show');
            Route::put('/{questionBank}', [QuestionBankController::class, 'update'])->name('update');
            Route::delete('/{questionBank}', [QuestionBankController::class, 'destroy'])->name('destroy');
        });

        // Question Import Routes (separated)
        Route::prefix('soal-import')->name('question-import.')->group(function () {
            Route::get('/{tryout_detail_id}/download-template', [QuestionImportController::class, 'downloadTemplate'])->name('download-template');
            Route::post('/{tryout_detail_id}/import', [QuestionImportController::class, 'import'])->name('import');
        });

        Route::resource('tryout', AdminTryoutController::class);
        Route::post('tryout/{tryout}/clone', [AdminTryoutController::class, 'clone'])->name('tryout.clone');
        Route::get('tryout/{tryout}/preview', [AdminTryoutController::class, 'preview'])->name('tryout.preview');
        Route::post('tryout/{tryout}/release-utbk', [AdminTryoutController::class, 'releaseUtbk'])->name('tryout.release-utbk');
        Route::post('tryout/{tryout}/reset-utbk', [AdminTryoutController::class, 'resetUtbk'])->name('tryout.reset-utbk');

        // Material Management Routes
        Route::prefix('materi')->name('material.')->group(function () {
            Route::get('/', [MaterialManagementController::class, 'index'])->name('index');
            Route::get('/create', [MaterialManagementController::class, 'create'])->name('create');
            Route::post('/drive-title', [MaterialManagementController::class, 'driveTitle'])->name('drive-title');
            Route::post('/', [MaterialManagementController::class, 'store'])->name('store');
            Route::get('/{material}/edit', [MaterialManagementController::class, 'edit'])->name('edit');
            Route::put('/{material}', [MaterialManagementController::class, 'update'])->name('update');
            Route::delete('/{material}', [MaterialManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{material}/toggle', [MaterialManagementController::class, 'toggle'])->name('toggle');

            // Material Categories
            Route::prefix('kategori')->name('material-category.')->group(function () {
                Route::get('/', [MaterialCategoryController::class, 'index'])->name('index');
                Route::post('/', [MaterialCategoryController::class, 'store'])->name('store');
                Route::put('/{category}', [MaterialCategoryController::class, 'update'])->name('update');
                Route::delete('/{category}', [MaterialCategoryController::class, 'destroy'])->name('destroy');
            });
        });

        Route::prefix('feedback')->name('feedback.')->group(function () {
            Route::get('/', [AdminFeedbackController::class, 'index'])->name('index');
            Route::get('/{tryout}', [AdminFeedbackController::class, 'show'])->name('show');
            Route::get('/{tryout}/create', [AdminFeedbackController::class, 'create'])->name('create');
            Route::post('/{tryout}', [AdminFeedbackController::class, 'store'])->name('store');
            Route::get('/{tryout}/{question}/edit', [AdminFeedbackController::class, 'edit'])->name('edit');
            Route::put('/{tryout}/{question}', [AdminFeedbackController::class, 'update'])->name('update');
            Route::delete('/{tryout}/{question}', [AdminFeedbackController::class, 'destroy'])->name('destroy');
            Route::get('/{tryout}/responses', [AdminFeedbackController::class, 'responses'])->name('responses');
            Route::get('/{tryout}/responses/{submission}', [AdminFeedbackController::class, 'responseDetail'])->name('responses.detail');
        });

        Route::get('class/{class}/assessments', [ClassController::class, 'assessments'])->name('class.assessments');
        Route::post('class/{class}/assessments', [ClassController::class, 'storeAssessment'])->name('class.assessments.store');
        Route::delete('class/{class}/assessments/{assessmentType}', [ClassController::class, 'destroyAssessment'])->name('class.assessments.destroy');
        // Legacy URLs remain available while all newly generated links use the Tutor terminology.
        Route::get('tentor', fn () => redirect()->route('admin.tentors.index'))->middleware('module:tentor');
        Route::get('tentor/create', fn () => redirect()->route('admin.tentors.create'))->middleware('module:tentor');
        Route::get('tentor/{tentor}/edit', fn (Tentor $tentor) => redirect()->route('admin.tentors.edit', $tentor))->middleware('module:tentor');
        Route::get('tutor', fn () => redirect()->route('admin.user.index', ['role' => 'tutor']))
            ->name('tentors.index');
        Route::resource('tutor', TentorController::class)
            ->except(['index', 'show'])
            ->names('tentors')
            ->parameters(['tutor' => 'tentor']);
        Route::resource('rombel', StudyGroupController::class)
            ->except(['show'])
            ->names('study-groups')
            ->parameters(['rombel' => 'studyGroup']);
        Route::resource('jadwal-kelas', ClassScheduleController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names('class-schedules')
            ->parameters(['jadwal-kelas' => 'classSchedule']);
        Route::middleware('module:class')->group(function () {
            Route::get('jadwal-kelas/{classSchedule}', [ClassScheduleController::class, 'show'])->name('class-schedules.show');
            Route::post('jadwal-kelas/{classSchedule}/generate', [ClassScheduleController::class, 'generate'])->name('class-schedules.generate');
            Route::put('sesi-kelas/{session}', [ClassScheduleController::class, 'updateSession'])->name('class-sessions.update');
            Route::get('sesi-kelas/{session}/absensi', [ClassAttendanceController::class, 'show'])->name('class-attendance.show');
            Route::post('sesi-kelas/{session}/absensi', [ClassAttendanceController::class, 'mark'])->name('class-attendance.mark');
            Route::post('sesi-kelas/{session}/absensi-tutor', [ClassAttendanceController::class, 'markTutor'])->name('class-attendance.tutor.mark');
        });
        Route::get('penggajian-tutor', [TutorPayrollController::class, 'index'])->name('tutor-payrolls.index');
        Route::post('penggajian-tutor/generate', [TutorPayrollController::class, 'generate'])->name('tutor-payrolls.generate');
        Route::post('penggajian-tutor/honor', [TutorPayrollController::class, 'updateHonor'])->name('tutor-payrolls.honor.update');
        Route::put('penggajian-tutor/{tutorPayroll}', [TutorPayrollController::class, 'update'])->name('tutor-payrolls.update');
        Route::resource('class', ClassController::class);
        Route::resource('certification', CertificationController::class);
        Route::delete('/user/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('user.bulk-destroy');
        Route::get('/user/export/excel', [UserController::class, 'exportExcel'])->name('user.export-excel');
        Route::get('user/{user}/report', [UserController::class, 'report'])->name('user.report');
        Route::get('user/login-as-page', [UserController::class, 'loginAsPage'])->name('user.login-as-page');
        Route::post('user/{user}/login-as', [UserController::class, 'loginAs'])->name('user.login-as');
        Route::resource('user', UserController::class);
        Route::get('participant-destination-categories/official/institutions', [ParticipantDestinationCategoryController::class, 'officialInstitutions'])
            ->name('participant-destination-categories.official.institutions');
        Route::get('participant-destination-categories/official/programs', [ParticipantDestinationCategoryController::class, 'officialPrograms'])
            ->name('participant-destination-categories.official.programs');
        Route::post('participant-destination-categories/official-api-setting', [ParticipantDestinationCategoryController::class, 'updateOfficialApiSetting'])
            ->name('participant-destination-categories.official-api-setting');
        Route::resource('participant-destination-categories', ParticipantDestinationCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['participant-destination-categories' => 'participantDestinationCategory']);
        Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');

        // Route untuk admin leaderboard
        Route::prefix('leaderboard')->name('leaderboard.')->group(function () {
            Route::get('/', [LeaderboardController::class, 'index'])->name('index');
            Route::get('/{package_id}/{tryout_id}/export/excel', [LeaderboardController::class, 'exportExcel'])->name('export-excel');
            Route::get('/{package_id}/{tryout_id}/export/pdf', [LeaderboardController::class, 'exportPdf'])->name('export-pdf');
            Route::get('/{package_id}/{tryout_id}', [LeaderboardController::class, 'show'])->name('show');
        });

        Route::resource('discussion', DiscussionController::class);
        Route::resource('faq', FaqController::class)->except(['show']);

        // Koreksi essay
        Route::prefix('essay-review')->name('essay-review.')->group(function () {
            Route::get('/', [EssayReviewController::class, 'index'])->name('index');
            Route::post('/automatic/start', [EssayReviewController::class, 'startAutomaticCorrection'])->name('automatic.start');
            Route::get('/jobs/status', [EssayReviewController::class, 'getJobStatus'])->name('jobs.status');
            Route::delete('/jobs/{job}', [EssayReviewController::class, 'deleteJob'])->name('jobs.delete');
            Route::post('/jobs/{job}/retry', [EssayReviewController::class, 'retryJob'])->name('jobs.retry');
            Route::get('/{tryout}', [EssayReviewController::class, 'tryout'])->name('tryout');
            Route::get('/{tryout}/user/{user}', [EssayReviewController::class, 'user'])->name('user');
            Route::post('/{detail}/review', [EssayReviewController::class, 'review'])->name('review');
        });

        // Tes Koran Routes
        Route::prefix('tes-koran')->name('tes-koran.')->group(function () {
            Route::get('/', [AdminTesKoranController::class, 'index'])->name('index');
            Route::get('/tambah', [AdminTesKoranController::class, 'create'])->name('create');
            Route::post('/tambah', [AdminTesKoranController::class, 'store'])->name('store');
            Route::get('/{tesKoran}/edit', [AdminTesKoranController::class, 'edit'])->name('edit');
            Route::put('/{tesKoran}/edit', [AdminTesKoranController::class, 'update'])->name('update');
            Route::delete('/{tesKoran}', [AdminTesKoranController::class, 'destroy'])->name('destroy');
            Route::post('/{tesKoran}/toggle', [AdminTesKoranController::class, 'toggle'])->name('toggle');
            Route::get('/{tesKoran}/hasil', [AdminTesKoranController::class, 'results'])->name('results');
            Route::get('/{tesKoran}/hasil/export', [AdminTesKoranController::class, 'export'])->name('results.export');
            Route::get('/{tesKoran}/preview', [AdminTesKoranController::class, 'preview'])->name('preview');
        });

        // Route untuk laporan user
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/', [LaporanController::class, 'index'])->name('index');
            Route::get('/export/excel', [LaporanController::class, 'exportExcel'])->name('export-excel');
            Route::get('/export/pdf', [LaporanController::class, 'exportPdf'])->name('export-pdf');
            Route::get('/{tryout}/proctoring-snapshots', [LaporanController::class, 'proctoringSnapshots'])->name('proctoring-snapshots');
            Route::delete('/{tryout}/proctoring-snapshots', [LaporanController::class, 'destroyAllProctoringSnapshots'])->name('proctoring-snapshots.destroy-all');
            Route::delete('/{tryout}/proctoring-snapshots/{snapshot}', [LaporanController::class, 'destroyProctoringSnapshot'])->name('proctoring-snapshots.destroy');
            Route::get('/{tryout}/attempt/{token}', [LaporanController::class, 'attemptDetail'])->name('attempt');
            Route::post('/{tryout}/attempt/{token}/reset', [LaporanController::class, 'resetAttempt'])->name('reset-attempt');
            Route::post('/{tryout}/user/{user}/add-time', [LaporanController::class, 'addTime'])->name('add-time');
            Route::post('/{tryout}/user/{user}/reset', [LaporanController::class, 'resetUserAttempt'])->name('reset-user');
            Route::get('/{id}', [LaporanController::class, 'show'])->name('show');
        });

        // Route akses user
        Route::prefix('akses')->name('akses.')->group(function () {
            Route::get('/', [AksesController::class, 'index'])->name('index');
            Route::get('/manage', [AksesController::class, 'manage'])->name('manage');
            Route::get('/pengajuan', [AksesController::class, 'requests'])->name('requests.index');
            Route::post('/grant', [AksesController::class, 'grant'])->name('grant');
            Route::post('/grant-rombel', [AksesController::class, 'grantStudyGroup'])->name('grant-study-group');
            Route::post('/revoke', [AksesController::class, 'revoke'])->name('revoke');

            // Legacy routes for backward compatibility
            Route::get('/paket/{package_id}', [AksesController::class, 'show'])->name('show');
            Route::post('/pengajuan/{access}/approve', [AksesController::class, 'approveRequest'])->name('requests.approve');
            Route::post('/pengajuan/{access}/reject', [AksesController::class, 'rejectRequest'])->name('requests.reject');
        });

        // Route pembayaran
        Route::prefix('pembayaran')->name('pembayaran.')->group(function () {
            Route::get('/', [PembayaranController::class, 'index'])->name('index');
            Route::get('/manual/create', [PembayaranController::class, 'createManual'])->name('manual.create');
            Route::post('/manual', [PembayaranController::class, 'storeManual'])->name('manual');
            Route::post('/{payment}/cicilan', [PembayaranController::class, 'recordInstallment'])->name('installments.store');
            Route::get('/item/{id}', [PembayaranController::class, 'showIndividual'])->name('item.show');
            Route::post('/item/{id}/confirm', [PembayaranController::class, 'confirmIndividual'])->name('item.confirm');
            Route::post('/item/{id}/reject', [PembayaranController::class, 'rejectIndividual'])->name('item.reject');
            Route::get('/{id}', [PembayaranController::class, 'show'])->name('show');
            Route::post('/{id}/confirm', [PembayaranController::class, 'confirm'])->name('confirm');
            Route::post('/{id}/reject', [PembayaranController::class, 'reject'])->name('reject');
        });

        // Certificate Management Routes
        Route::prefix('sertifikat')->name('certificate.')->middleware('certificate.enabled')->group(function () {
            Route::get('/', [CertificateController::class, 'index'])->name('index');
            Route::get('/create', [CertificateController::class, 'create'])->name('create');
            Route::post('/store', [CertificateController::class, 'store'])->name('store');
            Route::get('/{certificate}/show', [CertificateController::class, 'show'])->name('show');
            Route::get('/{certificate}/edit', [CertificateController::class, 'edit'])->name('edit');
            Route::put('/{certificate}/update', [CertificateController::class, 'update'])->name('update');
            Route::delete('/{certificate}/destroy', [CertificateController::class, 'destroy'])->name('destroy');
            Route::get('/{certificate}/download-template', [CertificateController::class, 'downloadTemplate'])->name('download-template');
            Route::post('/bulk-action', [CertificateController::class, 'bulkAction'])->name('bulk-action');
        });

        // Certificate download route
        Route::get('certificate/{certificate}/download', [CertificateController::class, 'downloadTemplate'])
            ->middleware('certificate.enabled')
            ->name('certificate.downloadTemplate');
        Route::post('certificate/bulk-action', [CertificateController::class, 'bulkAction'])
            ->middleware('certificate.enabled')
            ->name('certificate.bulkAction');
    });
