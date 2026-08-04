<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProjectService
{
    /**
     * Get all available projects with their mapped course IDs and course counts.
     */
    public function getProjects(): array
    {
        // 1. Fetch Moodle course categories as base projects
        $categories = DB::table('course_categories')
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        $projects = [];

        foreach ($categories as $cat) {
            $courseIds = DB::table('course')
                ->where('category', $cat->id)
                ->where('id', '!=', 1)
                ->pluck('id')
                ->toArray();

            $projects[$cat->id] = [
                'id' => (string) $cat->id,
                'name' => $cat->name,
                'description' => $cat->description ?: 'Project category containing ' . count($courseIds) . ' courses.',
                'course_ids' => $courseIds,
                'course_count' => count($courseIds),
            ];
        }

        // 2. Ensure prominent custom projects like R2R exist if mapped
        if (!isset($projects['r2r'])) {
            // Find R2R category or top courses
            $r2rCourses = DB::table('course')
                ->where('fullname', 'like', '%R2R%')
                ->orWhere('shortname', 'like', '%R2R%')
                ->pluck('id')
                ->toArray();

            if (!empty($r2rCourses)) {
                $projects['r2r'] = [
                    'id' => 'r2r',
                    'name' => 'R2R Project',
                    'description' => 'Ready to Read (R2R) dedicated project with mapped courses.',
                    'course_ids' => $r2rCourses,
                    'course_count' => count($r2rCourses),
                ];
            }
        }

        return $projects;
    }

    /**
     * Get a specific project by ID.
     */
    public function getProjectById(string $id): ?array
    {
        $all = $this->getProjects();
        return $all[$id] ?? null;
    }

    /**
     * Resolve single or multiple project IDs to an array of mapped course IDs.
     *
     * @param string|array $projectId
     * @return array
     */
    public function getCourseIdsForProject($projectId): array
    {
        if (empty($projectId)) {
            return [];
        }

        if (is_array($projectId)) {
            $courseIds = [];
            foreach ($projectId as $pid) {
                $project = $this->getProjectById((string) $pid);
                if ($project && !empty($project['course_ids'])) {
                    $courseIds = array_merge($courseIds, $project['course_ids']);
                }
            }
            return array_values(array_unique($courseIds));
        }

        $project = $this->getProjectById((string) $projectId);
        return $project ? $project['course_ids'] : [];
    }
}
