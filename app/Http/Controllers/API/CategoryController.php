<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    protected $colors = [
        '#F44336', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5', '#2196F3',
        '#03A9F4', '#00BCD4', '#009688', '#4CAF50', '#8BC34A', '#CDDC39',
        '#FFEB3B', '#FFC107', '#FF9800', '#FF5722', '#795548'
    ];

    public function __construct()
    {
        $this->middleware('login');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $categories = $user->categories->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'color' => $item->color,
            ];
        });

        return [
            'errcode'=>0,
            'categories' => $categories,
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:categories,name|max:10',
        ]);
        if ($validator->fails()) {
            return [
                'errcode' => 1,
                'errmsg' => $validator->errors()->first(),
            ];
        }

        $category = $user->categories()->create([
            'name' => $request->input('name'),
            'color' => array_random($this->colors),
        ]);

        return $category->only(['id', 'name', 'color']);
    }

    /**
     * @param Request $request
     * @param $categoryID
     * @return array
     */
    public function delete(Request $request, $categoryID)
    {
        Category::query()->where('id', $categoryID)
            ->delete();

        return [
            'errcode' => 0,
            'msg' => 'deleted.',
        ];
    }
}