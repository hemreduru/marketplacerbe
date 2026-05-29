<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Buybox\BuyboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * PR 4.8 — Rakip / buybox takip (Spec 11.1 buybox_loss).
 */
class BuyboxController extends Controller
{
    public function __construct(private readonly BuyboxService $service) {}

    public function index()
    {
        $user = Auth::user();

        return view('reports.buybox', [
            'rows' => $this->service->trackerRows($user),
            'lostCount' => $this->service->lostBuybox($user)->count(),
        ]);
    }

    public function sync(): RedirectResponse
    {
        $user = Auth::user();
        $checked = 0;

        foreach ($user->marketplaceCredentials()->with('marketplace')->get() as $credential) {
            $result = $this->service->sync($credential);
            if ($result->ok) {
                $checked += $result->data['checked'] ?? 0;
            }
        }

        return back()->with('info', __('reports.buybox_synced', ['count' => $checked]));
    }
}
