<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $base = DailyReport::query()->visibleTo($user);

        $today      = now()->toDateString();
        $weekStart  = now()->startOfWeek()->toDateString();
        $weekEnd    = now()->endOfWeek()->toDateString();

        $stats = [
            'today_reports'    => (clone $base)->whereDate('report_date', $today)->count(),
            'week_reports'     => (clone $base)->whereBetween('report_date', [$weekStart, $weekEnd])->count(),
            'today_submitters' => (clone $base)->whereDate('report_date', $today)->distinct('user_id')->count('user_id'),
            'overtime_count'   => (clone $base)->whereDate('report_date', $today)->where('overtime_status', true)->count(),
            'need_help_count'  => (clone $base)->whereDate('report_date', $today)->where('need_leader_help', true)->count(),
        ];

        $recentReports = (clone $base)
            ->with('user')
            ->latest('report_date')
            ->latest('id')
            ->limit(8)
            ->get();

        $myReportToday = DailyReport::where('user_id', $user->id)
            ->whereDate('report_date', $today)
            ->first();

        return view('dashboard', compact('stats', 'recentReports', 'myReportToday'));
    }
}
