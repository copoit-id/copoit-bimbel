<?php

use App\Http\Controllers\admin\AksesController;
use App\Http\Controllers\admin\CertificationController;
use App\Http\Controllers\admin\CertificateController;
use App\Http\Controllers\admin\ClassController;
use App\Http\Controllers\admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\admin\DiscussionController;
use App\Http\Controllers\admin\EssayReviewController;
use App\Http\Controllers\admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\admin\LaporanController;
use App\Http\Controllers\admin\LeaderboardController;
use App\Http\Controllers\admin\PackageController as AdminPackageController;
use App\Http\Controllers\admin\PembayaranController;
use App\Http\Controllers\admin\QuestionController;
use App\Http\Controllers\admin\QuestionBankController;
use App\Http\Controllers\admin\SettingController;
use App\Http\Controllers\admin\TryoutController as AdminTryoutController;
use App\Http\Controllers\admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\user\CertificateValidationController;
use App\Http\Controllers\user\DashboardController;
use App\Http\Controllers\user\EventController;
use App\Http\Controllers\user\FeedbackController as UserFeedbackController;
use App\Http\Controllers\user\HelpController;
use App\Http\Controllers\user\PackageController;
use App\Http\Controllers\user\TryoutController;
use App\Http\Controllers\superadmin\SuperAdminController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
// routes/web.php
Route::get('/phpinfo', function () {
    phpinfo();
});

Route::get('/m1grat3', function () {
    Artisan::call('migrate');
    Artisan::call('storage:link');
    Artisan::call('optimize:clear');
});

Route::get('/setup-project', function () {
    $results = [];

    try {
        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
            $results[] = 'APP_KEY generated.';
        } else {
            $results[] = 'APP_KEY sudah tersedia, skip key:generate.';
        }

        $commands = [
            ['command' => 'storage:link', 'options' => []],
            ['command' => 'migrate', 'options' => ['--force' => true]],
            ['command' => 'db:seed', 'options' => ['--force' => true]],
            ['command' => 'optimize:clear', 'options' => []],
        ];

        foreach ($commands as $item) {
            Artisan::call($item['command'], $item['options']);
            $results[] = sprintf('Artisan %s selesai.', $item['command']);
        }

        return response()->json([
            'status' => 'success',
            'steps' => $results,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.admins.index');
        }

        return $user->canAccessAdminPanel()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('user.dashboard.index');
    }

    return redirect()->route('login');
});

Route::get('/live-score/{tryout}', [LaporanController::class, 'publicLiveScore'])
    ->middleware('signed')
    ->name('laporan.live-score.public');

// Authentication routes

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');


Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Route untuk logout as (admin kembali ke akun admin)
Route::post('/logout-as', [UserController::class, 'logoutAs'])->middleware('auth')->name('logout-as');

