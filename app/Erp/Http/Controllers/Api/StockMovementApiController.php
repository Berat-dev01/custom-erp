<?php

namespace App\Erp\Http\Controllers\Api;

use App\Erp\Services\Inventory\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class StockMovementApiController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('erp.stock_movements.create');

        $data = $request->validate([
            'product_id'   => ['required', 'exists:erp_products,id'],
            'warehouse_id' => ['required', 'exists:erp_warehouses,id'],
            'type'         => ['required', 'in:in,out,adjustment'],
            'quantity'     => ['required', 'numeric', 'min:0.001'],
            'unit_cost'    => ['nullable', 'numeric', 'min:0'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $this->stockService->recordMovement([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message'     => 'Stock movement recorded.',
            'movement_id' => $movement->id,
        ], 201);
    }
}
