<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use VIPSoft\Unzip\Unzip;
use Carbon\Carbon;

class SiteAdminController extends Controller
{

    // Gets the base url by exploding the laravel "url" output


    private function getBaseUrl()
    {
        $explodedUrl = explode("/", url("/"));
        return "https://" . $explodedUrl[2];
    }

    public function getCompanies()
    {

        $companies =  DB::table("company")->join("users", "users.companyID", "=", "company.companyID")->where("users.userRoleID", "=", 1)->select(["company.companyID", "company.companyName as company_name", "company.companyAddress1 as company_address", "company.emailSuffix as company_email_suffix",  "company.companyAdminID", "users.userFirstName as admin_firstname", "users.userLastName as admin_lastname", "users.userEmail as admin_email", "company.created_at"])->get();

        foreach ($companies as $company) {
            $companyUsersNo = DB::table("users")->where("companyID", "=", $company->companyID)->count();
            $companyCourses = DB::table("courseEnrolment")->join("course", "course.courseID", "=", "courseEnrolment.courseID")->where("userID", "=", $company->companyAdminID)->select(["course.courseName", "courseEnrolment.created_at as purchased_at"])->get();
            $company->users_count = $companyUsersNo;
            $company->courses_list = $companyCourses;
        }

        return response()->json(["success" => true, "registeredCompanies" => $companies]);
    }

    public function editCompany(Request $req)
    {
        $companyID = $req->companyID;
        $companyName = $req->companyName;
        $userFirstName = $req->firstName;
        $userLastName = $req->lastName;
        $companyAddress = $req->companyAddress;

        // Checks if companyID exists
        if (DB::table("company")->where("companyID", "=", $companyID)->exists()) {

            DB::table("company")->where("companyID", "=", $companyID)->update(["companyName" => $companyName, "companyAddress1" => $companyAddress]);

            DB::table("users")->where(['companyID' => $companyID, "userRoleID" => 1])->update([ "userFirstName" => $userFirstName, "userLastName" => $userLastName]);

            return response()->json(["success" => true, "message" => "Company Updated Successful"]);
        } else {
            return response()->json(["success" => false, "message" => "Company Does Not Exists"], 400);
        }
    }

    public function deleteCompany (Request $req) {
        $companyID = $req->companyID;
         if (DB::table("company")->where("companyID", "=", $companyID)->exists()) {
            DB::table("company")->where("companyID", "=", $companyID)->delete();
            return response()->json(["success" => true, "message" => "Company Deleted Successfully"]);
        } else {
            return response()->json(["success" => false, "message" => "Company Does Not Exists"], 400);
        }
    }

    public function getUsers()
    {
        $users = DB::table("users")->join("company", "company.companyID", "=", "users.companyID")->join("role", "role.roleID", "=", "users.userRoleID")->select(["users.userFirstName", "users.userLastName", "users.userEmail", "company.companyName", "role.roleName", "users.created_at"])->get();
        return response()->json(["success" => true, "registeredUsers" => $users]);
    }

    public function createCourse(Request $req)
    {
        $courseName = $req->courseName;
        $courseDescription = $req->courseDescription;
        $courseCategory = $req->courseCategory;
        $coursePrice = $req->coursePrice;
        // have to pass 1 (true) or 0 (false) from the FrontEnd
        $published = $req->published;
        $duration = $req->duration;

        // Validate that a file was uploaded
        $validator = Validator::make($req->file(), [
            "courseImage" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => "Course Image not uploaded"], 400);
        }

        $courseImageName = $req->file("courseImage")->getClientOriginalName();
        $courseImagePath = $req->file("courseImage")->storeAs("CourseCoverImages", $courseImageName, "learningPlatformFolder");