// User routes (add auth middleware)
Route::prefix('user')->middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard.index');

    // Profile routes
    Route::get('/profile', [\App\Http\Controllers\user\ProfileController::class, 'index'])->name('user.profile.index');
    Route::put('/profile', [\App\Http\Controllers\user\ProfileController::class, 'update'])->name('user.profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\user\ProfileController::class, 'updatePassword'])->name('user.profile.password.update');

    Route::prefix('paket-pembelian')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('user.package.index');
        Route::post('/{package_id}/buy', [PackageController::class, 'buyPackage'])->name('user.package.buy');
        Route::get('/payment/success', [PackageController::class, 'paymentSuccess'])->name('user.package.payment.success');
        Route::get('/payment/failed', [PackageController::class, 'paymentFailed'])->name('user.package.payment.failed');
        Route::get('/riwayat-pembelian', [PackageController::class, 'riwayatPembelian'])->name('user.package.riwayatPembelian');
        Route::get('/riwayat-pembelian/paket-aktif', [PackageController::class, 'riwayatPembelianAktif'])->name('user.package.riwayatPembelianAktif');
        Route::get('/{id_package}/bimbel', [PackageController::class, 'indexBimbel'])->name('user.package.bimbel');
        Route::get('/{id_package}/tryout', [PackageController::class, 'indexTryout'])->name('user.package.tryout');
        Route::get('/{id_package}/tryout/{id_tryout}/riwayat', [PackageController::class, 'riwayatTryout'])->name('user.package.tryout.riwayat');
        Route::get('/{id_package}/tryout/{id_tryout}/ranking', [PackageController::class, 'rankingTryout'])->name('user.package.tryout.ranking');
        Route::get('/{id_package}/tryout/{id_tryout}/pembahasan/{token}', [PackageController::class, 'pembahasanTryout'])->name('user.package.tryout.pembahasan');
    });

    Route::prefix('event')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('user.event.index');
        Route::post('/{package_id}/join', [EventController::class, 'joinEvent'])->name('user.event.join');
        Route::post('/tryout/{tryout_id}/join', [EventController::class, 'joinFreeTryout'])->name('user.event.tryout.join');
    });

    Route::get('/bantuan', [HelpController::class, 'index'])->name('user.help.index');

    Route::prefix('tryout')->group(function () {
        Route::get('/{id_package}/{id_tryout}/lobby', [TryoutController::class, 'indexLobby'])->name('user.tryout.lobby');
        Route::get('/{id_package}/{id_tryout}/tryout/{number}', [TryoutController::class, 'indexTryout'])->name('user.tryout.index');
        Route::post('/{id_package}/{id_tryout}/tryout/{number}/save', [TryoutController::class, 'saveAnswer'])->name('user.tryout.save');
        Route::post('/{id_package}/{id_tryout}/subtest/flush', [TryoutController::class, 'flushSubtestAnswers'])->name('user.tryout.subtest.flush');
        Route::post('/{id_package}/{id_tryout}/flag', [TryoutController::class, 'toggleFlag'])->name('user.tryout.flag');
        Route::post('/{id_package}/{id_tryout}/finish', [TryoutController::class, 'finishTryout'])->name('user.tryout.finish');
        Route::get('/{id_package}/{id_tryout}/hasil', [TryoutController::class, 'indexResult'])->name('user.tryout.result');
        Route::post('/{id_package}/{id_tryout}/feedback', [UserFeedbackController::class, 'store'])->name('user.tryout.feedback.store');
        Route::post(
            '/listening/mark-played/{id_package}/{id_tryout}/{question_id}',
            [TryoutController::class, 'markPlayed']
        )->name('user.tryout.markPlayed');
    });

    Route::prefix('package')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('user.package.index');
        Route::get('/tryout-list', [PackageController::class, 'listTryout'])->name('user.package.tryout.list');
        Route::get('/sertifikasi-list', [PackageController::class, 'listSertifikasi'])->name('user.package.sertifikasi.list');
        Route::post('/buy/{package_id}', [PackageController::class, 'buyPackage'])->name('user.package.buy');

        // Existing routes
        Route::get('/bimbel/{id_package}', [PackageController::class, 'indexBimbel'])->name('user.package.bimbel');
        Route::get('/tryout/{id_package}', [PackageController::class, 'indexTryout'])->name('user.package.tryout');
        Route::get('/sertifikasi/{id_package}', [PackageController::class, 'indexSertifikasi'])->name('user.package.sertifikasi');
        Route::get('/{id_package}/tryout/{id_tryout}/riwayat', [PackageController::class, 'riwayatTryout'])->name('user.package.tryout.riwayat');
        Route::get('/{id_package}/tryout/{id_tryout}/ranking', [PackageController::class, 'rankingTryout'])->name('user.package.tryout.ranking');
        Route::get('/{id_package}/tryout/{id_tryout}/statistik', [PackageController::class, 'statistikTryout'])->name('user.package.tryout.statistik');
    });

    // Certificate validation routes
    Route::prefix('sertifikat')->middleware('certificate.enabled')->group(function () {
        Route::get('/validasi', [CertificateValidationController::class, 'index'])->name('user.certificate.validation');
        Route::post('/validasi', [CertificateValidationController::class, 'validateCertificate'])->name('user.certificate.validate');
        Route::post('/download', [CertificateValidationController::class, 'downloadCertificate'])->name('user.certificate.download');
        Route::get('/download/{certificate_id}', [CertificateValidationController::class, 'downloadById'])->name('user.certificate.validation.download');

        // Certificate generation routes
        Route::get('/preview/{package_id}/{tryout_id}/{token}', [\App\Http\Controllers\user\CertificateController::class, 'preview'])->name('user.certificate.preview');
        Route::get('/view/{certificate_id}/{token}', [\App\Http\Controllers\user\CertificateController::class, 'view'])->name('user.certificate.view');
        Route::get('/preview-with-data/{certificate_id}/{token}', [\App\Http\Controllers\user\CertificateController::class, 'previewWithData'])->name('user.certificate.preview.with.data');
        Route::get('/download/{certificate_id}/{token}', [\App\Http\Controllers\user\CertificateController::class, 'download'])->name('user.certificate.download');

        // Template preview route
        Route::get('/template/preview', [\App\Http\Controllers\user\CertificateController::class, 'previewTemplate'])->name('user.certificate.template.preview');
        Route::get('/template/test', [\App\Http\Controllers\user\CertificateController::class, 'testSertifikat'])->name('user.certificate.template.test');
    });
});

