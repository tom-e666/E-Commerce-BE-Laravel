<?php

namespace App\Http\Controllers;

use App\Models\Shipping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'in:active,inactive',
            'search' => 'string|max:255',
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Shipping::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $shippings = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $shippings
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_fee' => 'required|numeric|min:0',
            'fee_per_km' => 'required|numeric|min:0',
            'min_distance' => 'required|numeric|min:0',
            'max_distance' => 'required|numeric|min:0|gt:min_distance',
            'estimated_delivery_time' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $shipping = Shipping::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Shipping method created successfully',
            'data' => $shipping
        ], 201);
    }

    public function show($id)
    {
        $shipping = Shipping::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $shipping
        ]);
    }

    public function update(Request $request, $id)
    {
        $shipping = Shipping::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'base_fee' => 'numeric|min:0',
            'fee_per_km' => 'numeric|min:0',
            'min_distance' => 'numeric|min:0',
            'max_distance' => 'numeric|min:0|gt:min_distance',
            'estimated_delivery_time' => 'string|max:255',
            'status' => 'in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $shipping->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Shipping method updated successfully',
            'data' => $shipping
        ]);
    }

    public function destroy($id)
    {
        $shipping = Shipping::findOrFail($id);

        // Kiểm tra xem phương thức vận chuyển có đang được sử dụng không
        $isUsed = DB::table('orders')->where('shipping_id', $id)->exists();
        
        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete shipping method that has been used in orders'
            ], 400);
        }

        $shipping->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Shipping method deleted successfully'
        ]);
    }

    public function calculateShippingFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'distance' => 'required|numeric|min:0',
            'shipping_id' => 'required|exists:shippings,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $shipping = Shipping::findOrFail($request->shipping_id);
        $distance = $request->distance;

        // Kiểm tra khoảng cách có nằm trong phạm vi cho phép không
        if ($distance < $shipping->min_distance || $distance > $shipping->max_distance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Distance is out of range for this shipping method'
            ], 400);
        }

        // Tính phí vận chuyển
        $shippingFee = $shipping->base_fee + ($distance * $shipping->fee_per_km);

        return response()->json([
            'status' => 'success',
            'data' => [
                'shipping_method' => $shipping,
                'distance' => $distance,
                'shipping_fee' => $shippingFee,
                'estimated_delivery_time' => $shipping->estimated_delivery_time
            ]
        ]);
    }
} 