<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    private function isAdmin($token)
    {
        // Checks if token has admin privileges and return companyID of Admin
        if (DB::table("users")->where("token", "=", $token)->where("userRoleID", "=", 1)->exists()) {
            $user = DB::table("users")->where("token", "=", $token)->get();
            return ["isAdmin" => true, "companyID" => $user[0]->companyID, "userID" => $user[0]->userID];
        } else {
            return ["isAdmin" => false];
        }
    }

    private function userExists($token)
    {
        // Checks if token has a corresponding user in the DB and return the userID and companyID
        if (DB::table("users")->where("token", "=", $token)->exists()) {
            $user = DB::table("users")->where("token", "=", $token)->get();
            return ["userExists" => true, "companyID" => $user[0]->companyID, "userID" => $user[0]->userID, "password" => $user[0]->userPassword];
        } else {
            return ["userExists" => false];
        }
    }

    public function getUserDetails($token)
    {

        $userExists = $this->userExists($token);
        // Checks if a user really exists
        if ($userExists["userExists"]) {
            $userDetails = DB::table("users")->join("company", "company.companyID", "=", "users.companyID")->where("token", "=", $token)->get();
            return response()->json(["success" => true, "userDetails" => $userDetails]);
        } else {
            return response()->json(["success" => false, "message" => "Users does not exist"], 400);
        }
    }

    public function updateUserDetails(Request $req, $token)
    {
        $firstName = $req->firstName;
        $lastName = $req->lastName;
        $userExists = $this->userExists($token);
        // Checks if a user really exists
        if ($userExists["userExists"]) {
            DB::table("users")->where("token", "=", $token)->update(["userFirstName" => $firstName, "userLastName" => $lastName]);
            return response()->json(["success" => true, "message" => "Profile Updated"]);
        } else {
            return response()->json(["success" => false, "message" => "Users does not exist"], 400);
        }
    }

    public function updateCompanyDetails(Request $req, $token)
    {
        $companyName = $req->companyName;
        $companyAddress = $req->address;

        $isAdmin = $this->isAdmin($token);
        // Checks if a user is Admin
        if ($isAdmin["isAdmin"]) {
            DB::table("company")->where("companyID", "=", $isAdmin["companyID"])->update(["companyName" => $companyName, "companyAddress1" => $companyAddress]);
            return response()->json(["success" => true, "message" => "Company Details Updated"]);
        } else {
            return response()->json(["success" => false, "message" => "Users not Admin"], 401);
        }
    }

    public function updatePassword(Request $req, $token)
    {
        $currentPassword = $req->currentPassword;
        $newPassword = $req->newPassword;
        $userExists = $this->userExists($token);
        // Checks if a user really exists
        if ($userExists["userExists"]) {
            $pass_ok = password_verify($currentPassword, $userExists["password"]);
            if ($pass_ok) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                DB::table("users")->where("token", "=", $token)->update(["userPassword" => $hash]);
                return response()->json(["success" => true, "message" => "Password Updated"]);
            } else {
                return response()->json(["success" => false, "message" => "Invalid Password"], 401);
            }
        } else {
            return response()->json(["success" => false, "message" => "Users does not exist"], 400);
        }
    }
}
