<?php
class GHNWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        try
        {
            $data = $request->all();
        Log::info('GHN Webhook Received: ', $data);
        $orderCode = $data['order_code'] ?? null;
        $status = $data['status'] ?? null;
        if(!$orderCode || !$status) {
            return response()->json(['message' => 'Invalid data'], 400);
        }
        $shipping = Shipping::where('ghn_order_code', $orderCode)->first();
        if (!$shipping) {
            return response()->json(['message' => 'Shipping not found'], 404);
        }
        $shipping->status = $status;
        $shipping->save();
        if(in_array($status, ['delivered', 'cancelled', 'returned'])) {
            $order = Order::find($shipping->order_id);
            if ($order) {
                $order->status = $status === 'delivered' ? 'completed' : 'cancelled';
                $order->save();
            }
            return response()->json(['message' => 'Shipping status updated successfully'], 200);
        }
        } catch (\Exception $e) {
            Log::error('GHN Webhook Error: ', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
