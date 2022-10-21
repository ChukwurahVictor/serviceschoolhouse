<?php

namespace App\Services;

use App\Services\GetCompanyService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailService
{
   public function sendMail($template, $user, $course) 
   {
      $body = $template->emailBody;  // this is template dynamic body. You may get other parameters too from database. $title = $template->title; $from = $template->from;
      $subject = $template->emailSubject;

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
         'body' => $body,
      ];
      // return $details['body'];

      //$mailObject = new TestBook($request); // you can make php artisan make:mail MyMail
      Mail::to($details['email'])->send(new \App\Mail\CreateUser($details));

   }

   public function saveTemplate($type, $subject, $body) 
   {
      $typeExists = DB::table('emailTemplates')->where('type', $type)-first();
      if($typeExists) {
         $updateBody = DB::table('emailTemplates')->where('type', $type)->update([
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

   public function customMail($body, $subject, $user)
   {
      //get users, loop through, send mail with sendMail function
   }
}

