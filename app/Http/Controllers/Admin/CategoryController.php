<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        
    return response()->json(Category::all());
    }
    public function create()
    {
        return view('admin.category.addCategory');
    }
    public function edit($id)
    {
         return response()->json(
        Category::findOrFail($id)
    );
    }
    public function update(Request $request, $id)
    {
        Category::find($id)->update([
            'name'=>$request->name,
        ]);
        return response()->json([
            'status'=>true,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories'
        ]);

        Category::create(['name' => $request->name]);

        return response()->json([
            'status'=>true,
        ]);
    }

    public function destroy($id)
    {
        Category::findOrFail($id)->delete();
        return response()->json([
            'status'=>true,
        ]);
    }
}

