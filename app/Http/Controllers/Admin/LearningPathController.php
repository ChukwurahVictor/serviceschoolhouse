<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LearningPathController extends Controller
{
    public function index(Request $req)
    {
        $token = $req->token;
        $companyID = $this->getCompanyID($token);

        $data = LearningPath::with([
                    'groups' => function($query) 
                    {
                        $query->/*select('learningPathDetailID')*/with('group');
                    },
                    'details' => function($query) 
                    {
                    $query->/*select('learningPathDetailID')*/with(['course'/* => function($query){$query->select(['courseName']);}*/]);
                    }
                ])
                ->where('companyID', $companyID)
                ->get();
        //$d = LearningPath::with(['details', fn($query) => $query->with('course')])->get();
        // return $d;
        // $learning_paths = DB::table('learning_paths')->where('companyID', $companyID)->get();

        // $data = [];
        // $i = -1;
        // foreach($learning_paths as $learn)
        // {
        //     $i++;
        //     $data[$i]['id'] = $learn->learningPathID;
        //     $data[$i]['title'] = $learn->title;
        //     $data[$i]['description'] = $learn->description;
        //     $data[$i]['duration'] = $learn->duration;
        //     $data[$i]['created_at'] = $learn->created_at;
        //     $data[$i]['updated_at'] = $learn->updated_at;

        //     $data[$i]['courses'] = DB::table('learning_path_details')->select('course.courseID', 'course.courseName', 'learning_path_details.sort')->join('course', 'course.courseID', '=', 'learning_path_details.courseID')->where('learningPathID', $learn->learningPathID)->get();
        // }
        return response(['success' => true, 'data' => $data]);
    }

    public function store(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'description' => 'required',
            'duration' => 'required',
            'groups.*.groupID' => 'required',
            'courses.*.courseID' => 'required'
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()], 422);
        }

        try{
            DB::beginTransaction();
            $token = $req->token;
            $title = $req->title;
            $description = $req->description;
            $duration = $req->duration;
            $companyID = $this->getCompanyID($token);

            $id = DB::table('learning_paths')->insertGetId([
                'title' => $title,
                'companyID' => $companyID,
                'description' => $description,
                'duration' => $duration,
                'created_at' => now(),
            ]);
    
            for($i = 0; $i < count($req->groups); $i++)
            {
                $groupID = $req->groups[$i]["groupID"];
    
                DB::table('learning_path_groups')->insert([
                    'learningPathID' => $id,
                    'groupID' => $groupID,
                ]);
            }
    
            for($i = 0; $i < count($req->courses); $i++)
            {
                $courseID = $req->courses[$i]["courseID"];
                $sort = $req->courses[$i]["sort"];
    
                DB::table('learning_path_details')->insert([
                    'learningPathID' => $id,
                    'courseID' => $courseID,
                    'sort' => $sort
                ]);
            }

            DB::commit();
            return response(['success' => true, 'message' => 'resource created successfully'], 201);
        } catch (Exception $e)
        {
            DB::rollBack();
            return response(['error' => 'Oops... Something went wrong'], 500);
        }        
    }

    public function update(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'description' => 'required',
            'duration' => 'required',
            'groups.*.groupID' => 'required',
            'courses.*.courseID' => 'required'
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()]);
        }

        try{
            DB::beginTransaction();
            $token = $req->token;
            $title = $req->title;
            $description = $req->description;
            $duration = $req->duration;
            $companyID = $this->getCompanyID($token);


            DB::table('learning_paths')->where('learningPathID', $id)->update([
                'title' => $title,
                'companyID' => $companyID,
                'description' => $description,
                'duration' => $duration,
                'updated_at' => now(),
            ]);

            DB::table('learning_path_groups')->where('learningPathID', $id)->delete();
            DB::table('learning_path_details')->where('learningPathID', $id)->delete();

            for($i = 0; $i < count($req->groups); $i++)
            {
                $groupID = $req->groups[$i]["groupID"];
    
                DB::table('learning_path_groups')->insert([
                    'learningPathID' => $id,
                    'groupID' => $groupID,
                ]);
            }

            for($i = 0; $i < count($req->courses); $i++)
            {
                $courseID = $req->courses[$i]["courseID"];
                $sort = $req->courses[$i]["sort"];

                DB::table('learning_path_details')->insert([
                    'learningPathID' => $id,
                    'courseID' => $courseID,
                    'sort' => $sort
                ]);
            }
            
        
            DB::commit();
            return response(['success' => true, 'message' => 'resource id ' . $id . ' updated successfully'], 200);
        } catch (Exception $e)
        {
            DB::rollBack();
            return response(['error' => 'Oops... Something went wrong'], 500);
        }       
    }

    public function destroy($id)
    {
        DB::table('learning_path_groups')->where('learningPathID', $id)->delete();
        DB::table('learning_path_details')->where('learningPathID', $id)->delete();
        DB::table('learning_paths')->where('learningPathID', $id)->delete();
        return response(['success' => true, 'message' => 'resource deleted successfully']);
    }

}
