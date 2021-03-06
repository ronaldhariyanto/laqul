<?php

namespace App\Http\Controllers\Api\Auth\ResetPassword;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Auth\PasswordReset\PasswordReset;
use App\Models\User\User;

class ResetController extends Controller
{
    private function validateRequest(Request $request)
    {
        $request->validate([
            'client_id' => 'bail|required|string|exists:oauth_clients,id',
            'token' => ['bail','required','string','size:150',
                Rule::exists('password_resets')->where(function ($query) {
                    $query->where('updated_at', '>=', Carbon::now()->subMinutes(60));
                })],
            'password' => 'required|min:8|max:30|confirmed'
            ]);
    }

    public function resetPassword(Request $request)
    {
        $this->validateRequest($request);
        DB::transaction(function () use ($request) {
            $reset = PasswordReset::where('token', $request->token)->first();
            $user = User::find($reset->user_id);
            $reset->delete();
            $user->password = bcrypt($request->password);
            $user->save();
        });
        return response()->json(['success' => true]);
    }
}
