<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\EmailService;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    private function sendVerifyEmail($firstname, $email, $email_token)
    {
        $details = [
            'name' => $firstname,
            'email' => $email,
            'link' => 'https://serviceschoolhouse.com/verifyemail/' . $email_token,
            'websiteLink' => 'https://serviceschoolhouse.com/'
        ];

        Mail::to($email)->send(new \App\Mail\VerifyEmail($details));
    }

    private function sendForgotPasswordEmail($email, $forgot_password_token)
    {
        $details = [
            'email' => $email,
            'resetPasswordLink' => 'https://learningplatform.sandbox.9ijakids.com/forgot-password/' . $forgot_password_token,
            'websiteLink' => 'https://learningplatform.sandbox.9ijakids.com'
        ];
        Mail::to($email)->send(new \App\Mail\ForgotPassword($details));
    }

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    // each endpoint will have a function
    public function signup(Request $req)
    {
        $companyName = $req->companyName;
        $companyAddress = $req->companyAddress;
        $companyEmailSuffix = $req->companyEmailSuffix;
        $firstname = $req->firstName;
        $lastname = $req->lastName;
        $email = $req->email;
        $adminRole = $req->adminRole;
        $tel = $this->formatIntlPhoneNo($req->tel);
        $hash = password_hash($req->password, PASSWORD_DEFAULT);
        $token = $this->RandomCodeGenerator(80);
        $email_token = $this->RandomCodeGenerator(80);

        if (DB::table("users")->join("company", "users.companyID", "=", "company.companyID")->where("users.userEmail", "=", $email)->orWhere("company.companyName", "=", $companyName, "or")->orWhere("company.emailSuffix", "=", $companyEmailSuffix)->doesntExist()) {
            
            $getRoleId = DB::table("users")->join("groupRole", "users.groupRoleId", "=", "groupRole.groupRoleId")->where("roleName", "=", $adminRole)->get();
            $roleId = $getRoleId[0]->groupRoleId;
            
            $id = DB::table("users")->insertGetId(
                ["userFirstName" => $firstname, "userLastName" => $lastname, "userEmail" => $email, "userPhone" => $tel, "userPassword" => $hash, "userRoleID" => 1, "groupRoleId" => $roleId, "token" => $token, "email_token" => $email_token, "verified_status" => "unverified"],
            );

            $companyID = DB::table("company")->insertGetId([
                "companyName" => $companyName,
                "companyAddress1" => $companyAddress,
                "companyAdminID" => $id,
                "emailSuffix" => $companyEmailSuffix,
                "companyAdminRole" => $adminRole
            ]);

            DB::table("users")->where("userID", "=", $id)->update(["companyID" => $companyID]);

            $defaultGroups = DB::table("groupRole")->get();

            foreach($defaultGroups as $group) {
                $roleName=$group->roleName;
                DB::table("group")->insert(["companyID" => $companyID, "groupName" => $roleName]);
            } 

            $query = DB::table("users")->where("userEmail", "=", $email)->get();
            $userData = ["token" => $query[0]->token, "role" => "admin", "name" => $query[0]->userFirstName.' '.$query[0]->userLastName];
            
            $this->emailService->createTemplate($companyID);
            $this->sendVerifyEmail($firstname, $email, $email_token);
            
            return response()->json(["success" => true, "data" => $userData, "message" => 'Email sent, please check your inbox']);
        } else {
            return response()->json(["success" => false, "message" => "Company or Admin User Already Exist"], 401);
        }
    }

    public function verifyEmail($email_token)
    {
        if (DB::table('users')->where('email_token', '=', $email_token)->exists()) {
            DB::table("users")->where("email_token", "=", $email_token)->update(["email_token" => "", "verified_status" => "verified"]);
            return response()->json(["success" => true, "message" => "Email verified, please login"]);
        } else {
            return response()->json(["success" => false, "message" => "Link has expired please login"], 400);
        }
    }

    public function login(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => 'required',
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()]);
        }

        $email = $req->email;
        $password = $req->password;

        $user = DB::table("users")->join("role", "users.userRoleID", "=", "role.roleID")
        ->join("groupRole", "groupRole.groupRoleId", "=", "users.groupRoleId" )->join("company", "company.companyID", "=", "users.companyID")->where("users.userEmail", "=", $email)->select(["users.*", "role.roleName", "groupRole.roleName as groupRoleName", "companyName"])->first();

        if(!$user || $password != password_verify($password, $user->userPassword))
        {
            return response(["success" => false, "message" => "Invalid email or password", $user, $password], 401);
        }

        $token = $this->RandomCodeGenerator(80);
        DB::table("users")->where("userEmail", "=", $email)->update(["token" => $token]);

        $loginAttempts = DB::table("login_logs")->where("email", "=", $email)->where("status", "=", 200)->first();
        $firstLogin = true;
        if($loginAttempts)
        {
            $firstLogin = false;
        }

        DB::table("login_logs")->insert([ "email" => $email, "message" => "login successful", "status" => 200, "updated_at" => Carbon::now()->toDateTimeString()]); //logout status

        $name = $user->userFirstName.' '.$user->userLastName;
        $userData = ["name"=> $name, "token" => $token, "role" => $user->roleName, "groupRoleName"=> $user->groupRoleName, "companyName"=> $user->companyName, "firstLogin"=> $firstLogin];

        return response()->json(["success" => true, "data" => $userData], 200);

        
    }

    public function logout(Request $req)
    {
        $token = $req->token;

        $user = DB::table('users')->where('token', $token)->first();
        if($user) {
            DB::table('users')->where('token', $token)->update([
                'token' => ''
            ]);

            DB::table('login_logs')->where('email', $user->userEmail)->update([
                'updated_at' => Carbon::now()->toDateTimeString()
            ]);

            return response(["success" => true, "message" => "Logout successful"], 200);
        }

        return response(["success" => false, "message" => "User not logged in"], 401);
    }

    public function forgotPassword(Request $req)
    {
        $email = $req->email;
        $forgot_password_token = $this->RandomCodeGenerator(80);
        // $userExists = DB::table('users')->where('userEmail', '=', $email);
        if (DB::table('users')->where('userEmail', '=', $email)->exists()) {
            DB::table('users')->where('userEmail', '=', $email)->update(["forgot_password_token" => $forgot_password_token]);
            $this->sendForgotPasswordEmail($email, $forgot_password_token);
            return response()->json(["success" => true, "message" => "An email has been sent to you."]);
        } else {
            return response()->json(["success" => false, "message" => "Users does not exist"], 400);
        }
    }

    public function updateForgotPassword(Request $req)
    {
        $token = $req->token;
        $newPassword = $req->newPassword;

        if (DB::table('users')->where('forgot_password_token', '=', $token)->exists()) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            DB::table("users")->where("forgot_password_token", "=", $token)->update(["userPassword" => $hash, "forgot_password_token" => ""]);
            return response()->json(["success" => true, "message" => "password reset successful"]);
        } else {
            return response()->json(["success" => false, "message" => "Link has expired please login"], 400);
        }
    }

}
