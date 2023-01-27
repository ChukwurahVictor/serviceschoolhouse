<?php

namespace App\Http\Controllers\Api\Users\Admin;

use App\Http\Controllers\Controller;
use App\Models\Webinar;
use App\Models\WebinarGroup;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebinarController extends Controller
{
    public function index(Request $req)
    {
        $token = $req->token;
        $companyID = $this->getCompanyID($token);

        $data = Webinar::with('webinar_groups')->where('company_id', $companyID)
                ->get();
        return response(['success' => true, 'data' => $data]);
    }

    public function store(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'start_date_time' => 'required',
            'groups_invited.*.groupID' => 'required',
            'link' => 'required',
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()], 422);
        }

        try{
            DB::beginTransaction();
            $token = $req->token;
            $start_date_time = $req->start_date_time;
            $title = $req->title;
            // $groups_invited = json_encode($req->groups_invited);
            $link = $req->link;
            $companyID = $this->getCompanyID($token);

            $webinar = Webinar::create([
                'title' => $title,
                'company_id' => $companyID,
                'start_date_time' => $start_date_time,
                'link' => $link,
            ]);

            for($i = 0; $i < count($req->groups_invited); $i++)
            {
                $groupID = $req->groups_invited[$i]["groupID"];
    
                WebinarGroup::create([
                    'webinar_id' => $webinar->id,
                    'group_id' => $groupID,
                ]);
            }
    
            DB::commit();
            return response(['success' => true, 'message' => 'resource created successfully'], 201);
        } catch (Exception $e)
        {
            DB::rollBack();
            Log::error($e);
            return response(['error' => 'Oops... Something went wrong'], 500);
        }        
    }

    public function update(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'start_date_time' => 'required',
            'groups_invited.*.groupID' => 'required',
            'link' => 'required',
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()], 422);
        }

        try{
            DB::beginTransaction();
            $token = $req->token;
            $start_date_time = $req->start_date_time;
            $title = $req->title;
            $groups_invited = json_encode($req->groups_invited);
            $link = $req->link;
            $companyID = $this->getCompanyID($token);

            Webinar::where('id', $id)->update([
                'title' => $title,
                'start_date_time' => $start_date_time,
                'groups_invited' => $groups_invited,
                'link' => $link,
            ]);

            WebinarGroup::where('id', $id)->delete();

            for($i = 0; $i < count($req->groups_invited); $i++)
            {
                $groupID = $req->groups_invited[$i]["groupID"];
    
                WebinarGroup::create([
                    'webinar_id' => $id,
                    'group_id' => $groupID,
                ]);
            }
    
            DB::commit();
            return response(['success' => true, 'message' => 'resource updated successfully'], 200);
        } catch (Exception $e)
        {
            DB::rollBack();
            return response(['error' => 'Oops... Something went wrong'], 500);
        }        
    }

    public function destroy($id)
    {
        WebinarGroup::where('id', $id)->delete();
        Webinar::where('id', $id)->delete();
        return response(['success' => true, 'message' => 'resource deleted successfully']);
    }

}
