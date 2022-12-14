<?php

namespace App\Http\Controllers;

use App\Services\GetCompanyService;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
class UserController extends Controller
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

    private function sendUserCreationEmail($firstname, $email, $password)
    {
        $details = [
            'name' => $firstname,
            'email' => $email,
            'password' => $password,
            'website_link' => env('APP_URL'),
            'login_link' => env('APP_URL').'/login'
        ];
        Mail::to($email)->send(new \App\Mail\CreateUser($details));
    }

    public function testmailCreate()
    {
        //$email = 'oluwatemilorun.adewuyi@9ijakids.com';
        $email = 'ucheofunne.o@gmail.com';
        $details = [
            'name' => 'Temi',
            'email' => $email,
            'password' => 'LearningPlatform',
            'website_link' => env('APP_URL'),
            'login_link' => env('APP_URL').'/login'
        ];
        Mail::to($email)->send(new \App\Mail\CreateUser($details));
    }

    public function __construct(GetCompanyService $getCompanyService)
    {
        $this->getCompanyService = $getCompanyService;
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
        $hash = password_hash($employeeID, PASSWORD_DEFAULT);
        $newtoken = $this->RandomCodeGenerator(80);
        $groupid = $req->groupID;
        $checkToken = $this->isAdmin($token);

        if (DB::table("users")->where("userEmail", "=", $email)->doesntExist()) {
            $query = DB::table("users")->join("company", "users.companyID", "=", "company.companyID")->select(["users.userID", "company.emailSuffix", "company.companyID"])->where("users.token", "=", $token)->where("users.userRoleID", "=", 1)->get();

            if ($query[0]->emailSuffix === $email_suffix) {
                $companyID = $query[0]->companyID;
                $queryForGroupCategory = DB::table("groupRole")->where("roleName", "=", $roleName)->get();

                //Get user groupRoleID for either Agent, Supervisor, or Manager
                $groupRoleId = $queryForGroupCategory[0]->groupRoleId;

                // check if group belongs to company
                $checkGroup = DB::table("group")->where("companyID", "=", $companyID)->where("groupID", $groupid)->first();
                if(!$checkGroup) {
                    return response()->json(["success" => false, "message" => "Group does not belong to company."]);
                }
                $company = DB::table('company')->where('companyID', $companyID)->first(); //get company

                // calculate cost and check if company has enough funds
                $cost = DB::table('groupEnrolment')->join('course', 'groupEnrolment.courseID', '=', 'course.courseID')->where('groupID', $groupid)->sum('course.price');
                if ($company->wallet < $cost) {
                    return response()->json(["success" => false, "message" => "Insufficient funds."]);

                }
                
                // insert user to user table and get the id
                $user = DB::table("users")->insertGetId([
                    "userFirstName" => $firstname, 
                    "userLastName" => $lastname, 
                    "userEmail" => $email, 
                    "userPhone" => $tel, 
                    "userGender" => $gender, 
                    "userGrade" => $grade, 
                    "userPassword" => $hash, 
                    "userRoleID" => 2, 
                    "groupRoleId" => $groupRoleId, 
                    "location" => $location, 
                    "companyID" => $companyID,  
                    "employeeID" => $employeeID, 
                    "token" => $newtoken
                ]);

                $this->sendUserCreationEmail($firstname, $email, $employeeID);

                DB::table("userGroup")->insert(["userID" => $user, "groupID" => $groupid]); // add user to group

                $balance = $company->wallet - $cost;
                DB::table('company')->where('companyID', $companyID)->update(["wallet" => $balance ]); // deduct cost and update company's wallet

                $courses = DB::table('groupEnrolment')->join('course', 'groupEnrolment.courseID', '=', 'course.courseID')->where('groupID', $groupid)->get();

                //Add to billing table
                foreach($courses as $course) {
                    $billing = DB::table('billing')->insert([
                        "companyID" => $companyID,
                        "userID" => $user,
                        "cost" => $course->price,
                        "courseID" => $course->courseID,
                    ]);
                }

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
        $checkToken = $this->isAdmin($token);

        $companyID = $this->getCompanyID($token);
        
        $extension = $request->file('upload_file')->getClientOriginalExtension();
        if ($extension == 'csv') {
            $upload = $request->file('upload_file');
            $getPath = $upload->getRealPath();

            $file = fopen($getPath, 'r');
            $headerLine = true;
            $Errors = [];
            $Success = [];


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
                        $userPhone = $data[4];
                        $userGender = $data[5];
                        $userGrade = $data[6];
                        $location = $data[7];
                        $roleName = $data[8];
                        $groupName = $data[9];
                        $groupRole = DB::table("groupRole")->where("roleName", "=", $roleName)->get();

                        //Get user groupRoleID for either Agent, Supervisor, or Manager
                        count($groupRole) > 0 ? $groupRoleId = $groupRole[0]->groupRoleId : $groupRoleId = 1;
                        $userToken = $this->RandomCodeGenerator(80);
                    }

                    $userEmail_suffix = explode("@", $userEmail)[1];
                    $userPhone = $this->formatIntlPhoneNo($userPhone);
                    $hash = password_hash($employeeID, PASSWORD_DEFAULT);

                    // check if email does not exist
                    if (DB::table("users")->where("userEmail", "=", $userEmail)->doesntExist()) {
                        $query = DB::table("users")->join("company", "users.companyID", "=", "company.companyID")->select(["users.userID", "company.emailSuffix", "company.companyID"])->where("users.token", "=", $token)->where("users.userRoleID", "=", 1)->get();

                        if ($query[0]->emailSuffix === $userEmail_suffix) {
                            $companyID = $query[0]->companyID;
                            $queryForGroupCategory = DB::table("groupRole")->where("roleName", "=", $roleName)->get();

                            // check if group belongs to company
                            $group = DB::table('group')->where('groupName', $groupName)->where("companyID", "=", $companyID)->first(); //get group
                            if(!$group) {
                                array_push($Errors, $groupName . " does not belong to company. ", $group->groupID, $companyID);
                            } else {
                                $company = DB::table('company')->where('companyID', $companyID)->first(); //get company
    
                                // calculate cost and check if company has enough funds
                                $cost = DB::table('groupEnrolment')->join('course', 'groupEnrolment.courseID', '=', 'course.courseID')->where('groupID', $group->groupID)->sum('course.price');
                                if ($company->wallet < $cost) {
                                    array_push($Errors, "Insufficient funds for " . $userEmail);
                                } else {
                                    $user = DB::table('users')->insertGetId([
                                        "userFirstName" => $userFirstName, 
                                        "userLastName" => $userLastName, 
                                        "userEmail" => $userEmail, 
                                        "userGender" => $userGender, 
                                        "userRoleID" => 2, 
                                        "groupRoleId" => $groupRoleId, 
                                        "userGrade" => $userGrade,  
                                        "location" => $location, 
                                        "companyID" => $companyID, 
                                        "userPassword" => $hash, 
                                        "employeeID" => $employeeID,
                                        "token" => $userToken
                                    ]);
                                    
                                    DB::table("userGroup")->insert(["userID" => $user, "groupID" => $group->groupID]);
                                    
                                    $balance = $company->wallet - $cost;
                                    DB::table('company')->where('companyID', $companyID)->update(["wallet" => $balance]);

                                    $courses = DB::table('groupEnrolment')->join('course', 'groupEnrolment.courseID', '=', 'course.courseID')->where('groupID', $group->groupID)->get();

                                    //Add to billing table
                                    foreach($courses as $course) {
                                        $billing = DB::table('billing')->insert([
                                            "companyID" => $companyID,
                                            "userID" => $user,
                                            "cost" => $course->price,
                                            "courseID" => $course->courseID,
                                        ]);
                                    }
    
                                    $this->sendUserCreationEmail($userFirstName, $userEmail, $employeeID);
                                    
                                    array_push($Success, "User Account Created for " . $userEmail);
                                }
                            }
                        } else {
                                array_push($Errors, $userEmail." not company Email ");   
                            }
                    }else 
                        array_push($Errors, "We found a duplicate for ".$userEmail);     
                }
            }

            if ($Errors) {
                return response()->json(["success" => true, "error" => $Errors]);
            } elseif($Success && $Errors) {
                return response()->json(["success" => true, "message" => "successful", "error" => $Errors]);
            } else
                return response()->json(["success" => true, "message" => "successful"]);
        } else 
            return response()->json(["success" => true, "error" => "file format not supported"]);
    }

    public function convertSinglePassword(Request $req) {
        $userID = $req->userID;
        $user = DB::table("users")->where("userID", "=", $userID)->get();
        
        if(DB::table("users")->where("userID", "=", $userID)->exists()) {
            $hash = password_hash($user[0]->employeeID, PASSWORD_DEFAULT);

            DB::table("users")->where("userID", "=", $userID)->update([
                "userPassword" => $hash
            ]);

            $this->sendUserCreationEmail($user[0]->userFirstName, $user[0]->userEmail, $user[0]->employeeID);

            return response()->json(["success" => true, "message" => "password changed and email sent successfully."], 200);
        } else {
            return response()->json(["success" => false, "message" => "User not found."], 400);
        }
    }

    public function convertGroupPassword(Request $req) {
        $companyID = $req->companyID;
        $users = DB::table("users")->where("companyID", "=", $companyID)->get();
        $success = [];
        $errors = [];
        
        foreach ($users as $user) {
            if(DB::table("users")->where("userID", "=", $user->userID)->where("companyID", "=", $companyID)->exists()) {
                $hash = password_hash($user->employeeID, PASSWORD_DEFAULT);

                DB::table("users")->where("userID", "=", $user->userID)->where("companyID", "=", $companyID)->update([
                    "userPassword" => $hash
                ]);

                $this->sendUserCreationEmail($user->userFirstName, $user->userEmail, $user->employeeID);
                array_push($success, "password changed for user " . $user->userEmail);
            } else {
                array_push($errors, $user->userEmail . " not found.");
            }
        }
        if ($errors) {
            return response()->json(["success" => false, "error" => $errors]);
        } elseif($success && $errors) {
            return response()->json(["success" => true, "message" => "successful", "error" => $errors]);
        } else
            return response()->json(["success" => true, "message" => "password changed and email sent successfully."], 200);
    }
    
    public function getCompany(Request $req)
    {
        $token = $req->token;
        $companyID = $this->getCompanyID($token);
        $company = $this->getCompanyService->getCompany($companyID);

        if($company) {
            return response()->json(["success" => true, "message" => "Company fetched successfully.", "data" => $company]);
        } else {
            return response()->json(["success" => false, "message" => "Company not found."], 400);
        }
    }

    public function getBilling(Request $req)
    {
        $token = $req->token;
        $companyID = $this->getCompanyID($token);
        $billing = $this->getCompanyService->getCompanyBilling($companyID);
        if($billing) {
            $total = count($billing);
            if ($total > 0) {
                return response()->json(["success" => true, "data" => $billing]);

            } else {
                return response()->json(["success" => true, "message" => "No Billing Available"], 204);
            }
        } else {
            return response()->json(["success" => false, "message" => "Company not found."], 400);
        }
    }
}