        // Checks if courseName already exists
        if (DB::table("course")->where("courseName", "=", $courseName)->doesntExist()) {
            // Checks if file upload was successful
            if (!$courseImagePath) {
                return response()->json(["success" => false, "message" => "File not Uploaded"], 400);
            } else {
                $imagePath = $this->getBaseUrl() . "/" . $courseImagePath;

                DB::table("course")->insert(["courseName" => $courseName, "courseDescription" => $courseDescription, "price" => $coursePrice, "courseCategory" => $courseCategory, "image" => $imagePath, "published" => $published, "duration" => $duration]);

                return response()->json(["success" => true, "message" => "Course Creation Successful"]);
            }
        } else {
            return response()->json(["success" => false, "message" => "Course Already Exists"], 400);
        }
    }

    public function editCourse(Request $req)
    {
        $token=$req->token;
        $courseID = $req->courseID;
        $courseName = $req->courseName;
        $courseDescription = $req->courseDescription;
        $courseCategory = $req->courseCategory;
        $price = $req->price;
        // have to pass 1 (true) or 0 (false) from the FrontEnd
        $published = $req->published;
        $image= $req->image;
        $duration = $req->duration;

        // var_dump($courseID);
        // var_dump($token);
        $query=DB::table("users")->where('token',"=", $token)->get();
        
        // var_dump($query);
        
        // Checks if courseID exists

        if(count($query)==1){
            if (DB::table("course")->where("courseID", "=", $courseID)->exists()) {

                if($req->file("courseImage")) {
                    $courseImageName = $req->file("courseImage")->getClientOriginalName();
                    $courseImagePath = $req->file("courseImage")->storeAs("CourseCoverImages", $courseImageName, "learningPlatformFolder");
                    DB::table("course")->where("courseID", "=", $courseID)->update(["published" => $courseImagePath]);
                }

                DB::table("course")->where("courseID", "=", $courseID)->update(["courseName" => $courseName, "courseDescription" => $courseDescription, "price" => $price, "courseCategory" => $courseCategory,  "published" => $published, "image" => $image,
                "duration" => $duration]);

                return response()->json(["success" => true, "message" => "Course Updated Successful"]);
            } else {
                return response()->json(["success" => false, "message" => "Course Does Not Exists"], 400);
            }
        }
        return response()->json(["success" => false, "message" =>"Not a Site Admin"]);
    }


    public function publish (Request $req) {
        $published = $req->published;
        $courseID = $req->courseID;
        if (DB::table("course")->where("courseID", "=", $courseID)->exists()) {
            DB::table("course")->where(["courseID" => $courseID])->update(["published" => $published]);
            return response()->json(["success" => true, "message" => "Course has been Successfully Published"]);
        } 
        else {
            return response()->json(["success" => false, "message" => "Course Does Not Exists"], 400);
        }
    }

    public function deleteCourse(Request $req)
    {
        $courseID = $req->courseID;

        // Checks if courseID exists
        if (DB::table("course")->where("courseID", "=", $courseID)->exists()) {

            DB::table("course")->where("courseID", "=", $courseID)->delete();

            return response()->json(["success" => true, "message" => "Course Deleted Successful"]);
        } else {
            return response()->json(["success" => false, "message" => "Course Does Not Exists"], 400);
        }
    }

    public function addBundle(Request $req)
    {
        $bundleName = $req->bundleName;
        $bundleDescription = $req->bundleDescription;
        $bundlePrice = $req->bundlePrice;
        $courses = $req->courses;

        // Checks if a bundle with that name already exists
        if (DB::table("bundle")->where("bundleTitle", "=", $bundleName)->doesntExist()) {

            // Checks if all courses in the array exists
            foreach ($courses as $course) {
                if (DB::table("course")->where("courseID", "=", $course["id"])->doesntExist()) {
                    return response()->json(["success" => false, "message" => "Course with id " . $course["id"] . " does not exist"], 400);
                }
            }

            // Insert Bundle details in the bundle table
            $bundleID = DB::table("bundle")->insertGetId(["bundleTitle" => $bundleName, "bundleDescription" => $bundleDescription, "price" => $bundlePrice]);

            // Loop through course list and insert into courseBundleTable
            foreach ($courses as $course) {
                DB::table("courseBundle")->insert(["bundleID" => $bundleID, "courseID" => $course["id"]]);
            }

            return response()->json(["success" => true, "message" => "Bundle created successfully"]);
        } else {
            return response()->json(["success" => false, "message" => "Bundle name already exists"], 400);
        }
    }

    public function editBundle(Request $req)
    {
        $bundleName = $req->bundleName;
        $bundleDescription = $req->bundleDescription;
        $bundlePrice = $req->bundlePrice;
        $bundleID = $req->bundleID;
        $courses = $req->courses;

        // Checks if module exists
        if (DB::table("bundle")->where("bundleID", "=", $bundleID)->exists()) {

            // Checks if all courses in the array exists
            foreach ($courses as $course) {
                if (DB::table("course")->where("courseID", "=", $course["id"])->doesntExist()) {
                    return response()->json(["success" => false, "message" => "Course with id " . $course["id"] . " does not exist"], 400);
                }
            }

            // Updated bundle table
            DB::table("bundle")->where("bundleID", "=", $bundleID)->update(["bundleTitle" => $bundleName, "bundleDescription" => $bundleDescription, "price" => $bundlePrice]);

            // Delete previous courses associated with bundle ID in courseBundle table
            DB::table("courseBundle")->where("bundleID", "=", $bundleID)->delete();

            // Loop through the new course list and insert into courseBundle Table
            foreach ($courses as $course) {
                DB::table("courseBundle")->insert(["bundleID" => $bundleID, "courseID" => $course["id"]]);
            }

            return response()->json(["success" => true, "message" => "Bundle Updated Successfully"]);
        } else {
            return response()->json(["success" => false, "message" => "Bundle does not exist"], 400);
        }
    }

    public function getOrders(Request $req)
    {

        $orders = DB::table("orders")->join("company", "company.companyID", "=", "orders.companyID")->join("course", "course.courseID", "=", "orders.courseID")->select(["orderNumber", "orders.companyID", "company.companyName", "orders.courseID", "course.courseName", "seats", "status", "orders.created_at", "orders.updated_at"])->get();

        return response()->json(["success" => true, "orders" => $orders]);
    }

  


    public function editOrderStatus(Request $req)
    {

        $orderNumber = $req->orderNumber;
        $orderStatus = $req->status;

        if (DB::table("orders")->where("orderNumber", "=", $orderNumber)->exists()) {

            DB::table("orders")->where("orderNumber", "=", $orderNumber)->update(["status" => $orderStatus]);

            return response()->json(["success" => true, "message" => "Order Status Updated Successfully"]);
        } else {
            return response()->json(["success" => false, "message" => "Order does not exist"]);
        }
        }

  
     

    public function deleteBundle(Request $req)
    {
        $bundleID = $req->bundleID;

        // Checks if module exists
        if (DB::table("bundle")->where("bundleID", "=", $bundleID)->exists()) {

            DB::table("bundle")->where("bundleID", "=", $bundleID)->delete();

            return response()->json(["success" => true, "message" => "Bundle Deleted Successfully"]);
        } else {
            return response()->json(["success" => false, "message" => "Bundle does not exist"], 400);
        }
    }

    public function addModule(Request $req)
    {
        $moduleName = $req->moduleName;
        $moduleDescription = $req->moduleDescription;
        $courseID = $req->courseID;
        // $duration =$req->duration;
        // $test= CarbonInterval::seconds('900')->cascade()->forHumans();
        $duration=Carbon::now()->hour(0)->minute(0)->second($req->duration)->toTimeString();
        // $duration=Carbon::createFromTime(0, $req->duration, 0)->toTimeString();
        var_dump($duration);
        

        // Validate that a file was uploaded
        $validator = Validator::make($req->file(), [
            "folderzip" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => "Module Folder not uploaded"], 400);
        }
        $moduleFolderName = $req->file("folderzip")->getClientOriginalName();
        // Customize "learningPlatformFolder" in config > filesystem.php
        $moduleFolderPath = $req->file("folderzip")->storeAs("ModuleFolders", $moduleFolderName, "learningPlatformFolder");

        // Checks if course exists
        if (DB::table("course")->where("courseID", "=", $courseID)->exists()) {
            // Checks if module already exists
            if (DB::table("module")->where("courseID", "=", $courseID)->where("moduleName", "=", $moduleName)->doesntExist()) {

                // Check of folder was uploaded successfully
                if (!$moduleFolderPath) {
                    return response()->json(["success" => false, "message" => "Folder not Uploaded"], 400);
                } else {
                    $foldername = explode(".", $moduleFolderName)[0];
                    $folderPath = $this->getBaseUrl() . "/" . "ModuleFolders" . "/" . $foldername;

                    $unzipper = new Unzip();
                    // Unzip the zip folder uploaded above
                    $files = $unzipper->extract(storage_path("../../") . $moduleFolderPath, storage_path("../../ModuleFolders"));
                    // Check if Zip File still exists then delete
                    if (File::exists(storage_path("../../") . $moduleFolderPath)) {
                        File::delete(storage_path("../../") . $moduleFolderPath);
                    }

                    $moduleID = DB::table("module")->insertGetId(["moduleName" => $moduleName, "moduleDescription" => $moduleDescription, "courseID" => $courseID, "folder" => $folderPath,"duration" =>$duration]);

                    return response()->json(["success" => true, "message" => "Module Added", "moduleID" => $moduleID]);
                }
            } else {
                return response()->json(["success" => true, "message" => "Module already exist"], 400);
            }
        } else {
            return response()->json(["success" => false, "message" => "Course does not exist"], 400);
        }
    }
    

    public function editModule(Request $req)
    {
        $moduleName = $req->moduleName;
        $moduleDescription = $req->moduleDescription;
        $moduleID = $req->moduleID;
        $folder= $req->folder;
        $courseID=$req->courseID;
        $duration=Carbon::now()->hour(0)->minute(0)->second($req->duration)->toTimeString();


        // Checks if module exists
        if (DB::table("module")->where("moduleID", "=", $moduleID)->exists()) {

            DB::table("module")->where("moduleID", "=", $moduleID)->update(["moduleName" => $moduleName, "moduleDescription" => $moduleDescription, "folder" => $folder, "duration"=> $duration]);

            // if(DB::table("course")->where("courseID","=",$courseID)->exists()){

            //     $query=DB::table("module")->join("course","course.courseID","=","module.courseID")->selectRaw("any_value(SEC_TO_TIME( SUM( TIME_TO_SEC( module.duration) ) ))as moduleDuration ")->where("module.courseID","=",$courseID)->get();
    
            //     DB::table("course")->update(["duration" => $query]);
    
            //     // return response()->json(["success" => true, "message" => "Course Duration Updated Successfully"]);
    
            // }
            return response()->json(["success" => true, "message" => "Module Updated Successfully"]);
        } else {
            return response()->json(["success" => false, "message" => "Module does not exist"], 400);
        }
    }

    public function deleteModule(Request $req)
    {
        $moduleID = $req->moduleID;


        // Checks if module exists
        if (DB::table("module")->where("moduleID", "=", $moduleID)->exists()) {

            DB::table("module")->where("moduleID", "=", $moduleID)->delete();
            return response()->json(["success" => true, "message" => "Module Deleted Successfully"]);
        } else {
            return response()->json(["success" => false, "message" => "Module does not exist"], 400);
        }
    }

    public function sumOfModule(Request $req){
        $courseID=$req->courseID;

        if(DB::table("course")->where("courseID","=",$courseID)->exists()){

            $query=DB::table("module")->join("course","course.courseID","=","module.courseID")->selectRaw("any_value(SEC_TO_TIME( SUM( TIME_TO_SEC( module.duration) ) ))as courseDuration ")->where("module.courseID","=",$courseID)->get();


            
            // var_dump($query);
            // DB::table("course")->where("courseID","=",$courseID)->update(["duration" => $query]);

            return response()->json(["success" => true,"Sum of Modules" =>$query]);
        }

        return response()->json(["success" => false, "message" => "Update not successful"]);

       
    }

    public function addTopic(Request $req)
    {
        $topicName = $req->topicName;
        $topicDuration = $req->topicDuration;
        $moduleID = $req->moduleID;
        $courseID = $req->courseID;

        // Validate that a file was uploaded
        $validator = Validator::make($req->file(), [
            "folderzip" => "required"
        ]);
        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => "Topic Folder not uploaded"], 400);
        }
        $topicFolderName = $req->file("folderzip")->getClientOriginalName();
        $topicFolderPath = $req->file("folderzip")->storeAs("TopicFolders", $topicFolderName, "learningPlatformFolder");

        // Check if module ID exists for that particular course
        if (DB::table("module")->where("moduleID", "=", $moduleID)->where("courseID", "=", $courseID)->exists()) {
            // Checks if the topic has already been added
            if (DB::table("topic")->where("topicName", "=", $topicName)->where("moduleID", "=", $moduleID)->doesntExist()) {
                // Check of folder was uploaded successfully
                if (!$topicFolderPath) {
                    return response()->json(["success" => false, "message" => "Folder not Uploaded"], 400);
                } else {
                    $foldername = explode(".", $topicFolderName)[0];
                    $folderPath = $this->getBaseUrl() . "/" . "TopicFolders" . "/" . $foldername;

                    $unzipper = new Unzip();
                    // Unzip the zip folder uploaded above
                    $files = $unzipper->extract(storage_path("../../") . $topicFolderPath, storage_path("../../TopicFolders"));
                    // Check if Zip File still exists then delete
                    if (File::exists(storage_path("../../") . $topicFolderPath)) {
                        File::delete(storage_path("../../") . $topicFolderPath);
                    }

                    DB::table("topic")->insert(["topicName" => $topicName, "moduleID" => $moduleID, "duration" => $topicDuration, "folder" => $folderPath]);

                    return response()->json(["success" => true, "message" => "Topic Added"]);
                }
            } else {
                return response()->json(["success" => false, "message" => "Topic already exists"], 400);
            }
        } else {
            return response()->json(["success" => false, "message" => "Module does not exist"], 400);
        }
    }

    public function testFileUpload(Request $req)
    {
        $name = $req->file("image")->getClientOriginalName();

        // $path = $req->file("image")->store("images");

        $path = $this->getBaseUrl() . "/" . $req->file("image")->storeAs("CourseCoverImages", $name, "learningPlatformFolder");
        

        // $path = $req->file("image")->storeAs("../../../../CourseCoverImages", $name);

        if (!$path) {
            return response()->json(["success" => false, "message" => "File not Uploaded"], 400);
        } else {
            // return response()->json(["success" => true, "message" => "Upload Sucessful", "path" => url("/") . "/" . $path]);
            // echo $_SERVER;
            return response()->json(["success" => true, "message" => "Upload Sucessful", "path" => $path,]);
        }
    }

    public function testFolderUpload(Request $req)
    {
        $name = $req->file("folderzip")->getClientOriginalName();

        $foldername = explode(".", $name)[0];


        // $path = $this->getBaseUrl() . "/" . $req->file("folderzip")->storeAs("CourseCoverImages", $name, "learningPlatformFolder");;

        $path = $req->file("folderzip")->storeAs("TopicFolders", $name, "learningPlatformFolder");

        if (!$path) {


            return response()->json(["success" => false, "message" => "File not Uploaded"], 400);
        } else {

            $unzipper = new Unzip();
            // Unzip the zip folder uploaded above
            $files = $unzipper->extract(storage_path("../../") . $path, storage_path("../../TopicFolders"));
            // Check if Zip File still exists then delete
            if (File::exists(storage_path("../../") . $path)) {
                File::delete(storage_path("../../") . $path);
            }
            return response()->json(["success" => true, "message" => "Upload Sucessful", "foldername" => $foldername, "name" => $name]);
        }
    }

