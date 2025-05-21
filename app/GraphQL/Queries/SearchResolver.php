<?php

namespace App\GraphQL\Queries;

use App\GraphQL\Traits\GraphQLResponse;
use App\Models\Product;
use App\Models\Brand;
use App\Services\SearchEnrichmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchResolver
{
    use GraphQLResponse;
    
    protected $searchService;
    
    public function __construct(SearchEnrichmentService $searchService)
    {
        $this->searchService = $searchService;
    }
    
    /**
     * Handle the smartSearch query
     */
    public function smartSearch($_, array $args)
    {
        // Track execution time
        $startTime = microtime(true);
        
        // Extract parameters
        $query = $args['query'] ?? '';
        $page = $args['page'] ?? 1;
        $perPage = $args['perPage'] ?? 10;
        
        // Process the query with our enrichment service
        $searchParams = $this->searchService->processQuery($query);
        Log::info('Search parameters: ', $searchParams);
        // Extract processed data
        $searchTerms = $searchParams['search_terms'];
        $brands = $searchParams['filters']['brands'] ?? [];
        $priceRange = $searchParams['filters']['price_range'] ?? ['min' => null, 'max' => null];
        $sortBy = $searchParams['sort'] ?? 'relevance';
        
        // Start building query
        $productsQuery = Product::query()->where('status', true);
        
        if (!empty($searchTerms)) {
            $productsQuery->where(function($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    if (empty(trim($term))) continue;
                    
                    $term = trim($term);
                    $q->orWhere('name', 'like', "%{$term}%");
                    
                    $q->orWhereHas('details', function($detailsQuery) use ($term) {
                        $detailsQuery->where('description', 'like', "%{$term}%")
                                    ->orWhere('keywords', 'like', "%{$term}%");
                    });
                }
            });
        }
        
        // Apply brand filter
        if (!empty($brands)) {
            $brandIds = Brand::whereIn('name', $brands)->pluck('id')->toArray();
            $productsQuery->whereIn('brand_id', $brandIds);
        }
        
        // Apply price filter
        if (!empty($priceRange['min'])) {
            $productsQuery->where('price', '>=', $priceRange['min']);
        }
        if (!empty($priceRange['max'])) {
            $productsQuery->where('price', '<=', $priceRange['max']);
        }
        
        // Get total count before pagination
        $totalCount = $productsQuery->count();
        
        // Apply sort
        switch ($sortBy) {
            case 'price_low':
                $productsQuery->orderBy('price', 'asc');
                break;
            case 'price_high':
                $productsQuery->orderBy('price', 'desc');
                break;
            case 'newest':
                $productsQuery->orderBy('created_at', 'desc');
                break;
            default:
                $productsQuery->orderBy('id', 'desc'); // Default sort
        }
        
        // Get products - we'll get all results since you mentioned there's an issue with pagination
        // If you want to re-add pagination, replace this with:
        // $products = $productsQuery->with('details')->paginate($perPage, ['*'], 'page', $page);
        $products = $productsQuery->with('details')->get();
        
        // Get filter options
        $filters = $this->getFilterOptions($searchParams);
        
        // Calculate execution time
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return $this->success([
            'products' => $products,
            'total' => $totalCount,
            'filters' => $filters,
            'metadata' => [
                'original_query' => $query,
                'interpreted_query' => implode(' ', $searchTerms),
                'processing_time_ms' => $executionTime
            ]
        ], 'Search results retrieved successfully', 200);
    }
    
    /**
     * Get filter options for the search results
     */
    private function getFilterOptions($searchParams)
    {
        $searchTerms = $searchParams['search_terms'];
        
        try {
            // Get brand filter options - fix the issue with JSON_EXTRACT
            $brands = Brand::whereHas('products', function($query) use ($searchTerms) {
                $query->where('status', true);
                if (!empty($searchTerms)) {
                    $query->where(function($q) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            if (empty(trim($term))) continue;
                            
                            $term = '%' . trim($term) . '%';
                            $q->orWhere('name', 'like', $term);
                            // Don't try to use JSON_EXTRACT on details column
                        }
                    });
                }
            })
            ->select('id', 'name', DB::raw('(SELECT COUNT(*) FROM products WHERE products.brand_id = brands.id AND products.status = 1) as count'))
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'count' => $brand->count
                ];
            });
            
            // Get price range
            $priceStats = Product::where('status', true)
                ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
                ->first();
                
            return [
                'brands' => $brands,
                'categories' => [], 
                'price_range' => [
                    'min' => (float) ($priceStats->min_price ?? 0),
                    'max' => (float) ($priceStats->max_price ?? 1000)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error getting filter options: ' . $e->getMessage());
            return [
                'brands' => [],
                'categories' => [],
                'price_range' => [
                    'min' => 0,
                    'max' => 1000
                ]
            ];
        }
    }
}