<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoyaltyController extends Controller
{

    public function fetchLoyalties(Request $req)
    {
        $loyalties = DB::table('loyaltylevels')->get();
        $total = count($loyalties);
        if ($total > 0) {
            return response()->json(["success" => true, "message" => "Loyalties fetched successfully.", "data" => $loyalties]);
        } else {
            return response()->json(["success" => true, "message" => "No loyalty level available"]);
        }
    }

    public function createLoyalty(Request $req)
    {
        $title = $req->title;
        $min_points = $req->min_points;
        $max_points = $req->max_points;
        $badge = $req->badge;

        $loyaltylevel = DB::table('loyaltylevels')->where('title', $title)->doesntExist();

        if ($loyaltylevel) {
            DB::table('loyaltylevels')->insert([
                "title" => $title,
                "min_points" =>$min_points,
                "max_points" =>$max_points,
                "badges" => $badge
            ]);
            return response()->json(["success" => true, "message" => "Loyalty level created successfully."], 201);
        } else {
            return response()->json(["success" => false, "message" => "Loyalty level already exists."], 400);
        }
    }

    public function fetchLoyalty(Request $req)
    {
        $loyaltyID = $req->loyaltyID;

        $loyaltylevelExists = DB::table('loyaltylevels')->where('loyaltylevelID', $loyaltyID)->exists();

        if ($loyaltylevelExists) {
            $loyaltylevel = DB::table('loyaltylevels')->where('loyaltylevelID', $loyaltyID)->get();
            return response()->json(["success" => true, "message" => "Loyalty level fetched successfully.", "data" => $loyaltylevel]);
        } else {
            return response()->json(["success" => false, "message" => "Loyalty level does not exist."], 400);
        }
    }

    public function updateLoyalty(Request $req)
    {
        $loyaltyID = $req->loyaltyID;
        $title = $req->title;
        $min_points = $req->min_points;
        $max_points = $req->max_points;
        $badge = $req->badge;

        $loyaltylevelExists = DB::table('loyaltylevels')->where('loyaltylevelID', $loyaltyID)->exists();

        if ($loyaltylevelExists) {
            DB::table('loyaltylevels')->where('loyaltylevelID', $loyaltyID)->update([
                'title' => $title,
                'min_points' => $min_points,
                'max_points' => $max_points,
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

        if (DB::table('loyaltylevels')->where('loyaltyID', $loyaltyID)->exists()) {
            DB::table('loyaltylevels')->where('loyaltyID', $loyaltyID)->delete();
            return response()->json(["success" => false, "message" => "Loyalty level deleted successfully."]);
        } else {
            return response()->json([ "success" => false, "message" => "Loyalty level does not exist."], 400);
        }
    }
}
