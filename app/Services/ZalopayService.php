<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Shipping;
use App\Models\UserCredential;
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
    public function createPaymentOrder($order, $callbackUrl, $returnUrl)
    {
        $appTransId = $this->generateTransactionId();
        $data = $this->preparePaymentData($order, $appTransId, $callbackUrl, $returnUrl);
        
        return $this->sendRequest($this->apiUrl . '/create', $data);
    }   
    private function generateTransactionId()
    {
        return 'ZP' . time() . rand(1000, 9999);
    }
    private function preparePaymentData($order, $appTransId, $callbackUrl, $returnUrl)
    {   
        $totalWithShipping = $order->total_price;
        if ($order->shipping && $order->shipping->shipping_fee > 0) {
            $totalWithShipping += $order->shipping->shipping_fee;
        }    
        $data = [
            'app_id' => $this->appId,
            'app_trans_id' => $appTransId,
            'app_user' => $order->user_id,
            'app_time' => time(),
            'amount' => $totalWithShipping,
            'item' => json_encode($order->items->map(function($item) {
                return [
                    'item_id' => $item->product_id,
                    'item_name' => $item->product->name,
                    'item_price' => $item->price,
                    'item_quantity' => $item->quantity,
                ];
            })->toArray()),
            'description' => 'Order #' . $order->id,
            'embed_data' => json_encode([
                'redirecturl' => $returnUrl,
                'order_id' => $order->id,
                'customer_email' => $order->user->email??'',
                'customer_phone' => $order->user->phone??'',
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
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $data);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('ZaloPay API Error', ['error' => $e->getMessage()]);
            return [
                'return_code' => -1,
                'return_message' => $e->getMessage()
            ];
        }
    }
    public function verifyCallback($requestData)
    {
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
            $payment->status = 'completed';
            $payment->transaction_id = $decodedData['zp_trans_id'] ?? '';
            $payment->save();   
            event(new \App\Events\OrderStatusChanged($payment->order));
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