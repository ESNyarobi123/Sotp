<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\GuestSession;
use App\Models\Payment;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $publicSnapshot = Cache::remember('home.public-snapshot.v1', now()->addMinutes(5), function (): array {
            $revenueToday = (float) Payment::completed()
                ->whereDate('paid_at', today())
                ->sum('amount');

            return [
                'total_workspaces' => Workspace::query()->count(),
                'total_devices' => Device::query()->count(),
                'active_sessions' => GuestSession::active()->count(),
                'revenue_today' => number_format($revenueToday, 0),
            ];
        });

        $workspaceSnapshot = null;

        $user = auth()->user();
        $workspace = $user?->workspace;

        if ($workspace) {
            $workspaceId = $workspace->id;

            $revenueMonth = (float) Payment::completed()
                ->where('workspace_id', $workspaceId)
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount');

            $revenueTodayWorkspace = (float) Payment::completed()
                ->where('workspace_id', $workspaceId)
                ->whereDate('paid_at', today())
                ->sum('amount');

            $recentDevices = Device::query()
                ->where('workspace_id', $workspaceId)
                ->orderByRaw("FIELD(status, 'online', 'unknown', 'offline')")
                ->orderByDesc('updated_at')
                ->take(2)
                ->get(['id', 'name', 'model', 'status']);

            $recentPayments = Payment::query()
                ->where('workspace_id', $workspaceId)
                ->where('status', 'completed')
                ->latest('paid_at')
                ->take(2)
                ->get(['id', 'phone_number', 'amount', 'paid_at', 'status']);

            $workspaceSnapshot = [
                'workspace' => $workspace,
                'online_users' => GuestSession::active()->where('workspace_id', $workspaceId)->count(),
                'total_devices' => Device::query()->where('workspace_id', $workspaceId)->count(),
                'online_devices' => Device::online()->where('workspace_id', $workspaceId)->count(),
                'total_clients' => GuestSession::query()->where('workspace_id', $workspaceId)->distinct()->count('client_mac'),
                'active_clients' => GuestSession::active()->where('workspace_id', $workspaceId)->distinct()->count('client_mac'),
                'sessions_today' => GuestSession::query()->where('workspace_id', $workspaceId)->whereDate('created_at', today())->count(),
                'revenue_today' => number_format($revenueTodayWorkspace, 0),
                'revenue_month' => number_format($revenueMonth, 0),
                'available_wallet_balance' => number_format((float) $workspace->availableWalletBalance(), 0),
                'recent_devices' => $recentDevices,
                'recent_payments' => $recentPayments,
            ];
        }

        return view('welcome', [
            'publicSnapshot' => $publicSnapshot,
            'workspaceSnapshot' => $workspaceSnapshot,
        ]);
    }
}
