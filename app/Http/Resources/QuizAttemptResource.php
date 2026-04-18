<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz,
            'user_id' => $this->userid,
            'attempt_number' => (int) $this->attempt,
            'state' => $this->state,
            'preview' => (bool) $this->preview,
            'sum_grades' => $this->sumgrades !== null ? (float) $this->sumgrades : null,
            'started_at' => $this->timestart ? date(DATE_ATOM, $this->timestart) : null,
            'finished_at' => $this->timefinish ? date(DATE_ATOM, $this->timefinish) : null,
            'updated_at' => $this->timemodified ? date(DATE_ATOM, $this->timemodified) : null,
        ];
    }
}
