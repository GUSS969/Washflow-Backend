<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan {{ ucfirst($report_name) }} WashFlow</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #1e3a8a;
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #6b7280;
            font-size: 14px;
        }
        .meta-info {
            margin-bottom: 20px;
            font-size: 11px;
            color: #4b5563;
        }
        .meta-info table {
            width: 100%;
        }
        .meta-info td {
            padding: 2px 0;
        }
        .summary-boxes {
            margin-bottom: 30px;
            width: 100%;
        }
        .summary-boxes td {
            width: 25%;
            padding: 5px;
        }
        .summary-card {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
        }
        .summary-card .title {
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .summary-card .value {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
        }
        .summary-card.revenue {
            background-color: #ecfdf5;
            border-color: #a7f3d0;
        }
        .summary-card.revenue .value {
            color: #047857;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .orders-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            font-size: 11px;
        }
        .orders-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        .orders-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>WASHFLOW</h1>
        <p>Smart Laundry Management System</p>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td><strong>Jenis Laporan:</strong> Laporan {{ ucfirst($report_name) }}</td>
                <td style="text-align: right;"><strong>Periode:</strong> {{ $start_date }} s/d {{ $end_date }}</td>
            </tr>
            <tr>
                <td><strong>Dibuat Pada:</strong> {{ now()->format('d M Y H:i:s') }}</td>
                <td style="text-align: right;"><strong>Status Pembayaran:</strong> Lunas (Pendapatan Terhitung)</td>
            </tr>
        </table>
    </div>

    <table class="summary-boxes">
        <tr>
            <td>
                <div class="summary-card">
                    <div class="title">Total Order</div>
                    <div class="value">{{ $total_orders }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="title">Diproses</div>
                    <div class="value">{{ $orders_processed }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="title">Selesai</div>
                    <div class="value">{{ $orders_completed }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card revenue">
                    <div class="title">Total Pendapatan</div>
                    <div class="value">Rp {{ number_format($total_revenue, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <h3>Daftar Transaksi</h3>
    <table class="orders-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 20%;">Invoice</th>
                <th style="width: 25%;">Pelanggan</th>
                <th style="width: 15%;">Status Cucian</th>
                <th style="width: 15%;">Pembayaran</th>
                <th style="width: 20%; text-align: right;">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $order->invoice }}</strong><br><span style="font-size: 8px; color: #9ca3af;">{{ $order->created_at->format('d/m/Y H:i') }}</span></td>
                    <td>{{ $order->customer->name }}<br><span style="font-size: 8px; color: #9ca3af;">{{ $order->customer->phone }}</span></td>
                    <td>
                        <span class="badge {{ $order->status == 'selesai' || $order->status == 'sudah_diambil' ? 'badge-success' : 'badge-warning' }}">
                            {{ $order->status }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $order->payment_status == 'lunas' ? 'badge-success' : 'badge-warning' }}">
                            {{ $order->payment_status }}
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: bold;">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #9ca3af; padding: 20px;">Tidak ada transaksi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        WashFlow Smart Laundry Management System - Laporan Keuangan Otomatis
    </div>
</body>
</html>
