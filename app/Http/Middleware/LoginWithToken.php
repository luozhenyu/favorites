<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class LoginWithToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $third_session = $request->input('third_session');

        /** @var User $user */
        if (!$user = User::query()->where('third_session', $third_session)->first()) {

            return response()->json([
                'errcode' => 1,
                'errmsg' => 'invalid third_session',
                'third_session' => $third_session,
            ]);
        }

        Auth::login($user);

        return $next($request);
    }
}