public function addCourseToOrders(Request $req){
        $token = $req->token;
        $orderNumber = $req->orderNumber;
        $courseID= $req->courseID;
        $companyID = $req->companyID;
        $status = $req->status;
        $seats = $req->seats;

        if (DB::table("orders")->where("orderNumber", "=", $orderNumber)->where("companyID","=",$companyID)->exists()) {
    
            $checkToken = DB::table("users")->where('token',"=", $token)->where("userRoleID","=",1)->get();
        
                // Checks if order number doesnt already have the course added
                if (DB::table("orders")->where("orderNumber","=", $orderNumber)->where("courseID", "=", $courseID)->doesntexist()) {
        
                        DB::table("orders")->insert(["courseID" => $courseID
                        , "orderNumber" => $orderNumber, "companyID"=>$companyID
                        ,"status" => $status, "seats" => $seats
                    ]);
                        // $query=DB::table("module")->join("orders","orders.courseID","=","module.courseID")->selectRaw("count(moduleName)as moduleCount")-where("courseID",$courseID)->where("orderNumber","=", $orderNumber);
    
                        return response()->json(["success" => true, "message" => "Course Added Successfully"]);
                    } else {
                        return response()->json(["success" => true, "message" => "Course Already exists for the company"]);
                    }
                } else {
                    return response()->json(["success" => false, "message" => "Incorrect order number or companyID"]);
                }
          
}

