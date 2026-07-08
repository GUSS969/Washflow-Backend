<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Users
        $owner = User::create([
            'name' => 'Owner WashFlow',
            'email' => 'owner@washflow.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        $cashier = User::create([
            'name' => 'Kasir WashFlow',
            'email' => 'kasir@washflow.com',
            'password' => Hash::make('password'),
            'role' => 'kasir',
        ]);

        $customerUser = User::create([
            'name' => 'Budi Pelanggan',
            'email' => 'pelanggan@washflow.com',
            'password' => Hash::make('password'),
            'role' => 'pelanggan',
        ]);

        // 2. Create Default Services
        $serviceKiloan = Service::create([
            'service_name' => 'Laundry Kiloan',
            'price' => 8000,
            'unit' => 'kg',
        ]);

        $serviceKemeja = Service::create([
            'service_name' => 'Cuci Kemeja',
            'price' => 12000,
            'unit' => 'item',
        ]);

        $serviceSepatu = Service::create([
            'service_name' => 'Cuci Sepatu Premium',
            'price' => 35000,
            'unit' => 'pasang',
        ]);

        $serviceJas = Service::create([
            'service_name' => 'Dry Cleaning Jas',
            'price' => 50000,
            'unit' => 'item',
        ]);

        // 3. Create Default Customers
        $customerBudi = Customer::create([
            'name' => 'Budi Pelanggan',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 12',
        ]);

        $customerSiti = Customer::create([
            'name' => 'Siti Aminah',
            'phone' => '089876543210',
            'address' => 'Jl. Mawar No. 5',
        ]);

        // 4. Create Mock Orders for testing dashboard/reports
        
        // Order 1: Completed & Paid Order (yesterday)
        $order1 = Order::create([
            'invoice' => 'INV-' . Carbon::yesterday()->format('Ymd') . '-0001',
            'customer_id' => $customerBudi->id,
            'user_id' => $cashier->id,
            'status' => 'sudah_diambil',
            'payment_status' => 'lunas',
            'total_price' => 44000,
            'created_at' => Carbon::yesterday()->setHour(10),
            'updated_at' => Carbon::yesterday()->setHour(17),
        ]);

        OrderDetail::create([
            'order_id' => $order1->id,
            'service_id' => $serviceKiloan->id,
            'weight' => 2.5,
            'subtotal' => 20000,
        ]);

        OrderDetail::create([
            'order_id' => $order1->id,
            'service_id' => $serviceKemeja->id,
            'qty' => 2,
            'subtotal' => 24000,
        ]);

        Payment::create([
            'order_id' => $order1->id,
            'method' => 'cash',
            'amount' => 44000,
            'payment_date' => Carbon::yesterday()->setHour(11),
        ]);

        // Order 2: In-progress & Unpaid Order (today)
        $order2 = Order::create([
            'invoice' => 'INV-' . Carbon::today()->format('Ymd') . '-0001',
            'customer_id' => $customerSiti->id,
            'user_id' => $cashier->id,
            'status' => 'dicuci',
            'payment_status' => 'belum_lunas',
            'total_price' => 35000,
            'created_at' => Carbon::today()->setHour(9),
            'updated_at' => Carbon::today()->setHour(9),
        ]);

        OrderDetail::create([
            'order_id' => $order2->id,
            'service_id' => $serviceSepatu->id,
            'qty' => 1,
            'subtotal' => 35000,
        ]);

        // Order 3: Completed but Unpaid Order (today)
        $order3 = Order::create([
            'invoice' => 'INV-' . Carbon::today()->format('Ymd') . '-0002',
            'customer_id' => $customerBudi->id,
            'user_id' => $cashier->id,
            'status' => 'selesai',
            'payment_status' => 'belum_lunas',
            'total_price' => 50000,
            'created_at' => Carbon::today()->setHour(8),
            'updated_at' => Carbon::today()->setHour(12),
        ]);

        OrderDetail::create([
            'order_id' => $order3->id,
            'service_id' => $serviceJas->id,
            'qty' => 1,
            'subtotal' => 50000,
        ]);
    }
}
