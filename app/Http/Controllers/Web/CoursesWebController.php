<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoursesWebController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = Course::with('categoryInfo')->where('id', '!=', 1)->where('visible', 1);

        if ($search) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('fullname', 'like', $like)
                    ->orWhere('shortname', 'like', $like);
            });
        }

        $courses = $query->orderBy('sortorder')->paginate(25)->withQueryString();
        return view('courses.index', compact('courses', 'search'));
    }

    public function show(int $id)
    {
        $course = Course::with('categoryInfo')->findOrFail($id);

        $studentCount = DB::table('user_enrolments as ue')
            ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
            ->join('user as u', 'u.id', '=', 'ue.userid')
            ->where('e.courseid', $id)
            ->where('u.deleted', 0)
            ->distinct()
            ->count('u.id');

        $activityCount = DB::table('course_modules')->where('course', $id)->where('deletioninprogress', 0)->count();
        $quizCount = DB::table('quiz')->where('course', $id)->count();

        $avgGrade = DB::table('grade_items')
            ->join('grade_grades', 'grade_grades.itemid', '=', 'grade_items.id')
            ->where('grade_items.courseid', $id)
            ->where('grade_items.itemtype', 'course')
            ->whereNotNull('grade_grades.finalgrade')
            ->avg('grade_grades.finalgrade');

        $completionsStarted = DB::table('course_completions')->where('course', $id)->count();
        $completionsDone = DB::table('course_completions')->where('course', $id)->whereNotNull('timecompleted')->count();

        $students = User::where('deleted', 0)
            ->whereIn('id', function ($q) use ($id) {
                $q->select('ue.userid')
                    ->from('user_enrolments as ue')
                    ->join('enrol as e', 'e.id', '=', 'ue.enrolid')
                    ->where('e.courseid', $id);
            })
            ->limit(10)
            ->get();

        $activities = DB::table('course_modules as cm')
            ->join('modules as m', 'm.id', '=', 'cm.module')
            ->where('cm.course', $id)
            ->where('cm.deletioninprogress', 0)
            ->orderBy('cm.section')
            ->limit(15)
            ->get(['cm.id', 'cm.section', 'cm.visible', 'm.name as type']);

        return view('courses.show', compact(
            'course', 'studentCount', 'activityCount', 'quizCount', 'avgGrade',
            'completionsStarted', 'completionsDone', 'students', 'activities'
        ));
    }
}
