<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'id_number' => $this->idnumber,
            'parent_id' => $this->parent,
            'depth' => $this->depth,
            'path' => $this->path,
            'course_count' => $this->coursecount,
            'visible' => (bool) $this->visible,
            'children' => CourseCategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
