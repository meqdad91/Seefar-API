<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\GradeResource;
use App\Http\Resources\UserResource;
use App\Models\Course;
use App\Models\GradeGrade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $visibleOnly = $request->boolean('visible_only', true);

        $query = Course::query()->where('id', '!=', 1); // 1 = site

        if ($visibleOnly) {
            $query->where('visible', 1);
        }

        if ($categoryId) {
            $query->where('categoryInfo', $categoryId);
        }

        if ($search) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('fullname', 'like', $like)
                    ->orWhere('shortname', 'like', $like)
                    ->orWhere('idnumber', 'like', $like);
            });
        }

        return CourseResource::collection(
            $query->with('categoryInfo')->orderBy('sortorder')->paginate($perPage)
        );
    }

    public function show(int $id)
    {
        $course = Course::with('categoryInfo')->findOrFail($id);
        return new CourseResource($course);
    }

    public function students(Request $request, int $id)
    {
        Course::findOrFail($id);
        $perPage = (int) $request->query('per_page', 25);

        $students = User::query()
            ->where('deleted', 0)
            ->whereIn('id', function ($q) use ($id) {
                $q->select('ue.userid')
                    ->from('user_enrolments as ue')
                    ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                    ->where('e.courseid', $id);
            })
            ->paginate($perPage);

        return UserResource::collection($students);
    }

    public function activities(Request $request, int $id)
    {
        Course::findOrFail($id);
        $perPage = (int) $request->query('per_page', 25);

        $page = DB::table('course_modules as cm')
            ->join('modules as m', 'm.id', '=', 'cm.module')
            ->where('cm.course', $id)
            ->where('cm.deletioninprogress', 0)
            ->select('cm.id', 'cm.section', 'cm.visible', 'cm.instance', 'm.name as type')
            ->orderBy('cm.section')
            ->orderBy('cm.id')
            ->paginate($perPage);

        return ActivityResource::collection($page);
    }

    public function grades(Request $request, int $id)
    {
        Course::findOrFail($id);
        $perPage = (int) $request->query('per_page', 50);

        $grades = GradeGrade::with('item')
            ->whereHas('item', fn ($q) => $q->where('courseid', $id))
            ->paginate($perPage);

        return GradeResource::collection($grades);
    }
}
