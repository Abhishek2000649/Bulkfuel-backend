<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseProduct;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
class StockController extends Controller
{
    public function index()
    {
        try {

            $data = WarehouseProduct::with(['warehouse', 'product'])->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No warehouse products found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Warehouse products fetched successfully',
                'data' => $data
            ], 200);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Resource not found'
            ], 404);
        } catch (QueryException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Database query error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (HttpException $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode());
        } catch (Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function create()
    {
        try {

            // Optional authorization check
            // $this->authorize('create', Stock::class);

            $warehouses = Warehouse::select('id', 'name')->get();
            $products   = Product::select('id', 'name')->get();

            if ($warehouses->isEmpty() || $products->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Required data not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data fetched successfully',
                'data' => [
                    'warehouses' => $warehouses,
                    'products'   => $products
                ]
            ], 200);
        } catch (AuthenticationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated. Please login.'
            ], 401);
        } catch (AuthorizationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Forbidden. You do not have permission.'
            ], 403);
        } catch (QueryException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (NotFoundHttpException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Route not found.'
            ], 404);
        } catch (Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

   public function store(Request $request)
{
    try {

        // Manual validation to return JSON errors
        $validator = Validator::make($request->all(), [
            'warehouse_id'   => 'required|exists:warehouses,id',
            'product_id'     => 'required|exists:products,id',
            'stock_quantity' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $productStock = Product::where('id', $request->product_id)->value('stock');

        if ($productStock < $request->stock_quantity) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient stock available'
            ], 422);
        }

        $exists = WarehouseProduct::where('warehouse_id', $request->warehouse_id)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => false,
                'message' => 'Stock already exists for this warehouse and product'
            ], 422);
        }

        WarehouseProduct::create([
            'warehouse_id'   => $request->warehouse_id,
            'product_id'     => $request->product_id,
            'stock_quantity' => $request->stock_quantity,
        ]);

        Product::where('id', $request->product_id)
            ->decrement('stock', $request->stock_quantity);

        return response()->json([
            'status'  => true,
            'message' => 'Stock added successfully'
        ], 201);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error',
            'error'   => $e->getMessage()
        ], 500);

    } catch (Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong',
            'error'   => $e->getMessage()
        ], 500);
    }
}
    public function edit($id)
{
    try {

        $warehouses = Warehouse::all();
        $products = Product::all();
        $wareHouseProduct = WarehouseProduct::findOrFail($id);

        return response()->json([
            'status'  => true,
            'message' => 'Data fetched successfully',
            'data'    => $wareHouseProduct
        ], 200);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Record not found'
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error',
            'error'   => $e->getMessage()
        ], 500);

    } catch (Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong',
            'error'   => $e->getMessage()
        ], 500);
    }
}
    public function update(Request $request, $id)
{
    try {

        // Manual validation for JSON response
        $validator = Validator::make($request->all(), [
            'warehouse_id'   => 'required|exists:warehouses,id',
            'product_id'     => 'required|exists:products,id',
            'stock_quantity' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $warehouseProduct = WarehouseProduct::findOrFail($id);

        $oldStock = $warehouseProduct->stock_quantity;
        $productStock = Product::where('id', $request->product_id)->value('stock');

        $difference = $request->stock_quantity - $oldStock;

        if ($difference > 0 && $productStock < $difference) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient stock available'
            ], 422);
        }

        // Update product stock
        Product::where('id', $request->product_id)->update([
            'stock' => $productStock - $difference
        ]);

        $warehouseProduct->update([
            'warehouse_id'   => $request->warehouse_id,
            'product_id'     => $request->product_id,
            'stock_quantity' => $request->stock_quantity
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Stock updated successfully'
        ], 200);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Record not found'
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error',
            'error'   => $e->getMessage()
        ], 500);

    } catch (Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong',
            'error'   => $e->getMessage()
        ], 500);
    }
}

   public function delete($id)
{
    try {

        $warehouseProduct = WarehouseProduct::findOrFail($id);
$oldStock = $warehouseProduct->stock_quantity;

        if (!$warehouseProduct) {
            return response()->json([
                'status'  => false,
                'message' => 'Record not found'
            ], 404);
        }

        $oldProductStock = Product::where('id', $warehouseProduct->product_id)
            ->value('stock');

        Product::where('id', $warehouseProduct->product_id)->update([
            'stock' => ($oldProductStock + $oldStock),
        ]);

        $warehouseProduct->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Record deleted successfully'
        ], 200);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error',
            'error'   => $e->getMessage()
        ], 500);

    } catch (Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong',
            'error'   => $e->getMessage()
        ], 500);
    }
}
}
