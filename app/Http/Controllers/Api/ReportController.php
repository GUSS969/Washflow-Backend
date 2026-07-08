<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    private function getReportData($startDate, $endDate)
    {
        $orders = Order::with(['customer', 'user', 'payments'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalOrders = $orders->count();
        $ordersProcessed = $orders->whereIn('status', ['diterima', 'dicuci', 'dikeringkan', 'disetrika'])->count();
        $ordersCompleted = $orders->whereIn('status', ['selesai', 'sudah_diambil'])->count();
        
        // Revenue is based on orders that are fully paid
        $totalRevenue = $orders->where('payment_status', 'lunas')->sum('total_price');

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_orders' => $totalOrders,
            'orders_processed' => $ordersProcessed,
            'orders_completed' => $ordersCompleted,
            'total_revenue' => $totalRevenue,
            'orders' => $orders
        ];
    }

    private function checkOwner(Request $request)
    {
        if ($request->user()->role !== 'owner') {
            abort(response()->json(['message' => 'Unauthorized. Only owners can access reports.'], 403));
        }
    }

    private function handleResponse(Request $request, $data, $reportName)
    {
        if ($request->input('format') === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf', array_merge($data, ['report_name' => $reportName]));
            return $pdf->download("report_{$reportName}_" . now()->format('Ymd_His') . ".pdf");
        }

        return response()->json($data);
    }

    public function daily(Request $request)
    {
        $this->checkOwner($request);

        $startDate = Carbon::today()->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        $data = $this->getReportData($startDate, $endDate);
        return $this->handleResponse($request, $data, 'harian');
    }

    public function weekly(Request $request)
    {
        $this->checkOwner($request);

        $startDate = Carbon::now()->subDays(6)->startOfDay(); // last 7 days including today
        $endDate = Carbon::now()->endOfDay();

        $data = $this->getReportData($startDate, $endDate);
        return $this->handleResponse($request, $data, 'mingguan');
    }

    public function monthly(Request $request)
    {
        $this->checkOwner($request);

        $startDate = Carbon::now()->startOfMonth()->startOfDay();
        $endDate = Carbon::now()->endOfMonth()->endOfDay();

        $data = $this->getReportData($startDate, $endDate);
        return $this->handleResponse($request, $data, 'bulanan');
    }

    public function yearly(Request $request)
    {
        $this->checkOwner($request);

        $startDate = Carbon::now()->startOfYear()->startOfDay();
        $endDate = Carbon::now()->endOfYear()->endOfDay();

        $data = $this->getReportData($startDate, $endDate);
        return $this->handleResponse($request, $data, 'tahunan');
    }
}
