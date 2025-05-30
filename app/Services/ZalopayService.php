<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Shipping;
use App\Models\UserCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
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
    public function createPaymentOrder($order, $callbackUrl, $returnUrl)
    {
        $appTransId = $this->generateTransactionId($order->id);
        $data = $this->preparePaymentData($order, $appTransId, $callbackUrl, $returnUrl);
        
        return $this->sendRequest($this->apiUrl . '/create', $data);
    }   
    private function generateTransactionId($orderId):string
    {
        return  date('ymd').'_' . $orderId;
    }
private function preparePaymentData($order, $appTransId, $callbackUrl, $returnUrl)
{   
    $totalWithShipping = $order->total_price;
    if ($order->shipping && $order->shipping->shipping_fee > 0) {
        $totalWithShipping += $order->shipping->shipping_fee;
    }
    
    // Convert to integer - ZaloPay expects amount in Vietnamese dong with no decimals
    $amount = (int)($totalWithShipping * 100) / 100;
    
    // Format order items for the description
    $itemDescription = $order->items->map(function($item) {
        return "Order from Laptop Ecommerce";
    })->join(', ');
    
    $data = [
        'app_id' => (int)$this->appId,
        'app_trans_id' => $appTransId,
        'app_user' => (string)$order->user_id,
        'app_time' => (int) (microtime(true) * 1000),
        'amount' => $amount,
        'currency' => 'VND',
        'callback_url' => $callbackUrl,
        'description' => 'Order #' . $order->id . ': ' . $itemDescription,
        'item' => json_encode([
            'name' => 'Order #' . $order->id,
            'quantity' => 1,
            'price' => $amount
        ]),
        'embed_data' => json_encode([
            'redirecturl' => $returnUrl,
            'order_id' => $order->id,
            'customer_email' => $order->user->email ?? '',
            'customer_phone' => $order->user->phone ?? '',
        ]),
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
    return $data;
}

    private function sendRequest($url, $data)
    {
        try {
            Log::info('ZaloPay Request', ['url' => $url, 'data' => $data]);
            
            // Increase timeout limits to prevent socket hang up
            $response = Http::timeout(30) // 30 seconds timeout
                            ->withHeaders([
                                'Content-Type' => 'application/x-www-form-urlencoded',
                            ])->post($url, $data);
                            
            Log::info('ZaloPay Response', ['response' => $response->json()]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('ZaloPay API Error', [
                'error' => $e->getMessage(),
                'url' => $url,
                'data' => $data
            ]);
            return [
                'return_code' => -1,
                'return_message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify ZaloPay callback and return expected response
     * 
     * ZaloPay expects a JSON response with the following format:
     * - For success: {"return_code": 1, "return_message": "success"}
     * - For failure: {"return_code": 0, "return_message": "failed"}
     * 
     * @param array $requestData
     * @return array Response to be sent back to ZaloPay
     */
    public function verifyCallback($requestData)
    {
        // For testing purposes only
        if (isset($requestData['data']) && strpos($requestData['data'], 'TestMode') !== false) {
            Log::info('Test mode callback accepted');
            
            // Process the order status here for testing
            $decodedData = json_decode(base64_decode($requestData['data']), true);
            $orderId = $decodedData['order_id'] ?? null;
            
            if ($orderId) {
                // Update order payment status
                // This is simplified for testing
                Log::info("Payment successful for order: {$orderId}");
            }
            
            // Return success response for test mode
            return [
                'return_code' => 1,
                'return_message' => 'success'
            ];
        }
        
        // Fix: Use the passed $requestData parameter instead of undefined variable
        if (!isset($requestData['data']) || !isset($requestData['mac'])) {
            Log::error('ZaloPay callback missing required parameters', ['data' => $requestData]);
            return false;
        }
        
        $data = $requestData['data'];
        $requestMac = $requestData['mac'];
        
        $mac = hash_hmac('sha256', $data, $this->key2);
        
        if ($mac !== $requestMac) {
            return false;
        }
        
        $decodedData = json_decode(base64_decode($data), true);
        
        $appTransId = $decodedData['app_trans_id'] ?? '';
        $payment = Payment::where('transaction_id', $appTransId)->first();
        if (!$payment) {
            return false;
        }
        if ($payment && $decodedData['status'] == 1) {
            $payment->payment_status = 'completed';
            $payment->transaction_id = $decodedData['zp_trans_id'] ?? '';
            $payment->save();   
            event(new \App\Events\OrderStatusChanged($payment->order));
            
            // Return success response
            return [
                'return_code' => 1,
                'return_message' => 'success'
            ];
        }
        
        // Return failure response
        return [
            'return_code' => 0,
            'return_message' => 'failed'
        ];
    }
    public function getTransactionStatus($appTransId)
    {
        try {
            $data = [
                'app_id' => $this->appId,
                'app_trans_id' => $appTransId,
            ];
            
            $mac = hash_hmac('sha256', $this->appId . "|" . $appTransId . "|" . $this->key1, $this->key1);
            
            $data['mac'] = $mac;
            
            $response = Http::post("{$this->apiUrl}/query", $data);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('ZaloPay API Error - getTransactionStatus: ' . $e->getMessage());
            return [
                'return_code' => -1,
                'return_message' => 'Failed to get transaction status: ' . $e->getMessage()
            ];
        }
    }
}