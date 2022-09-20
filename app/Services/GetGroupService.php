<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GroupService
{

   public function getCompanyGroups($companyID)
   {
      $group = DB::table('group')->where('companyID', $companyID)->get();
      return $group;
   }

   public function checkGroupBelongToCompany ($groupid)
   {
      $group = DB::table('group')->where('')->first();
   }

   public function getGroupCourses($companyID)
   {
      $courses = DB::table('groupEnrolment')->join('course', 'groupEnrolment.courseID', '=', 'course.courseID')->where('groupID', $groupid)->get();
      return $courses;
   }

   public function getGroupCoursesCost($groupid)
   {
      $cost = DB::table('groupEnrolment')->join('course', 'groupEnrolment.courseID', '=', 'course.courseID')->where('groupID', $groupid)->sum('course.price');
      return $cost;
   }
}
