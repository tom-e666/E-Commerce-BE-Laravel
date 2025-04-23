<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'integer|exists:brands,id',
            'search' => 'string|max:255',
            'sort_by' => 'in:price,name,created_at,average_rating',
            'sort_order' => 'in:asc,desc',
            'min_price' => 'numeric|min:0',
            'max_price' => 'numeric|min:0|gte:min_price',
            'rating' => 'numeric|min:1|max:5',
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
        $brandId = $request->input('brand_id');
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $rating = $request->input('rating');

        $query = Product::with(['brand:id,name', 'details'])
            ->where('status', 'active');

        // Tìm kiếm theo tên và mô tả
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('details', function($q) use ($search) {
                      $q->where('description', 'like', "%{$search}%");
                  });
            });
        }

        // Lọc theo thương hiệu
        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        // Lọc theo khoảng giá
        if ($minPrice) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice) {
            $query->where('price', '<=', $maxPrice);
        }

        // Lọc theo đánh giá
        if ($rating) {
            $query->whereHas('details', function($q) use ($rating) {
                $q->where('average_rating', '>=', $rating);
            });
        }

        // Sắp xếp
        if ($sortBy === 'average_rating') {
            $query->orderByRaw('(SELECT average_rating FROM product_details WHERE product_details.product_id = products.id) ' . $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'data' => [
                'products' => $products,
                'filters' => [
                    'brand_id' => $brandId,
                    'search' => $search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                    'rating' => $rating
                ]
            ]
        ]);
    }

    public function show($id)
    {
        $product = Product::with(['brand:id,name', 'details', 'reviews.user:id,name'])
            ->where('status', 'active')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    public function getBrands()
    {
        $brands = Brand::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $brands
        ]);
    }

    public function searchSuggestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $keyword = $request->input('keyword');

        $suggestions = Product::select('id', 'name', 'price')
            ->where('status', 'active')
            ->where(function($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhereHas('details', function($q) use ($keyword) {
                        $q->where('description', 'like', "%{$keyword}%");
                    });
            })
            ->limit(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $suggestions
        ]);
    }
} 