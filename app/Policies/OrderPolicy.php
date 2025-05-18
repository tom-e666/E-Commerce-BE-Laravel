<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\UserCredential;

class OrderPolicy
{
    public function view(UserCredential $user, Order $order)
    {
        return $user->isAdmin() || $user->isStaff() || $order->user_id === $user->id;
    }

    public function update(UserCredential $user, Order $order)
    {
        return $user->isAdmin() || $user->isStaff();
    }
    
    public function cancel(UserCredential $user, Order $order)
    {
        if ($order->user_id === $user->id && $order->status === 'pending') {
            return true;
        }
        return $user->isAdmin() || $user->isStaff();
    }
}