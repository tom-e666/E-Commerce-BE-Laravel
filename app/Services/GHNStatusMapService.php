<?php

namespace App\Services;

class GHNStatusMapService
{
    /**
     * Map GHN shipping status to application shipping status
     *
     * @param string $ghnStatus
     * @return string
     */
    public static function mapShippingStatus(string $ghnStatus): string
    {
        $statusMap = [
            'ready_to_pick' => 'processing',
            'picking' => 'processing',
            'picked' => 'processing',
            'delivering' => 'shipped',
            'delivered' => 'delivered',
            'delivery_failed' => 'pending',
            'cancelled' => 'cancelled',
            'returned' => 'cancelled',
        ];
        
        return $statusMap[$ghnStatus] ?? 'pending';
    }
    
    /**
     * Map GHN shipping status to application order status
     *
     * @param string $ghnStatus
     * @return string|null Returns null if order status shouldn't be updated
     */
    public static function mapOrderStatus(string $ghnStatus): ?string
    {
        $orderStatusMap = [
            'delivered' => 'delivered', 
            'cancelled' => 'cancelled',
            'returned' => 'cancelled'
        ];
        
        // Only return a status if it should trigger an order update
        return $orderStatusMap[$ghnStatus] ?? null;
    }
    
    /**
     * Check if status requires inventory restoration
     *
     * @param string $ghnStatus
     * @return bool
     */
    public static function shouldRestoreInventory(string $ghnStatus): bool
    {
        return in_array($ghnStatus, ['cancelled', 'returned']);
    }
}
