<?php

namespace App;
use App\Progression;

use Illuminate\Database\Eloquent\Model;

class Progression extends Model
{
    protected $fillable = [
        'student_id', 'skill_id', 'student_validation', 'teacher_validation'
    ];

    public static function getStudentProgressionByModuleId($moduleId) {
        
    }

    public static function getProgressionsByStudentIdAndByFormationId($formationId, $userId) {
        $progressions = Progression::
            select('progressions.*')
            ->where([
                ['progressions.student_id',$userId],
            ])->get();

        $studentValitations = $teacherValidations = 0;

        foreach($progressions as $progression):
            if($progression->student_validation) $studentValitations++;
            if($progression->teacher_validation) $teacherValidations++;
        endforeach;

        $newProgressions = [
            'totalSkills' => $progressions->count(),
            'studentValidations' => $studentValitations,
            'teacherValidations' => $teacherValidations,
        ];

        return $newProgressions;
    }
}
