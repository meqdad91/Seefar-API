<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilterService
{
    /**
     * Extract normalized filter parameters from the HTTP request.
     */
    public function getFilters(Request $request): array
    {
        return [
            'course_id' => $this->normalizeArray($request->query('course_id'), true),
            'project_id' => $this->normalizeArray($request->query('project_id')),
            'sex' => $this->normalizeArray($request->query('sex')),
            'country' => $this->normalizeArray($request->query('country')),
            'age_group' => $this->normalizeArray($request->query('age_group')),
            'origin' => $this->normalizeArray($request->query('origin')),
            'completion_status' => $this->normalizeArray($request->query('completion_status')),
            'start_date' => $request->query('start_date') ?: null,
            'end_date' => $request->query('end_date') ?: null,
        ];
    }

    /**
     * Normalize query inputs into unique non-empty arrays.
     */
    private function normalizeArray($val, bool $asInt = false): array
    {
        if (empty($val)) {
            return [];
        }

        if (is_array($val)) {
            $items = array_filter(array_map('trim', $val), fn ($item) => $item !== '');
        } else {
            $items = array_filter(array_map('trim', explode(',', (string) $val)), fn ($item) => $item !== '');
        }

        if ($asInt) {
            $items = array_map('intval', $items);
        }

        return array_values(array_unique($items));
    }

    /**
     * Apply filter criteria to a base query for user IDs or user records.
     */
    public function applyUserFilters($query, array $filters, string $userTableAlias = 'u')
    {
        // Filter by Country/Region (supports multiple countries)
        if (!empty($filters['country'])) {
            $query->whereIn("{$userTableAlias}.country", (array) $filters['country']);
        }

        // Filter by Sex / Gender (supports multiple sexes)
        if (!empty($filters['sex'])) {
            $sexes = (array) $filters['sex'];
            $query->whereIn("{$userTableAlias}.id", function ($sub) use ($sexes) {
                $sub->select('d.userid')
                    ->from('user_info_data as d')
                    ->join('user_info_field as f', 'f.id', '=', 'd.fieldid')
                    ->whereIn('f.shortname', ['gender', 'sex'])
                    ->where(function ($q) use ($sexes) {
                        foreach ($sexes as $idx => $s) {
                            if ($idx === 0) {
                                $q->where('d.data', 'like', '%' . $s . '%');
                            } else {
                                $q->orWhere('d.data', 'like', '%' . $s . '%');
                            }
                        }
                    });
            });
        }

        // Filter by Age Group (supports multiple age groups)
        if (!empty($filters['age_group'])) {
            $ageGroups = (array) $filters['age_group'];
            $query->whereIn("{$userTableAlias}.id", function ($sub) use ($ageGroups) {
                $sub->select('d.userid')
                    ->from('user_info_data as d')
                    ->join('user_info_field as f', 'f.id', '=', 'd.fieldid')
                    ->whereIn('f.shortname', ['age', 'agegroup', 'dob', 'dateofbirth'])
                    ->where(function ($q) use ($ageGroups) {
                        foreach ($ageGroups as $idx => $ag) {
                            if ($idx === 0) {
                                $q->where('d.data', 'like', '%' . $ag . '%');
                            } else {
                                $q->orWhere('d.data', 'like', '%' . $ag . '%');
                            }
                        }
                    });
            });
        }

        return $query;
    }

    /**
     * Apply timeline / date range filters to a timestamp column (e.g. timecreated, timestart, timecompleted).
     */
    public function applyDateFilter($query, array $filters, string $column)
    {
        if (!empty($filters['start_date'])) {
            $startTimestamp = strtotime($filters['start_date'] . ' 00:00:00');
            if ($startTimestamp !== false) {
                $query->where($column, '>=', $startTimestamp);
            }
        }

        if (!empty($filters['end_date'])) {
            $endTimestamp = strtotime($filters['end_date'] . ' 23:59:59');
            if ($endTimestamp !== false) {
                $query->where($column, '<=', $endTimestamp);
            }
        }

        return $query;
    }
}