public function coursesInBundles(Request $req){
    $token = $req->token;
        
    if(DB::table("users")->where('token',"=", $token)->where("userRoleID","=",3)->exists()){
        $query = DB::table("courseBundle")->join("bundle","bundle.bundleID","=", "courseBundle.bundleID")->get();

        return response()->json(["success" => true, "data" => $query]);
    }
    return response()->json(["success" => false, "message" =>"Not a Site Admin"]);
}


public function adminLogin (Request $req) {
    $token= $req->token;
    $accessCode= $req->accessCode;
    $userEmail= $req->userEmail;

    if(DB::table("users")->where('userEmail',"=", $userEmail)->where("accessCode","=",$accessCode)->exists()) {
        $query=DB::table("users")->select(["token"])->where("accessCode","=",$accessCode)->get();
        // $query=DB::table('admin')->select(["admin.*"])->get();
            return response()->json(["success" => true,  "message" => "Admin login successful","data" => $query]);
    }
        else{
            return response()->json(["success" => false,  "message" => "Login failed, Invalid access code or Email"], 400);
    }
}

  
public function assignCoursesToCompany(Request $req){
    $companyID = $req->companyID;
    // $courseID = $req->courseID;
    $courseDescriptions= $req->courseDescription;
    // $seats = $req->seats;

    if (!is_array($courseDescriptions)){
        return response()->json(["success" => false, "error" =>"Course description is not an array"],400);
    }
    foreach($courseDescriptions as $courseDescription){
        var_dump($courseID);
        $courseID= $courseDescription->courseID;
        
        $seats=$courseDescription->seats;

        DB::table("courseSeat")->insert(["seats" => $seats, "courseID" => $courseID]);

        return response()->json(["success" => true, "message" => "Course Assigned successfully"]);
    }
    return response()->json(["success" => false, "message" =>"Course Assign unsuccessful"]);
}  

