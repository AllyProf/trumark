<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized access. Only system administrators can view security audit logs.');
        }

        $search = $request->get('search');
        $category = $request->get('category');
        $userId = $request->get('user_id');

        $query = AuditLog::with('user');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%")
                  ->orWhere('isp', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $logs = $query->latest()->paginate(100)->appends($request->query());
        $users = \App\Models\User::orderBy('name')->get();
        $categories = AuditLog::distinct()->pluck('category')->toArray();

        return view('audit_logs.index', compact('logs', 'users', 'categories', 'search', 'category', 'userId'));
    }
}
