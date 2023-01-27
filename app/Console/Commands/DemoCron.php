<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DemoCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:cron';

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
        // $users = DB::table('users')->get();
        
        // $foo = [];
        // foreach ($users as $user) {
        //     $userCourses = DB::table('courseEnrolment')->where('userID', '=', '280')->select('courseID')->get();
        //     $data = [];
        //     $i = -1;
        //     foreach ($userCourses as $course) {
        //         //return $course;
        //         $i++;
        //         $courseTitle = DB::table('course')->where('courseID', $course->courseID)->select('courseName')->first();
        //         //$module = DB::table('module')->where('courseID', $course->courseID)->first();
        //         $firstModule = DB::table('courseTrackerlog')->where('courseID', $course->courseID)->where('userID', $user->userID)->first();
        //         $checkAssessment = DB::table('courseAssessmentLog')->where('courseID', $course->courseID)->where('userID', $user->userID)->first();
                
        //         //return $firstModule;
        //         if($firstModule) {
                    
        //             if(!$checkAssessment && $firstModule->created_at < Carbon::now()->subWeek(2)->toDateTimeString()) 
        //             {
        //                 $data[$i]['course_title'] = $courseTitle->courseName;
        //             } 
        //         }                 
        //     }
        //     array_push($foo, $data);
            
        //     if(!empty($foo))
        //     {
        //         $courses = collect($data);
        //         $collection = $courses->pluck('course_title');
        //         $details = [
        //             'email' => $user->userEmail,
        //             'courses' => $collection
        //         ];
                
        //         Mail::to($user->userEmail)->send(new \App\Mail\CourseReminder($details));
        //     } 
        // }
        Log::info("Demo Cron");
    }
}
