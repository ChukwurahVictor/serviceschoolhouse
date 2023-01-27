<?php

namespace App\Http\Controllers\Api\Users\SiteAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        //table column name convention adds a bug to the query, find a way to select required columns
        $companies = Company::with(['users' => function($query){
            $query->where('userRoleID', 1);
        }])->withCount('users')->with(['courses_enrolled' => function($query){
            $query->with('course');
        }])->get();

        return response(["success" => true, "registeredCompanies" => $companies]);

    }

    public function update(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'companyID' => 'required',
            'companyName' => 'required',
            'companyFirstName' => 'required',
            'companyLastName' => 'required',
            'companyAddress' => 'required',
        ]);

        if($validator->fails()){   
            return response(['error' => $validator->errors()->all()], 422);
        }
        $companyID = $req->companyID;
        $companyName = $req->companyName;
        $userFirstName = $req->firstName;
        $userLastName = $req->lastName;
        $companyAddress = $req->companyAddress;

        $company = Company::where('companyID', $companyID)->first();
        if(!$company)
        {
           return response(["success" => false, "message" => "Company Does Not Exists"], 404); 
        }

        Company::where('companyID', $companyID)->update([
            'companyName' => $companyName, 
            'companyAddress1' => $companyAddress
        ]);

        User::where('companyID', $companyID)->where('userRoleID', 1)->update([
            'userFirstName' => $userFirstName, 
            'userLastName' => $userLastName
        ]);

        return response(["success" => true, "message" => "Company Updated Successful"], 200);
    }

    public function delete($id) {
        Company::where('companyID', $id)->delete();
        return response(["success" => true, "message" => "Company Deleted Successfully"]);
    }
}
