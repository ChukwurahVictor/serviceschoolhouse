<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPathDetail extends Model
{
    use HasFactory;

    public function learning_path()
    {
        return $this->belongsTo(LearningPath::class, 'learningPathID');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID', 'courseID');
    }
}
