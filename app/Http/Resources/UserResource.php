<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->firstname,
            'last_name' => $this->lastname,
            'full_name' => trim("{$this->firstname} {$this->lastname}"),
            'id_number' => $this->idnumber,
            'phone1' => $this->phone1,
            'phone2' => $this->phone2,
            'institution' => $this->institution,
            'department' => $this->department,
            'city' => $this->city,
            'country' => $this->country,
            'lang' => $this->lang,
            'timezone' => $this->timezone,
            'suspended' => (bool) $this->suspended,
            'deleted' => (bool) $this->deleted,
            'confirmed' => (bool) $this->confirmed,
            'first_access_at' => $this->firstaccess ? date(DATE_ATOM, $this->firstaccess) : null,
            'last_access_at' => $this->lastaccess ? date(DATE_ATOM, $this->lastaccess) : null,
            'last_login_at' => $this->lastlogin ? date(DATE_ATOM, $this->lastlogin) : null,
            'created_at' => $this->timecreated ? date(DATE_ATOM, $this->timecreated) : null,
            'updated_at' => $this->timemodified ? date(DATE_ATOM, $this->timemodified) : null,
        ];
    }
}
