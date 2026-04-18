<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuizAttemptResource;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $search = $request->query('search');
        $courseId = $request->query('course_id');

        $query = Quiz::query();

        if ($courseId) {
            $query->where('course', $courseId);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return QuizResource::collection($query->orderBy('id')->paginate($perPage));
    }

    public function show(int $id)
    {
        return new QuizResource(Quiz::findOrFail($id));
    }

    public function attempts(Request $request, int $id)
    {
        Quiz::findOrFail($id);
        $perPage = (int) $request->query('per_page', 25);
        $state = $request->query('state');
        $userId = $request->query('user_id');

        $query = QuizAttempt::where('quiz', $id);

        if ($state) {
            $query->where('state', $state);
        }

        if ($userId) {
            $query->where('userid', $userId);
        }

        return QuizAttemptResource::collection(
            $query->orderByDesc('timestart')->paginate($perPage)
        );
    }
}
