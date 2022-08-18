<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    public function filterParams(Request $req)
    {
        $token = $req->token;
        $queryForCompanyId = DB::table("users")->where("token", "=", $token)->select("companyID")->get();
        $companyID = $queryForCompanyId[0]->companyID;
        $location = DB::table("users")->where("companyID", "=", $companyID)->selectRaw("distinct(location)")->get();
        $queryForGrade = DB::table("users")->where("companyID", "=", $companyID)->selectRaw("distinct(userGrade)")->get();
        $queryForRoleName = DB::table("groupRole")->selectRaw("roleName")->get();
        $queryForGroup = DB::table("group")->join("users", "group.companyID", "=", "users.companyID")->where("group.companyID", "=", $companyID)->selectRaw("distinct(groupName)")->get();
        $gender = ["M", "F"];

        return response()->json(["success" => true, "location" => $location, "grade" => $queryForGrade, "roleName" => $queryForRoleName, "group" => $queryForGroup, "gender" => $gender]);
    }

    public function allCourses(Request $req)
    {
        $token = $req->token;
        $userGender = $req->userGender;
        $location = $req->location;
        $roleName = $req->roleName;
        $userGrade = $req->userGrade;
        $query = DB::table("users")->join("role", "users.userRoleID", "=", "role.RoleID")->where("token", "=", $token)->select(["companyID", "roleName"])->get();

        $companyID = $query[0]->companyID;

        $groupRoleID = null;
        if ($roleName) {
            $queryForGroupRoleID = DB::table("groupRole")->where("roleName", "=", $roleName)->get();
            $groupRoleID = $queryForGroupRoleID[0]->groupRoleId;
        }
        $queryForCourses = DB::table("courseEnrolment")->join("course", "courseEnrolment.CourseID", "=", "course.CourseID")->join("users", "courseEnrolment.userID", "=", "users.userID")->where("courseEnrolment.companyID", "=", $companyID)->where("groupRoleID", "like", "%" . $groupRoleID . "%")->where("userGrade", "like", "%" . $userGrade . "%")->where("location", "like", "%" . $location . "%")->where("userGender", "like", "%" . $userGender . "%")->selectRaw("any_value(courseEnrolment.companyID) as companyID, course.courseID, courseName, count(courseEnrolment.userID) as enrolled")->groupBy("courseID")->get();

        if (!$queryForCourses) {
            $queryForCourses = [];
            return response()->json(["success" => true, "message" => $queryForCourses]);
        }

        foreach ($queryForCourses as $course) {
            $courseID = $course->courseID;
            $totalEnrolled = $course->enrolled;

            $queryForCompleted = DB::table("courseAssessmentLog")->join("users", "courseAssessmentLog.userID", "=", "users.userID")->where("status", "=", "pass")->where("courseID", "=", $courseID)->where("companyID", "=", $companyID)->where("groupRoleID", "like", "%" . $groupRoleID . "%")->where("userGrade", "like", "%" . $userGrade . "%")->where("location", "like", "%" . $location . "%")->where("userGender", "like", "%" . $userGender . "%")->selectRaw("score, status")->orderBy("score", "desc")->get();

            $averageSum = DB::table("courseAssessmentLog")->join("users", "courseAssessmentLog.userID", "=", "users.userID")->where("courseID", "=", $courseID)->where("companyID", "=", $companyID)->where("groupRoleID", "like", "%" . $groupRoleID . "%")->where("userGrade", "like", "%" . $userGrade . "%")->where("location", "like", "%" . $location . "%")->where("userGender", "like", "%" . $userGender . "%")->selectRaw("round(avg(score), 0) as average")->get();

            $averageRange = DB::table("courseAssessmentLog")->join("users", "courseAssessmentLog.userID", "=", "users.userID")->where("courseID", "=", $courseID)->where("companyID", "=", $companyID)->where("groupRoleID", "like", "%" . $groupRoleID . "%")->where("userGrade", "like", "%" . $userGrade . "%")->where("location", "like", "%" . $location . "%")->where("userGender", "like", "%" . $userGender . "%")->selectRaw("concat( MIN(score) , '-', MAX(score)) as average_range")->get();

            $complete = count($queryForCompleted);
            $incomplete = $totalEnrolled - $complete;
            $course->complete = $complete;
            $course->incomplete = $incomplete;
            $course->averageSum = $averageSum[0]->average ?: $course->averageSum = null;
            $course->averageRange = $averageRange[0]->average_range ?? $course->averageRange = null;
        }

        return response()->json(["success" => true, "message" => $queryForCourses]);
    }

    public function candidateTable(Request $req)
    {
        $token = $req->token;
        //Get admin userID by default to populate the table
        $queryForUserID = DB::table("users")->where("token", "=", $token)->selectRaw("userID, companyID")->get();
        $getUserID = $queryForUserID[0]->userID;
        $companyID = $queryForUserID[0]->companyID;

        $userID = $req->userID ?? $getUserID;

        if (DB::table("users")->where("userID", "=", $userID)->exists()) {

            $query = DB::table("users")->join("groupRole", "users.groupRoleId", "=", "groupRole.groupRoleId")->where("users.userID", "=", $userID)->selectRaw("employeeID, concat(userFirstName,' ' ,userLastName) as usersName, userEmail, groupRole.roleName, userGrade, location, userGender")->get();

            $queryForCandidate = DB::table("courseEnrolment")->join("course", "course.courseID", "=", "courseEnrolment.courseID")->where("courseEnrolment.userID", "=", $userID)->selectRaw("courseName, courseEnrolment.courseID")->get();

            foreach ($queryForCandidate as $course) {

                $courseID = $course->courseID;

                $status = DB::table("courseAssessmentLog")->join("users", "users.userID", "=", "courseAssessmentLog.userID")->where("courseAssessmentLog.userID", "=", $userID)->where("courseAssessmentLog.courseID", "=", $courseID)->where("companyID", "=", $companyID)->select("status")->get();

                $started = DB::table("courseTrackerLog")->join("module", "courseTrackerLog.moduleID", "=", "module.moduleID")
                ->join("users", "users.userID", "=", "courseTrackerLog.userID")->join("course", "course.courseID", "=", "module.courseID")->where("courseTrackerLog.userID", "=", $userID)->where("course.courseID", "=", $courseID)->where("companyID", "=", $companyID)->selectRaw("distinct(courseTrackerLog.moduleID) as started")->get();

                $getModuleCompletedCount = DB::table("courseTrackerLog")->join("module", "courseTrackerLog.moduleID", "=", "module.moduleID")
                ->join("users", "users.userID", "=", "courseTrackerLog.userID")->join("course", "course.courseID", "=", "module.courseID")->where("courseTrackerLog.userID", "=", $userID)->where("course.courseID", "=", $courseID)->where("companyID", "=", $companyID)->where("status", "=", "pass")->selectRaw("distinct(courseTrackerLog.moduleID) as moduleID")->get();

                $moduleCompleted = count($getModuleCompletedCount);

                $getModuleCount = DB::table('module')->join("course", "course.courseID", "=", "module.courseID")->where("course.courseID", "=", $courseID)->where("type", "=", null)->select("moduleID", "moduleName", "course.courseID")->get();
                $moduleCount = count($getModuleCount);

                $moduleProgress = $moduleCompleted . "/" . $moduleCount;
                // $course->averageRange = $averageRange[0]->averageRange ?? $course->averageRange = null;
                (count($status) > 0) ? $course->status = $status[0]->status : $course->status = 'pending';
                (count($started) > 0) ? $course->started = true : $course->started = false;
                $course->moduleProgress = $moduleProgress;

                $candidateScore = DB::table('courseAssessmentLog')->selectRaw("max(score) as candidateScore")->where("courseID", "=", $courseID)->where("userID", "=", $userID)->get();

                $averageScore = DB::table("courseAssessmentLog")->selectRaw("ROUND(avg(score)) as nationalAverage")
                ->join("users", "users.userID", "=", "courseAssessmentLog.userID")
                ->where("courseID", "=", $courseID)->where("companyID", "=", $companyID)->get();

                if (count($candidateScore) > 0) {
                
                    if (($candidateScore[0]->candidateScore > 0) && ($candidateScore[0]->candidateScore <= 49)) {
                        $course->averageRange = '0%-49%';
                    }elseif (($candidateScore[0]->candidateScore >= 50) && ($candidateScore[0]->candidateScore <= 74)) {
                        $course->averageRange = '50%-74%';
                    }elseif (($candidateScore[0]->candidateScore >= 75) && ($candidateScore[0]->candidateScore <= 89)) {
                        $course->averageRange= '75%-89%';
                    }elseif ($candidateScore[0]->candidateScore >= 90) {
                        $course->averageRange = '90%+';
                    }
                } else 
                    $course->averageRange = null;

                $course->candidateSummary = [$candidateScore[0], $averageScore[0]];
            }
            return response()->json(["success" => true, "candidateDetails" => $query, "candidateSummary" => $queryForCandidate]);
        } else {
            return response()->json(["success" => false, "message" => "User details incorrect"], 400);
        }
    }

    public function searchCandidate(Request $req)
    {
        $token = $req->token;
        $queryForCompanyId = DB::table("users")->where("token", "=", $token)->select("companyID")->get();
        $companyID = $queryForCompanyId[0]->companyID;

        if ($req->searchRequest) {
            $searchRequestVal = $req->searchRequest;

            if (strpos($searchRequestVal, ' ') !== false) {
                $searchRequest = explode(" ", $searchRequestVal);
                $userFirstName = $searchRequest[0];
                $userLastName = $searchRequest[1];

                $searchQuery = DB::table("users")->where("companyID", "=", $companyID)->where("userFirstName", "like", "%" . $userFirstName . "%")->orWhere("userLastName", "like", "%" . $userLastName . "%")->selectRaw("concat(userFirstName,' ' ,userLastName) as usersName, userID")->get();
            } else {
                $searchQuery = DB::table("users")->where("companyID", "=", $companyID)->where(function ($query) use ($searchRequestVal) {
                    $query->where("userFirstName", "like", "%" . $searchRequestVal . "%")
                        ->orWhere("userLastName", "like", "%" . $searchRequestVal . "%")
                        ->orWhere("userEmail", "like", "%" . $searchRequestVal . "%")
                        ->orWhere("employeeID", "like", "%" . $searchRequestVal . "%");
                })->selectRaw("concat(userFirstName,' ' ,userLastName) as usersName, userID")->get();
            }

            return response()->json(["success" => true, "search" => $searchQuery]);
        } else {
            return response()->json(["success" => false, "search" => []]);
        }
    }

    public function courseView(Request $req)
    {
        $token = $req->token;
        // $courseID = $req->courseID;
        $userGender = $req->userGender;
        $location = $req->location;
        $roleName = $req->roleName;
        $userGrade = $req->userGrade;
        $page_number = $req->page_number ?? 1;
        $page_size = $req->page_size ?? 100;
        $offset = ($page_number - 1) * $page_size;

        $users = DB::table("users")->where("token", "=", $token)->get();
        $companyID = $users[0]->companyID;

        $allCourses = DB::table("courseSeat")->join("course", "course.courseID", "=", "courseSeat.courseID")->where("companyID", "=", $companyID)->select(["courseName", "courseSeat.courseID"])->get();
        $courseID = $req->courseID ?? $allCourses[0]->courseID;
        $courseName = $allCourses[0]->courseName;

        if (DB::table("course")->where("courseID", "=", $courseID)->exists()) {

            if (DB::table("courseEnrolment")->where("courseEnrolment.courseID", "=", $courseID)->where("companyID", "=", $companyID)->exists()) {

                $modules = DB::table("module")->where("courseID", "=", $courseID)->where("type", "=", null)->get();
                $completionOnFirstAttempt = [];
                $completionOnMultipleAttempt = [];

                $groupRoleID = null;
                if ($roleName) {
                    $queryForGroupRoleID = DB::table("groupRole")->where("roleName", "=", $roleName)->get();
                    $groupRoleID = $queryForGroupRoleID[0]->groupRoleId;
                }
                $enrolled = DB::table("courseEnrolment")->join("users", "courseEnrolment.userID", "=", "users.userID")->where("courseEnrolment.courseID", "=", $courseID)->where("courseEnrolment.companyID", "=", $companyID)->where("groupRoleID", "like", "%" . $groupRoleID . "%")->where("userGrade", "like", "%" . $userGrade . "%")->where("location", "like", "%" . $location . "%")->where("userGender", "like", "%" . $userGender . "%")->get();
                
                foreach ($modules as $module) {
                    $moduleID = $module->moduleID;

                    $getSingleModuleCompletedCount = DB::select("select courseTrackerLog.userID, COUNT(*) total_attempts FROM  courseTrackerLog join users on users.userID=courseTrackerLog.userID where companyID=$companyID and moduleID=$moduleID and status='pass' and groupRoleID like '%$groupRoleID%' and userGrade like '%$userGrade%' and location like '%$location%' and userGender like '%$userGender%' GROUP BY courseTrackerLog.userID HAVING COUNT(*) = 1");

                    $getMultipleModuleCompletedCount = DB::select("select courseTrackerLog.userID, COUNT(*) total_attempts FROM  courseTrackerLog join users on users.userID=courseTrackerLog.userID where companyID=$companyID and moduleID=$moduleID and status='pass' and groupRoleID like '%$groupRoleID%' and userGrade like '%$userGrade%' and location like '%$location%' and userGender like '%$userGender%' GROUP BY courseTrackerLog.userID HAVING COUNT(*) > 1");

                    count($getSingleModuleCompletedCount) > 0 ? $singleModuleCompletedCount=count($getSingleModuleCompletedCount) : $singleModuleCompletedCount=0;

                    count($getMultipleModuleCompletedCount) > 0 ? $multipleModuleCompletedCount=count($getMultipleModuleCompletedCount) : $multipleModuleCompletedCount=0;

                    array_push($completionOnFirstAttempt, $singleModuleCompletedCount);
                    array_push($completionOnMultipleAttempt, $multipleModuleCompletedCount);
                }

                $users = DB::table("courseEnrolment")->join("users", "courseEnrolment.userID", "=", "users.userID")->where("courseID", "=", $courseID)->where("courseEnrolment.companyID", "=", $companyID)->where("groupRoleID", "like", "%" . $groupRoleID . "%")->where("userGrade", "like", "%" . $userGrade . "%")->where("location", "like", "%" . $location . "%")->where("userGender", "like", "%" . $userGender . "%")->selectRaw("employeeID, concat(userFirstName,' ' ,userLastName) as usersName, users.userID")->skip($offset)->take($page_size)->get();

                $totalUsers = DB::table("courseEnrolment")->join("users", "courseEnrolment.userID", "=", "users.userID")->where("courseID", "=", $courseID)->where("courseEnrolment.companyID", "=", $companyID)->where("groupRoleID", "like", "%" . $groupRoleID . "%")->where("userGrade", "like", "%" . $userGrade . "%")->where("location", "like", "%" . $location . "%")->where("userGender", "like", "%" . $userGender . "%")->selectRaw("employeeID, concat(userFirstName,' ' ,userLastName) as usersName, users.userID")->count();

                $countCompleted = 0;
                $countPassed = 0;
                $countFailed = 0;
                $i = 0;
                $j = 0;
                $k = 0;
                $l = 0;

                foreach ($users as $user) {
                    $userID = $user->userID;
                    $queryForCompleted = DB::table("courseAssessmentLog")->join("users", "courseAssessmentLog.userID", "=", "users.userID")->where("courseAssessmentLog.userID", "=", $userID)->where("courseID", "=", $courseID)->where("users.companyID", "=", $companyID)->selectRaw("score, status")->orderBy("score", "desc")->get();

                    $getCompleted = DB::table("courseTrackerLog")->join("module", "courseTrackerLog.moduleID", "=", "module.moduleID")->join("course", "course.courseID", "=", "module.courseID")->join("users", "users.userID", "=", "courseTrackerLog.userID")->where("course.courseID", "=", $courseID)->where("users.companyID", "=", $companyID)->where("status", "=", "pass")->where("courseTrackerLog.userID", "=", $userID)->selectRaw("courseTrackerLog.moduleID")->groupBy("courseTrackerLog.moduleID")->get();

                    $moduleProgress = count($getCompleted) . "/" . count($modules);
                    if (count($getCompleted) == count($modules)) {
                        $countCompleted++;
                    }

                    if (count($queryForCompleted) > 0 ) {
                        if ($queryForCompleted[0]->status === "pass") {
                            $countPassed++;
                        } else {
                            $countFailed++;
                        }
                    }
                        
                    $user->moduleProgress=$moduleProgress;
                    $user->status = $queryForCompleted[0]->status  ?? $user->status = 'pending';
                    $user->score = $queryForCompleted[0]->score  ?? $user->score = 0;

                    if (($user->score > 0) && ($user->score <= 49)) {
                        $i++;
                        $user->averageRange = '0%-49%';
                    }elseif (($user->score >= 50) && ($user->score <= 74)) {
                        $j++;
                        $user->averageRange = '50%-74%';
                    }elseif (($user->score >= 75) && ($user->score <= 89)) {
                        $k++;
                        $user->averageRange= '75%-89%';
                    }elseif ($user->score >= 90) {
                        $l++;
                        $user->averageRange = '90%+';
                    }
                }    

                $courseAssessmentRange = array("0-49", "50-74", "75-89", "90");
                $courseAssessmentValues = array($i, $j, $k, $l);
                $courseUserDetails = array("noOfModules"=>count($modules), "enrolled" => count($enrolled), "completed" => $countCompleted, "notCompleted" => (count($enrolled) - $countCompleted), "passed" => $countPassed, "failed"=>$countFailed);

                return response()->json(["success" => true, "courseID"=>$courseID, "courseName"=> $courseName, "courseDetails" => $courseUserDetails, "courseEngagementChart" => [ "first_attempt"=>$completionOnFirstAttempt, "multiple_attempt"=>$completionOnMultipleAttempt], "courseAssessment"=> ["courseAssessmentRange"=> $courseAssessmentRange, "courseAssessmentValues"=> $courseAssessmentValues], "totalUsers" => $totalUsers,  "courseTableDetails" => $users]);
            }else 
                return response()->json(["success" => false, "message" => "Course not assigned to company"], 401);
        } else 
            return response()->json(["success" => false, "message" => "Invalid Course ID"], 400);
    }

    public function courseModuleUsers(Request $req)
    {
        $token = $req->token;
        $moduleID = $req->moduleID;
        $courseID = $req->courseID;
        $userGender = $req->userGender;
        $location = $req->location;
        $roleName = $req->roleName;
        $userGrade = $req->userGrade;
        $page_number = $req->page_number ?? 1;
        $page_size = $req->page_size ?? 100;
        $offset = ($page_number - 1) * $page_size;

        $queryForCompanyId = DB::table("users")->where("token", "=", $token)->select("companyID")->get();
        if (count($queryForCompanyId) < 0) {
            return response()->json(["success" => false, "message" => "User does not exist"], 401);
        }

        $companyID = $queryForCompanyId[0]->companyID;

        $allCourses = DB::table("courseSeat")->join("course", "course.courseID", "=", "courseSeat.courseID")->where("companyID", "=", $companyID)->select(["courseName", "courseSeat.courseID"])->get();
        $courseID = $req->courseID ?? $allCourses[0]->courseID;

        $queryForModuleId = DB::table("module")->where("courseID", "=", $courseID)->select(["moduleID", "moduleName"])->get();
        $moduleID = $req->moduleID ?? $queryForModuleId[0]->moduleID;

        
        $queryForCourseId = DB::table("module")->where("moduleID", "=", $moduleID)->select(["courseID", "moduleName"])->get();
        if (count($queryForCourseId) < 0) {
            return response()->json(["success" => false, "message" => "Module does not exist"], 401);
        }
        
        $courseID = $queryForCourseId[0]->courseID;
        $moduleName = $queryForCourseId[0]->moduleName;
        

        if (DB::table("courseEnrolment")->where("courseEnrolment.courseID", "=", $courseID)->where("companyID", "=", $companyID)->exists()) {

            if ($userGender || $location || $userGrade || $roleName) {
                $groupRoleID = null;
                if ($roleName) {
                    $queryForGroupRoleID = DB::table("groupRole")->where("roleName", "=", $roleName)->get();
                    $groupRoleID = $queryForGroupRoleID[0]->groupRoleId;
                }

                $users = DB::table("courseEnrolment")->join("users", "courseEnrolment.userID", "=", "users.userID")->where("courseID", "=", $courseID)->where("courseEnrolment.companyID", "=", $companyID)->where("groupRoleID", "like", "%" . $groupRoleID . "%")->where("userGrade", "like", "%" . $userGrade . "%")->where("location", "like", "%" . $location . "%")->where("userGender", "like", "%" . $userGender . "%")->selectRaw("employeeID, concat(userFirstName,' ' ,userLastName) as usersName, users.userID")->skip($offset)->take($page_size)->get();

                $totalUsers = DB::table("courseEnrolment")->join("users", "courseEnrolment.userID", "=", "users.userID")->where("courseID", "=", $courseID)->where("courseEnrolment.companyID", "=", $companyID)->where("groupRoleID", "like", "%" . $groupRoleID . "%")->where("userGrade", "like", "%" . $userGrade . "%")->where("location", "like", "%" . $location . "%")->where("userGender", "like", "%" . $userGender . "%")->selectRaw("employeeID, concat(userFirstName,' ' ,userLastName) as usersName, users.userID")->count();
                
            } else {
                $users = DB::table("courseEnrolment")->join("users", "courseEnrolment.userID", "=", "users.userID")->where("courseID", "=", $courseID)->where("courseEnrolment.companyID", "=", $companyID)->selectRaw("employeeID, concat(userFirstName,' ' ,userLastName) as usersName, users.userID")->skip($offset)->take($page_size)->get();

                $totalUsers = DB::table("courseEnrolment")->join("users", "courseEnrolment.userID", "=", "users.userID")->where("courseID", "=", $courseID)->where("courseEnrolment.companyID", "=", $companyID)->selectRaw("employeeID, concat(userFirstName,' ' ,userLastName) as usersName, users.userID")->count();

            }

            foreach ($users as $user) {
                $userID = $user->userID;
                $getModuleCompletedCount = DB::table('courseTrackerLog')->select(["courseTrackerLog.userID","moduleID", "userID", "score", "status", "created_at"])->where("moduleID", "=", $moduleID)->where("userID", "=", $userID)->orderBy("score", "desc")->get();

                count($getModuleCompletedCount) > 0 ? $user->attempts=count($getModuleCompletedCount) : $user->attempts=null;
                count($getModuleCompletedCount) > 0 ? $user->maxScore=$getModuleCompletedCount[0]->score : $user->maxScore=null;
                count($getModuleCompletedCount) > 0 ? $user->status=$getModuleCompletedCount[0]->status : $user->status=null;
            }

            return response()->json(["success" => true, "moduleID"=>$moduleID, "moduleName"=> $moduleName, "courseModuleUsers" => $users, "totalUsers" => $totalUsers]);
        } else 
        return response()->json(["success" => false, "message" => "Course not assigned to company"], 401);
    }
}