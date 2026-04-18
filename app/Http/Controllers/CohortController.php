<?php

namespace App\Http\Controllers;

use App\Http\Resources\CohortResource;
use App\Http\Resources\UserResource;
use App\Models\Cohort;
use App\Models\User;
use Illuminate\Http\Request;

class CohortController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 25);
        $search = $request->query('search');
        $visibleOnly = $request->boolean('visible_only', true);

        $query = Cohort::query();

        if ($visibleOnly) {
            $query->where('visible', 1);
        }

        if ($search) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)->orWhere('idnumber', 'like', $like);
            });
        }

        return CohortResource::collection($query->orderBy('name')->paginate($perPage));
    }

    public function show(int $id)
    {
        return new CohortResource(Cohort::findOrFail($id));
    }

    public function members(Request $request, int $id)
    {
        Cohort::findOrFail($id);
        $perPage = (int) $request->query('per_page', 25);

        $members = User::where('deleted', 0)
            ->whereIn('id', function ($q) use ($id) {
                $q->select('userid')->from('cohort_members')->where('cohortid', $id);
            })
            ->paginate($perPage);

        return UserResource::collection($members);
    }
}
