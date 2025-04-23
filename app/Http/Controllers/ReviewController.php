<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\ProductDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kiểm tra đơn hàng đã giao chưa
        $order = Order::where('id', $request->order_id)
            ->where('user_id', auth()->id())
            ->where('status', 'delivered')
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found or not delivered'
            ], 404);
        }

        // Kiểm tra sản phẩm có trong đơn hàng không
        $orderItem = OrderItem::where('order_id', $order->id)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found in order'
            ], 404);
        }

        // Kiểm tra đã đánh giá chưa
        $existingReview = Review::whereHas('productDetail', function($q) use ($request) {
            $q->where('product_id', $request->product_id);
        })
        ->where('user_id', auth()->id())
        ->first();

        if ($existingReview) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already reviewed this product'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Tạo đánh giá
            $productDetail = ProductDetail::where('product_id', $request->product_id)->first();
            
            $review = Review::create([
                'user_id' => auth()->id(),
                'product_detail_id' => $productDetail->id,
                'rating' => $request->rating,
                'comment' => $request->comment
            ]);

            // Cập nhật đánh giá trung bình của sản phẩm
            $averageRating = Review::whereHas('productDetail', function($q) use ($request) {
                $q->where('product_id', $request->product_id);
            })->avg('rating');

            $productDetail->update(['average_rating' => $averageRating]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Review created successfully',
                'data' => $review->load(['user:id,name'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'rating' => 'integer|min:1|max:5',
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
        $productId = $request->input('product_id');
        $rating = $request->input('rating');

        $query = Review::with(['user:id,name', 'productDetail.product:id,name'])
            ->whereHas('productDetail', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });

        if ($rating) {
            $query->where('rating', $rating);
        }

        $reviews = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Thống kê đánh giá
        $stats = Review::select(
            DB::raw('COUNT(*) as total_reviews'),
            DB::raw('AVG(rating) as average_rating'),
            DB::raw('COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star'),
            DB::raw('COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star'),
            DB::raw('COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star'),
            DB::raw('COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star'),
            DB::raw('COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star')
        )
        ->whereHas('productDetail', function($q) use ($productId) {
            $q->where('product_id', $productId);
        })
        ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'reviews' => $reviews,
                'statistics' => $stats
            ]
        ]);
    }

    public function destroy($id)
    {
        $review = Review::where('user_id', auth()->id())
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            $productDetail = $review->productDetail;
            $productId = $productDetail->product_id;

            // Xóa đánh giá
            $review->delete();

            // Cập nhật đánh giá trung bình của sản phẩm
            $averageRating = Review::whereHas('productDetail', function($q) use ($productId) {
                $q->where('product_id', $productId);
            })->avg('rating');

            $productDetail->update(['average_rating' => $averageRating]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Review deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete review',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 