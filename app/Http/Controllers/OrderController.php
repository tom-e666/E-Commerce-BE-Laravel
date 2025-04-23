<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Shipping;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'in:pending,paid,shipping,delivered,cancelled',
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

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $status = $request->input('status');

        $query = Order::with(['items.product:id,name,details', 'payment', 'shipping'])
            ->where('user_id', auth()->id());

        if ($status) {
            $query->where('status', $status);
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
        $order = Order::with(['items.product:id,name,details', 'payment', 'shipping'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'payment_method' => 'required|in:cash,credit_card,bank_transfer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Tạo đơn hàng
            $order = Order::create([
                'user_id' => auth()->id(),
                'status' => 'pending',
                'total_price' => 0
            ]);

            $totalPrice = 0;
            $orderItems = [];

            // Thêm sản phẩm vào đơn hàng
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Product {$product->name} is out of stock");
                }

                $itemTotal = $product->price * $item['quantity'];
                $totalPrice += $itemTotal;

                $orderItems[] = [
                    'order_id' => $order->order_id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal
                ];

                // Cập nhật số lượng tồn kho
                $product->decrement('stock', $item['quantity']);
            }

            // Cập nhật tổng giá đơn hàng
            $order->update(['total_price' => $totalPrice]);

            // Thêm chi tiết đơn hàng
            OrderItem::insert($orderItems);

            // Tạo thông tin thanh toán
            Payment::create([
                'order_id' => $order->order_id,
                'method' => $request->payment_method,
                'status' => 'pending',
                'amount' => $totalPrice
            ]);

            // Tạo thông tin vận chuyển
            Shipping::create([
                'order_id' => $order->order_id,
                'address' => $request->shipping_address,
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => $order->load(['items.product:id,name,details', 'payment', 'shipping'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel($id)
    {
        $order = Order::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            // Cập nhật trạng thái đơn hàng
            $order->update(['status' => 'cancelled']);

            // Hoàn trả số lượng tồn kho
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }

            // Cập nhật trạng thái thanh toán
            $order->payment->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 