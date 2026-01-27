<?php

namespace App\Http\Controllers\Admin;

use App\Models\pragati\InsurancePackage;
use App\Models\pragati\Order;
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

        return redirect()->route('insurance-packages.index')
            ->with('success', 'Insurance package created successfully!');
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

        return redirect()->route('insurance-packages.index')
            ->with('success', 'Insurance package updated successfully!');
    }

    public function destroy(InsurancePackage $insurancePackage)
    {
        if ($insurancePackage->orders()->exists()) {
            return redirect()->route('insurance-packages.index')
                ->with('error', 'Cannot delete package because it has associated orders!');
        }

        $insurancePackage->delete();

        return redirect()->route('insurance-packages.index')
            ->with('success', 'Insurance package deleted successfully!');
    }

    public function toggleStatus(InsurancePackage $insurancePackage)
    {
        $status = !$insurancePackage->is_active;
        $insurancePackage->update(['is_active' => $status]);

        $statusText = $status ? 'activated' : 'deactivated';
        return redirect()->route('insurance-packages.index')
            ->with('success', "Package {$statusText} successfully!");
    }

    // Public methods for guest users
    public function publicIndex()
    {
        $packages = InsurancePackage::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();
        return view('packages.index', compact('packages'));
    }

    public function publicShow($id)
    {
        $insurancePackage = InsurancePackage::where('is_active', true)->find($id);
        
        if (!$insurancePackage) {
            abort(404, 'Package not found or is not active');
        }
        
        return view('packages.show', compact('insurancePackage'));
    }

    // Purchase package and create order with policy
    public function purchase(Request $request, $packageId)
    {
        $package = InsurancePackage::where('is_active', true)->findOrFail($packageId);
        
        // Generate unique policy number
        $policyNumber = 'POL-' . strtoupper(uniqid()) . '-' . date('Y');
        
        // Calculate dates
        $startDate = now()->startOfDay();
        $endDate = now()->addMonths($package->duration_months)->endOfDay();
        
        // Create order/policy
        $order = Order::create([
            'user_id' => auth()->id(),
            'insurance_package_id' => $package->id,
            'policy_number' => $policyNumber,
            'status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        
        return redirect()->route('orders.show', $order->id)
            ->with('success', 'Policy purchased successfully!');
    }

    // Show order/policy details
    public function showOrder(Order $order)
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this policy.');
        }
        
        // Cast dates to Carbon instances for existing records
        $order->start_date = \Carbon\Carbon::parse($order->start_date);
        $order->end_date = \Carbon\Carbon::parse($order->end_date);
        
        $order->load('package');
        return view('orders.show', compact('order'));
    }
}
