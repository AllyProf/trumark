<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $branches = Branch::withCount(['users', 'customers'])->get();
        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        return view('branches.create');
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:branches',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        Branch::create($request->all());

        return redirect()->route('branches.index')->with('success', 'Branch created successfully!');
    }

    public function edit(Branch $branch)
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,'.$branch->id,
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $branch->update($request->all());

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully!');
    }

    public function destroy(Branch $branch)
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        if ($branch->users()->count() > 0 || $branch->customers()->count() > 0) {
            return back()->with('error', 'Cannot delete branch with assigned staff or customers.');
        }

        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully!');
    }

    public function toggleStatus(Branch $branch)
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $branch->update(['is_active' => !$branch->is_active]);

        return back()->with('success', 'Branch status updated!');
    }
}
