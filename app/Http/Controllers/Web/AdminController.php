<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceSyncLog;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * Admin paneli — sistem metrikleri, abonelik/ödeme görünümü, activity-log,
 * kullanıcı yönetimi + impersonate.
 */
class AdminController extends Controller
{
    public function dashboard(): View
    {
        $since = now()->subDay();

        return view('admin.dashboard', [
            'metrics' => [
                'users' => User::count(),
                'active_subscriptions' => Subscription::where('status', 'active')->count(),
                'sync_success_24h' => MarketplaceSyncLog::where('status', 'success')->where('created_at', '>=', $since)->count(),
                'sync_failed_24h' => MarketplaceSyncLog::where('status', 'failed')->where('created_at', '>=', $since)->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
            'recentActivity' => Activity::query()->latest()->limit(10)->get(),
            'recentPayments' => SubscriptionPayment::query()->with(['user', 'plan'])->latest()->limit(10)->get(),
        ]);
    }

    public function users(): View
    {
        $users = User::query()
            ->withCount('marketplaceCredentials')
            ->latest()
            ->paginate(20);

        return view('admin.users', ['users' => $users]);
    }

    /**
     * Kullanıcı kimliğine bürün — orijinal admin id session'a saklanır.
     */
    public function impersonate(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', __('admin.cannot_impersonate_admin'));
        }

        session()->put('impersonator_id', Auth::id());
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', __('admin.impersonating', ['name' => $user->name]));
    }

    /**
     * Impersonation'ı sonlandır — orijinal admin'e geri dön. Admin-dışı da
     * çağırabilir (bürünülen kullanıcı admin değildir); route auth grubunda.
     */
    public function stopImpersonating(): RedirectResponse
    {
        $originalId = session()->pull('impersonator_id');

        if ($originalId !== null) {
            Auth::loginUsingId((int) $originalId);

            return redirect()->route('admin.users')
                ->with('success', __('admin.stopped_impersonating'));
        }

        return redirect()->route('dashboard');
    }
}
