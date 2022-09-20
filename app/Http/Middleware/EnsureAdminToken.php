<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureAdminToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->token;
        if (DB::table("users")->where("token", "=", $token)->where("userRoleID", "=", 1)->doesntExist()) {
            return response()->json(["success" => false, "message" => "User Not Admin."], 401);
        }
        return $next($request);
    }
}
