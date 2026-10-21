<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionSubmission
{
    public static function run(Request $request, Closure $operation): bool
    {
        $token = $request->validate(['submission_token' => ['required', 'uuid']])['submission_token'];
        $identity = ['user_id' => $request->user()->id, 'scope' => $request->path(), 'token' => $token];

        return DB::transaction(function () use ($request, $identity, $operation) {
            // Lock the account before checking the token, including on separate PHP workers.
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($user->is_active, 403);
            if (in_array($identity['scope'], ['sales', 'purchases'], true)) {
                abort_unless($user->isBackOffice(), 403);
            }
            if (DB::table('transaction_submissions')->where($identity)->exists()) {
                return false;
            }
            $operation();
            DB::table('transaction_submissions')->insert($identity + ['created_at' => now()]);

            return true;
        });
    }
}
