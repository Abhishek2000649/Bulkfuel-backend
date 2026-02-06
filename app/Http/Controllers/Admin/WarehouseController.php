<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return response()->json(Warehouse::all());
    }

    public function create()
    {
        return view('admin.warehouse.create');
    }
    public function edit( $id )
    {
        
        
        return response()->json(Warehouse::findOrFail($id));
    }
    public function update(Request $request, $id)
    {
        Warehouse::find($id)->update(
            [
                'name'=>$request->name,
                'address'=>$request->address,
                'city'=>$request->city,
                'state'=>$request->state,
                'pincode'=>$request->pincode,
            ]
            );
            return  response()->json([
            'status'=>true,
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address'=>'required',
            'city' => 'required',
            'state'=>'required',
            'pincode' => 'required',
        ]);

        Warehouse::create($request->all());
        return  response()->json([
            'status'=>true,
        ]);
    }

    public function delete($id)
    {
        Warehouse::findOrFail($id)->delete();
        return  response()->json([
            'status'=>true,
        ]);
    }
}
