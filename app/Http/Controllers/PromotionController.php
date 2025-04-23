<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromotionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'in:active,expired,upcoming',
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

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = Promotion::query();

        // Lọc theo trạng thái
        if ($status) {
            $now = Carbon::now();
            switch ($status) {
                case 'active':
                    $query->where('is_active', true)
                        ->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now);
                    break;
                case 'expired':
                    $query->where('end_date', '<', $now);
                    break;
                case 'upcoming':
                    $query->where('start_date', '>', $now);
                    break;
            }
        }

        // Tìm kiếm theo mã
        if ($search) {
            $query->where('promo_code', 'like', "%{$search}%");
        }

        $promotions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'data' => $promotions
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'promo_code' => 'required|string|max:50|unique:promotions',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'max_uses' => 'required|integer|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $promotion = Promotion::create([
            'promo_code' => $request->promo_code,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'max_uses' => $request->max_uses,
            'min_order_amount' => $request->min_order_amount,
            'max_discount_amount' => $request->max_discount_amount,
            'is_active' => $request->input('is_active', true),
            'usage_count' => 0
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Promotion created successfully',
            'data' => $promotion
        ], 201);
    }

    public function show($id)
    {
        $promotion = Promotion::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $promotion
        ]);
    }

    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'promo_code' => 'string|max:50|unique:promotions,promo_code,' . $id,
            'discount_type' => 'in:percentage,fixed',
            'discount_value' => 'numeric|min:0',
            'start_date' => 'date|after_or_equal:today',
            'end_date' => 'date|after:start_date',
            'max_uses' => 'integer|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kiểm tra nếu đã có người sử dụng mã
        if ($promotion->usage_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update promotion that has been used'
            ], 400);
        }

        $promotion->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Promotion updated successfully',
            'data' => $promotion
        ]);
    }

    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);

        // Kiểm tra nếu đã có người sử dụng mã
        if ($promotion->usage_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete promotion that has been used'
            ], 400);
        }

        $promotion->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Promotion deleted successfully'
        ]);
    }

    public function validatePromoCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'promo_code' => 'required|string|max:50',
            'order_amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $promotion = Promotion::where('promo_code', $request->promo_code)
            ->where('is_active', true)
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now())
            ->where('usage_count', '<', DB::raw('max_uses'))
            ->first();

        if (!$promotion) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired promotion code'
            ], 404);
        }

        // Kiểm tra số tiền đơn hàng tối thiểu
        if ($promotion->min_order_amount && $request->order_amount < $promotion->min_order_amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order amount does not meet minimum requirement'
            ], 400);
        }

        // Tính toán số tiền giảm giá
        $discount = 0;
        if ($promotion->discount_type === 'percentage') {
            $discount = $request->order_amount * ($promotion->discount_value / 100);
        } else {
            $discount = $promotion->discount_value;
        }

        // Áp dụng giới hạn số tiền giảm giá tối đa
        if ($promotion->max_discount_amount && $discount > $promotion->max_discount_amount) {
            $discount = $promotion->max_discount_amount;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'promotion' => $promotion,
                'discount_amount' => $discount,
                'final_amount' => $request->order_amount - $discount
            ]
        ]);
    }
} 