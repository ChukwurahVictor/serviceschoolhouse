<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
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

    public function checkoutOrders(Request $req)
    {

        $token = $req->token;
        $courses = $req->courses;
        $isAdmin = $this->isAdmin($token);
        $alreadyOrdered = [];

        foreach ($courses as $course) {
            $orderNumber = $this->RandomCodeGenerator(6);
            // DB::table("orders")->updateOrInsert(["companyID" => $isAdmin["companyID"], "courseID" => $course["id"]], ["orderNumber" => $orderNumber,  "status" => "pending", "seats" => $course["seats"]]);
            if (DB::table("orders")->where("companyID", "=", $isAdmin["companyID"])->where("courseID", "=", $course["id"])->doesntExist()) {
                DB::table("orders")->insert(["companyID" => $isAdmin["companyID"], "courseID" => $course["id"], "orderNumber" => $orderNumber,  "status" => "pending", "seats" => $course["seats"]]);
            } else {
                array_push($alreadyOrdered, "Course with ID " . $course["id"] . " Already Ordered");
            }
        }
        return response()->json(["success" => true, "message" => "Order has been created.", "orderExists" => $alreadyOrdered]);
    }

    public function getOrders($token)
    {
        $isAdmin = $this->isAdmin($token);
        if ($isAdmin["isAdmin"]) {
            $result = DB::table("orders")->join("course", "course.courseID", "=", "orders.courseID")->where("companyID", "=", $isAdmin["companyID"])->get();
            return response()->json(["success" => true, "orders" => $result]);
        } else {
            return response()->json(["success" => false, "message" => "Users Not Admin"], 401);
        }
    }
}
