<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseCategoryResource;
use App\Models\CourseCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 25);
        $tree = $request->boolean('tree', false);

        $query = CourseCategory::where('visible', 1)->orderBy('sortorder');

        if ($tree) {
            $query->where('parent', 0)->with('children.children.children');
        }

        return CourseCategoryResource::collection($query->paginate($perPage));
    }

    public function show(int $id)
    {
        $category = CourseCategory::with('children')->findOrFail($id);
        return new CourseCategoryResource($category);
    }
}
