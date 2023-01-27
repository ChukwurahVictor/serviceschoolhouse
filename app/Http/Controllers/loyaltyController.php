<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class LoyaltyController extends Controller
{

    public function fetchLoyalties(Request $req)
    {
        $loyalties = DB::table('loyaltyLevels')->get();
        $total = count($loyalties);
        if ($total > 0) {
            return response()->json(["success" => true, "message" => "Loyalties fetched successfully.", $loyalties]);
        } else {
            return response()->json(["success" => true, "message" => "No loyalty level available"]);
        }
    }

    public function createLoyalty(Request $req)
    {
        $title = $req->title;
        $points = $req->points;

        if (DB::table('loyaltyLevels')->where('title', $title)->orwhere('points', $points)->doesntExist()) {
            DB::table('loyaltyLevels')->insert([
                "title" => $title,
                "points" => $points
            ]);
        } else {
            return response()->json(["success" => false, "message" => "Loyalty level already exists."], 400);
        }
    }

    public function fetchLoyalty(Request $req)
    {
        $loyaltyID = $req->loyaltyID;

        $loyaltylevelExists = DB::table('loyaltyLevels')->where('loyaltyLevelID', $loyaltyID)->exists();

        if ($loyaltylevelExists) {
            $loyaltylevel = DB::table('loyaltyLevels')->where('loyaltyLevelID', $loyaltyID)->get();
            return response()->json(["success" => true, "message" => "Loyalty level fetched successfully.", "data" => $loyaltylevel]);
        } else {
            return response()->json(["success" => false, "message" => "Loyalty level does not exist."], 400);
        }
    }

    public function updateLoyalty(Request $req)
    {
        $loyaltyID = $req->loyaltyID;
        $title = $req->title;
        $checkpoints = $req->checkpoints;
        $badge = $req->badge;

        $loyaltylevelExists = DB::table('loyaltyLevels')->where('loyaltyLevelID', $loyaltyID)->exists();

        if ($loyaltylevelExists) {
            DB::table('loyaltyLevels')->where('loyaltyLevelID', $loyaltyID)->update([
                'title' => $title,
                'checkpoints' => $checkpoints,
                'badges' => $badge,
            ]);
            return response()->json(["success" => true, "message" => "Loyalty level updated successfully."]);
        } else {
            return response()->json([ "success" => false, "message" => "Loyalty level does not exist."], 400);
        }
    }

    public function deleteLoyalty(Request $req)
    {
        $loyaltyID = $req->loyaltyID;

        if (DB::table('loyaltyLevels')->where('loyaltyLevelID', $loyaltyID)->exists()) {
            DB::table('loyaltyLevels')->where('loyaltyLevelID', $loyaltyID)->delete();
            return response()->json(["success" => false, "message" => "Loyalty level deleted successfully."]);
        } else {
            return response()->json([ "success" => false, "message" => "Loyalty level does not exist."], 400);
        }
    }
}