public function  getCandidatesScores(Request $req){
    // $courseID = $req->courseID;
    // $userID = $req->userID;

    $scoresandAttempts=DB::table("courseAssessmentLog")->join("users","users.userID", "=", "courseAssessmentLog.userID")->selectRaw("concat(any_value(users.userFirstName), ' ', any_value(users.userLastName)) as name, any_value(count(courseAssessmentLog.userID)) as noOfAttempts,status,courseAssessmentLog.score, courseID")->groupBy("courseAssessmentLog.ID")
    // ->skip($offset)->take($page_size)
    ->get();

    // $scoresandAttempts=DB::table("courseAssessmentLog")->selectRaw(" any_value(count(courseAssessmentLog.userID)) as noOfAttempts")->where("courseAssessmentLog.userID","", $userID)->where("courseAssessmentLog.courseID","",$courseID)->get();
    
   
    return response()->json(["success" => true, "scoresandAttempts" => $scoresandAttempts]);

}

public function getDeadline(Request $req){
    $courseID= $req->courseID;

    $current_time = Carbon::now();

    if ($current_time>= '2022-02-28 00:00:00' && $current_time<='2022-06-30 23:59:59') {
        return response()->json(["success" => true, "message" => "Course is currently active"]);
    } 
    return response()->json(["success" => false, "message" =>"Course is currently inactive"]);
}

public function addToWishList(Request $req){
    $courseID = $req->courseID;
    $userID = $req->userID;

    if(DB::table("wishlist")->where('userID',"=", $userID)->where('courseID','=', $courseID)->doesntExist()){
        $query = DB::table("wishlist")->insert(["courseID"=> $courseID, "userID"=> $userID]);

        return response()->json(["success" => true, "data"=>$query , "message"=>"Course Added to wishlist"]);
    }
    else{
        return response()->json(["success" => false,  "message" => "Course Already in wishlist"]);
    }
    
}
}
