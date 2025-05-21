<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\UserCredential;
use App\Models\Order;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(UserCredential $user): bool
    {
        // Only admin and staff can view all payments
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view a specific payment.
     */
    public function view(UserCredential $user, Payment $payment): bool
    {
        // Users can view their own payments, admin and staff can view any payment
        $order = Order::find($payment->order_id);
        return ($order && $order->user_id === $user->id) || $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can create payments.
     */
    public function create(UserCredential $user, Order $order): bool
    {
        // Users can create payments for their own orders, admin and staff can create for any order
        return $order->user_id === $user->id || $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update payments.
     */
    public function update(UserCredential $user, Payment $payment): bool
    {
        // Only admin and staff can update payment status
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can delete payments.
     */
    public function delete(UserCredential $user, Payment $payment): bool
    {
        // Only admin can delete payments
        return $user->isAdmin();
    }

    public function verify(UserCredential $user, Payment $payment): bool
    {
        $order = Order::find($payment->order_id);
        return $order && $order->user_id === $user->id;
    }
}