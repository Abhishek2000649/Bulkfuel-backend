<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;
class SettlementController extends Controller
{
    
  

public function deliveryAgents()
{
    try {

        $agents = User::where('role', 'DELIVERY_AGENT')
            ->select('id', 'name')
            ->get();

        if ($agents->isEmpty()) {
            return response()->json([
                'status'  => true,
                'message' => 'No delivery agents found.',
                'data'    => []
            ], 200);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Delivery agents fetched successfully.',
            'data'    => $agents
        ], 200);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error occurred.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);

    } catch (HttpException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'HTTP error occurred.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], $e->getStatusCode());

    } catch (Throwable $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

  public function pendingSettlement($agentId)
{
    try {

        $settlement = Settlement::where('delivery_agent_id', $agentId)
            ->where('status', 'PENDING')
            ->latest()
            ->first();

        if (!$settlement) {
            return response()->json([
                'status'  => false,
                'message' => 'No pending settlement found.',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'status'     => true,
            'message'    => 'Pending settlement fetched successfully.',
            'data'       => $settlement
        ], 200);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error occurred.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);

    } catch (HttpException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'HTTP error occurred.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], $e->getStatusCode());

    } catch (Throwable $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong.',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    
public function completeSettlement(Request $request)
{
    try {

        $request->validate([
            'settlement_id'   => 'required|exists:settlements,id',
            'settlement_mode' => 'required|in:CASH,BANK,UPI',
        ]);

        $settlement = Settlement::with('orders')
            ->findOrFail($request->settlement_id);

        if ($settlement->status === 'SETTLED') {
            return response()->json([
                'status'  => false,
                'message' => 'Settlement already settled'
            ], 400);
        }

        // 1️⃣ Update settlement
        $settlement->update([
            'status'          => 'SETTLED',
            'settlement_mode' => $request->settlement_mode,
            'settlement_date' => now(),
        ]);

        // 2️⃣ Update all related orders
        $settlement->orders()->update([
            'settlement_status' => 'SETTLED'
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Settlement settled successfully'
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Validation error',
            'errors'  => $e->errors()
        ], 422);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Settlement not found',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 404);

    } catch (QueryException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Database error occurred',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);

    } catch (HttpException $e) {

        return response()->json([
            'status'  => false,
            'message' => 'HTTP error occurred',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], $e->getStatusCode());

    } catch (Throwable $e) {

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong',
            'error'   => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

}
