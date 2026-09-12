<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class UserNavigationService
{
    public function context(?User $user): array
    {
        $branding = (array) config('client.branding', []);
        $planModules = app(PlanModuleService::class);
        $canUseAiDiscussion = (bool) ($branding['ai_discussion_feature_enabled'] ?? false)
            && (bool) data_get($branding, 'ai_discussion_settings.enabled', false);
        $canUseTutorChat = $user
            && ! $user->isTutor()
            && (bool) ($branding['tutor_chat_enabled'] ?? false)
            && $planModules->allows('discussion')
            && Route::has('user.chat.schedule.show');
        $tutorChatService = $canUseTutorChat ? app(TutorChatService::class) : null;
        $tutorChatContacts = $canUseTutorChat
            ? $tutorChatService->chatContactsForStudent($user)
            : collect();

        return [
            'user' => $user,
            'currentRoute' => (string) request()->route()?->getName(),
            'planModules' => $planModules,
            'canShowDashboard' => $planModules->allows('dashboard'),
            'canShowProfile' => $planModules->allows('profile'),
            'canShowPackage' => $planModules->allows('package'),
            'canShowSchedule' => (bool) $user
                && $planModules->allows('schedule')
                && Route::has('user.class-schedule.index'),
            'canShowBooking' => (bool) ($branding['booking_schedule_enabled'] ?? false)
                && $planModules->allows('booking')
                && Route::has('user.booking.index'),
            'canShowLearningProgress' => (bool) ($branding['learning_progress_enabled'] ?? false)
                && $planModules->allows('booking')
                && Route::has('user.development.index'),
            'canShowEvent' => $planModules->allows('event'),
            'canShowMaterial' => $planModules->allows('material'),
            'canShowTryout' => $planModules->allows('tryout'),
            'canShowTesKoran' => $planModules->allows('tes_koran'),
            'canShowFaq' => $planModules->allows('faq'),
            'canShowCertificate' => $planModules->allows('certificate'),
            'canUseAiDiscussion' => $canUseAiDiscussion,
            'canShowAiLearning' => $canUseAiDiscussion && $planModules->allows('ai_learning'),
            'canShowAffiliateMenu' => (bool) ($branding['affiliate_menu_enabled'] ?? false)
                && $planModules->allows('affiliate')
                && Route::has('user.affiliate.index'),
            'canShowTutorChat' => $tutorChatContacts->isNotEmpty(),
            'canUseTutorChat' => (bool) $canUseTutorChat,
            'tutorChatContacts' => $tutorChatContacts,
            'tutorChatUnreadCount' => $tutorChatContacts->isNotEmpty()
                ? $tutorChatService->unreadCountFor($user)
                : 0,
            'tesKoranEnabled' => (bool) ($branding['tes_koran_enabled'] ?? true)
                && $planModules->allows('tes_koran'),
            'liveSessionAvailable' => (bool) config('client.live_session_available', false),
        ];
    }
}
