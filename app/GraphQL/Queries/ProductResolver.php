<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\Product;
use App\Models\ProductDetail;
use App\GraphQL\Traits\GraphQLResponse;
use App\Queries\ProductQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Execution\HttpGraphQLContext;
use App\Services\AuthService;
use Illuminate\Support\Facades\Log;
final class ProductResolver
{
    use GraphQLResponse;

    /**
     * Get all active products
     *
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with products or error
     */
    public function getProducts($_, array $args): array
    {
        try {
            $query = Product::query();
            
            if (isset($args['status'])) {
                if ($args['status'] === 'all') {
                    $user = AuthService::Auth();
                    
                    if (!$user || Gate::denies('viewAny', Product::class)) {
                        $query->where('status', true);
                    }
                } else {
                    $query->where('status', $args['status'] === 'active');
                }
            } else {
                $query->where('status', true);
            }
            
            // Apply category filter if provided
            if (isset($args['category_id']) && !empty($args['category_id'])) {
                $query->where('category_id', $args['category_id']);
            }
            
            // Apply brand filter if provided
            if (isset($args['brand_id']) && !empty($args['brand_id'])) {
                $query->where('brand_id', $args['brand_id']);
            }
            
            // Apply price range filter if provided
            if (isset($args['price_min'])) {
                $query->where('price', '>=', $args['price_min']);
            }
            
            if (isset($args['price_max'])) {
                $query->where('price', '<=', $args['price_max']);
            }
            $products = $query->get();
            $productDetails = ProductDetail::all();
            Log::info('Product Details all', ['details' => $productDetails]);
            $user = AuthService::Auth();
            if ($user) {
                $products = $products->filter(function ($product) use ($user) {
                    return Gate::allows('view', $product);
                });
            }
            
            $formattedProducts = $products->map(function ($product) {
                return $this->formatProductResponse($product);
            });
            
            return $this->success([
                'products' => $formattedProducts,
            ], 'Success', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch products: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a single product by ID
     *
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with product or error
     */
    public function getProduct($_, array $args): array
    {
        try {
            if (!isset($args['id'])) {
                return $this->error('id is required', 400);
            }
            
            $product = Product::find($args['id']);
            if ($product === null) {
                return $this->error('Product not found', 404);
            }
            
            if (!$product->status) {
                $user = AuthService::Auth();
                
                if (!$user || Gate::denies('view', $product)) {
                    return $this->error('Product not available', 404);
                }
            }
            return $this->success([
                'product' => $this->formatProductResponse($product),
            ], 'Success', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch product: ' . $e->getMessage(), 500);
        }
    }
    public function searchProducts($_, array $args): array
    {
        try {
            $query = Product::query();
            
            // Apply search term if provided
            if (isset($args['search']) && !empty($args['search'])) {
                $searchTerm = $args['search'];
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhereHas('details', function($q) use ($searchTerm) {
                          $q->where('description', 'like', "%{$searchTerm}%")
                            ->orWhere('specifications', 'like', "%{$searchTerm}%")
                            ->orWhere('keywords', 'like', "%{$searchTerm}%");
                      });
                });
            }
            
            // Only show active products for regular users
            $user = AuthService::Auth();
            if (!$user || Gate::denies('viewAny', Product::class)) {
                $query->where('status', true);
            }
            
            // Apply other filters from getProducts method
            if (isset($args['category_id']) && !empty($args['category_id'])) {
                $query->where('category_id', $args['category_id']);
            }
            
            if (isset($args['brand_id']) && !empty($args['brand_id'])) {
                $query->where('brand_id', $args['brand_id']);
            }
            
            if (isset($args['price_min'])) {
                $query->where('price', '>=', $args['price_min']);
            }
            
            if (isset($args['price_max'])) {
                $query->where('price', '<=', $args['price_max']);
            }
            
            // Apply sorting
            $sortField = $args['sort_field'] ?? 'created_at';
            $sortDirection = $args['sort_direction'] ?? 'desc';
            
            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['name', 'price', 'created_at', 'stock'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }
            
            // Validate sort direction
            $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortField, $sortDirection);
            
            // Apply pagination
            $page = $args['page'] ?? 1;
            $perPage = $args['per_page'] ?? 10;
            $products = $query->with('details', 'category', 'brand')
                             ->paginate($perPage, ['*'], 'page', $page);
            
            $formattedProducts = collect($products->items())->map(function ($product) {
                return $this->formatProductResponse($product);
            });
            
            return $this->success([
                'products' => $formattedProducts,
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
            ], 'Success', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to search products: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get related products
     *
     * @param mixed $_ Root value (not used)
     * @param array $args Query arguments
     * @return array Response with related products or error
     */
    public function getRelatedProducts($_, array $args): array
    {
        try {
            if (!isset($args['product_id'])) {
                return $this->error('product_id is required', 400);
            }
            
            $product = Product::find($args['product_id']);
            if (!$product) {
                return $this->error('Product not found', 404);
            }
            
            // Find products in the same category
            $query = Product::where('id', '!=', $product->id)
                          ->where('status', true)
                          ->where('category_id', $product->category_id);
                          
            // Limit results
            $limit = $args['limit'] ?? 5;
            $relatedProducts = $query->with('details')
                                   ->inRandomOrder()
                                   ->limit($limit)
                                   ->get();
            
            $formattedProducts = $relatedProducts->map(function ($product) {
                return $this->formatProductResponse($product);
            });
            
            return $this->success([
                'products' => $formattedProducts,
            ], 'Success', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch related products: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Format product data for response
     *
     * @param Product $product Product model
     * @return array Formatted product data
     */
    private function formatProductResponse(Product $product): array
    {
        $productDetail = $product->details;
        Log::info(' 121 Product Details ', ['details' => $productDetail]);
        $result = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'status' => (bool) $product->status,
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
        if ($product->relationLoaded('brand') && $product->brand) {
            $result['brand_name'] = $product->brand->name;
        }
        
        if ($product->relationLoaded('category') && $product->category) {
            $result['category_name'] = $product->category->name;
        }
        
        // Add product details if available
        if ($productDetail) {
            $result['details'] = [
                'description' => $productDetail->description,
                'specifications' => $productDetail->specifications,
                'images' => $productDetail->images,
                'keywords' => $productDetail->keywords,
            ];
        } else {
            $result['details'] = null;
        }
        
        return $result;
    }
}