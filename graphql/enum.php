<?php
# Order Status - Main workflow states
enum OrderStatus {
  PENDING @enum(value: "pending")           # Order created, awaiting payment
  PAYMENT_PENDING @enum(value: "payment_pending")  # Payment initiated but not confirmed
  PAID @enum(value: "paid")                 # Payment confirmed
  PROCESSING @enum(value: "processing")     # Order being prepared
  READY_TO_SHIP @enum(value: "ready_to_ship") # Ready for shipping
  SHIPPED @enum(value: "shipped")           # Package dispatched
  OUT_FOR_DELIVERY @enum(value: "out_for_delivery") # Package out for delivery
  DELIVERED @enum(value: "delivered")       # Order completed
  CANCELLED @enum(value: "cancelled")       # Order cancelled
  REFUNDED @enum(value: "refunded")         # Payment refunded
}

# Payment Status - Internal payment states
enum PaymentStatus {
  PENDING @enum(value: "pending")           # Payment not initiated
  INITIATED @enum(value: "initiated")       # Payment gateway called
  PROCESSING @enum(value: "processing")     # Payment being processed
  SUCCESS @enum(value: "success")           # Payment successful
  FAILED @enum(value: "failed")             # Payment failed
  CANCELLED @enum(value: "cancelled")       # Payment cancelled
  REFUND_PENDING @enum(value: "refund_pending") # Refund initiated
  REFUNDED @enum(value: "refunded")         # Refund completed
  COD_PENDING @enum(value: "cod_pending")   # COD selected, awaiting delivery
  COD_COLLECTED @enum(value: "cod_collected") # COD payment collected
}

# Shipping Status - Internal shipping states
enum ShippingStatus {
  NOT_CREATED @enum(value: "not_created")   # Shipping not yet created
  PENDING @enum(value: "pending")           # Shipping order created
  PICKED_UP @enum(value: "picked_up")       # Package picked up by carrier
  IN_TRANSIT @enum(value: "in_transit")     # Package in transit
  OUT_FOR_DELIVERY @enum(value: "out_for_delivery") # Package out for delivery
  DELIVERED @enum(value: "delivered")       # Package delivered
  DELIVERY_FAILED @enum(value: "delivery_failed") # Delivery attempt failed
  RETURNED @enum(value: "returned")         # Package returned to sender
  CANCELLED @enum(value: "cancelled")       # Shipping cancelled
}

# VNPay Response Codes Mapping
enum VNPayResponseCode {
  SUCCESS @enum(value: "00")                # Transaction successful
  INVALID_SIGNATURE @enum(value: "97")      # Invalid signature
  INVALID_DATA @enum(value: "02")           # Invalid data
  CANCELLED @enum(value: "24")              # Transaction cancelled
  INSUFFICIENT_FUNDS @enum(value: "51")     # Insufficient funds
  EXPIRED @enum(value: "75")                # Transaction expired
  PROCESSING_ERROR @enum(value: "99")       # Processing error
}

# ZaloPay Status Codes
enum ZaloPayStatus {
  PENDING @enum(value: "1")                 # Payment pending
  SUCCESS @enum(value: "2")                 # Payment successful
  FAILED @enum(value: "3")                  # Payment failed
}

# GHN Shipping Status Codes  
enum GHNStatus {
  READY_TO_PICK @enum(value: "ready_to_pick")
  PICKING @enum(value: "picking")
  PICKED @enum(value: "picked")
  STORING @enum(value: "storing")
  TRANSPORTING @enum(value: "transporting")
  SORTING @enum(value: "sorting")
  DELIVERING @enum(value: "delivering")
  DELIVERED @enum(value: "delivered")
  DELIVERY_FAIL @enum(value: "delivery_fail")
  WAITING_TO_RETURN @enum(value: "waiting_to_return")
  RETURN @enum(value: "return")
  RETURNED @enum(value: "returned")
  EXCEPTION @enum(value: "exception")
  DAMAGE @enum(value: "damage")
  LOST @enum(value: "lost")
}