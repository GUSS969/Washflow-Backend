<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\Notification;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Order::with(['customer', 'user', 'orderDetails.service', 'payments']);

        if ($user->role === 'pelanggan') {
            $customerIds = DB::table('customers')
                ->where('name', $user->name)
                ->pluck('id')
                ->toArray();
            $query->whereIn('customer_id', $customerIds);
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->orderBy('created_at', 'desc')->get();
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isPelanggan = $user->role === 'pelanggan';

        $request->validate([
            'customer_id'   => $isPelanggan ? 'nullable|exists:customers,id' : 'required|exists:customers,id',
            'details'       => 'required|array|min:1',
            'details.*.service_id' => 'required|exists:services,id',
            'details.*.weight'     => 'nullable|numeric|min:0',
            'details.*.qty'        => 'nullable|integer|min:0',
            'payment_status'       => 'nullable|in:lunas,belum_lunas',
            'delivery_type'        => 'nullable|in:antar_sendiri,minta_dijemput',
            'service_type'         => 'nullable|in:cuci_setrika,cuci_saja,setrika_saja',
            'perfume_type'         => 'nullable|in:tanpa_parfum,reguler,antibakteri,lavender,floral,sport',
            'cloth_notes'          => 'nullable|string|max:1000',
            'estimated_ready'      => 'nullable|date',
            'notes'                => 'nullable|string',
        ]);

        $cashierId = $isPelanggan ? null : $user->id;
        $customerId = $request->customer_id;

        if ($isPelanggan && !$customerId) {
            $customer = Customer::firstOrCreate(
                ['name' => $user->name],
                ['phone' => $user->email, 'address' => '-']
            );
            $customerId = $customer->id;
        }

        try {
            $order = DB::transaction(function () use ($request, $cashierId, $customerId, $isPelanggan) {
                $today   = now()->format('Ymd');
                $count   = Order::whereDate('created_at', now()->toDateString())->count();
                $counter = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
                $invoice = "INV-{$today}-{$counter}";

                $order = Order::create([
                    'invoice'        => $invoice,
                    'customer_id'    => $customerId,
                    'user_id'        => $cashierId,
                    'status'         => $isPelanggan ? 'menunggu_konfirmasi' : 'diterima',
                    'payment_status' => $request->payment_status ?? 'belum_lunas',
                    'delivery_type'  => $request->delivery_type ?? 'antar_sendiri',
                    'service_type'   => $request->service_type  ?? 'cuci_setrika',
                    'perfume_type'   => $request->perfume_type  ?? 'reguler',
                    'cloth_notes'    => $request->cloth_notes,
                    'estimated_ready'=> $request->estimated_ready,
                    'notes'          => $request->notes,
                    'total_price'    => 0,
                ]);

                $totalPrice = 0;
                foreach ($request->details as $detail) {
                    $service    = Service::findOrFail($detail['service_id']);
                    $weight     = $detail['weight'] ?? null;
                    $qty        = $detail['qty'] ?? null;
                    $multiplier = ($service->unit === 'kg') ? ($weight ?? 1) : ($qty ?? 1);
                    $subtotal   = $service->price * $multiplier;
                    $totalPrice += $subtotal;

                    OrderDetail::create([
                        'order_id'   => $order->id,
                        'service_id' => $service->id,
                        'weight'     => $weight,
                        'qty'        => $qty,
                        'subtotal'   => $subtotal,
                    ]);
                }

                $order->update(['total_price' => $totalPrice]);

                $owners = User::where('role', 'owner')->get();
                foreach ($owners as $owner) {
                    Notification::create([
                        'user_id' => $owner->id,
                        'title'   => 'Transaksi Baru',
                        'message' => "Order baru {$invoice} senilai Rp " . number_format($totalPrice, 0, ',', '.') . " telah diterima.",
                    ]);
                }

                // Notifikasi ke kasir jika order dari pelanggan
                if ($isPelanggan) {
                    $cashiers = User::where('role', 'kasir')->get();
                    foreach ($cashiers as $cashier) {
                        Notification::create([
                            'user_id' => $cashier->id,
                            'title'   => '📋 Pesanan Baru Masuk',
                            'message' => "Pesanan {$invoice} dari pelanggan perlu dikonfirmasi. Silakan cek dan konfirmasi pesanan.",
                        ]);
                    }
                }

                return $order;
            });

            return response()->json([
                'message' => 'Order created successfully',
                'order'   => Order::with(['customer', 'user', 'orderDetails.service'])->find($order->id)
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Order creation failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $order = Order::with(['customer', 'user', 'orderDetails.service', 'payments'])->find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        return response()->json($order);
    }

    public function update(Request $request, string $id)
    {
        try {
            $order = Order::find($id);
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $request->validate([
                'status'         => 'sometimes|required|in:menunggu_konfirmasi,diterima,dicuci,dikeringkan,disetrika,selesai,sudah_diambil',
                'payment_status' => 'sometimes|required|in:lunas,belum_lunas',
                'estimated_ready'=> 'sometimes|nullable|date',
                'cloth_notes'    => 'sometimes|nullable|string',
            ]);

            if (isset($request->status) && $request->status === 'sudah_diambil') {
                if ($order->payment_status !== 'lunas') {
                    return response()->json([
                        'message' => 'Gagal: Pesanan tidak dapat diserahkan karena belum lunas.',
                        'errors'  => ['status' => ['Pesanan belum lunas']]
                    ], 422);
                }
            }

            $oldStatus = $order->status;
            $order->update($request->only(['status', 'payment_status', 'estimated_ready', 'cloth_notes']));

            if (isset($request->status) && $request->status === 'selesai' && $oldStatus !== 'selesai') {
                $customer     = $order->customer;
                $customerUser = User::where('name', $customer->name)->first();
                if ($customerUser) {
                    Notification::create([
                        'user_id' => $customerUser->id,
                        'title'   => 'Cucian Selesai!',
                        'message' => "Halo {$customer->name}, cucian Anda dengan invoice {$order->invoice} sudah selesai diproses dan siap diambil.",
                    ]);
                }
            }

            if (isset($request->status) && $request->status === 'diterima' && $oldStatus === 'menunggu_konfirmasi') {
                $customer     = $order->customer;
                $customerUser = User::where('name', $customer->name)->first();
                if ($customerUser) {
                    Notification::create([
                        'user_id' => $customerUser->id,
                        'title'   => '✅ Pesanan Dikonfirmasi!',
                        'message' => "Pesanan Anda ({$order->invoice}) telah dikonfirmasi dan diterima kasir. Cucian sedang dalam antrian.",
                    ]);
                }
            }

            return response()->json(['message' => 'Order updated successfully', 'order' => Order::with(['customer', 'user', 'orderDetails.service', 'payments'])->find($order->id)]);
        } catch (\Exception $e) {
            \Log::error('Order update failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, string $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $request->validate([
            'status' => 'required|in:menunggu_konfirmasi,diterima,dicuci,dikeringkan,disetrika,selesai,sudah_diambil',
        ]);

        if ($request->status === 'sudah_diambil' && $order->payment_status !== 'lunas') {
            return response()->json([
                'message' => 'Gagal: Pesanan tidak dapat diserahkan karena belum lunas.',
                'errors'  => ['status' => ['Pesanan belum lunas']]
            ], 422);
        }

        $oldStatus = $order->status;
        $order->update(['status' => $request->status]);

        if ($request->status === 'selesai' && $oldStatus !== 'selesai') {
            $customer     = $order->customer;
            $customerUser = User::where('name', $customer->name)->first();
            if ($customerUser) {
                Notification::create([
                    'user_id' => $customerUser->id,
                    'title'   => 'Cucian Selesai!',
                    'message' => "Halo {$customer->name}, cucian Anda dengan invoice {$order->invoice} sudah selesai diproses dan siap diambil.",
                ]);
            }
        }

        if ($request->status === 'diterima' && $oldStatus === 'menunggu_konfirmasi') {
            $customer     = $order->customer;
            $customerUser = User::where('name', $customer->name)->first();
            if ($customerUser) {
                Notification::create([
                    'user_id' => $customerUser->id,
                    'title'   => '✅ Pesanan Dikonfirmasi!',
                    'message' => "Pesanan Anda ({$order->invoice}) telah dikonfirmasi dan diterima kasir. Cucian sedang dalam antrian.",
                ]);
            }
        }

        return response()->json(['message' => 'Order status updated successfully', 'order' => $order]);
    }

    public function destroy(string $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}