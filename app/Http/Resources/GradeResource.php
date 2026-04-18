<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->itemid,
            'user_id' => $this->userid,
            'item_name' => $this->item->itemname ?? null,
            'item_type' => $this->item->itemtype ?? null,
            'course_id' => $this->item->courseid ?? null,
            'raw_grade' => $this->rawgrade !== null ? (float) $this->rawgrade : null,
            'final_grade' => $this->finalgrade !== null ? (float) $this->finalgrade : null,
            'grade_max' => $this->item->grademax ?? null ? (float) $this->item->grademax : null,
            'grade_min' => $this->item->grademin ?? null ? (float) $this->item->grademin : null,
            'grade_pass' => isset($this->item) && $this->item->gradepass !== null ? (float) $this->item->gradepass : null,
            'feedback' => $this->feedback ? strip_tags($this->feedback) : null,
            'updated_at' => $this->timemodified ? date(DATE_ATOM, $this->timemodified) : null,
        ];
    }
}
