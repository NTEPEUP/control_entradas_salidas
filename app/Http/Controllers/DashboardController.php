<?php


namespace App\Http\Controllers;
use App\Models\AccessLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total'      => AccessLog::count(),
            'granted'    => AccessLog::where('status', 'granted')->count(),
            'denied'     => AccessLog::where('status', 'denied')->count(),
            'rate'       => AccessLog::count() > 0 ? round((AccessLog::where('status', 'granted')->count() / AccessLog::count()) * 100, 1) : 0,
        ];
        $recent = AccessLog::latest()->take(10)->get();
        return view('dashboard', compact('stats', 'recent'));
    }
}
