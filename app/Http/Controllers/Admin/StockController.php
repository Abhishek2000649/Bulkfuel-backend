<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseProduct;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
          return response()->json(
        WarehouseProduct::with(['warehouse', 'product'])->get()
    );
    }

    public function create()
    {
        return view('admin.stock.addStock', [
            'warehouses' => Warehouse::all(),
            'products' => Product::all()
        ]);
    }

  public function store(Request $request)
{
    $request->validate([
        'warehouse_id'   => 'required|exists:warehouses,id',
        'product_id'     => 'required|exists:products,id',
        'stock_quantity' => 'required|numeric|min:1',
    ]);

    $productStock = Product::where('id', $request->product_id)->value('stock');

    if ($productStock < $request->stock_quantity) {
        return response()->json([
            'message' => 'Insufficient stock available'
        ], 422);
    }

    $exists = WarehouseProduct::where('warehouse_id', $request->warehouse_id)
        ->where('product_id', $request->product_id)
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'Stock already exists for this warehouse and product'
        ], 422);
    }

    WarehouseProduct::create([
        'warehouse_id'   => $request->warehouse_id,
        'product_id'     => $request->product_id,
        'stock_quantity' => $request->stock_quantity,
    ]);

    Product::where('id', $request->product_id)->decrement(
        'stock',
        $request->stock_quantity
    );

    return response()->json(['status' => true]);
}
    public function edit($id)
    {
         $warehouses = Warehouse::all();
            $products = Product::all();
        $wareHouseProduct = WarehouseProduct::findOrFail($id);
         return response()->json(
            WarehouseProduct::findOrFail($id)
        );
    }
   public function update(Request $request, $id)
{
    $request->validate([
        'warehouse_id'   => 'required|exists:warehouses,id',
        'product_id'     => 'required|exists:products,id',
        'stock_quantity' => 'required|numeric|min:1',
    ]);

    $warehouseProduct = WarehouseProduct::findOrFail($id);

    $oldStock = $warehouseProduct->stock_quantity;
    $productStock = Product::where('id', $request->product_id)->value('stock');

    $difference = $request->stock_quantity - $oldStock;

    if ($difference > 0 && $productStock < $difference) {
        return response()->json([
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

    return response()->json(['status' => true]);
}


    public function delete($id)
    {
        $oldStock = WarehouseProduct::where('id', $id)->value('stock_quantity');
        
        $warehouseProduct = WarehouseProduct::find($id);
        $oldProductStock = Product::where('id', $warehouseProduct->product_id)->value('stock');

        Product::where('id', $warehouseProduct->product_id)->update(
            [
                'stock' => ($oldProductStock+$oldStock),
            ],);
        $warehouseProduct->delete();
         return response()->json(['status' => true]);
    }





}
