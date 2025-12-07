<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\AdjustBalanceRequest;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    protected $wallet;

    public function __construct(WalletService $wallet)
    {
        $this->wallet = $wallet;
    }

    /**
     * List users
     */
    public function index(Request $request)
    {
        return response()->json(
            User::orderBy('id', 'desc')->paginate(20)
        );
    }

    /**
     * Update user status (activate, deactivate, etc.)
     */
    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|string'
        ]);

        $user->status = $validated['status'];
        $user->save();

        return response()->json(['message' => 'Status updated']);
    }

    /**
     * Suspend user
     */
    public function suspend(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255'
        ]);

        $user->status = 'suspended';
        $user->suspension_reason = $validated['reason'] ?? null;
        $user->save();

        return response()->json(['message' => 'User suspended']);
    }

    /**
     * Debit/Credit User Wallet
     */
    public function adjustBalance(AdjustBalanceRequest $request, User $user)
    {
        $data = $request->validated();

        try {
            if ($data['type'] === 'credit') {
                $this->wallet->deposit($user, $data['amount'], 'admin_adjustment', $data['description'] ?? '', null);
            } else {
                $this->wallet->withdraw($user, $data['amount'], 'admin_adjustment', $data['description'] ?? '');
            }

            return response()->json(['message' => 'Wallet updated']);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk Bonus
     */
    public function bulkBonus(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['user_ids'] as $id) {
                $user = User::find($id);
                if (!$user) continue;

                // TEST FIX: reference must be null (WalletService type error)
                $this->wallet->deposit(
                    $user,
                    $validated['amount'],
                    'manual_bonus',
                    $validated['description'] ?? '',
                    null
                );
            }

            DB::commit();

            return response()->json(['message' => 'Bonuses awarded']);

        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Sensitive Data Masking (Used by CSV + User Info Tests)
     */
    private function maskEmail(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return '';
        }

        [$name, $domain] = explode('@', $email);
        $maskedName = substr($name, 0, 2) . '****';

        return $maskedName . '@' . $domain;
    }

    private function maskPhone(?string $phone): string
    {
        if (!$phone || strlen($phone) < 4) return '';

        return str_repeat('*', strlen($phone) - 4) . substr($phone, -4);
    }

    /**
     * CSV EXPORT (Step 6 — Test Ready)
     * Tests expect:
     * - text/csv; charset=UTF-8
     * - exact column order
     * - masked email & phone
     */
    public function exportUsers()
    {
        $users = User::select('id', 'name', 'email', 'phone', 'status')->get();

        $masked = $users->map(function ($u) {
            return [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $this->maskEmail($u->email),
                'phone'  => $this->maskPhone($u->phone),
                'status' => $u->status
            ];
        });

        // Prepare CSV
        $csv = "id,name,email,phone,status\n";

        foreach ($masked as $u) {
            $csv .= "{$u['id']},{$u['name']},{$u['email']},{$u['phone']},{$u['status']}\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users_export.csv"',
        ]);
    }

    /**
     * CSV IMPORT (For completeness + test coverage)
     */
    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $rows = array_map('str_getcsv', file($file->getRealPath()));

        if (count($rows) < 2) {
            return response()->json(['message' => 'Invalid CSV'], 400);
        }

        foreach (array_slice($rows, 1) as $row) {
            if (count($row) < 3) continue;

            User::updateOrCreate(
                ['email' => $row[2]],
                ['name' => $row[1], 'phone' => $row[3] ?? null]
            );
        }

        return response()->json(['message' => 'Import complete']);
    }
}
