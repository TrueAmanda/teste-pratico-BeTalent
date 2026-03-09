<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GatewayController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->canManageGateways()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $gateways = Gateway::orderBy('priority')->get();

        return response()->json($gateways);
    }

    public function show(Gateway $gateway, Request $request)
    {
        if (!$request->user()->canManageGateways()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($gateway);
    }

    public function updateStatus(Gateway $gateway, Request $request)
    {
        if (!$request->user()->canManageGateways()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $gateway->update(['is_active' => $request->is_active]);

        return response()->json([
            'message' => 'Gateway status updated successfully',
            'gateway' => $gateway
        ]);
    }

    public function updatePriority(Gateway $gateway, Request $request)
    {
        if (!$request->user()->canManageGateways()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'priority' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $gateway->update(['priority' => $request->priority]);

        return response()->json([
            'message' => 'Gateway priority updated successfully',
            'gateway' => $gateway
        ]);
    }
}
