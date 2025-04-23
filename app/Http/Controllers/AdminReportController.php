<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'admin']);
    }

    public function revenueByTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'in:day,week,month,year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $groupBy = $request->input('group_by', 'day');

        $query = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$startDate, $endDate]);

        switch ($groupBy) {
            case 'day':
                $query->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total_price) as revenue'),
                    DB::raw('COUNT(*) as order_count')
                )->groupBy('date');
                break;
            case 'week':
                $query->select(
                    DB::raw('YEARWEEK(created_at) as week'),
                    DB::raw('SUM(total_price) as revenue'),
                    DB::raw('COUNT(*) as order_count')
                )->groupBy('week');
                break;
            case 'month':
                $query->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('SUM(total_price) as revenue'),
                    DB::raw('COUNT(*) as order_count')
                )->groupBy('month');
                break;
            case 'year':
                $query->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('SUM(total_price) as revenue'),
                    DB::raw('COUNT(*) as order_count')
                )->groupBy('year');
                break;
        }

        $revenueData = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $revenueData
        ]);
    }

    public function bestSellingProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'limit' => 'integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;
        $limit = $request->input('limit', 10);

        $query = OrderItem::select(
            'product_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(total) as total_revenue')
        )
        ->with('product:id,name,details')
        ->groupBy('product_id')
        ->orderBy('total_quantity', 'desc');

        if ($startDate && $endDate) {
            $query->whereHas('order', function($q) use ($startDate, $endDate) {
                $q->where('status', 'delivered')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            });
        } else {
            $query->whereHas('order', function($q) {
                $q->where('status', 'delivered');
            });
        }

        $bestSellers = $query->limit($limit)->get();

        return response()->json([
            'status' => 'success',
            'data' => $bestSellers
        ]);
    }

    public function reviewManagement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
            'rating' => 'integer|min:1|max:5',
            'product_id' => 'integer|exists:products,id',
            'sort_by' => 'in:created_at,rating',
            'sort_order' => 'in:asc,desc'
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
        $rating = $request->input('rating');
        $productId = $request->input('product_id');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = Review::with(['user:id,name', 'productDetail.product:id,name']);

        if ($rating) {
            $query->where('rating', $rating);
        }

        if ($productId) {
            $query->whereHas('productDetail', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        $reviews = $query->orderBy($sortBy, $sortOrder)
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
        )->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'reviews' => $reviews,
                'statistics' => $stats
            ]
        ]);
    }

    public function deleteReview($id)
    {
        try {
            $review = Review::findOrFail($id);
            $productId = $review->product_id;
            
            $review->delete();

            // Cập nhật lại đánh giá trung bình của sản phẩm
            $product = Product::findOrFail($productId);
            $averageRating = Review::where('product_id', $productId)
                ->avg('rating');
            $product->update(['average_rating' => $averageRating]);

            return response()->json([
                'status' => 'success',
                'message' => 'Review deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete review'
            ], 500);
        }
    }

    public function revenueByBrand(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        $query = OrderItem::select(
            'products.brand_id',
            'brands.name as brand_name',
            DB::raw('SUM(order_items.total) as total_revenue'),
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('COUNT(DISTINCT orders.id) as order_count')
        )
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->join('brands', 'products.brand_id', '=', 'brands.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.status', 'delivered')
        ->groupBy('products.brand_id', 'brands.name');

        if ($startDate && $endDate) {
            $query->whereBetween('orders.created_at', [$startDate, $endDate]);
        }

        $revenueByBrand = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $revenueByBrand
        ]);
    }

    public function orderConversionRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        $query = Order::select(
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('COUNT(CASE WHEN status = "delivered" THEN 1 END) as completed_orders'),
            DB::raw('COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_orders'),
            DB::raw('ROUND((COUNT(CASE WHEN status = "delivered" THEN 1 END) / COUNT(*)) * 100, 2) as conversion_rate')
        );

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $conversionStats = $query->first();

        return response()->json([
            'status' => 'success',
            'data' => $conversionStats
        ]);
    }

    public function newCustomersByTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'in:day,week,month,year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $groupBy = $request->input('group_by', 'day');

        // Lấy danh sách khách hàng mới (chưa có đơn hàng trước đó)
        $query = DB::table('user_credentials')
            ->whereNotExists(function($query) use ($startDate) {
                $query->select(DB::raw(1))
                    ->from('orders')
                    ->whereRaw('orders.user_id = user_credentials.id')
                    ->where('orders.created_at', '<', $startDate);
            })
            ->whereBetween('created_at', [$startDate, $endDate]);

        switch ($groupBy) {
            case 'day':
                $query->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as new_customers')
                )->groupBy('date');
                break;
            case 'week':
                $query->select(
                    DB::raw('YEARWEEK(created_at) as week'),
                    DB::raw('COUNT(*) as new_customers')
                )->groupBy('week');
                break;
            case 'month':
                $query->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as new_customers')
                )->groupBy('month');
                break;
            case 'year':
                $query->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('COUNT(*) as new_customers')
                )->groupBy('year');
                break;
        }

        $newCustomers = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $newCustomers
        ]);
    }

    public function returnRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        $query = OrderItem::select(
            DB::raw('COUNT(*) as total_items'),
            DB::raw('COUNT(CASE WHEN returns.id IS NOT NULL THEN 1 END) as returned_items'),
            DB::raw('ROUND((COUNT(CASE WHEN returns.id IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as return_rate')
        )
        ->leftJoin('returns', 'order_items.id', '=', 'returns.order_item_id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.status', 'delivered');

        if ($startDate && $endDate) {
            $query->whereBetween('orders.created_at', [$startDate, $endDate]);
        }

        $returnStats = $query->first();

        return response()->json([
            'status' => 'success',
            'data' => $returnStats
        ]);
    }

    public function revenueByCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        $query = OrderItem::select(
            'products.category_id',
            'categories.name as category_name',
            DB::raw('SUM(order_items.total) as total_revenue'),
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('COUNT(DISTINCT orders.id) as order_count')
        )
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->join('categories', 'products.category_id', '=', 'categories.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.status', 'delivered')
        ->groupBy('products.category_id', 'categories.name');

        if ($startDate && $endDate) {
            $query->whereBetween('orders.created_at', [$startDate, $endDate]);
        }

        $revenueByCategory = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $revenueByCategory
        ]);
    }

    public function returningCustomers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Tổng số khách hàng
        $totalCustomers = DB::table('user_credentials')->count();

        // Số khách hàng có từ 2 đơn hàng trở lên
        $returningCustomers = DB::table('orders')
            ->select('user_id')
            ->where('status', 'delivered')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2');

        if ($startDate && $endDate) {
            $returningCustomers->whereBetween('created_at', [$startDate, $endDate]);
        }

        $returningCount = $returningCustomers->count();

        // Tính tỷ lệ khách hàng quay lại
        $returnRate = $totalCustomers > 0 ? round(($returningCount / $totalCustomers) * 100, 2) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_customers' => $totalCustomers,
                'returning_customers' => $returningCount,
                'return_rate' => $returnRate
            ]
        ]);
    }

    public function averageDeliveryTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        $query = Order::select(
            DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_delivery_hours'),
            DB::raw('AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_delivery_days')
        )
        ->where('status', 'delivered');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $deliveryStats = $query->first();

        return response()->json([
            'status' => 'success',
            'data' => $deliveryStats
        ]);
    }

    public function promotionUsage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        $query = Order::select(
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('COUNT(CASE WHEN promo_code IS NOT NULL THEN 1 END) as orders_with_promotion'),
            DB::raw('ROUND((COUNT(CASE WHEN promo_code IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as promotion_usage_rate'),
            DB::raw('SUM(CASE WHEN promo_code IS NOT NULL THEN discount_amount ELSE 0 END) as total_discount_amount')
        );

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $promotionStats = $query->first();

        // Thống kê theo từng mã giảm giá
        $promotionDetails = Order::select(
            'promo_code',
            DB::raw('COUNT(*) as usage_count'),
            DB::raw('SUM(discount_amount) as total_discount')
        )
        ->whereNotNull('promo_code')
        ->groupBy('promo_code');

        if ($startDate && $endDate) {
            $promotionDetails->whereBetween('created_at', [$startDate, $endDate]);
        }

        $promotionDetails = $promotionDetails->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'overall_stats' => $promotionStats,
                'promotion_details' => $promotionDetails
            ]
        ]);
    }
} 