<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'admin']);
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
            'status' => 'in:pending,paid,shipping,delivered,cancelled',
            'search' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = Order::with(['orderItems.product', 'shipping', 'payment', 'user']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('shipping', function($q) use ($search) {
                    $q->where('phone', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    public function show($id)
    {
        try {
            $order = Order::with(['orderItems.product', 'shipping', 'payment', 'user'])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,paid,shipping,delivered,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($id);
            
            // Kiểm tra trạng thái hiện tại
            if ($order->status === 'cancelled') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update status of cancelled order'
                ], 400);
            }

            // Cập nhật trạng thái đơn hàng
            $order->update(['status' => $request->status]);

            // Cập nhật trạng thái thanh toán nếu đơn hàng đã thanh toán
            if ($request->status === 'paid') {
                $order->payment->update(['status' => 'completed']);
            }

            // Cập nhật trạng thái vận chuyển
            if (in_array($request->status, ['shipping', 'delivered'])) {
                $order->shipping->update(['status' => $request->status]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => $order->load(['orderItems.product', 'shipping', 'payment', 'user'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 