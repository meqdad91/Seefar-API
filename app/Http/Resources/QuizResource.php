<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'course_id' => $this->course,
            'intro' => $this->intro ? strip_tags($this->intro) : null,
            'time_open' => $this->timeopen ? date(DATE_ATOM, $this->timeopen) : null,
            'time_close' => $this->timeclose ? date(DATE_ATOM, $this->timeclose) : null,
            'time_limit_seconds' => (int) $this->timelimit,
            'attempts_allowed' => (int) $this->attempts,
            'grade_method' => (int) $this->grademethod,
            'grade_max' => (float) $this->grade,
            'sum_grades' => (float) $this->sumgrades,
            'created_at' => $this->timecreated ? date(DATE_ATOM, $this->timecreated) : null,
            'updated_at' => $this->timemodified ? date(DATE_ATOM, $this->timemodified) : null,
        ];
    }
}
