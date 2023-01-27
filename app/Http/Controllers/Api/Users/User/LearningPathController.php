<?php

namespace App\Http\Controllers\Api\Users\User;

use App\Http\Controllers\Controller;
use App\Models\{
    LearningPath,
    User,
};
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LearningPathController extends Controller
{
    public function index(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         'token' => 'required',
    //     ]);

    //     if($validator->fails()){   
    //         return response(['error' => $validator->errors()->all()], 422);
    //     }

    //     $token = $req->token;
    //     $user = User::where('token', $token)->first();
    //     if(!$user)
    //     {
    //         return response(['error' => 'Token expired'], 401);
    //     }
    //     $data = DB::table('users')
    //             ->select('learning_paths.learningPathID', 'learning_paths.title', 'learning_paths.description', 'learning_paths.duration')
    //             ->join('userGroup', 'users.userID', '=', 'userGroup.userID')
    //             ->join('learning_path_groups', 'userGroup.groupID', '=', 'learning_path_groups.groupID')
    //             ->join('learning_paths', 'learning_paths.learningPathID', '=', 'learning_path_groups.learningPathID')
    //             ->where('users.token', $token)
    //             ->groupBy('learning_paths.learningPathID')
    //             ->get();
    //     foreach ($data as $row)
    //     {
    //         $tracker = DB::table("trackers")->where("userID", "=", $user->userID)->where("learningPathID", "=", $row->learningPathID)->first();   
    //         if($tracker)
    //         {
    //             $row->completionStatus = $tracker->status;
    //         } else {
    //             $row->completionStatus = 'none';
    //         }
    //     } 
    //     return response(['success' => true, 'data' => $data], 200);
    // }
    {
        $validator = Validator::make($req->all(), [
            'token' => 'required',
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()], 422);
        }

        $token = $req->token;
        $user = User::where('token', $token)->first();
        if(!$user)
        {
            return response(['error' => 'Token expired'], 401);
        }

        $data = DB::table('users')
                ->select('learning_paths.learningPathID', 'learning_paths.title', 'learning_paths.description', 'learning_paths.duration')
                ->join('userGroup', 'users.userID', '=', 'userGroup.userID')
                ->join('learning_path_groups', 'userGroup.groupID', '=', 'learning_path_groups.groupID')
                ->join('learning_paths', 'learning_paths.learningPathID', '=', 'learning_path_groups.learningPathID')
                ->where('users.token', $token)
                ->groupBy('learning_paths.learningPathID')
                ->get();

        if(count($data) < 1) {
            return response(['success' => true, 'data' => $data], 200);
        }

        foreach ($data as $row)
        {
            $tracker = DB::table("trackers")->where("userID", "=", $user->userID)->where("learningPathID", "=", $row->learningPathID)->first();  
            $row->lockStatus = true; 
            if($tracker)
            {
                $row->completionStatus = $tracker->status;
                $row->lockStatus = false;
            } else {
                $row->completionStatus = 'none';
            }
        }
        // To get the course progress
        $courses = DB::table("learning_path_details")->where('learningPathID', $row->learningPathID)->get();
            $cou = 0;
            foreach($courses as $course)
            {
                if(DB::table('courseAssessmentLog')->where('courseID', $course->courseID)->where('userID', $user->userID)->where('status', 'pass')->exists())
                {
                    $cou += 1;
                }
            }
            $row->courses_completed = $cou;
            $row->total_courses = count($courses);
            $course_progress = $row->courses_completed . '/' . $row->total_courses;

        return response(['success' => true, 'data' => $data, 'course_progress' => $course_progress], 200);
    }

    public function show(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'token' => 'required',
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()], 422);
        }

        $token = $req->token;
        $user = User::where('token', $token)->first();
        if(!$user)
        {
            return response(['error' => 'Token expired'], 401);
        }

        $track = DB::table('trackers')->where('status', '=', 'incomplete')->where('userID', $user->userID)->first();
        if($track && $track->learningPathID != $id)
        {
            return response(['error' => 'Please complete pending learning path'], 400);
        } else{
            if(!$track)
            {
                $trackID = DB::table('trackers')->insertGetId([
                    'learningPathID' => $id,
                    'userID' => $user->userID,
                    'created_at' => now()
                ]);
            } else{
                $trackID = $track->trackerID;
            }

            $data = DB::table('learning_path_details')
                ->join('course', 'learning_path_details.courseID', '=', 'course.courseID')
                ->join("module", "module.courseID","=", "learning_path_details.courseID")
                ->join("trackers", "trackers.learningPathID", "=", "learning_path_details.learningPathID")
                ->selectRaw("learning_path_details.sort, course.courseID, course.courseName, course.courseDescription, course.duration, course.courseType, count(moduleName) as no_of_modules")
                ->where("trackers.trackerID", "=", $trackID)           
                ->where('learning_path_details.learningPathID', $id) 
                ->groupBy("module.courseID")
                ->orderBy('sort', 'asc')
                ->get();

            foreach($data as $row)
            {
                $courseID = $row->courseID;
                $tracker = DB::table("courseTrackerLog")
                ->join("module", "courseTrackerLog.moduleID", "=", "module.moduleID")
                ->join("course", "module.courseID", "=", "course.courseID")
                ->selectRaw("count(distinct(courseTrackerLog.moduleID)) as modules_completed")->where("userID", "=", $user->userID)->where("course.courseID", "=", $courseID)->where("status", "=", "pass")->first();

                $getAssessmentStatus = DB::table("courseAssessmentLog")->where("courseID", "=", $courseID)->where("userID", "=", $user->userID)->orderBy("score", "desc")->first();

                if(!$getAssessmentStatus)
                {
                    $row->assessmentStatus = null;
                } else {
                    if($getAssessmentStatus->score >= 80)
                    {
                        $row->assessmentStatus = 'pass';
                    } else {
                        $row->assessmentStatus = 'fail';
                    }
                }
                
                $row->modules_completed = $tracker->modules_completed;
            }

            return response(['success' => true, 'data' => $data], 200);
        }

        
    }

    public function update(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'token' => 'required',
            'status' => 'required',
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()], 422);
        }

        $token = $req->token;
        $learningPathID = $id;
        $status = $req->status;
        $user = User::where('token', $token)->first();
        if(!$user)
        {
            return response(['error' => 'Token expired'], 401);
        }

        $track = DB::table('trackers')->where('status', '=', 'incomplete')->where('userID', $user->userID)->first();
        if($track && $track->learningPathID != $learningPathID)
        {
            return response(['error' => 'Please complete pending learning path'], 400);
        } else{
            DB::table('trackers')->where('learningPathID', $learningPathID)->where('userID', $user->userID)->update([
                'status' => 'complete'
            ]);
    
            return response(['success' => true, 'message' => 'Learning path updated successfully'], 200);
        }

       
    }
}
