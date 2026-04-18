<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Http\Resources\GradeResource;
use App\Http\Resources\QuizAttemptResource;
use App\Http\Resources\UserResource;
use App\Models\Course;
use App\Models\GradeGrade;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $search = $request->query('search');
        $sort = $request->query('sort', 'id');
        $direction = $request->query('direction', 'asc');

        $allowedSorts = ['id', 'username', 'email', 'firstname', 'lastname', 'lastaccess', 'timecreated'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $query = User::query()->where('deleted', 0);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('username', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like);
            });
        }

        return UserResource::collection(
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc')->paginate($perPage)
        );
    }

    public function show(int $id)
    {
        $user = User::where('deleted', 0)->findOrFail($id);
        return new UserResource($user);
    }

    public function courses(Request $request, int $id)
    {
        User::where('deleted', 0)->findOrFail($id);
        $perPage = (int) $request->query('per_page', 15);

        $courses = Course::query()
            ->whereIn('id', function ($q) use ($id) {
                $q->select('e.courseid')
                    ->from('enrol as e')
                    ->join('user_enrolments as ue', 'ue.enrolid', '=', 'e.id')
                    ->where('ue.userid', $id);
            })
            ->paginate($perPage);

        return CourseResource::collection($courses);
    }

    public function grades(Request $request, int $id)
    {
        User::where('deleted', 0)->findOrFail($id);
        $perPage = (int) $request->query('per_page', 50);
        $courseId = $request->query('course_id');

        $query = GradeGrade::with('item')->where('userid', $id);

        if ($courseId) {
            $query->whereHas('item', fn ($q) => $q->where('courseid', $courseId));
        }

        return GradeResource::collection($query->paginate($perPage));
    }

    public function quizAttempts(Request $request, int $id)
    {
        User::where('deleted', 0)->findOrFail($id);
        $perPage = (int) $request->query('per_page', 25);
        $state = $request->query('state');

        $query = QuizAttempt::where('userid', $id);

        if ($state) {
            $query->where('state', $state);
        }

        return QuizAttemptResource::collection(
            $query->orderByDesc('timestart')->paginate($perPage)
        );
    }
}
