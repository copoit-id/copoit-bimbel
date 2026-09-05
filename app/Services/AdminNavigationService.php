<?php

namespace App\Services;

use App\Models\GeneralPage;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AdminNavigationService
{
    public function __construct(
        private readonly PlanModuleService $planModules,
        private readonly TutorContentVisibilityService $tutorContentVisibility,
        private readonly TutorChatService $tutorChatService,
    ) {
    }

    /**
     * Prepare every navigation decision for the shared admin/tutor sidebar.
     *
     * The Blade component deliberately receives only render-ready flags so that
     * plan access, role permissions, branding switches, and route availability
     * remain in one server-side source of truth.
     *
     * @return array<string, mixed>
     */
    public function context(?User $user): array
    {
        $branding = (array) config('client.branding', []);
        $isTutor = $user?->isTutor() ?? false;
        $canAccessAdminPanel = $user?->canAccessAdminPanel() ?? false;
        $featureVisibility = [];

        foreach ([
            'dashboard', 'question_bank', 'package', 'schedule', 'class',
            'tryout', 'study_group', 'material', 'tes_koran',
            'material_category', 'user', 'akses', 'leaderboard', 'laporan',
            'essay_review', 'feedback', 'finance', 'pembayaran',
            'recurring_bill', 'tutor_payroll', 'discount', 'affiliate',
            'general_page', 'artikel', 'faq', 'certificate',
            'update_notification', 'settings', 'booking',
            'activity',
        ] as $feature) {
            $featureVisibility[$feature] = $this->canViewFeature(
                $user,
                $feature,
                $canAccessAdminPanel,
            );
        }

        $routeIs = static fn (string ...$patterns): bool => request()->routeIs(...$patterns);
        $routeExists = static fn (string $route): bool => Route::has($route);
        $generalPublicVisibility = $this->publicPageVisibility();

        $canShowDestinationCategories = $featureVisibility['user']
            && $routeExists('admin.participant-destination-categories.index');
        $canShowMaterialMenu = $featureVisibility['material'] && $routeExists('admin.material.index');
        $canShowMaterialCategoryMenu = $featureVisibility['material_category']
            && $routeExists('admin.material.material-category.index');
        $canShowDiscountMenu = (bool) ($branding['discount_menu_enabled'] ?? true)
            && $featureVisibility['discount'] && $routeExists('admin.discounts.index');
        $canShowAffiliateMenu = (bool) ($branding['affiliate_menu_enabled'] ?? false)
            && $featureVisibility['affiliate'] && $routeExists('admin.affiliate.index');
        $canShowClassScheduleMenu = $featureVisibility['schedule'] && $routeExists('admin.class-schedules.index');
        $canShowLegacyClassMenu = $featureVisibility['class'] && $routeExists('admin.class.index');
        $canShowStudyGroupMenu = $featureVisibility['study_group'] && $routeExists('admin.study-groups.index');
        $canShowRecurringBillMenu = (bool) ($branding['recurring_bill_menu_enabled'] ?? false)
            && $featureVisibility['recurring_bill'] && $routeExists('admin.recurring-bills.index');
        $canShowTutorPayrollMenu = $featureVisibility['tutor_payroll'] && $routeExists('admin.tutor-payrolls.index');
        $canShowFinanceMenu = $featureVisibility['finance'] && $routeExists('admin.finance.income.index');
        $canShowPaymentsMenu = $featureVisibility['pembayaran'] && $routeExists('admin.pembayaran.index');
        $canShowActivityMenu = $featureVisibility['activity'] && $routeExists('admin.activity.index');
        $canShowUpdateNotificationsMenu = ($featureVisibility['update_notification'] ?? false)
            && $routeExists('admin.update-notifications.index');
        $canShowTutorScheduleMenu = $isTutor && $this->planModules->allows('schedule') && $routeExists('tutor.schedule.index');
        $canShowTutorDashboard = $isTutor && $routeExists('tutor.dashboard');
        $canShowTutorAttendanceMenu = $canShowTutorScheduleMenu && $this->planModules->allows('attendance')
            && $routeExists('tutor.attendance.index');
        $canShowTutorEarningsMenu = $isTutor && $this->planModules->allows('tutor_payroll')
            && $routeExists('tutor.earnings.index');
        $canShowTutorBookingMenu = $isTutor && (bool) ($branding['booking_schedule_enabled'] ?? false)
            && $this->planModules->allows('booking') && $routeExists('tutor.booking.index');
        $canShowTutorDevelopmentMenu = $isTutor && (bool) ($branding['learning_progress_enabled'] ?? false)
            && $this->planModules->allows('booking') && $routeExists('tutor.development.index');
        $canShowTutorChatMenu = $isTutor && (bool) ($branding['tutor_chat_enabled'] ?? false)
            && $this->planModules->allows('discussion') && $routeExists('tutor.chat.index');
        $canShowTutorProfileMenu = $isTutor && $this->planModules->allows('profile') && $routeExists('tutor.profile.edit');
        $isMaterialManagementActive = $routeIs('admin.material.index', 'admin.material.create', 'admin.material.edit');
        $isClassScheduleActive = $canShowClassScheduleMenu && $routeIs('admin.class-schedules.*', 'admin.class-attendance.*');
        $canShowMasterMenu = $featureVisibility['package'] || $canShowClassScheduleMenu || $canShowLegacyClassMenu
            || $featureVisibility['tryout'] || $canShowStudyGroupMenu || $canShowMaterialMenu || $featureVisibility['tes_koran'];
        $canShowCategoryMenu = $canShowMaterialCategoryMenu || $canShowDestinationCategories;
        $canShowUserMenu = $featureVisibility['user'] || $featureVisibility['akses'];
        $canShowReportMenu = $featureVisibility['leaderboard'] || $featureVisibility['laporan']
            || $featureVisibility['essay_review'] || $featureVisibility['feedback'];
        $canShowFinanceSection = $canShowFinanceMenu || $canShowPaymentsMenu || $canShowRecurringBillMenu || $canShowTutorPayrollMenu;
        $canShowAdminLanding = $featureVisibility['general_page'] && (bool) ($generalPublicVisibility['landing'] ?? false);
        $canShowAdminArticles = $featureVisibility['artikel'] && (bool) ($generalPublicVisibility['artikel'] ?? false);

        return [
            'sidebarPrimary' => (bool) ($branding['sidebar_primary_color'] ?? false),
            'sidebarWrapperClasses' => (bool) ($branding['sidebar_primary_color'] ?? false) ? 'bg-primary border-r border-primary text-white' : 'bg-white border-r border-gray-200',
            'sidebarInnerClasses' => (bool) ($branding['sidebar_primary_color'] ?? false) ? 'bg-primary text-white' : 'bg-white',
            'sectionLabelClass' => (bool) ($branding['sidebar_primary_color'] ?? false) ? 'text-white/70' : 'text-[#999999]',
            'linkActiveClass' => (bool) ($branding['sidebar_primary_color'] ?? false) ? 'bg-white/10 text-white' : 'bg-primary text-white',
            'linkInactiveClass' => (bool) ($branding['sidebar_primary_color'] ?? false) ? 'text-white/80 hover:bg-white/10' : 'text-black hover:bg-gray-100',
            'iconActiveClass' => 'text-white',
            'iconInactiveClass' => (bool) ($branding['sidebar_primary_color'] ?? false) ? 'text-white/80' : 'text-black',
            'isTutor' => $isTutor,
            'isTutorContentIsolated' => $isTutor && $this->tutorContentVisibility->isIsolated(),
            'featureVisibility' => $featureVisibility,
            'canShowDestinationCategories' => $canShowDestinationCategories,
            'canShowMaterialMenu' => $canShowMaterialMenu,
            'canShowMaterialCategoryMenu' => $canShowMaterialCategoryMenu,
            'canShowDiscountMenu' => $canShowDiscountMenu,
            'canShowAffiliateMenu' => $canShowAffiliateMenu,
            'canShowClassScheduleMenu' => $canShowClassScheduleMenu,
            'canShowLegacyClassMenu' => $canShowLegacyClassMenu,
            'canShowStudyGroupMenu' => $canShowStudyGroupMenu,
            'canShowRecurringBillMenu' => $canShowRecurringBillMenu,
            'canShowTutorPayrollMenu' => $canShowTutorPayrollMenu,
            'canShowFinanceMenu' => $canShowFinanceMenu,
            'canShowPaymentsMenu' => $canShowPaymentsMenu,
            'canShowActivityMenu' => $canShowActivityMenu,
            'canShowUpdateNotificationsMenu' => $canShowUpdateNotificationsMenu,
            'canShowTutorDashboard' => $canShowTutorDashboard,
            'canShowTutorScheduleMenu' => $canShowTutorScheduleMenu,
            'canShowTutorAttendanceMenu' => $canShowTutorAttendanceMenu,
            'canShowTutorEarningsMenu' => $canShowTutorEarningsMenu,
            'canShowTutorBookingMenu' => $canShowTutorBookingMenu,
            'canShowTutorDevelopmentMenu' => $canShowTutorDevelopmentMenu,
            'canShowTutorChatMenu' => $canShowTutorChatMenu,
            'canShowTutorProfileMenu' => $canShowTutorProfileMenu,
            'tutorChatUnreadCount' => $canShowTutorChatMenu ? $this->tutorChatService->unreadCountFor($user) : 0,
            'canShowMasterMenu' => $canShowMasterMenu,
            'canShowCategoryMenu' => $canShowCategoryMenu,
            'canShowUserMenu' => $canShowUserMenu,
            'canShowReportMenu' => $canShowReportMenu,
            'canShowFinanceSection' => $canShowFinanceSection,
            'canShowAdminLanding' => $canShowAdminLanding,
            'canShowAdminArticles' => $canShowAdminArticles,
            'canShowCertificateMenu' => (bool) ($branding['certificate_management_enabled'] ?? true) && $featureVisibility['certificate'],
            'faqLabel' => (string) ($branding['faq_label'] ?? 'FAQ'),
            'isMaterialManagementActive' => $isMaterialManagementActive,
            'isCategoryActive' => $routeIs('admin.material.material-category.*') || ($canShowDestinationCategories && $routeIs('admin.participant-destination-categories.*')),
            'isClassScheduleActive' => $isClassScheduleActive,
            'isMasterActive' => $routeIs('admin.package.*')
                || ($canShowStudyGroupMenu && $routeIs('admin.study-groups.*', 'admin.package-booking.*'))
                || $isClassScheduleActive || ($canShowLegacyClassMenu && $routeIs('admin.class.*'))
                || $routeIs('admin.tryout.*', 'admin.question.*', 'admin.tes-koran.*') || $isMaterialManagementActive,
            'isTutorDashboardActive' => $isTutor && $routeIs('tutor.dashboard'),
            'isTutorScheduleActive' => $isTutor && $routeIs('tutor.schedule.*'),
            'isTutorAttendanceActive' => $isTutor && $routeIs('tutor.attendance.*'),
            'isTutorEarningsActive' => $isTutor && $routeIs('tutor.earnings.*'),
            'isTutorBookingActive' => $isTutor && $routeIs('tutor.booking.*'),
            'isTutorDevelopmentActive' => $isTutor && $routeIs('tutor.development.*'),
            'isTutorChatActive' => $isTutor && $routeIs('tutor.chat.*'),
            'isTutorProfileActive' => $isTutor && $routeIs('tutor.profile.*'),
            'isTesKoranActive' => $routeIs('admin.tes-koran.*'),
            'isUserActive' => $routeIs('admin.user.*', 'admin.akses.*', 'admin.tentors.*'),
            'isReportActive' => $routeIs('admin.leaderboard.*', 'admin.laporan.*', 'admin.essay-review.*', 'admin.feedback.*'),
            'isFinanceActive' => $routeIs('admin.finance.*', 'admin.pembayaran.*')
                || ($canShowRecurringBillMenu && $routeIs('admin.recurring-bills.*'))
                || ($canShowTutorPayrollMenu && $routeIs('admin.tutor-payrolls.*')),
            'isGeneralActive' => $routeIs('admin.general-pages.*') || ($canShowAdminArticles && $routeIs('admin.artikel.*')),
        ];
    }

    private function canViewFeature(?User $user, string $feature, bool $canAccessAdminPanel): bool
    {
        if (! $this->planModules->allows($feature)) {
            return false;
        }

        return $feature === 'dashboard'
            ? $canAccessAdminPanel
            : $user?->hasPermission($feature, 'view') ?? false;
    }

    /**
     * @return array<string, bool>
     */
    private function publicPageVisibility(): array
    {
        if (! Schema::hasTable('general_pages')) {
            return [];
        }

        return GeneralPage::query()
            ->whereIn('page_key', ['landing', 'statistik-ptn', 'artikel'])
            ->pluck('is_active', 'page_key')
            ->map(static fn (mixed $isActive): bool => (bool) $isActive)
            ->all();
    }
}
