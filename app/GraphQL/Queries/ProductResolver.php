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
            
            // Filter by status if provided, otherwise default to active products
            if (isset($args['status'])) {
                if ($args['status'] === 'all') {
                    $user = AuthService::Auth();
                    // Only admin/staff can see all products including inactive
                    if (!$user || (!$user->isAdmin() && !$user->isStaff())) {
                        $query->where('status', true);
                    }
                } else {
                    $query->where('status', $args['status'] === 'active');
                }
            } else {
                $query->where('status', true);
            }
            
            $products = $query->with('details')->get();
            
            $formattedProducts = $products->map(function ($product) {
                return $this->formatProductResponse($product);
            });
            
            return $this->success([
                'products' => $formattedProducts,
                'total' => $products->count(),
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
            
            $product = Product::with('details')->find($args['id']);
            
            if ($product === null) {
                return $this->error('Product not found', 404);
            }
            
            // Check if product is inactive and if user can view it
            if (!$product->status) {
                $user = AuthService::Auth();
                
                if (!$user || (!$user->isAdmin() && !$user->isStaff())) {
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

    /**
     * Get paginated products with filters
     *
     * @param mixed $root Root value (not used)
     * @param array $args Query arguments
     * @param HttpGraphQLContext $context GraphQL context
     * @param ResolveInfo $resolveInfo GraphQL resolve info
     * @return array Response with paginated products or error
     */
    public function getPaginatedProducts($root, array $args, HttpGraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        try {
            $request = new Request($args);
            $productQuery = new ProductQuery();
            
            // Check if user can view inactive products
            $user = AuthService::Auth();
            $canViewInactive = $user && ($user->isAdmin() || $user->isStaff());
            
            // Set default status filter if not provided and user isn't admin/staff
            if (!isset($args['status']) && !$canViewInactive) {
                $request->merge(['status' => 'active']);
            }
            
            $products = $productQuery->paginate($request);
            $totalProducts = $productQuery->getTotalCount();
            
            $formattedProducts = $products->map(function ($product) {
                return $this->formatProductResponse($product);
            });

            return $this->success([
                'products' => $formattedProducts,
                'total' => $totalProducts,
                'current_page' => $args['page'] ?? 1,
                'per_page' => $args['per_page'] ?? 10,
            ], 'Success', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch paginated products: ' . $e->getMessage(), 500);
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
        
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'status' => (bool) $product->status,
            'brand_id' => $product->brand_id,
            'details' => $productDetail ? [
                'description' => $productDetail->description,
                'specifications' => $productDetail->specifications,
                'images' => $productDetail->images,
                'keywords' => $productDetail->keywords,
            ] : null,
        ];
    }
}