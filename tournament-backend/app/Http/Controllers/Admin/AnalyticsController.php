<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\GameMatch;
use App\Models\GameSuggestion;
use App\Models\Order;
use App\Models\PageView;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $today = now()->toDateString();

        $recent = TournamentRegistration::query()
            ->with(['user:id,username', 'tournament:id,name'])
            ->orderByDesc('registered_at')
            ->limit(10)
            ->get()
            ->map(fn (TournamentRegistration $r) => [
                'username' => $r->user?->username ?? '—',
                'tournament' => $r->tournament?->name ?? '—',
                'registered_at' => $r->registered_at,
            ]);

        return response()->json([
            'totals' => [
                'users' => User::count(),
                'active_tournaments' => Tournament::whereIn('status', ['open', 'ongoing'])->count(),
                'matches_today' => GameMatch::query()
                    ->where(function ($q) use ($today) {
                        $q->whereDate('scheduled_at', $today)
                            ->orWhereDate('completed_at', $today);
                    })
                    ->count(),
                'orders' => Order::count(),
                'open_disputes' => Dispute::whereIn('status', ['open', 'pending', 'under_review'])->count(),
                'pending_suggestions' => GameSuggestion::where('status', 'pending')->count(),
            ],
            'recent_registrations' => $recent,
        ]);
    }

    public function overview(): JsonResponse
    {
        $since = now()->subDays(30);

        return response()->json([
            'totals' => [
                'users' => User::count(),
                'page_views_30d' => PageView::where('viewed_at', '>=', $since)->count(),
                'signups_30d' => User::where('created_at', '>=', $since)->count(),
                'orders_30d' => Order::where('created_at', '>=', $since)->count(),
                'registrations_30d' => TournamentRegistration::where('registered_at', '>=', $since)->count(),
            ],
            'signups_by_day' => $this->countsByDay(User::query(), 'created_at', $since),
            'orders_by_day' => $this->countsByDay(Order::query(), 'created_at', $since),
            'registrations_by_day' => $this->countsByDay(TournamentRegistration::query(), 'registered_at', $since),
            'page_views_by_day' => $this->countsByDay(PageView::query(), 'viewed_at', $since),
            'popular_pages' => PageView::where('viewed_at', '>=', $since)
                ->select('path', DB::raw('COUNT(*) as views'))
                ->groupBy('path')
                ->orderByDesc('views')
                ->limit(10)
                ->get(),
        ]);
    }

    public function tournaments(): JsonResponse
    {
        $since = now()->subDays(30);

        $byTournament = TournamentRegistration::query()
            ->select('tournament_id', DB::raw('COUNT(*) as registrations'))
            ->where('registered_at', '>=', $since)
            ->groupBy('tournament_id')
            ->orderByDesc('registrations')
            ->limit(15)
            ->get()
            ->load('tournament:id,name,slug');

        return response()->json([
            'registrations_by_tournament' => $byTournament,
            'registrations_by_day' => $this->countsByDay(TournamentRegistration::query(), 'registered_at', $since),
        ]);
    }

    private function countsByDay($query, string $column, $since): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite'
            ? "date({$column})"
            : "DATE({$column})";

        return $query
            ->where($column, '>=', $since)
            ->selectRaw("{$dateExpr} as day, COUNT(*) as count")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => ['day' => $row->day, 'count' => (int) $row->count])
            ->all();
    }
}
