<?php

namespace App\Services;

use App\Services\GetCompanyService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailService
{
   public function sendMail($template, $type, $user, $course) 
   {
      // return $template;
      $body = $template->emailBody;  // this is template dynamic body. You may get other parameters too from database. $title = $template->title; $from = $template->from;
      $subject = $template->emailSubject;
      $image = $template->image;

      if($user) {
         $data['firstname'] = $user->userFirstName;
         $data['lastname'] = $user->userLastName;
         $data['email'] = $user->userEmail;
         $data['password'] = $user->employeeID;
      }

      if($course) {
         $data['title'] = $course->courseName;
      }

      foreach($data as $key=>$parameter)
      {
         $body = str_replace('{{' . $key . '}}', $parameter, $body); // this will replace {{username}} with $data['username']
      }
      
      $details = [
         'firstname' => $user->userFirstName,
         'email' => "chukwurahvictor7@gmail.com",
         'subject' => $subject,
         'image' => $image,
         'body' => $body,
      ];

      // return $details;

      if($type == 'user_registration') {
         Mail::to($details['email'])->send(new \App\Mail\CreateUser($details));
      } else if ($type == 'assigned_course') {
         Mail::to($details['email'])->send(new \App\Mail\AssignedACourse($details));
      }
   }

   public function sendCustomMail($subject, $body, $user, $course) 
   {
      if($user) {
         $data['firstname'] = $user->userFirstName;
         $data['lastname'] = $user->userLastName;
         $data['email'] = $user->userEmail;
         $data['password'] = $user->employeeID;
      }

      if($course) {
         $data['title'] = $course->courseName;
      }

      foreach($data as $key=>$parameter)
      {
         $body = str_replace('{{' . $key . '}}', $parameter, $body); // this will replace {{username}} with $data['username']
      }
      
      $details = [
         'firstname' => $user->userFirstName,
         'email' => "chukwurahvictor7@gmail.com",
         'subject' => $subject,
         'body' => $body,
      ];
      Mail::to($details['email'])->send(new \App\Mail\CustomMail($details));
   }

   public function saveTemplate($type, $subject, $body, $companyID) 
   {
      $typeExists = DB::table('emailTemplates')->where('type', $type)->where('companyID', $companyID)->first();
      if($typeExists) {
         $updateBody = DB::table('emailTemplates')->where('type', $type)->where('companyID', $companyID)->update([
            'emailSubject' => $subject,
            'emailBody' => $body,
         ]);  
      } else {
         return response()->json(["success" => false, "message" => "Invalid type entered"]);
      }
   }

   public function getMailTemplate($type)
   {
      $typeExists = DB::table('emailTemplates')->where('type', $type)->first();
      if(!$typeExists) {
         return response()->json(["success" => false, "message" => "Invalid mail type entered"]);
      } else {
         $template = DB::table('emailTemplates')->where('type', $type)->first();
         return $template;
      }
   }

   public function createTemplate($companyID)
   {
      // create default template for newly created company
      $temps = DB::table('defaultEmailTemplates')->get();
      foreach ($temps as $temp) {
         DB::table('emailTemplates')->insert([
            'emailSubject' => $temp->emailSubject,
            'emailBody' => $temp->emailBody,
            'companyID' => $companyID,
            'type' => $temp->type
         ]);
      }
   }
}

