<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{

    private function sendUserCreationEmail($firstname, $email, $password)
    {
        $details = [
            'name' => $firstname,
            'password' => $password,
            'login' => 'https://learningplatform.sandbox.9ijakids.com/login',
        ];
        Mail::to($email)->send(new \App\Mail\CreateUser($details));
    }

    public function createCompanyUser(Request $req)
    {
        $token = $req->token;
        $firstname = $req->firstName;
        $lastname = $req->lastName;
        $employeeID = $req->employeeID;
        $email = $req->email;
        $email_suffix = explode("@", $req->email)[1];
        $tel = $this->formatIntlPhoneNo($req->tel);
        $gender = $req->gender;
        $grade = $req->grade;
        $location = $req->location;
        $roleName = $req->roleName;
        $hash = password_hash("LearningPlatform", PASSWORD_DEFAULT);
        $newtoken = $this->RandomCodeGenerator(80);

        if (DB::table("users")->where("userEmail", "=", $email)->doesntExist()) {
            $query = DB::table("users")->join("company", "users.companyID", "=", "company.companyID")->select(["users.userID", "company.emailSuffix", "company.companyID"])->where("users.token", "=", $token)->where("users.userRoleID", "=", 1)->get();

            if ($query[0]->emailSuffix === $email_suffix) {
                $companyID = $query[0]->companyID;
                $queryForGroupCategory = DB::table("groupRole")->where("roleName", "=", $roleName)->get();

                //Get user groupRoleID for either Agent, Supervisor, or Manager
                $groupRoleId = $queryForGroupCategory[0]->groupRoleId;

                DB::table("users")->insertGetId(["userFirstName" => $firstname, "userLastName" => $lastname, "userEmail" => $email, "userPhone" => $tel, "userGender" => $gender, "userGrade" => $grade, "userPassword" => $hash, "userRoleID" => 2, "groupRoleId" => $groupRoleId, "location" => $location, "companyID" => $companyID,  "employeeID" => $employeeID, "token" => $newtoken]);

                $this->sendUserCreationEmail($firstname, $email, "LearningPlatform");

                return response()->json(["success" => true, "message" => "User Account Created"]);
            } else {
                return response()->json(["success" => false, "message" => "User Email not Company Email"], 400);
            }
        } else {
            return response()->json(["success" => false, "message" => "User Already Registered"], 400);
        }
    }

    public function editCompanyUser(Request $req)
    {
        $adminToken = $req->token;
        $firstname = $req->firstName;
        $lastname = $req->lastName;
        $email = $req->email;
        $userToken = $req->userToken;
        $employeeID = $req->employeeID;
        $location = $req->location;
        $email_suffix = explode("@", $req->email)[1];
        // $tel = $this->formatIntlPhoneNo($req->tel);
        $gender = $req->gender;
        $grade = $req->grade;

        if ($userToken) {
            $queryUserTable = DB::table("users")->where("token", "=", $userToken)->orWhere("userEmail", "=", $email)->get();
        } else
            $queryUserTable = DB::table("users")->where("userEmail", "=", $email)->get();

        if (count($queryUserTable) === 1) {
            $query = DB::table("users")->join("company", "users.companyID", "=", "company.companyID")->select(["company.emailSuffix", "company.companyID"])->where("users.token", "=", $adminToken)->where("users.userRoleID", "=", 1)->get();
            $userID = $queryUserTable[0]->userID;
            $adminCompanyID = $query[0]->companyID;
            $userCompanyID = $queryUserTable[0]->companyID;
            if ($adminCompanyID === $userCompanyID) {
                if ($query[0]->emailSuffix === $email_suffix) {
                    DB::table("users")->where("userID", "=", $userID)->update([
                        "userFirstName" => $firstname, "userLastName" => $lastname, "userEmail" => $email,
                        // "userPhone" => $tel, 
                        "userGender" => $gender, "userGrade" => $grade, "employeeID" => $employeeID, "location" => $location
                    ]);
                    return response()->json(["success" => true, "message" => "User successfully updated"]);
                } else {
                    return response()->json(["success" => false, "message" => "User Email not Company Email"], 400);
                }
            } else
                return response()->json(["success" => true, "message" => "Admin does not belong to this user's company"]);
        } else {
            return response()->json(["success" => false, "message" => "User does not exist"], 400);
        }
    }

    public function deleteCompanyUser(Request $req)
    {
        $adminToken = $req->token;
        $userID = $req->userID;
        // $userToken = $req->userToken;
        $table = DB::table("users")->where("token", "=", $adminToken)->get();
        $adminCompanyID = $table[0]->companyID;

        $query = DB::table("users")->where("userID", "=", $userID)
            // ->orWhere("token", "=", $userToken)
            ->get();
        if (count($query) === 1) {
            $userCompanyID = $query[0]->companyID;
            if ($adminCompanyID === $userCompanyID) {
                DB::table("users")->where("userID", "=", $userID)->delete();
                return response()->json(["success" => true, "message" => "User successfully deleted"]);
            } else
                return response()->json(["success" => true, "message" => "Admin does not belong to this users company"]);
        } else {
            return response()->json(["success" => false, "message" => "User does not exist"], 400);
        }
    }

    public function getCompanyUsers(Request $req)
    {
        $token = $req->token;
        $query = DB::table("users")->where("token", "=", $token)->where("userRoleID", "=", 1)->select(["companyID"])->get();
        $companyID = $query[0]->companyID;

        $users = DB::table("users")->where("companyID", "=", $companyID)->select("userID", "userFirstName", "userLastname", "userEmail", "userGender", "userGrade", "employeeID", "location", "token AS usertoken")->get();
        $total = count($users);
        if (count($users) > 0) {
            return response()->json(["success" => true, "users" => $users, "total" => $total]);
        } else {
            return response()->json(["success" => true, "users" => [], "message" => "No Users Available"], 204);
        }
    }

    public function companyUserSearch(Request $req)
    {
        $token = $req->token;
        $searchParams = $req->searchParams;
        $page_number = $req->page_number;
        $page_size = $req->page_size;
        $offset = ($page_number - 1) * $page_size;
        $companyID =  $this->getCompanyID($token);

        $users = DB::table("users")
            // ->join("groupRole", "users.groupRoleId", "=", "users.userRoleID")
            ->where("companyID", "=", $companyID)->where(function ($query) use ($searchParams) {
                $query->where("employeeID", "like", "%" . $searchParams . "%")
                    ->orWhere("userFirstName", "like", "%" . $searchParams . "%")
                    ->orWhere("userLastname", "like", "%" . $searchParams . "%");
            })->select( "userID", "userFirstName", "userLastname", // "roleName as userRole",
             "userEmail", "userGender", "userGrade", "employeeID", "location", "token AS usertoken"
            )->skip($offset)->take($page_size)->get();
        $total = count($users);
        if (count($users) > 0) {
            return response()->json(["success" => true, "users" => $users, "total" => $total]);
        } else {
            return response()->json(["success" => true, "users" => [], "message" => "No Users Available"], 204);
        }
    }

    public function bulkUpload(Request $request) {
        $token = $request->token;
        $companyID = $this->getCompanyID($token);
        $extension = $request->file('upload_file')->getClientOriginalExtension();
        if ($extension == 'csv') {
            $upload = $request->file('upload_file');
            $getPath = $upload->getRealPath();

            $file = fopen($getPath, 'r');
            $headerLine = true;
            $Errors = [];

            while (($columns = fgetcsv($file, 1000, ","))!== FALSE) {
                if($headerLine) { $headerLine = false; }
                
                else {
                
                    if ($columns[0] == "")
                        continue;
                    $data =  $columns;
                    foreach ($data as $key => $value) {
                        $employeeID = $data[0];
                        $userFirstName = $data[1];
                        $userLastName = $data[2];
                        $userEmail = $data[3];
                        $userGender = $data[4];
                        $userGrade = $data[5];
                        $location = $data[6];
                        $roleName = $data[7];
                        $groupRole = DB::table("groupRole")->where("roleName", "=", $roleName)->get();
                        //Get user groupRoleID for either Agent, Supervisor, or Manager
                        count($groupRole) > 0 ? $groupRoleId = $groupRole[0]->groupRoleId : $groupRoleId = 1;
                        $userToken = $this->RandomCodeGenerator(80);
                    }

                    if ($employeeID && $userFirstName && $userFirstName && $userEmail) {
                        if (!DB::table('users')->where("userEmail", "=", $userEmail)->where("companyID", "=", $companyID)->exists()) {
                            $email_suffix = explode("@", $userEmail)[1];
                            $hash = password_hash($employeeID, PASSWORD_DEFAULT);

                            $query = DB::table("users")->join("company", "users.companyID", "=", "company.companyID")->select(["company.emailSuffix", "company.companyID"])->where("users.token", "=", $token)->where("users.userRoleID", "=", 1)->get();
                            if ($query[0]->emailSuffix === $email_suffix) {
                                DB::table('users')->insert(["userFirstName" => $userFirstName, "userLastName" => $userLastName, "userEmail" => $userEmail, "userGender" => $userGender, "userRoleID" => 2, "groupRoleId" => $groupRoleId, "userGrade" => $userGrade,  "location" => $location, "companyID" => $companyID, "userPassword" => $hash, "employeeID" => $employeeID, "token" => $userToken]);
                                $success = true;
                            } else {
                                array_push($Errors, $userEmail." not company Email ");   
                            }
                        }else 
                            array_push($Errors, "We found a duplicate for ".$userEmail);     
                    }else {
                        array_push($Errors, "One or more fields missing for ".$userFirstName.' '.$userLastName);
                    }
                }
            }

            if ($Errors) {
                return response()->json(["success" => true, "error" => $Errors]);
            } elseif($success && $Errors) {
                return response()->json(["success" => true, "message" => "successful", "error" => $Errors]);
            } else
                return response()->json(["success" => true, "message" => "successful"]);
        } else 
            return response()->json(["success" => true, "error" => "file format not supported"]);
        
    }
}