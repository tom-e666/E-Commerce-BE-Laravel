<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\Shipping;
use App\Services\GHNService;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\UserCredentail;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Gate;
final readonly class ShippingResolver
{
    protected GHNService $ghnService;
    public function __construct(GHNService $ghnService)
    {
        $this->ghnService = $ghnService;
    }
    public function getProvinces($_, array $args)
    {
        try{
            $response = $this->ghnService->getProvinces();
            if(isset($response['code']) && $response['code'] === 200)
            {
                return [
                    'code' => 200,
                    'message' => 'success',
                    'provinces' => isset($response['data']) ? collect($response['data'])->map(function($province) {
                        return [
                            'ProvinceID' => $province['ProvinceID'],
                            'ProvinceName' => $province['ProvinceName'],
                        ];
                    })->all() : [],
                ];
            }
            return [
                'code' => $response['code'],
                'message' => $response['message'],
                'provinces' => null,
            ];
        } catch(\Exception $e){
            return [
                'code' => 500,
                'message' => $e->getMessage(),
                'provinces' => null,
            ];
        }
        
    }
    public function getDistricts($_, array $args)
    {
        try {
            if(!isset($args['province_id'])) {
                return [
                    'code' => 400,
                    'message' => 'province_id is required',
                    'districts' => [],
                ];
            }
            
            $response = $this->ghnService->getDistricts($args['province_id']);
            
            // Debug logging
            Log::info('GHN Districts response', ['response' => $response]);
            
            if (isset($response['code']) && $response['code'] === 200) {
                return [
                    'code' => 200,
                    'message' => 'success',
                    'districts' => isset($response['data']) ? collect($response['data'])->map(function($district) {
                        return [
                            'DistrictID' => $district['DistrictID'],
                            'DistrictName' => $district['DistrictName'],
                        ];
                    })->all() : [],
                ];
            }
            
            return [
                'code' => $response['code'] ?? 500,
                'message' => $response['message'] ?? 'Failed to get districts',
                'districts' => [],
            ];
        } catch(\Exception $e) {
            Log::error('Error getting districts: ' . $e->getMessage());
            return [
                'code' => 500,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'districts' => [],
            ];
        }
    }
    public function getWards($_, array $args)
    {
        try {
            if(!isset($args['district_id'])) {
                return [
                    'code' => 400,
                    'message' => 'district_id is required',
                    'wards' => [],
                ];
            }
            
            $response = $this->ghnService->getWards($args['district_id']);
            
            // Debug logging
            Log::info('GHN Wards response', ['response' => $response]);
            
            if (isset($response['code']) && $response['code'] === 200) {
                return [
                    'code' => 200,
                    'message' => 'success',
                    'wards' => isset($response['data']) ? collect($response['data'])->map(function($ward) {
                        return [
                            'WardCode' => $ward['WardCode'],
                            'WardName' => $ward['WardName'],
                        ];
                    })->all() : [],
                ];
            }
            
            return [
                'code' => $response['code'] ?? 500,
                'message' => $response['message'] ?? 'Failed to get wards',
                'wards' => [],
            ];
        } catch(\Exception $e) {
            Log::error('Error getting wards: ' . $e->getMessage());
            return [
                'code' => 500,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'wards' => [],
            ];
        }
    }

    public function calculateShippingFee($_, array $args)
    {
        $validator = Validator::make($args, [
            'from_district_id' => 'required|integer',
            'to_district_id' => 'required|integer',
            'to_ward_code' => 'required|string',
            'weight' => 'required|numeric|min:0',
            'value' => 'nullable|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return [
                'code' => 400,
                'message' => $validator->errors()->first(),
                'shipping_fee' => null,
                'expected_delivery_time' => null
            ];
        }
        $response = $this->ghnService->calculateFee(
            $args['from_district_id'],
            $args['to_district_id'],
            $args['to_ward_code'],
            $args['weight'],
            $args['value'] ?? 0
        );
        if (isset($response['code']) && $response['code'] === 200) {
            return [
                'code' => 200,
                'message' => 'Success',
                'shipping_fee' => $response['data']['total'] ?? 0,
                'expected_delivery_time' => $response['data']['expected_delivery_time'] ?? null
            ];
        }   
        return [
            'code' => $response['code'] ?? 500,
            'message' => $response['message'] ?? 'Failed to calculate shipping fee',
            'shipping_fee' => null,
            'expected_delivery_time' => null
        ];
    }
    public function getShippingByOrderId($_, array $args)
{
    $user = AuthService::Auth();
    if (!$user) {
        return [
            'code' => 401,
            'message' => 'Unauthorized',
            'shipping' => null,
        ];
    }
    
    if (!isset($args['order_id'])) {
        return [
            'code' => 400,
            'message' => 'order_id is required',
            'shipping' => null,
        ];
    }
    
    $shipping = Shipping::where('order_id', $args['order_id'])->first();
    if ($shipping === null) {
        return [
            'code' => 404,
            'message' => 'Shipping not found for the given order_id',
            'shipping' => null,
        ];
    }
    
    // Use policy for authorization
    if (Gate::denies('view', $shipping)) {
        return [
            'code' => 403,
            'message' => 'You are not authorized to view this shipping information',
            'shipping' => null,
        ];
    }
    
    return [
        'code' => 200,
        'message' => 'success',
        'shipping' => $shipping,
    ];
}
    public function getShippings($_, array $args)
    {
        $query = Shipping::query();

        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }

        $shippings = $query->orderBy('created_at', 'desc')->get();

        if ($shippings->isEmpty()) {
            return [
                'code' => 404,
                'message' => 'Shipping not found matching the criteria',
                'shippings' => null,
            ];
        }

        return [
            'code' => 200,
            'message' => 'success',
            'shippings' => $shippings,
        ];
    }

}