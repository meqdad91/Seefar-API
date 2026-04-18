<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullname,
            'short_name' => $this->shortname,
            'id_number' => $this->idnumber,
            'category_id' => $this->category,
            'category' => new CourseCategoryResource($this->whenLoaded('categoryInfo')),
            'summary' => strip_tags((string) $this->summary),
            'format' => $this->format,
            'visible' => (bool) $this->visible,
            'lang' => $this->lang,
            'start_date' => $this->startdate ? date(DATE_ATOM, $this->startdate) : null,
            'end_date' => $this->enddate ? date(DATE_ATOM, $this->enddate) : null,
            'created_at' => $this->timecreated ? date(DATE_ATOM, $this->timecreated) : null,
            'updated_at' => $this->timemodified ? date(DATE_ATOM, $this->timemodified) : null,
        ];
    }
}
