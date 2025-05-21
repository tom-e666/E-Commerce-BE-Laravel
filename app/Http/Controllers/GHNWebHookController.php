<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Shipping;
use App\Models\Order;
use Illuminate\Support\Facades\Config; // Added for config access
use Illuminate\Support\Str; // Added for Str::equals

class GHNWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        try {
            // Verify Webhook Signature (Example)
            // This is a crucial security step. GHN should provide a secret key
            // and specify how they generate the signature (e.g., HMAC-SHA256 of the body).
            $ghnWebhookSecret = Config::get('services.ghn.webhook_secret');
            if ($ghnWebhookSecret) {
                $signature = $request->header('X-Ghn-Signature'); // Adjust header name if different
                if (!$signature) {
                    Log::warning('GHN Webhook: Missing signature');
                    return response()->json(['message' => 'Missing signature'], 401);
                }

                $payload = $request->getContent();
                $computedSignature = hash_hmac('sha256', $payload, $ghnWebhookSecret);

                if (!Str::equals($signature, $computedSignature)) {
                    Log::warning('GHN Webhook: Invalid signature', [
                        'received_signature' => $signature,
                        'computed_signature' => $computedSignature,
                    ]);
                    return response()->json(['message' => 'Invalid signature'], 403);
                }
                Log::info('GHN Webhook: Signature verified successfully.');
            } else {
                Log::warning('GHN Webhook: Secret not configured. Skipping signature verification. THIS IS INSECURE FOR PRODUCTION.');
            }

            $data = $request->all();
            Log::info('GHN Webhook Received: ', $data);
            $orderCode = $data['order_code'] ?? null;
            $status = $data['status'] ?? null;
            
            if(!$orderCode || !$status) {
                Log::warning('GHN Webhook: Invalid data - missing order_code or status.', $data);
                return response()->json(['message' => 'Invalid data'], 400);
            }
            
            $shipping = Shipping::where('ghn_order_code', $orderCode)->first();
            if (!$shipping) {
                Log::warning('GHN Webhook: Shipping not found for order_code.', ['order_code' => $orderCode]);
                return response()->json(['message' => 'Shipping not found'], 404);
            }
            
            $shipping->status = $status;
            $shipping->save();
            Log::info("GHN Webhook: Updated shipping status for ghn_order_code {$orderCode} to {$status}.");
            
            if(in_array($status, ['delivered', 'cancelled', 'returned'])) {
                $order = Order::find($shipping->order_id);
                if ($order) {
                    $previousOrderStatus = $order->status;
                    // Update order status based on shipping status
                    $newOrderStatus = $status === 'delivered' ? 'completed' : 'cancelled';
                    $order->status = $newOrderStatus;
                    $order->save();
                    Log::info("GHN Webhook: Updated order {$order->id} status from {$previousOrderStatus} to {$newOrderStatus}.");
                    
                    // If cancelled or returned, restore product stock
                    if ($status === 'cancelled' || $status === 'returned') {
                        foreach ($order->items as $item) {
                            $product = $item->product; // Assuming OrderItem has a 'product' relationship
                            if ($product) {
                                $product->stock += $item->quantity;
                                $product->save();
                                Log::info("GHN Webhook: Restored {$item->quantity} stock for product {$product->id} (Order {$order->id}).");
                            } else {
                                Log::warning("GHN Webhook: Product not found for order item {$item->id} while trying to restore stock.");
                            }
                        }
                    }
                } else {
                    Log::warning("GHN Webhook: Order not found for shipping_id {$shipping->id} (order_id {$shipping->order_id}) when trying to update final status.");
                }
                // The response for final status updates was inside the 'if ($order)' block, moved out.
            }
            // Moved the successful response outside the final status block to ensure it's always sent if no error before.
            return response()->json(['message' => 'Shipping status updated successfully'], 200);

        } catch (\Exception $e) {
            Log::error('GHN Webhook Error: ', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
