<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Return the branch ID that should be used to scope all data queries.
     * - super_admin: reads session('dashboard_branch_id'), null = all branches
     * - everyone else: always returns their own fixed branch_id
     */
    protected function getActiveBranchId(): ?int
    {
        $user = auth()->user();
        if (!$user) return null;

        if ($user->role === 'super_admin') {
            return session('dashboard_branch_id') ? (int) session('dashboard_branch_id') : null;
        }

        return $user->branch_id ? (int) $user->branch_id : null;
    }
}
