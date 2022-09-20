<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GetCompanyService {

   public function getCompany($companyID)
   {
      $company = DB::table('company')->where('companyID', $companyID)->first();
      return $company;
   }

   public function getCompanyWallet($companyID)
   {
      $wallet = DB::table('company')->where('companyID', $companyID)->select('wallet')->first();
      return $wallet;
   }

   public function getCompanyBilling($companyID)
   {
      if($company = DB::table('company')->where('companyID', $companyID)->first()) {
         $billing = DB::table('billing')->where('companyID', $companyID)->get();
         return $billing;
      }
   }

   // public function getCompanyID($token) {
   //    $table = DB::table("users")->where("token", "=", $token)->get();
   //    if (count($table)>0) {
   //       return $table[0]->companyID;
   //    }
   // }
}