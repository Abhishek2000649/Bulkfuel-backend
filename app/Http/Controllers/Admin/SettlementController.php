<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    /* =====================================
       1️⃣ GET ALL DELIVERY AGENTS
       ===================================== */
    public function deliveryAgents()
    {
        $agents = User::where('role', 'DELIVERY_AGENT')
            ->select('id', 'name')
            ->get();

        return response()->json($agents, 200);
    }

    /* =====================================
       2️⃣ GET PENDING SETTLEMENT BY AGENT
       ===================================== */
    public function pendingSettlement($agentId)
    {
        $settlement = Settlement::where('delivery_agent_id', $agentId)
            ->where('status', 'PENDING')
            ->latest()
            ->first();

        if (!$settlement) {
            return response()->json([
                'status' => false,
                'message' => 'No pending settlement found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'settlement' => $settlement
        ], 200);
    }

    /* =====================================
       3️⃣ COMPLETE SETTLEMENT (ADMIN)
       ===================================== */
public function completeSettlement(Request $request)
{
    $request->validate([
        'settlement_id'   => 'required|exists:settlements,id',
        'settlement_mode' => 'required|in:CASH,BANK,UPI',
    ]);

    $settlement = Settlement::with('orders')->findOrFail($request->settlement_id);

    if ($settlement->status === 'SETTLED') {
        return response()->json([
            'status' => false,
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
        'status' => true,
        'message' => 'Settlement settled successfully'
    ], 200);
}

}
