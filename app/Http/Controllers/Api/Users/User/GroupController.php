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

class GroupController extends Controller
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
        $data = DB::table('userGroup')
                ->select('group.groupID', 'group.groupName')
                ->join('group', 'userGroup.groupID', '=', 'group.groupID')
                ->where('userGroup.userID', $user->userID)
                ->get();

        return response(['success' => true, 'data' => $data], 200);
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

        $users = DB::table('userPoints')
                ->select('userPoints.*', 'users.userFirstName', 'users.userLastName', 'users.userID')
                ->join('users', 'userPoints.userID', '=', 'users.userID')
                ->where('groupID', $id)
                ->groupBy('userPoints.userID')
                ->get();
        $userArr = [];
        $i =-1;
        foreach($users as $user)
        {
            $i++;
            $userArr[$i]['name'] = $user->userFirstName . " " . $user->userLastName;
            $userArr[$i]['points'] = DB::table('userPoints')->where('userID', $user->userID)->where('groupID', $user->groupID)->sum('points');

        }
        $leaderboard = collect($userArr);
        $sortedUsers = $leaderboard->sortByDesc(function ($user) {
            return $user['points'];
        });
        return response(['success' => true, 'data' => $sortedUsers]);

    }

}
