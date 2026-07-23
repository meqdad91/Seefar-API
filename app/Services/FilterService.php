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
            'course_id' => $request->query('course_id') ? (int) $request->query('course_id') : null,
            'project_id' => $request->query('project_id') ?: null,
            'sex' => $request->query('sex') ?: null,
            'country' => $request->query('country') ?: null,
            'age_group' => $request->query('age_group') ?: null,
            'origin' => $request->query('origin') ?: null,
            'completion_status' => $request->query('completion_status') ?: null,
            'start_date' => $request->query('start_date') ?: null,
            'end_date' => $request->query('end_date') ?: null,
        ];
    }

    /**
     * Apply filter criteria to a base query for user IDs or user records.
     */
    public function applyUserFilters($query, array $filters, string $userTableAlias = 'u')
    {
        // Filter by Country/Region
        if (!empty($filters['country'])) {
            $query->where("{$userTableAlias}.country", $filters['country']);
        }

        // Filter by Sex / Gender (via Moodle custom profile field or user column if present)
        if (!empty($filters['sex'])) {
            $query->whereIn("{$userTableAlias}.id", function ($sub) use ($filters) {
                $sub->select('d.userid')
                    ->from('user_info_data as d')
                    ->join('user_info_field as f', 'f.id', '=', 'd.fieldid')
                    ->whereIn('f.shortname', ['gender', 'sex'])
                    ->where('d.data', 'like', '%' . $filters['sex'] . '%');
            });
        }

        // Filter by Age Group
        if (!empty($filters['age_group'])) {
            $query->whereIn("{$userTableAlias}.id", function ($sub) use ($filters) {
                $sub->select('d.userid')
                    ->from('user_info_data as d')
                    ->join('user_info_field as f', 'f.id', '=', 'd.fieldid')
                    ->whereIn('f.shortname', ['age', 'agegroup', 'dob', 'dateofbirth'])
                    ->where('d.data', 'like', '%' . $filters['age_group'] . '%');
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
