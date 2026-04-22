<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

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

            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'Unauthorized. Please login first.',
                    'data' => null
                ], 401);
            }

            $products = Product::with('category')->get()->map(function ($product) {

                $product->image_url = $product->image
                    ? asset($product->image)
                    : null;

                return $product;
            });
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
  


        try {

            $validated = $request->validate([
                'name'          => 'required|string|max:255',
                'price'         => 'required|numeric|min:0',
                'stock'         => 'required|integer|min:0',
                'description'   => 'required|string',
                'category_id'   => 'required|integer|exists:categories,id',
                'payment_type'  => 'required|in:cash,online,both',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120'
            ]);

            if ($request->file('image')) {

                $file = $request->file('image');

                $manager = new ImageManager(new Driver());

                $image = $manager->read($file->getPathname());

                $image->cover(256, 160);

                $fileName = time() . '.jpg';

                $path = public_path('images');

                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $image->save($path . '/' . $fileName);

                $validated['image'] = 'images/' . $fileName;
            }
            $product = Product::create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Product created successfully',
                'data'    => $product
            ], 201);
        } catch (ValidationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (QueryException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Database error occurred',
                'error'   => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    // Edit product page
    public function edit($id)
    {
        try {

            // 🔐 AUTH CHECK
            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'status' => false,
                    'status_code' => 401,
                    'message' => 'Unauthorized. Please login first.',
                    'data' => null
                ], 401);
            }

            // 🔢 VALIDATE ID
            if (!is_numeric($id)) {
                return response()->json([
                    'status' => false,
                    'status_code' => 400,
                    'message' => 'Invalid product ID.',
                    'data' => null
                ], 400);
            }

            // 🔍 FIND PRODUCT
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Product not found.',
                    'data' => null
                ], 404);
            }

            // 🖼️ ADD IMAGE URL
            $product->image_url = $product->image
                ? asset($product->image)
                : null;

            // 📦 GET CATEGORIES
            $categories = Category::all();

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Product details fetched successfully.',
                'data' => [
                    'product' => $product,
                    'categories' => $categories
                ]
            ], 200);
        }

        // ❌ DATABASE ERROR
        catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Database error occurred while fetching product.',
                'error' => $e->getMessage()
            ], 500);
        }

        // ❌ GENERAL ERROR
        catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Unable to load product details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            // 🔐 AUTH CHECK
            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'status' => false,
                    'status_code' => 401,
                    'message' => 'Unauthorized. Please login first.',
                    'data' => null
                ], 401);
            }

            // 🔢 VALIDATE ID
            if (!is_numeric($id)) {
                return response()->json([
                    'status' => false,
                    'status_code' => 400,
                    'message' => 'Invalid product ID.',
                    'data' => null
                ], 400);
            }

            // 📦 VALIDATION
            $validated = $request->validate([
                'name'          => 'required|string|max:255',
                'price'         => 'required|numeric|min:0',
                'stock'         => 'required|integer|min:0',
                'description'   => 'required|string',
                'category_id'   => 'required|exists:categories,id',
                'payment_type'  => 'required|in:cash,online,both',
                'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120'
            ]);

            // 🔍 FIND PRODUCT
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Product not found.',
                    'data' => null
                ], 404);
            }

            // 🖼️ IMAGE UPLOAD
            if ($request->hasFile('image')) {

                // ❌ DELETE OLD IMAGE (SAFE)
                if ($product->image && file_exists(public_path($product->image))) {
                    @unlink(public_path($product->image));
                }

                $file = $request->file('image');

                $manager = new ImageManager(new Driver());
                $image = $manager->read($file->getPathname());

                // ✂️ RESIZE / CROP
                $image->cover(256, 160);

                $fileName = time() . '.jpg';
                $path = public_path('images');

                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $image->save($path . '/' . $fileName);

                // ✅ ADD IMAGE TO VALIDATED DATA
                $validated['image'] = 'images/' . $fileName;
            }

            // 🔄 UPDATE PRODUCT
            $product->update($validated);

            // 📤 RESPONSE
            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Product updated successfully.',
                'data' => $product
            ], 200);
        }

        // ❌ VALIDATION ERROR
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        }

        // ❌ DATABASE ERROR
        catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Database error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }

        // ❌ GENERAL ERROR
        catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete product
    public function delete($id)
    {
        try {

            // 🔐 AUTH CHECK
            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'status' => false,
                    'status_code' => 401,
                    'message' => 'Unauthorized. Please login first.',
                    'data' => null
                ], 401);
            }

            // 🔢 VALIDATE ID
            if (!is_numeric($id)) {
                return response()->json([
                    'status' => false,
                    'status_code' => 400,
                    'message' => 'Invalid product ID.',
                    'data' => null
                ], 400);
            }

            // 🔍 FIND PRODUCT
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Product not found.',
                    'data' => null
                ], 404);
            }

            // 🖼️ DELETE IMAGE (IMPORTANT 🔥)
            if ($product->image && file_exists(public_path($product->image))) {
                @unlink(public_path($product->image)); // safe delete
            }

            // 🗑️ DELETE PRODUCT
            $product->delete();

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Product and image deleted successfully.',
                'data' => null
            ], 200);
        }

        // ❌ DATABASE ERROR
        catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Product cannot be deleted because it is linked to other records.',
                'error' => $e->getMessage()
            ], 500);
        }

        // ❌ GENERAL ERROR
        catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Unable to delete product.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
