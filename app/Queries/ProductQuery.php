<?php

namespace App\Queries;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductQuery
{
    protected $query;

    public function __construct()
    {
        $this->query = Product::query();
    }

    public function apply(Request $request)
    {
        $this->applyFilters($request);
        $this->applySorting($request);
        $this->applySearch($request);
        
        return $this->query;
    }

    protected function applyFilters(Request $request)
    {
        $filters = [];

        // Lọc theo brand
        if ($request->has('brand_id')) {
            $filters['brand_id'] = $request->brand_id;
        }

        // Lọc theo category
        if ($request->has('category_id')) {
            $filters['category_id'] = $request->category_id;
        }

        // Lọc theo khoảng giá
        if ($request->has('min_price')) {
            $filters['price'] = ['$gte' => (float)$request->min_price];
        }
        if ($request->has('max_price')) {
            $filters['price'] = array_merge($filters['price'] ?? [], ['$lte' => (float)$request->max_price]);
        }

        // Lọc theo trạng thái
        if ($request->has('status')) {
            $filters['status'] = $request->status === 'true';
        } else {
            $filters['status'] = true;
        }

        if (!empty($filters)) {
            $this->query->where($filters);
        }
    }

    protected function applySorting(Request $request)
    {
        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Danh sách các trường được phép sắp xếp
        $allowedSortFields = ['price', 'created_at', 'name', 'stock'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $this->query->orderBy($sortField, $sortOrder);
        }
    }

    protected function applySearch(Request $request)
    {
        if ($request->has('search')) {
            $search = $request->search;
            $this->query->where(function ($query) use ($search) {
                $query->where('name', 'regex', "/$search/i")
                    ->orWhere('description', 'regex', "/$search/i");
            });
        }
    }

    public function paginate(Request $request)
    {
        $perPage = min($request->get('per_page', 12), 100);
        $page = $request->get('page', 1);

        // Sử dụng skip và take thay vì paginate
        $skip = ($page - 1) * $perPage;
        
        return $this->apply($request)
            ->skip($skip)
            ->take($perPage)
            ->get();
    }
} 