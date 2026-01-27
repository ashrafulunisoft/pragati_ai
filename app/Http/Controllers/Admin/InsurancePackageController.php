<?php

namespace App\Http\Controllers\Admin;

use App\Models\pragati\InsurancePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;

class InsurancePackageController extends Controller
{
    public function index()
    {
        $packages = InsurancePackage::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.insurance-packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.insurance-packages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:insurance_packages,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'coverage_amount' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        InsurancePackage::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'coverage_amount' => $request->coverage_amount,
            'duration_months' => $request->duration_months,
            'is_active' => $request->is_active ?? true,
        ]);

        Session::flash('success', 'Insurance package created successfully!');
        return redirect()->route('insurance-packages.index');
    }

    public function show(InsurancePackage $insurancePackage)
    {
        return view('admin.insurance-packages.show', compact('insurancePackage'));
    }

    public function edit(InsurancePackage $insurancePackage)
    {
        return view('admin.insurance-packages.edit', compact('insurancePackage'));
    }

    public function update(Request $request, InsurancePackage $insurancePackage)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:insurance_packages,name,' . $insurancePackage->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'coverage_amount' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $insurancePackage->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'coverage_amount' => $request->coverage_amount,
            'duration_months' => $request->duration_months,
            'is_active' => $request->is_active ?? true,
        ]);

        Session::flash('success', 'Insurance package updated successfully!');
        return redirect()->route('insurance-packages.index');
    }

    public function destroy(InsurancePackage $insurancePackage)
    {
        if ($insurancePackage->orders()->exists()) {
            Session::flash('error', 'Cannot delete package because it has associated orders!');
            return redirect()->route('insurance-packages.index');
        }

        $insurancePackage->delete();
        Session::flash('success', 'Insurance package deleted successfully!');
        return redirect()->route('insurance-packages.index');
    }

    public function toggleStatus(InsurancePackage $insurancePackage)
    {
        $insurancePackage->update(['is_active' => !$insurancePackage->is_active]);
        Session::flash('success', 'Package status updated successfully!');
        return redirect()->route('insurance-packages.index');
    }
}