// Webhook route (outside auth middleware) - make sure this is correct
Route::post('/webhook/xendit', [PackageController::class, 'xenditWebhook'])->name('webhook.xendit');
Route::post('/webhook/midtrans', [PackageController::class, 'midtransWebhook'])->name('webhook.midtrans');

// Add route for checking payment status (for debugging)
Route::get('/admin/payment/{paymentId}/check', [PackageController::class, 'checkPaymentStatus'])->middleware(['auth', AdminMiddleware::class, 'admin.expiry']);

// Add route for manual payment activation
Route::post('/admin/payment/{paymentId}/activate', [PackageController::class, 'manualActivatePayment'])->middleware(['auth', AdminMiddleware::class, 'admin.expiry']);

// Super Admin Routes
Route::prefix('super-admin')->name('super-admin.')->middleware(['auth', 'super-admin'])->group(function () {
    Route::get('/admins', [SuperAdminController::class, 'index'])->name('admins.index');
    Route::post('/admins', [SuperAdminController::class, 'store'])->name('admins.store');
    Route::put('/admins/{admin}', [SuperAdminController::class, 'update'])->name('admins.update');
    Route::get('/roles', [\App\Http\Controllers\superadmin\RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [\App\Http\Controllers\superadmin\RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [\App\Http\Controllers\superadmin\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [\App\Http\Controllers\superadmin\RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/{role}/permissions', [\App\Http\Controllers\superadmin\RoleController::class, 'updatePermissions'])->name('roles.permissions');
});

// Admin Routes (add auth middleware)
Route::prefix('admin')->name('admin.')->middleware(['auth', AdminMiddleware::class, 'admin.expiry', 'permission'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Profile routes
    Route::get('/profile', [\App\Http\Controllers\admin\ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [\App\Http\Controllers\admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\admin\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Admin User Import Routes
    Route::get('/user/import', [\App\Http\Controllers\admin\UserImportController::class, 'showImportForm'])->name('user.import');
    Route::post('/user/import', [\App\Http\Controllers\admin\UserImportController::class, 'import'])->name('user.import.process');
    Route::get('/user/import/template', [\App\Http\Controllers\admin\UserImportController::class, 'downloadTemplate'])->name('user.import.template');
    Route::get('/user/import/status/{token}', function (string $token) {
        return response()->json([
            'progress' => cache()->get("import_users:{$token}:progress"),
            'done'     => cache()->get("import_users:{$token}:done", false),
        ]);
    })->name('user.import.status');


    // Package Management - Gunakan AdminPackageController dengan alias
    Route::get('/paket', [AdminPackageController::class, 'index'])->name('package.index');
    Route::get('/paket/tambah', [AdminPackageController::class, 'create'])->name('package.create');
    Route::post('/paket/store', [AdminPackageController::class, 'store'])->name('package.store');
    Route::get('/paket/{package_id}/edit', [AdminPackageController::class, 'edit'])->name('package.edit');
    Route::put('/paket/{package_id}/update', [AdminPackageController::class, 'update'])->name('package.update');
    Route::delete('/paket/{package_id}/destroy', [AdminPackageController::class, 'destroy'])->name('package.destroy');

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
        Route::get('/{questionBank}', [QuestionBankController::class, 'show'])->name('show');
        Route::get('/{questionBank}/questions/create', [QuestionBankController::class, 'createQuestionForm'])->name('questions.create');
        Route::post('/{questionBank}/questions', [QuestionBankController::class, 'storeQuestion'])->name('questions.store');
        Route::get('/questions/{question}/edit', [QuestionBankController::class, 'editQuestionForm'])->name('questions.edit');
        Route::put('/questions/{question}', [QuestionBankController::class, 'updateQuestion'])->name('questions.update');
        Route::delete('/questions/{question}', [QuestionBankController::class, 'destroyQuestion'])->name('questions.destroy');
        Route::post('/questions/{question}/clone', [QuestionBankController::class, 'cloneToTryout'])->name('questions.clone');
        Route::post('/questions/bulk-clone', [QuestionBankController::class, 'bulkCloneToTryout'])->name('questions.bulk-clone');
    });

    // Question Import Routes (separated)
    Route::prefix('soal-import')->name('question-import.')->group(function () {
        Route::get('/{tryout_detail_id}/download-template', [\App\Http\Controllers\admin\QuestionImportController::class, 'downloadTemplate'])->name('download-template');
        Route::post('/{tryout_detail_id}/import', [\App\Http\Controllers\admin\QuestionImportController::class, 'import'])->name('import');
    });

    Route::resource('tryout', AdminTryoutController::class);
    Route::get('tryout/{tryout}/preview', [AdminTryoutController::class, 'preview'])->name('tryout.preview');
    Route::post('tryout/{tryout}/release-utbk', [AdminTryoutController::class, 'releaseUtbk'])->name('tryout.release-utbk');
    Route::post('tryout/{tryout}/reset-utbk', [AdminTryoutController::class, 'resetUtbk'])->name('tryout.reset-utbk');

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
    Route::resource('class', ClassController::class);
    Route::resource('certification', CertificationController::class);
    Route::delete('/user/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('user.bulk-destroy');
    Route::get('user/{user}/report', [UserController::class, 'report'])->name('user.report');
    Route::get('user/login-as-page', [UserController::class, 'loginAsPage'])->name('user.login-as-page');
    Route::post('user/{user}/login-as', [UserController::class, 'loginAs'])->name('user.login-as');
    Route::resource('user', UserController::class);
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
    Route::resource('faq', \App\Http\Controllers\admin\FaqController::class)->except(['show']);

    // Koreksi essay
    Route::prefix('essay-review')->name('essay-review.')->group(function () {
        Route::get('/', [EssayReviewController::class, 'index'])->name('index');
        Route::get('/{tryout}', [EssayReviewController::class, 'tryout'])->name('tryout');
        Route::get('/{tryout}/user/{user}', [EssayReviewController::class, 'user'])->name('user');
        Route::post('/{detail}/review', [EssayReviewController::class, 'review'])->name('review');
    });

    // Route untuk laporan user
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/export/excel', [LaporanController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export/pdf', [LaporanController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{tryout}/attempt/{token}', [LaporanController::class, 'attemptDetail'])->name('attempt');
        Route::post('/{tryout}/attempt/{token}/reset', [LaporanController::class, 'resetAttempt'])->name('reset-attempt');
        Route::post('/{tryout}/user/{user}/add-time', [LaporanController::class, 'addTime'])->name('add-time');
        Route::post('/{tryout}/user/{user}/reset', [LaporanController::class, 'resetUserAttempt'])->name('reset-user');
        Route::get('/{id}', [LaporanController::class, 'show'])->name('show');
    });

    // Route akses user
    Route::prefix('akses')->name('akses.')->group(function () {
        Route::get('/', [AksesController::class, 'index'])->name('index');
        Route::get('/paket/{package_id}', [AksesController::class, 'show'])->name('show');
        Route::get('/paket/{package_id}/user/{user_id}', [AksesController::class, 'detail'])->name('detail');
        Route::get('/paket/{package_id}/create', [AksesController::class, 'create'])->name('create');
        Route::post('/paket/{package_id}/store', [AksesController::class, 'store'])->name('store');
        Route::post('/paket/{package_id}/bulk-destroy', [AksesController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/paket/{package_id}/user/{user_id}/extend', [AksesController::class, 'extendAccess'])->name('extend');
        Route::post('/paket/{package_id}/user/{user_id}/revoke', [AksesController::class, 'revokeAccess'])->name('revoke');
        Route::post('/paket/{package_id}/user/{user_id}/toggle', [AksesController::class, 'toggleStatus'])->name('toggle');
        Route::post('/pengajuan/{access}/approve', [AksesController::class, 'approveRequest'])->name('requests.approve');
        Route::post('/pengajuan/{access}/reject', [AksesController::class, 'rejectRequest'])->name('requests.reject');
    });

    // Route pembayaran
    Route::prefix('pembayaran')->name('pembayaran.')->group(function () {
        Route::get('/', [PembayaranController::class, 'index'])->name('index');
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
    Route::get('certificate/{certificate}/download', [\App\Http\Controllers\admin\CertificateController::class, 'downloadTemplate'])
        ->middleware('certificate.enabled')
        ->name('certificate.downloadTemplate');
    Route::post('certificate/bulk-action', [\App\Http\Controllers\admin\CertificateController::class, 'bulkAction'])
        ->middleware('certificate.enabled')
        ->name('certificate.bulkAction');
});
