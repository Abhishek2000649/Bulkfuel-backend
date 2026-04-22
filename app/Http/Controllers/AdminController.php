<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Exception;
use Illuminate\Support\Str;

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
                $product->image_url = $product->image;
                return $product;
            });

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
                'image'         => 'required|image|mimes:jpg,jpeg,png,webp|max:5120'
            ]);

            if ($request->hasFile('image')) {

                $file = $request->file('image');

                if (!$file->isValid()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid file'
                    ], 400);
                }

                try {
                    $category = Category::find($validated['category_id']);

                    if (!$category) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Category not found'
                        ], 404);
                    }

                    $categoryFolder = preg_replace('/\s+/', '_', trim($category->name)) . '_images';
                    $folderPath = "products_images/" . $categoryFolder;

                    $upload = (new UploadApi())->upload(
                        $file->getRealPath(),
                        [
                            'folder' => $folderPath,
                            'public_id' => uniqid(),
                            'transformation' => [
                                'width' => 256,
                                'height' => 160,
                                'crop' => 'fill'
                            ]
                        ]
                    );

                    $validated['image'] = $upload['secure_url'];
                    $validated['public_id'] = $upload['public_id'];
                } catch (\Exception $e) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Cloudinary upload failed',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $product = Product::create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Product created successfully',
                'data'    => $product
            ], 201, [], JSON_UNESCAPED_SLASHES);
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

            $product->image_url = $product->image;


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
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Database error occurred while fetching product.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
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

        // ✅ Validation
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'price'         => 'required|numeric|min:0',
            'stock'         => 'required|integer|min:0',
            'description'   => 'required|string',
            'category_id'   => 'required|exists:categories,id',
            'payment_type'  => 'required|in:cash,online,both',
            'image'         => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:5120'
        ]);

        // ✅ Find Product
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        // ✅ New Category Folder
        $category = Category::find($validated['category_id']);
        $categoryFolder = preg_replace('/\s+/', '_', trim($category->name)) . '_images';
        $newFolderPath = "products_images/" . $categoryFolder;

        // =====================================================
        // 🖼️ CASE 1: New Image Upload
        // =====================================================
        if ($request->hasFile('image')) {

            // ❌ Delete old image (SAFE METHOD)
            if ($product->image) {
                $this->deleteFromCloudinary($product->image);
            }

            // ✅ Upload new image
            $upload = (new UploadApi())->upload(
                $request->file('image')->getRealPath(),
                [
                    'folder' => $newFolderPath,
                    'transformation' => [
                        'width' => 256,
                        'height' => 160,
                        'crop' => 'fill'
                    ]
                ]
            );

            $validated['image'] = $upload['secure_url'];
            $validated['public_id'] = $upload['public_id'];
        }

        // =====================================================
        // 🔄 CASE 2: Only Category Changed
        // =====================================================
        else if ($product->category_id != $validated['category_id']) {

            if ($product->image) {

                // ✅ Step 1: Upload FIRST
                $upload = (new UploadApi())->upload(
                    $product->image,
                    [
                        'folder' => $newFolderPath,
                        'transformation' => [
                            'width' => 256,
                            'height' => 160,
                            'crop' => 'fill'
                        ]
                    ]
                );

                // ❌ Step 2: Delete old (SAFE METHOD)
                $this->deleteFromCloudinary($product->image);

                // ✅ Step 3: Update DB
                $validated['image'] = $upload['secure_url'];
                $validated['public_id'] = $upload['public_id'];
            }
        }

        // =====================================================
        // 💾 Update Product
        // =====================================================
        $product->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully.',
            'data' => $product
        ], 200, [], JSON_UNESCAPED_SLASHES);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'status' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);

    } catch (\Throwable $e) {

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}

private function deleteFromCloudinary($url)
{
    try {

        $path = parse_url($url, PHP_URL_PATH);

        $path = preg_replace('/^\/.*\/upload\//', '', $path);
        $path = preg_replace('/^v\d+\//', '', $path);

        $publicId = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);

        (new UploadApi())->destroy($publicId, [
            'resource_type' => 'image'
        ]);

    } catch (\Exception $e) {
        // optional: log error
    }
}

    // Delete product
    public function delete($id)
    {
        try {

            // 🔍 Find product
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Product not found.',
                    'data' => null
                ], 404);
            }

            // =====================================================
            // 🖼️ DELETE IMAGE FROM CLOUDINARY (SAFE METHOD)
            // =====================================================
            if ($product->image) {

                // 🔥 Extract public_id from URL
                $path = parse_url($product->image, PHP_URL_PATH);

                // remove "/image/upload/"
                $path = preg_replace('/^\/.*\/upload\//', '', $path);

                // remove version (v12345/)
                $path = preg_replace('/^v\d+\//', '', $path);

                // remove extension
                $publicId = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);

                // ✅ Delete from Cloudinary
                $delete = (new UploadApi())->destroy(
                    $publicId,
                    ['resource_type' => 'image']
                );

                // 🔍 Debug (optional)
                /*
            dd([
                'extracted_public_id' => $publicId,
                'cloudinary_response' => $delete
            ]);
            */
            }

            // =====================================================
            // 🗑️ DELETE PRODUCT FROM DATABASE
            // =====================================================
            $product->delete();

            return response()->json([
                'status' => true,
                'message' => 'Product and image deleted successfully.',
                'data' => null
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => 'Unable to delete product.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
