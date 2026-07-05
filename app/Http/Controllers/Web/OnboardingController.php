<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Self-serve onboarding sihirbazı — 4 adımlı kurulum rehberi.
 *
 * Adımlar gerçek kullanıcı durumundan türetilir (hesap → abonelik → ilk
 * pazaryeri credential → ilk sync). İlerleme users.onboarding_completed_steps'e
 * kalıcılaştırılır. Sihirbaz gate'lerden muaftır (her zaman görüntülenebilir).
 */
class OnboardingController extends Controller
{
    public function show(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $steps = $user->onboardingSteps();

        // İlerlemeyi kalıcılaştır (admin görünürlüğü + tamamlanma takibi).
        $completedKeys = array_keys(array_filter($steps));
        $user->update(['onboarding_completed_steps' => $completedKeys]);

        return view('onboarding.index', [
            'steps' => $steps,
            'completedCount' => count($completedKeys),
            'totalCount' => count($steps),
            'isComplete' => $user->isOnboardingComplete(),
        ]);
    }
}
