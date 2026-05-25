<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::orderBy('sort_order')->get();

        return view('admin.plans.index', compact('plans'));
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'nullable|numeric|min:0',
            'marketplaces_limit' => 'required|integer|min:-1',
            'orders_limit' => 'required|integer|min:-1',
            'products_limit' => 'required|integer|min:-1',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'required|integer|min:0',
            'features' => 'array',
            'features.*' => 'boolean',
        ]);

        $plan->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
            'is_popular' => $request->boolean('is_popular'),
            'features' => $request->input('features', []),
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "{$plan->display_name} planı güncellendi.");
    }
}
