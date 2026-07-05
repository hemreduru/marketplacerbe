<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Demo\DemoDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Demo/sandbox mod — satıcı gerçek anahtar girmeden ürünü keşfeder.
 */
class DemoController extends Controller
{
    public function __construct(private DemoDataService $demo) {}

    public function load(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->demo->populate($user);

        return redirect()->route('dashboard')->with('success', __('demo.loaded'));
    }

    public function clear(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->demo->clear($user);

        return redirect()->route('dashboard')->with('success', __('demo.cleared'));
    }
}
