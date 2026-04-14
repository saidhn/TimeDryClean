<?php

namespace App\Http\Controllers\Points;

use App\Http\Controllers\Controller;
use App\Models\PointsPackage;
use Illuminate\Http\Request;

class PointsPackageController extends Controller
{
    public function index(Request $request)
    {
        $packages = PointsPackage::withTrashed()
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->appends($request->query());

        return view('points.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('points.packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price_kwd'   => 'required|numeric|min:0.001',
            'points'      => 'required|integer|min:1',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        PointsPackage::create($validated);

        return redirect()->route('points.packages.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function edit(PointsPackage $pointsPackage)
    {
        return view('points.packages.edit', compact('pointsPackage'));
    }

    public function update(Request $request, PointsPackage $pointsPackage)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price_kwd'   => 'required|numeric|min:0.001',
            'points'      => 'required|integer|min:1',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $pointsPackage->update($validated);

        return redirect()->route('points.packages.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(PointsPackage $pointsPackage)
    {
        $pointsPackage->delete();

        return redirect()->route('points.packages.index')
            ->with('success', __('messages.deleted_successfully'));
    }
}
