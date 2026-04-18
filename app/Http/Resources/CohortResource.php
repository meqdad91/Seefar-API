<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CohortResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'id_number' => $this->idnumber,
            'description' => $this->description ? strip_tags($this->description) : null,
            'visible' => (bool) $this->visible,
            'context_id' => $this->contextid,
            'created_at' => $this->timecreated ? date(DATE_ATOM, $this->timecreated) : null,
            'updated_at' => $this->timemodified ? date(DATE_ATOM, $this->timemodified) : null,
        ];
    }
}
