<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Shipping;
use App\Models\UserCredentail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZalopayService
{
    protected $appId;
    protected $key1;
    protected $key2;
    protected $apiUrl;

    public function __construct()
    {
        $this->appId = env('ZALOPAY_APP_ID');
        $this->key1 = env('ZALOPAY_KEY_1');
        $this->key2 = env('ZALOPAY_KEY_2');
        $this->apiUrl = env('ZALOPAY_API_URL');
    }
    public function createPaymentOrder($orderId)
    {
        $order = Order::with(['items.product', 'user'])->find($orderId);
        if (!$order) {
            return [
                'code' => 404,
                'message' => 'Order not found',
                'payment_url' => null,
            ];
        }
        
        $user = $order->user;
        if (!$user) {
            return [
                'code' => 404,
                'message' => 'User not found',
                'payment_url' => null,
            ];
        }
        try {
            $appTransId = date('ymd') . '_' . substr(md5(uniqid()), 0, 10);

            $itemsData = json_encode($order->items->map(function($item) {
                return [
                    'item_id' => $item->product_id,
                    'item_name' => $item->product->name,
                    'item_price' => $item->price,
                    'item_quantity' => $item->quantity,
                ];
            })->toArray());

            $embedData = json_encode([
                'redirecturl' => route('payment.callback'),
                'order_id' => $order->id,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone
            ]);
            
            $data = [
                'app_id' => $this->appId,
                'app_trans_id' => $appTransId,
                'app_user' => $user->id,
                'app_time' => time(),
                'amount' => $order->total_price,
                'item' => $itemsData,
                'description' => 'Laptop ECommerce - Thanh toán đơn hàng #' . $order->id,
                'embed_data' => $embedData,
                'bank_code' => 'zalopayapp',
            ];
            
            $mac = hash_hmac(
                'sha256', 
                $data['app_id'] . "|" . 
                $data['app_trans_id'] . "|" . 
                $data['app_user'] . "|" . 
                $data['amount'] . "|" . 
                $data['app_time'] . "|" . 
                $data['embed_data'] . "|" . 
                $data['item'], 
                $this->key1
            );
            
            $data['mac'] = $mac;
            
            Log::info('ZaloPay API Request', ['data' => $data]);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/create", $data);
            
            $responseData = $response->json();
            Log::info('ZaloPay API Response', ['response' => $responseData]);
            
            if ($response->successful() && isset($responseData['return_code']) && $responseData['return_code'] == 1) {
                $order->payment_method = 'zalopay';
                $order->payment_status = 'pending';
                $order->payment_transaction_id = $appTransId;
                $order->save();
                
                return [
                    'code' => 200,
                    'message' => 'Payment order created successfully',
                    'payment_url' => $responseData['order_url'] ?? null,
                    'transaction_id' => $appTransId,
                    'data' => $responseData
                ];
            }
            
            return [
                'code' => 400,
                'message' => 'Failed to create payment: ' . ($responseData['return_message'] ?? 'Unknown error'),
                'payment_url' => null,
                'data' => $responseData
            ];
            
        } catch (\Exception $e) {
            Log::error('ZaloPay API Error', ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'payment_url' => null,
            ];
        }
    }
    
    public function verifyCallback($requestData)
    {
        // Extract data from callback
        $data = $requestData['data'];
        $requestMac = $requestData['mac'];
        
        // Calculate MAC for verification
        $mac = hash_hmac('sha256', $data, $this->key2);
        
        if ($mac !== $requestMac) {
            return false;
        }
        
        // Data is verified, proceed with updating order
        $decodedData = json_decode(base64_decode($data), true);
        
        // Extract app_trans_id from the callback data
        $appTransId = $decodedData['app_trans_id'] ?? '';
        
        // Find order by transaction_id
        $order = Order::where('payment_transaction_id', $appTransId)->first();
        
        if ($order && $decodedData['status'] == 1) {
            $order->payment_status = 'completed';
            $order->status = 'paid';
            $order->save();
            
            event(new \App\Events\OrderStatusChanged($order));
            return true;
        }
        
        return false;
    }
    
    public function getTransactionStatus($appTransId)
    {
        try {
            $data = [
                'app_id' => $this->appId,
                'app_trans_id' => $appTransId,
            ];
            
            // Calculate MAC
            $mac = hash_hmac('sha256', $data['app_id'] . "|" . $data['app_trans_id'], $this->key1);
            $data['mac'] = $mac;
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/query", $data);
            
            return $response->json();
            
        } catch (\Exception $e) {
            Log::error('ZaloPay Query Error', ['error' => $e->getMessage()]);
            return [
                'return_code' => -1,
                'return_message' => $e->getMessage()
            ];
        }
    }
}