<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $cartItems = CartItem::with(['product', 'product.brand', 'product.productDetails'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Tính tổng tiền giỏ hàng
        $total = $this->calculateCartTotal($cartItems->items());

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $cartItems,
                'total' => $total
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->product_id);
            
            // Kiểm tra số lượng tồn kho
            if ($product->stock < $request->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not enough stock available',
                    'available_stock' => $product->stock
                ], 400);
            }
            
            // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
            $existingCartItem = CartItem::where('product_id', $request->product_id)
                ->where('user_id', auth()->id())
                ->first();
            
            if ($existingCartItem) {
                // Kiểm tra tổng số lượng sau khi cập nhật
                $newQuantity = $existingCartItem->quantity + $request->quantity;
                if ($product->stock < $newQuantity) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Not enough stock available for additional quantity',
                        'available_stock' => $product->stock,
                        'current_cart_quantity' => $existingCartItem->quantity
                    ], 400);
                }
                
                // Nếu đã có, cập nhật số lượng
                $existingCartItem->quantity = $newQuantity;
                $existingCartItem->save();
                $cartItem = $existingCartItem;
            } else {
                // Nếu chưa có, tạo mới
                $cartItem = CartItem::create([
                    'user_id' => auth()->id(),
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to cart successfully',
                'data' => $cartItem->load(['product', 'product.brand', 'product.productDetails'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add product to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cartItem = CartItem::with('product')
                ->where('user_id', auth()->id())
                ->findOrFail($id);
            
            // Kiểm tra số lượng tồn kho
            if ($cartItem->product->stock < $request->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not enough stock available',
                    'available_stock' => $cartItem->product->stock
                ], 400);
            }
            
            $cartItem->quantity = $request->quantity;
            $cartItem->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Cart item updated successfully',
                'data' => $cartItem->load(['product', 'product.brand', 'product.productDetails'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update cart item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $cartItem = CartItem::where('user_id', auth()->id())
                ->findOrFail($id);
            $cartItem->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Cart item deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete cart item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function applyPromotion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'promotion_code' => 'required|exists:promotions,code'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $promotion = Promotion::where('code', $request->promotion_code)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->where('is_active', true)
                ->first();

            if (!$promotion) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired promotion code'
                ], 400);
            }

            // Kiểm tra số lần sử dụng mã giảm giá
            if ($promotion->usage_limit && $promotion->usage_count >= $promotion->usage_limit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Promotion code has reached its usage limit'
                ], 400);
            }

            $cartItems = CartItem::with(['product', 'product.brand', 'product.productDetails'])
                ->where('user_id', auth()->id())
                ->get();

            $total = $this->calculateCartTotal($cartItems);
            
            // Áp dụng giảm giá
            $discount = $this->calculateDiscount($total, $promotion);
            $finalTotal = $total - $discount;

            // Lưu thông tin mã giảm giá đã áp dụng
            DB::beginTransaction();
            try {
                $promotion->increment('usage_count');
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Promotion applied successfully',
                'data' => [
                    'original_total' => $total,
                    'discount' => $discount,
                    'final_total' => $finalTotal,
                    'promotion' => $promotion
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to apply promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateCartTotal($cartItems)
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item->product->price * $item->quantity;
        }
        return $total;
    }

    private function calculateDiscount($total, $promotion)
    {
        if ($promotion->discount_type === 'percentage') {
            return $total * ($promotion->discount_value / 100);
        } else {
            return min($promotion->discount_value, $total);
        }
    }
} 