<?php
namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ZalopayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ZalopayPaymentStatusListener implements ShouldQueue
{
    public function handle(OrderStatusChanged $event)
    {
        $order = $event->getOrder();
        if ($order->payment_status === 'completed' && $order->payment_method === 'zalopay') {
            Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total_price,
                'payment_method' => 'zalopay',
                'transaction_id' => $order->payment_transaction_id,
                'status' => 'completed',
            ]);
            Log::info('Payment completed for order #' . $order->id);
        }
    }
}