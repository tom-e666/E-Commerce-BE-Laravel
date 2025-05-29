<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\GraphQL\Enums\OrderStatus;
use App\GraphQL\Enums\PaymentStatus;
use App\GraphQL\Enums\PaymentMethod;
use Illuminate\Support\Facades\Log;

class OrderStatusService 
{
    public function updateOrderFromPaymentStatus(Payment $payment): void
    {
        $order = $payment->order;
        if (!$order) {
            Log::warning("No order found for payment", ['payment_id' => $payment->id]);
            return;
        }

        $newOrderStatus = $this->mapPaymentStatusToOrderStatus(
            $payment->payment_status, 
            $payment->payment_method,
            $order->status
        );

        if ($newOrderStatus && $newOrderStatus !== $order->status) {
            $oldStatus = $order->status;
            $order->update(['status' => $newOrderStatus]);
            
            Log::info("Order status updated", [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newOrderStatus,
                'payment_status' => $payment->payment_status,
                'payment_method' => $payment->payment_method
            ]);
        }
    }
    private function mapPaymentStatusToOrderStatus(string $paymentStatus, string $paymentMethod, string $currentOrderStatus): ?string
    {
        // Logic mapping based on payment status and method
        switch ($paymentStatus) {
            case PaymentStatus::COMPLETED:
                return $currentOrderStatus === OrderStatus::PENDING ? OrderStatus::CONFIRMED : null;
                
            case PaymentStatus::FAILED:
                return $currentOrderStatus === OrderStatus::PENDING ? OrderStatus::FAILED : null;
                
            case PaymentMethod::COD:
                return $currentOrderStatus === OrderStatus::PENDING ? OrderStatus::CONFIRMED : null;
                
            default:
                return null; 
        }
    }

    public function canTransitionTo(string $fromStatus, string $toStatus): bool
    {
        $allowedTransitions = [
            OrderStatus::PENDING => [OrderStatus::CONFIRMED, OrderStatus::FAILED, OrderStatus::CANCELLED],
            OrderStatus::CONFIRMED => [OrderStatus::PROCESSING, OrderStatus::CANCELLED],
            OrderStatus::PROCESSING => [OrderStatus::SHIPPING, OrderStatus::CANCELLED],
            OrderStatus::SHIPPING => [OrderStatus::COMPLETED, OrderStatus::CANCELLED],
            OrderStatus::COMPLETED => [], // Final state
            OrderStatus::CANCELLED => [], // Final state
            OrderStatus::FAILED => [OrderStatus::PENDING], // Can retry
        ];

        return in_array($toStatus, $allowedTransitions[$fromStatus] ?? []);
    }
}