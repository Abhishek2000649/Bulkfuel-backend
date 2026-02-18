<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;

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
        try {

            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'Unauthorized. Please login first.',
                    'data' => null
                ], 401);
            }

            $products = Product::with('category')->get();

            if ($products->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'status_code' => 404,
                    'message' => 'No products found.',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Products fetched successfully.',
                'data' => $products
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Database error occurred while fetching products.',
                'data' => null
            ], 500);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Unable to load products.',
                'data' => null
            ], 500);
        }
    }

    // Store new product
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric',
            'stock'       => 'required|numeric|min:0',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id'
        ]);

        Product::create([
            'name'        => $request->name,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'description' => $request->description,
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'status' => true
        ]);
    }

    // Edit product page
    public function edit($id)
    {
        try {

            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'Unauthorized. Please login first.',
                    'data' => null
                ], 401);
            }

            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Invalid product ID.',
                    'data' => null
                ], 400);
            }

            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'status_code' => 404,
                    'message' => 'Product not found.',
                    'data' => null
                ], 404);
            }

            $categories = Category::all();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Product details fetched successfully.',
                'data' => [
                    'product' => $product,
                    'categories' => $categories
                ]
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Database error occurred while fetching product.',
                'data' => null
            ], 500);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Unable to load product details.',
                'data' => null
            ], 500);
        }
    }

    // Update product
    public function update(Request $request, $id)
    {
        try {

            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'Unauthorized. Please login first.',
                    'data' => null
                ], 401);
            }

            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Invalid product ID.',
                    'data' => null
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'name'        => 'required|string|max:255',
                'price'       => 'required|numeric',
                'stock'       => 'required|numeric|min:0',
                'description' => 'required|string',
                'category_id' => 'required|exists:categories,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'Validation error.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'status_code' => 404,
                    'message' => 'Product not found.',
                    'data' => null
                ], 404);
            }

            $product->update([
                'name'        => $request->name,
                'price'       => $request->price,
                'stock'       => $request->stock,
                'description' => $request->description,
                'category_id' => $request->category_id,
            ]);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Product updated successfully.',
                'data' => $product
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Database error occurred while updating product.',
                'data' => null
            ], 500);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Unable to update product.',
                'data' => null
            ], 500);
        }
    }

    // Delete product
    public function delete($id)
    {
        try {

            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'Unauthorized. Please login first.',
                    'data' => null
                ], 401);
            }

            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Invalid product ID.',
                    'data' => null
                ], 400);
            }

            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'status_code' => 404,
                    'message' => 'Product not found.',
                    'data' => null
                ], 404);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Product deleted successfully.',
                'data' => null
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Product cannot be deleted because it is linked to other records.',
                'data' => null
            ], 500);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Unable to delete product.',
                'data' => null
            ], 500);
        }
    }
}
