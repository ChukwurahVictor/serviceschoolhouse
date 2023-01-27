<?php

namespace App\Http\Controllers\Api\Users\SiteAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function login (Request $req) 
    {
        $validator = Validator::make($req->all(), [
            'accessCode' => 'required',
            'userEmail' => 'required',
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()], 422);
        }
        
        $accessCode= $req->accessCode;
        $userEmail= $req->userEmail;

        $user = User::select('token')->where('userEmail', $userEmail)->where('accessCode', $accessCode)->first();
        if(!$user)
        {
            return response(["success" => false,  "message" => "Login failed, Invalid access code or Email"], 400);
        }

        return response()->json(["success" => true,  "message" => "Admin login successful","data" => $user]);
        
    }

}