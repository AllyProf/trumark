<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    private function authorizeAccess()
    {
        $role = Auth::user()->role;
        if ($role !== 'super_admin' && $role !== 'manager') {
            abort(403, 'Unauthorized. Only Managers and Super Admins can access Supplier Management.');
        }
    }

    public function index()
    {
        $this->authorizeAccess();

        $suppliers = Supplier::with('creator')
            ->latest()
            ->paginate(15);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $this->authorizeAccess();
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|numeric|digits:9',
            'email'           => 'nullable|email|max:255',
            'location'        => 'nullable|string|max:255',
            'service_offered' => 'nullable|string|max:255',
            'note'            => 'nullable|string|max:2000',
        ]);

        if ($request->filled('phone')) {
            $validated['phone'] = '+255' . $request->phone;
        }

        $supplier = Supplier::create(array_merge($validated, [
            'created_by' => Auth::id(),
            'status'     => 'active',
        ]));

        AuditLog::record("Registered new supplier: {$supplier->name}", 'Supplier Management');

        return redirect()->route('suppliers.index')
            ->with('success', "✅ Supplier \"{$supplier->name}\" registered successfully!");
    }

    public function show(Supplier $supplier)
    {
        $this->authorizeAccess();
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        $this->authorizeAccess();
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|numeric|digits:9',
            'email'           => 'nullable|email|max:255',
            'location'        => 'nullable|string|max:255',
            'service_offered' => 'nullable|string|max:255',
            'note'            => 'nullable|string|max:2000',
            'status'          => 'required|in:active,inactive',
        ]);

        if ($request->filled('phone')) {
            $validated['phone'] = '+255' . $request->phone;
        } else {
            $validated['phone'] = null;
        }

        $supplier->update($validated);

        AuditLog::record("Updated supplier: {$supplier->name}", 'Supplier Management');

        return redirect()->route('suppliers.index')
            ->with('success', "✅ Supplier \"{$supplier->name}\" updated successfully!");
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorizeAccess();

        $name = $supplier->name;
        $supplier->delete();

        AuditLog::record("Deleted supplier: {$name}", 'Supplier Management');

        return redirect()->route('suppliers.index')
            ->with('success', "Supplier \"{$name}\" has been removed.");
    }

    public function toggleStatus(Supplier $supplier)
    {
        $this->authorizeAccess();

        $supplier->update([
            'status' => $supplier->status === 'active' ? 'inactive' : 'active',
        ]);

        AuditLog::record("Toggled status of supplier: {$supplier->name} → " . strtoupper($supplier->status), 'Supplier Management');

        return back()->with('success', "Supplier status updated to " . ucfirst($supplier->status) . ".");
    }
}
