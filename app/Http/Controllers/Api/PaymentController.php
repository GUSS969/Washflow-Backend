<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('order.customer')->orderBy('payment_date', 'desc')->get();
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isPelanggan = $user->role === 'pelanggan';

        $request->validate([
            'order_id'      => 'required|exists:orders,id',
            'method'        => 'required|in:cash,qris,transfer,ewallet',
            'amount'        => 'required|numeric|min:0',
            'payment_proof' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'payment_notes' => 'nullable|string|max:500',
        ]);

        try {
            $payment = DB::transaction(function () use ($request, $isPelanggan, $user) {
                $order = Order::findOrFail($request->order_id);

                $proofPath = null;
                if ($request->hasFile('payment_proof')) {
                    $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
                }

                // If customer is paying, it's pending. If Kasir is paying, it's success.
                $paymentStatus = $isPelanggan ? 'pending' : 'success';
                
                $order->total_paid += $request->amount;
                if ($order->total_paid >= $order->total_price) {
                    $orderPaymentStatus = 'lunas';
                } else {
                    $orderPaymentStatus = 'belum_lunas';
                }

                $payment = Payment::create([
                    'order_id'      => $request->order_id,
                    'user_id'       => $isPelanggan ? null : $user->id,
                    'method'        => $request->method,
                    'amount'        => $request->amount,
                    'payment_date'  => now(),
                    'payment_proof' => $proofPath,
                    'payment_notes' => $request->payment_notes,
                    'status'        => $paymentStatus,
                ]);

                $order->update([
                    'payment_status' => $orderPaymentStatus,
                    'total_paid'     => $order->total_paid,
                ]);

                $methodLabel = match($request->method) {
                    'cash'     => 'Cash',
                    'qris'     => 'QRIS',
                    'transfer' => 'Transfer Bank',
                    'ewallet'  => 'E-Wallet',
                    default    => $request->method,
                };

                // Notify based on role
                if ($isPelanggan) {
                    $notificationMessage = "Pelanggan telah melakukan pembayaran untuk order {$order->invoice} via {$methodLabel}. Menunggu konfirmasi.";
                    $owners = User::whereIn('role', ['owner', 'kasir'])->get();
                    foreach ($owners as $u) {
                        Notification::create([
                            'user_id' => $u->id,
                            'title'   => 'Pembayaran Perlu Dikonfirmasi',
                            'message' => $notificationMessage,
                        ]);
                    }
                } else {
                    $notificationMessage = "Pembayaran untuk order {$order->invoice} sebesar Rp "
                        . number_format($request->amount, 0, ',', '.')
                        . " via {$methodLabel} telah dikonfirmasi Kasir.";
                    
                    $customerUser = User::where('name', $order->customer->name)->first();
                    if ($customerUser) {
                        Notification::create([
                            'user_id' => $customerUser->id,
                            'title'   => 'Pembayaran Dikonfirmasi',
                            'message' => "Terima kasih! Pembayaran Anda untuk order {$order->invoice} sudah kami terima.",
                        ]);
                    }
                }

                return $payment;
            });

            $saved = Payment::with('order')->find($payment->id);
            if ($saved->payment_proof) {
                $saved->payment_proof_url = Storage::url($saved->payment_proof);
            }

            return response()->json([
                'message' => 'Payment processed successfully',
                'payment' => $saved
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Payment store failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to process payment',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function confirm(Request $request, string $id)
    {
        $user = $request->user();
        if ($user->role === 'pelanggan') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $order = Order::find($payment->order_id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            DB::transaction(function () use ($payment, $order, $user) {
                $payment->update([
                    'status' => 'success',
                    'user_id' => $user->id
                ]);

                $order->total_paid += $payment->amount;
                if ($order->total_paid >= $order->total_price) {
                    $orderPaymentStatus = 'lunas';
                } else {
                    $orderPaymentStatus = 'belum_lunas';
                }

                $order->update([
                    'payment_status' => $orderPaymentStatus,
                    'total_paid'     => $order->total_paid,
                ]);

                $customerUser = User::where('name', $order->customer->name)->first();
                if ($customerUser) {
                    Notification::create([
                        'user_id' => $customerUser->id,
                        'title'   => 'Pembayaran Dikonfirmasi',
                        'message' => "Terima kasih! Pembayaran Anda untuk order {$order->invoice} telah dikonfirmasi oleh Kasir.",
                    ]);
                }
            });

            return response()->json([
                'message' => 'Payment confirmed successfully',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to confirm payment', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $payment = Payment::with('order.customer')->find($id);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        if ($payment->payment_proof) {
            $payment->payment_proof_url = Storage::url($payment->payment_proof);
        }
        return response()->json($payment);
    }
}