<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\GradeGrade;
use App\Models\User;
use Illuminate\Http\Request;

class UsersWebController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = User::where('deleted', 0);

        if ($search) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('username', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like);
            });
        }

        $users = $query->orderBy('id')->paginate(25)->withQueryString();
        return view('users.index', compact('users', 'search'));
    }

    public function show(int $id)
    {
        $user = User::where('deleted', 0)->findOrFail($id);

        $courses = Course::query()
            ->whereIn('id', function ($q) use ($id) {
                $q->select('e.courseid')
                    ->from('enrol as e')
                    ->join('user_enrolments as ue', 'ue.enrolid', '=', 'e.id')
                    ->where('ue.userid', $id);
            })
            ->limit(20)->get();

        $grades = GradeGrade::with('item')
            ->where('userid', $id)
            ->orderByDesc('timemodified')
            ->limit(10)
            ->get();

        return view('users.show', compact('user', 'courses', 'grades'));
    }
}
