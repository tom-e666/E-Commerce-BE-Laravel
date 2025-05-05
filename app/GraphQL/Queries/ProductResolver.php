<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\Product;
use App\Models\ProductDetail;
use App\GraphQL\Traits\GraphQLResponse;
use App\Queries\ProductQuery;
use Illuminate\Http\Request;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Execution\HttpGraphQLContext;

final readonly class ProductResolver
{
    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
        
    }
    public function getProducts($_, array $args): array
    {
        $products = Product::where('status', true)->get();
        return $this->success([
            'products'=> $products->toArray(),
        ], 'Success', 200);
    }
    public function getProduct($_, array $args): array
    {
        if(!isset($args['id']))
        {
            return $this->error('id is required', 400);
        }
        $product = Product::find($args['id']);
        if($product===null)
        {
            return $this->error('Product not found', 404);
        }
        if($product->status===false)
        {
            return $this->error('Product not available', 404);
        }

        $productDetail = ProductDetail::where('product_id', $product->id)->first();
        if($productDetail===null)
        {
            return $this->error('Product details not found', 404);
        }

        return $this->success([
            'product'=> array_merge($product->toArray(),['details'=>$productDetail->toArray()]),
            // 'reviews'=> $product->details->recentReviews(),
        ], 'Success', 200);
    }

    public function getPaginatedProducts($root, array $args, HttpGraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $request = new Request($args);
        $productQuery = new ProductQuery();
        $products = $productQuery->paginate($request);

        $formattedProducts = [];
        foreach ($products as $product) {
            $productDetail = $product->details;
            $formattedProducts[] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'status' => $product->status,
                'brand_id' => $product->brand_id,
                'details' => $productDetail ? [
                    'description' => $productDetail->description,
                    'specifications' => $productDetail->specifications,
                    'images' => $productDetail->images,
                    'keywords' => $productDetail->keywords,
                ] : null,
            ];
        }

        return $this->success([
            'products' => $formattedProducts,
        ], 'Success', 200);
    }
}
