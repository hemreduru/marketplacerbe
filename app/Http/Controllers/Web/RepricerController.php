<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepricerRule;
use App\Services\Repricer\RepricerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PR 4.9 — Kural tabanlı repricer yönetimi.
 */
class RepricerController extends Controller
{
    public function __construct(private readonly RepricerService $service) {}

    public function index()
    {
        $user = Auth::user();

        return view('reports.repricer', [
            'rules' => RepricerRule::where('user_id', $user->id)->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRule($request);
        $data['user_id'] = Auth::id();

        RepricerRule::create($data);

        return back()->with('success', __('reports.rule_created'));
    }

    public function update(Request $request, RepricerRule $rule): RedirectResponse
    {
        abort_unless($rule->user_id === Auth::id(), 403);

        $rule->update($this->validateRule($request));

        return back()->with('success', __('reports.rule_updated'));
    }

    public function destroy(RepricerRule $rule): RedirectResponse
    {
        abort_unless($rule->user_id === Auth::id(), 403);

        $rule->delete();

        return back()->with('success', __('reports.rule_deleted'));
    }

    public function run(): RedirectResponse
    {
        $result = $this->service->run(Auth::user());

        return back()->with('success', __('reports.repricer_ran', [
            'count' => $result['evaluated'],
            'dispatched' => $result['dispatched'],
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRule(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'strategy' => 'required|in:target_margin,undercut,fixed',
            'master_product_id' => 'nullable|integer|exists:master_products,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'target_margin' => 'nullable|numeric',
            'undercut_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
    }
}
