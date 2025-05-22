<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserCredential;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use App\Models\Shipping;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class MockOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        
        // Get all users (except admin)
        $users = UserCredential::where('role', 'user')->get();
        if ($users->isEmpty()) {
            $this->command->error('No users found. Please create some users first.');
            return;
        }
        
        // Get all product IDs (assuming products with IDs 1-200 exist)
        $productIds = range(1, 200);
        
        // Order statuses
        $orderStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        
        // Payment methods
        $paymentMethods = ['vnpay', 'zalopay', 'cod'];
        
        // Shipping statuses that align with order statuses
        $shippingStatuses = [
            'pending' => 'pending',
            'confirmed' => 'processing',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled'
        ];
        
        // Generate 100 mock orders
        $this->command->info('Creating 100 mock orders...');
        
        for ($i = 0; $i < 100; $i++) {
            // Random dates within last 30 days
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 24));
            
            // Select random user
            $user = $users->random();
            
            // Randomly select 1-5 products
            $numProducts = rand(1, 5);
            $selectedProductIds = array_rand(array_flip($productIds), $numProducts);
            if (!is_array($selectedProductIds)) {
                $selectedProductIds = [$selectedProductIds];
            }
            
            // Randomly select order status
            $orderStatus = $orderStatuses[array_rand($orderStatuses)];
            
            // Create order with initial total_price of 0
            $order = new Order();
            $order->user_id = $user->id;
            $order->status = $orderStatus;
            $order->total_price = 0; // Add this line to set initial value
            $order->created_at = $createdAt;
            $order->updated_at = $createdAt;
            $order->save();
            
            // Calculate total price and create order items
            $totalPrice = 0;
            
            foreach ($selectedProductIds as $productId) {
                $product = Product::find($productId);
                
                if ($product) {
                    $quantity = rand(1, 3);
                    $price = $product->price;
                    
                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $orderItem->product_id = $product->id;
                    $orderItem->quantity = $quantity;
                    $orderItem->price = $price;
                    $orderItem->save();
                    
                    $totalPrice += ($price * $quantity);
                }
            }
            
            // Update order total
            $order->total_price = $totalPrice;
            $order->save();
            
            // Create payment
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
            $paymentStatus = $orderStatus === 'cancelled' ? 'cancelled' : 
                            ($orderStatus === 'pending' ? 'pending' : 'completed');
            
            $payment = new Payment();
            $payment->order_id = $order->id;
            $payment->amount = $totalPrice;
            $payment->payment_method = $paymentMethod;
            $payment->payment_status = $paymentStatus;
            $payment->created_at = $createdAt;
            $payment->updated_at = $createdAt;
            
            // Set payment time if payment is completed
            if ($paymentStatus === 'completed') {
                $payment->payment_time = $createdAt->addMinutes(rand(5, 60));
                $payment->transaction_id = 'TRANS_' . strtoupper(Str::random(10));
            }
            
            $payment->save();
            
            // Create shipping for non-cancelled orders
            if ($orderStatus !== 'pending' && $orderStatus !== 'cancelled') {
                $shippingStatus = $shippingStatuses[$orderStatus];
                
                $shipping = new Shipping();
                $shipping->order_id = $order->id;
                $shipping->status = $shippingStatus;
                $shipping->address = $faker->address;
                $shipping->recipient_name = $user->full_name ?: $faker->name;
                $shipping->recipient_phone = $user->phone ?: $faker->phoneNumber;
                $shipping->shipping_method = 'standard';
                $shipping->province_name = $faker->state;
                $shipping->district_name = $faker->city;
                $shipping->ward_name = $faker->ward ?? 'Ward ' . rand(1, 20);
                $shipping->shipping_fee = rand(15000, 50000);
                $shipping->created_at = $createdAt->addHours(1);
                $shipping->updated_at = $createdAt->addHours(1);
                
                if ($orderStatus === 'shipped' || $orderStatus === 'delivered') {
                    $shipping->ghn_order_code = 'GHN' . rand(1000000, 9999999);
                    $shipping->expected_delivery_time = $createdAt->addDays(rand(1, 5))->format('Y-m-d');
                }
                
                $shipping->save();
            }
            
            if ($i % 10 === 0) {
                $this->command->info("Created {$i} orders");
            }
        }
        
        $this->command->info('Completed creating 100 mock orders with various statuses, payments, and shipping data');
    }
}
