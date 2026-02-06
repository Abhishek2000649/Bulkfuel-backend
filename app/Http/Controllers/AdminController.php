<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    // Add product page
    public function addProduct()
    {
        $categories = Category::all();
        return view('admin.product.addProduct', compact('categories'));
    }

    // Product list
    public function products()
    {
        return response()->json(
        Product::with('category')->get()
    );
    }

    // Store new product
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required',
            'price'       => 'required|numeric',
            'stock'       => 'required|numeric|min:0',
            'description' => 'required',
            'category_id' => 'required|exists:categories,id'
        ]);

        Product::create([
            'name'        => $request->name,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'description' => $request->description,
            'category_id' => $request->category_id,
        ]);

         return response()->json(['status' => true]);
    }

    // Edit product page
    public function edit($id)
    {
        $product    = Product::findOrFail($id);
        $categories = Category::all();

         return response()->json(
        Product::findOrFail($id)
    );
    }

    // Update product
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'        => 'required',
            'price'       => 'required|numeric',
            'stock'       => 'required|numeric|min:0',
            'description' => 'required',
            'category_id' => 'required|exists:categories,id'
        ]);

        Product::where('id', $id)->update([
            'name'        => $request->name,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'description' => $request->description,
            'category_id' => $request->category_id,
        ]);

         return response()->json(['status' => true]);
    }

    // Delete product
    public function delete($id)
    {
        Product::findOrFail($id)->delete();

    return response()->json(['status' => true]);
    }
}
