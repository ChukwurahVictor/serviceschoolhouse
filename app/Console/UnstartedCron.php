<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnstartedCron extends Command
{
   /**
    * The name and signature of the console command.
    *
    * @var string
    */
   protected $signature = 'unstarted:cron';

   /**
    * The console command description.
    *
    * @var string
    */
   protected $description = 'Send a reminder to user to complete the course they already started';

   /**
    * Create a new command instance.
    *
    * @return void
    */
   public function __construct()
   {
      parent::__construct();
   }

   /**
    * Execute the console command.
    *
    * @return int
    */

   public function handle()
   {
      // $users = DB::table('users')
      // ->join('login_logs', 'users.userEmail', '=', 'login_logs.email')
      // ->where('users.userID', '=', '280')->select('users.userEmail', 'users.userID')
      // ->where('login_logs.updated_at', '<=', Carbon::now()->subMinutes(10)->toDateTimeString())
      // ->where('login_logs.status', '=', 200)->groupBy('login_logs.email')->get();

      // $foo = [];
      // foreach($users as $user)
      // {
      //    $courses = DB::table('courseenrolment')->join('course', 'course.courseID', '=', 'courseenrolment.courseID')->where('userID', '=', '280')->get();
      //    $data = [];
      //    $i = -1;
      //    foreach($courses as $course)
      //    {
      //          $i++;
      //          //if it doesnt exist... course title
      //          // if(!DB::table('courseassessmentlog')->where('courseID', $course->courseID)->exists())
      //          // {
      //          //     $status =
      //          //     $data[$i]['course_title'] = $course->courseName;
      //          // }

      //          $x = DB::table('courseassessmentlog')->where('courseID', $course->courseID)->where('userID', '=', '280')->exists();
      //          if(!$x)
      //          {
      //             $data[$i]['course_title'] = $course->courseName;
      //          } 
               
      //    }

      //    array_push($foo, $data);
         
      //    $courses = collect($data);
      //    $collection = $courses->pluck('course_title');
      //    $details = [
      //          'email' => $user->userEmail,
      //          'courses' => $collection
      //    ];
         
      //    Mail::to($user->userEmail)->send(new \App\Mail\CourseReminder($details));
      // }
      Log::info("Unstarted Cron for Eniola");
   }
}