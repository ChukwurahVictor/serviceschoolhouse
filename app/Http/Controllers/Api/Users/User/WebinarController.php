<?php

namespace App\Http\Controllers\Api\Users\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WebinarGroup;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WebinarController extends Controller
{
    public function index(Request $req)
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
        $groups = DB::table('userGroup')->where('userID', $user->userID)->get();
        $data = [];
        $i = -1;
        foreach ($groups as $row)
        {
            $i++;
            $data[$i]['webinars'] = WebinarGroup::select('group_id', 'webinar_id')
            ->with(['webinar' => function($query){
                $query->select('id', 'title', 'start_date_time', 'link');
            }])->where('group_id', $row->groupID)->get();
        }
        return response(['success' => true, 'data' => $data]);
    }
}
