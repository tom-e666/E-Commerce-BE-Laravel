<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shipping;
use App\Services\GHNStatusMapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config; // Added for config access
use Illuminate\Support\Str; // Added for Str::equals

class GHNWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('GHN Webhook received', $request->all());
        
        // Validate request data
        $data = $request->all();
        if (!isset($data['order_code']) || !isset($data['status'])) {
            return response()->json(['error' => 'Invalid webhook data'], 400);
        }
        
        // Find shipping by GHN order code
        $shipping = Shipping::where('ghn_order_code', $data['order_code'])->first();
        if (!$shipping) {
            return response()->json(['error' => 'Shipping not found'], 404);
        }
        
        // Process status update using our mapping service
        $ghnStatus = $data['status'];
        $appShippingStatus = GHNStatusMapService::mapShippingStatus($ghnStatus);
        $appOrderStatus = GHNStatusMapService::mapOrderStatus($ghnStatus);
        
        DB::beginTransaction();
        try {
            // Update shipping status
            $shipping->status = $appShippingStatus;
            $shipping->save();
            
            // Update order status if needed
            if ($appOrderStatus) {
                $order = Order::find($shipping->order_id);
                if ($order) {
                    $previousOrderStatus = $order->status;
                    $order->status = $appOrderStatus;
                    $order->save();
                    
                    // Restore inventory if needed
                    if (GHNStatusMapService::shouldRestoreInventory($ghnStatus)) {
                        foreach ($order->items as $item) {
                            $product = $item->product;
                            if ($product) {
                                $product->stock += $item->quantity;
                                $product->save();
                            }
                        }
                    }
                    
                    Log::info('Order status updated via GHN webhook', [
                        'order_id' => $order->id,
                        'previous_status' => $previousOrderStatus,
                        'new_status' => $appOrderStatus,
                        'ghn_status' => $ghnStatus
                    ]);
                }
            }
            
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GHN Webhook error', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            return response()->json(['error' => 'Failed to process webhook'], 500);
        }
    }
}
