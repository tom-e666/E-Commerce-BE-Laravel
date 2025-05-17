<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\Shipping;
use App\Services\AuthService;
use App\GraphQL\Traits\GraphQLResponse;
use App\Models\UserCredentail;
use App\Models\OrderItem;
use App\Services\GHNService;
class GHNService
{
    protected $apiUrl;
    protected $token;
    protected $shopId;
    public function __construct()
    {
        $this->apiUrl =env('GHN_API_URL');
        $this->token = env('GHN_API_TOKEN');
        $this->shopId = env('GHN_SHOP_ID');
    }
    public function createShippingOrder($order, $shippingInfo)
    {
        try {
            $items = [];
            foreach ($order->items as $item) {
                $items[] = [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'weight' => 2500, // Estimated weight in grams per item - should be from your product details
                    'price' => $item->price
                ];
            }
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'token' => $this->token,
                'shopId' => $this->shopId
            ])->post("{$this->apiUrl}/v2/shipping-order/create", [
                'payment_type_id' => 1, // 1: Prepaid, 2: Cash on delivery
                'note' => $shippingInfo['note'] ?? '',
                'required_note' => 'KHONGCHOXEMHANG', // Options: CHOXEMHANGKHONGTHU, CHOTHUHANG, KHONGCHOXEMHANG
                'to_name' => $shippingInfo['recipient_name'],
                'to_phone' => $shippingInfo['recipient_phone'],
                'to_address' => $shippingInfo['address'],
                'to_ward_name' => $shippingInfo['ward_name'],
                'to_district_name' => $shippingInfo['district_name'],
                'to_province_name' => $shippingInfo['province_name'],
                'weight' => array_sum(array_map(function($item) { 
                    return 2500 * $item['quantity']; 
                }, $items)),
                'length' => 50, // in cm
                'width' => 40, // in cm
                'height' => 10, // in cm
                'service_type_id' => 2, // Light/laptop
                'items' => $items //danger with thief
            ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('GHN API Error - createOrder: ' . $e->getMessage());
            return ['code' => 500, 'message' => 'Failed to create shipping order: ' . $e->getMessage()];
        }
    }
    public function getOrderInfo($orderCode)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Token' => $this->token,
                'ShopId' => $this->shopId
            ])->post("{$this->apiUrl}/shipping-order/detail", [
                'order_code' => $orderCode
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GHN API Error - getOrderInfo: ' . $e->getMessage());
            return ['code' => 500, 'message' => 'Failed to get shipping order information'];
        }
    }
    public function cancelOrder($orderCode)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Token' => $this->token,
                'ShopId' => $this->shopId
            ])->post("{$this->apiUrl}/shipping-order/cancel", [
                'order_code' => $orderCode
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GHN API Error - cancelOrder: ' . $e->getMessage());
            return ['code' => 500, 'message' => 'Failed to cancel shipping order'];
        }
    }
    public function getTracking($orderCode)
    {
        try {
            $response = Http::withHeaders([
                'Token' => $this->token
            ])->post("{$this->apiUrl}/shipping-order/detail", [
                'order_code' => $orderCode
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GHN API Error - getTracking: ' . $e->getMessage());
            return ['code' => 500, 'message' => 'Failed to get tracking info'];
        }
    }
public function getProvinces()
{
    try {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Token' => $this->token  // Correct capitalization
        ])->get("{$this->apiUrl}/master-data/province");
        return $response->json();
    } catch (\Exception $e) {
       throw $e;
    }
}
    /**
     * Get districts by province ID
     */
    public function getDistricts($provinceId)
    {
        try {
            $response = Http::withHeaders([
                'Token' => $this->token
            ])->get("{$this->apiUrl}/master-data/district", [
                'province_id' => $provinceId
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GHN API Error - getDistricts: ' . $e->getMessage());
            return ['code' => 500, 'message' => 'Failed to get districts from GHN'];
        }
    }

    /**
     * Get wards by district ID
     */
    public function getWards($districtId)
    {
        try {
            $response = Http::withHeaders([
                'Token' => $this->token
            ])->get("{$this->apiUrl}/master-data/ward", [
                'district_id' => $districtId
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GHN API Error - getWards: ' . $e->getMessage());
            return ['code' => 500, 'message' => 'Failed to get wards from GHN'];
        }
    }
    public function calculateFee($fromDistrictId, $toDistrictId, $toWardCode, $weight, $insuranceValue = 0)
    {
        try {
            $response = Http::withHeaders([
                'Token' => $this->token,
                'ShopId' => $this->shopId
            ])->post("{$this->apiUrl}/shipping-order/fee", [
                'from_district_id' => $fromDistrictId,
                'to_district_id' => $toDistrictId,
                'to_ward_code' => $toWardCode,
                'weight' => $weight, // in grams
                'insurance_value' => $insuranceValue,
                'service_id' => 53320 // Standard delivery
            ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('GHN API Error - calculateFee: ' . $e->getMessage());
            return ['code' => 500, 'message' => 'Failed to calculate shipping fee'];
        }
    }
    public function cancelShippingOrder($orderCode)
    {
        try {
            $response = Http::withHeaders([
                'Token' => $this->token,
                'ShopId' => $this->shopId
            ])->post("{$this->apiUrl}v2/switch-status/cancel", [
                'order_codes' => $orderCode
            ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('GHN API Error - cancelShippingOrder: ' . $e->getMessage());
            return ['code' => 500, 'message' => 'Failed to cancel shipping order'];
        }
    }
}
    