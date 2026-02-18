<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeDashboardController extends Controller
{
    public function index()
    {
        $employee = Auth::user();
        return view('employee.dashboard', compact('employee'));
    }

    public function totalIncome()
    {
        // KPI Summary
        $totalIncome = Order::sum('sum_price');
        $thisMonthIncome = Order::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('sum_price');
        $completedOrders = Order::where('status', OrderStatus::COMPLETED)->count();
        $averageOrderValue = Order::count() > 0 ? $totalIncome / Order::count() : 0;

        // Monthly income for chart (last 12 months)
        $monthlyIncome = Order::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(sum_price) as total')
            )
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill missing months with 0
        $chartLabels = [];
        $chartData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $chartLabels[] = $date->format('M Y');
            $found = $monthlyIncome->firstWhere('month', $key);
            $chartData[] = $found ? (float) $found->total : 0;
        }

        // Income by status
        $incomeByStatus = Order::select(
                'status',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(sum_price) as total_amount')
            )
            ->groupBy('status')
            ->orderByDesc('total_amount')
            ->get();

        // Top 10 orders by value
        $topOrders = Order::with('user')
            ->orderByDesc('sum_price')
            ->limit(10)
            ->get();

        $dashboardRoute = 'employee.dashboard';

        return view('components.total-income', compact(
            'totalIncome',
            'thisMonthIncome',
            'completedOrders',
            'averageOrderValue',
            'chartLabels',
            'chartData',
            'incomeByStatus',
            'topOrders',
            'dashboardRoute'
        ));
    }

    public function invoiceData(Request $request)
    {
        $query = Order::with(['user', 'orderProductServices.product', 'orderProductServices.productService', 'orderDelivery']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // KPI summaries (applied on filtered query)
        $filteredQuery = clone $query;
        $totalInvoices = $filteredQuery->count();
        $totalRevenue = (clone $query)->sum('sum_price');
        $avgInvoiceValue = $totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0;

        // Paginated results
        $invoices = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $statuses = [
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::COMPLETED,
            OrderStatus::CANCELLED,
        ];

        $dashboardRoute = 'employee.dashboard';
        $currentRoute = 'employee.invoice-data';

        return view('components.invoice-data', compact(
            'invoices',
            'totalInvoices',
            'totalRevenue',
            'avgInvoiceValue',
            'statuses',
            'dashboardRoute',
            'currentRoute'
        ));
    }
}
