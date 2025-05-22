<?php

namespace App\Http\Controllers;

use App\Services\ZalopayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZalopayController extends Controller
{
    protected $zalopayService;

    public function __construct(ZalopayService $zalopayService)
    {
        $this->zalopayService = $zalopayService;
    }
    
    public function callback(Request $request)
    {
        try {
            // Log all request information for debugging
            Log::info('ZaloPay Callback received', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'query_params' => $request->query(),
                'body_params' => $request->post(),
                'raw_content' => $request->getContent(),
                'all_data' => $request->all(),
                'uri' => $request->getRequestUri()
            ]);

            // Handle different content types and request methods
            $data = [];
            $mac = '';

            // Parse based on content type
            if ($request->isJson()) {
                // Handle JSON content
                $jsonData = $request->json()->all();
                $data = $jsonData['data'] ?? null;
                $mac = $jsonData['mac'] ?? null;
            } else if (strpos($request->header('Content-Type'), 'application/x-www-form-urlencoded') !== false) {
                // Handle form URL encoded content
                $data = $request->input('data');
                $mac = $request->input('mac');
                
                // Try parsing the raw content if data isn't found
                if (empty($data) || empty($mac)) {
                    parse_str($request->getContent(), $parsedContent);
                    $data = $parsedContent['data'] ?? null;
                    $mac = $parsedContent['mac'] ?? null;
                }
            } else {
                // Last resort - try to find parameters anywhere
                $data = $request->input('data') ?? $request->query('data');
                $mac = $request->input('mac') ?? $request->query('mac');
            }

            // Check if we have the necessary data
            if (empty($data) || empty($mac)) {
                Log::warning('ZaloPay callback missing required parameters');
                return response()->json([
                    'return_code' => 0, 
                    'return_message' => 'Missing required parameters'
                ]);
            }

            // Create an array with the necessary data for verification
            $requestData = [
                'data' => $data,
                'mac' => $mac
            ];
            
            // Process the callback with the zalopay service
            $result = $this->zalopayService->verifyCallback($requestData);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('ZaloPay callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'return_code' => 0, 
                'return_message' => 'internal error: ' . $e->getMessage()
            ]);
        }
    }
    public function return(Request $request)
    {
        $orderId = $request->input('order_id');
        $status = $request->input('status'); 
        Log::info('User returned from ZaloPay', ['order_id' => $orderId, 'status' => $status]);
        
        $frontendUrl = env('FRONTEND_URL');
        return redirect()->away($frontendUrl . "/checkout/result?order_id={$orderId}&status={$status}");
    }
    public function paymentStatus($transactionId)
    {
        $result = $this->zalopayService->getTransactionStatus($transactionId);
        return response()->json($result);
    }
}