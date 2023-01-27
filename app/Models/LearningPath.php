<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPath extends Model
{
    use HasFactory;

    public function details()
    {
        return $this->hasMany(LearningPathDetail::class, 'learningPathID', 'learningPathID');
    }

    public function groups()
    {
        return $this->hasMany(LearningPathGroup::class, 'learningPathID', 'learningPathID');
    }
}
