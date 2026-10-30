<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\CommodityCategory;
use App\Models\CommodityUnit;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the main administrator control panel dashboard.
     */
    public function index(Request $request): View
    {
        $currentTab = $request->query('tab', 'dashboard');

        // 1. Users list with profiles (paginated for performance)
        $dbUsers = User::with(['umkmProfile', 'supplierProfile'])
            ->whereIn('role', ['umkm', 'supplier'])
            ->latest('created_at')
            ->paginate(10, ['*'], 'users_page')
            ->withQueryString();

        $users = $dbUsers->through(function ($u) {
            $name = $u->role === 'supplier'
                ? ($u->supplierProfile?->company_name ?? $u->supplierProfile?->owner_name ?? explode('@', $u->email)[0])
                : ($u->umkmProfile?->business_name ?? $u->umkmProfile?->owner_name ?? explode('@', $u->email)[0]);

            $phone = $u->role === 'supplier'
                ? ($u->supplierProfile?->phone ?? '081987654321')
                : ($u->umkmProfile?->phone ?? '081234567890');

            $statusFormatted = match (strtolower((string) $u->status)) {
                'active', 'aktif' => 'AKTIF',
                'pending', 'pending_verifikasi' => 'PENDING_VERIFIKASI',
                default => 'NONAKTIF',
            };

            return [
                'raw_id' => $u->id,
                'id' => 'USR-'.sprintf('%03d', $u->id),
                'name' => $name,
                'type' => $u->role === 'supplier' ? 'PEMASOK' : 'UMKM',
                'email' => $u->email,
                'phone' => $phone,
                'status' => $statusFormatted,
                'registered' => $u->created_at ? $u->created_at->format('d/m/Y') : date('d/m/Y'),
            ];
        });

        // 2. Metrics & Counts (Agregasi database murni dalam 1 query efisien)
        $userStats = User::selectRaw("
            COUNT(CASE WHEN role = 'umkm' THEN 1 END) as total_umkm,
            COUNT(CASE WHEN role = 'umkm' AND status = 'active' THEN 1 END) as active_umkm,
            COUNT(CASE WHEN role = 'supplier' THEN 1 END) as total_suppliers,
            COUNT(CASE WHEN role = 'supplier' AND status = 'active' THEN 1 END) as active_suppliers,
            COUNT(CASE WHEN status IN ('pending', 'PENDING_VERIFIKASI') THEN 1 END) as pending_users
        ")->first();

        $totalUmkm = (int) ($userStats->total_umkm ?? 0);
        $activeUmkm = (int) ($userStats->active_umkm ?? 0);
        $totalSuppliers = (int) ($userStats->total_suppliers ?? 0);
        $activeSuppliers = (int) ($userStats->active_suppliers ?? 0);
        $pendingUsersCount = (int) ($userStats->pending_users ?? 0);
        $totalCategories = CommodityCategory::count();

        // 3. Donut Chart: Distribusi User (Berdasarkan Role)
        $totalUsers = max(1, $totalUmkm + $totalSuppliers);
        $umkmPercent = round(($totalUmkm / $totalUsers) * 100);
        $supplierPercent = round(($totalSuppliers / $totalUsers) * 100);

        $circumference = 240;
        if ($totalUmkm > 0 && $totalSuppliers > 0) {
            $availableLength = $circumference - 20;
            $umkmDash = max(20, round(($totalUmkm / $totalUsers) * $availableLength));
            $supplierDash = max(20, round(($totalSuppliers / $totalUsers) * $availableLength));
            $supplierOffset = -($umkmDash + 15);
        } elseif ($totalUmkm > 0) {
            $umkmDash = 230;
            $supplierDash = 0;
            $supplierOffset = 0;
        } elseif ($totalSuppliers > 0) {
            $umkmDash = 0;
            $supplierDash = 230;
            $supplierOffset = 0;
        } else {
            $umkmDash = 115;
            $supplierDash = 115;
            $supplierOffset = -125;
        }

        // 4. Komoditas per Kategori (Agregasi kelompok kategori acuan)
        $commodityCounts = Commodity::selectRaw('LOWER(category) as cat_lower, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'cat_lower');

        $dbCategories = CommodityCategory::orderBy('name')->get();
        $categories = $dbCategories->map(function ($cat, $index) use ($commodityCounts) {
            $count = (int) ($commodityCounts[strtolower($cat->name)] ?? 0);

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'code' => 'KAT-'.sprintf('%02d', $index + 1),
                'totalCommodities' => $count,
                'description' => $cat->description ?: 'Kategori komoditas bahan baku pangan standar.',
            ];
        });

        $maxCommodityCount = max(1, $categories->max('totalCommodities') ?: 1);

        // 5. Units & Total Commodities
        $dbUnits = CommodityUnit::latest()->get();
        $units = $dbUnits->map(function ($unit) {
            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'symbol' => $unit->symbol ?: $unit->name,
                'type' => $unit->description ?: 'Standar',
            ];
        });
        $totalCommodities = Commodity::count();

        // 6. Pertumbuhan Pengguna (Berdasarkan pendaftaran per bulan di tahun berjalan)
        $currentYear = now()->year;
        $usersThisYear = User::select('created_at')
            ->whereYear('created_at', $currentYear)
            ->get();

        $monthCounts = array_fill(1, 12, 0);
        foreach ($usersThisYear as $u) {
            if ($u->created_at) {
                $monthCounts[(int) $u->created_at->format('n')]++;
            }
        }

        $maxGrowth = max(1, max($monthCounts));
        $monthlyBars = [];
        $shortMonthLabels = [1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR', 5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGU', 9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES'];

        $barIdx = 0;
        foreach ($monthCounts as $m => $cnt) {
            $barHeight = $cnt > 0 ? max(14, round(($cnt / $maxGrowth) * 85)) : 6;
            $x = 30 + ($barIdx * 22);
            $monthlyBars[] = [
                'month_num' => $m,
                'label' => $shortMonthLabels[$m],
                'count' => $cnt,
                'x' => $x,
                'y' => 120 - $barHeight,
                'height' => $barHeight,
                'is_current' => $m === (int) now()->format('n'),
            ];
            $barIdx++;
        }

        // 7. Tren Aktivitas Minggu Ini & Bulan Ini (Line Chart Wave Bezier)
        $buildWavePaths = function (array $points, int $baselineY = 215) {
            if (empty($points)) {
                return ['line' => '', 'area' => ''];
            }
            $path = "M {$points[0]['x']},{$points[0]['y']}";
            for ($i = 0; $i < count($points) - 1; $i++) {
                $p0 = $points[$i];
                $p1 = $points[$i + 1];
                $cx = round(($p0['x'] + $p1['x']) / 2);
                $path .= " C {$cx},{$p0['y']} {$cx},{$p1['y']} {$p1['x']},{$p1['y']}";
            }
            $lastX = end($points)['x'];
            $firstX = $points[0]['x'];
            $area = "{$path} L {$lastX},{$baselineY} L {$firstX},{$baselineY} Z";

            return ['line' => $path, 'area' => $area];
        };

        // Aktivitas 7 Hari Terakhir (Orders + Pendaftaran Pengguna)
        $weeklyDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $dDate = now()->subDays($i)->format('Y-m-d');
            $dLabel = strtoupper(now()->subDays($i)->translatedFormat('D'));
            $weeklyDays[$dDate] = [
                'date' => $dDate,
                'label' => $dLabel,
                'count' => 0,
            ];
        }

        $recentOrders = Order::select('created_at')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get();
        foreach ($recentOrders as $ord) {
            $d = $ord->created_at?->format('Y-m-d');
            if (isset($weeklyDays[$d])) {
                $weeklyDays[$d]['count']++;
            }
        }

        $recentRegs = User::select('created_at')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get();
        foreach ($recentRegs as $reg) {
            $d = $reg->created_at?->format('Y-m-d');
            if (isset($weeklyDays[$d])) {
                $weeklyDays[$d]['count']++;
            }
        }

        $weeklyList = array_values($weeklyDays);
        $maxWeekly = max(5, max(array_column($weeklyList, 'count')));

        $weeklySvgPoints = [];
        foreach ($weeklyList as $idx => $item) {
            $x = 60 + ($idx * 100);
            $normalized = $item['count'] / $maxWeekly;
            $y = 195 - round($normalized * 155);
            $weeklySvgPoints[] = [
                'x' => $x,
                'y' => $y,
                'label' => $item['label'],
                'count' => $item['count'],
                'date' => $item['date'],
            ];
        }
        $weeklyCurve = $buildWavePaths($weeklySvgPoints);

        // Aktivitas Bulanan (12 Bulan untuk toggle tab 'Bulan')
        $monthlySvgPoints = [];
        foreach ($monthCounts as $m => $cnt) {
            $x = 50 + (($m - 1) * 55);
            $normalized = $cnt / $maxGrowth;
            $y = 195 - round($normalized * 155);
            $monthlySvgPoints[] = [
                'x' => $x,
                'y' => $y,
                'label' => $shortMonthLabels[$m],
                'count' => $cnt,
            ];
        }
        $monthlyCurve = $buildWavePaths($monthlySvgPoints);

        // 8. Aktivitas Terbaru (Logs Pengguna Riil dari Database)
        $recentUsers = User::with(['umkmProfile', 'supplierProfile'])
            ->latest('created_at')
            ->take(5)
            ->get();

        $systemLogs = $recentUsers->map(function ($u) {
            $isSupplier = $u->role === 'supplier';
            $name = $isSupplier
                ? ($u->supplierProfile?->company_name ?: ($u->supplierProfile?->owner_name ?: explode('@', $u->email)[0]))
                : ($u->umkmProfile?->business_name ?: ($u->umkmProfile?->owner_name ?: explode('@', $u->email)[0]));

            $roleLabel = $isSupplier ? 'Pemasok Bahan Baku' : ($u->role === 'umkm' ? 'Mitra Kuliner UMKM' : 'Administrator');

            $action = match (strtolower((string) $u->status)) {
                'active', 'aktif' => "Akun terverifikasi aktif ({$roleLabel})",
                'pending', 'pending_verifikasi' => "Pendaftaran baru menunggu verifikasi ({$roleLabel})",
                'inactive', 'nonaktif' => "Status akun dinonaktifkan ({$roleLabel})",
                default => "Pendaftaran akun baru ({$roleLabel})",
            };

            return [
                'id' => 'USR-'.substr((string) $u->id, 0, 8),
                'actor' => $name,
                'role' => $u->role,
                'status' => $u->status,
                'action' => $action,
                'detail' => 'Status verifikasi: '.strtoupper((string) $u->status).' • '.$u->email,
                'timestamp' => $u->created_at ? $u->created_at->diffForHumans() : 'Baru saja',
                'raw_time' => $u->created_at ? $u->created_at->format('d/m/Y H:i') : '-',
            ];
        })->toArray();

        return view('admin.dashboard', compact(
            'currentTab',
            'users',
            'totalUmkm',
            'activeUmkm',
            'totalSuppliers',
            'activeSuppliers',
            'pendingUsersCount',
            'totalCategories',
            'totalUsers',
            'umkmPercent',
            'supplierPercent',
            'umkmDash',
            'supplierDash',
            'supplierOffset',
            'categories',
            'maxCommodityCount',
            'units',
            'totalCommodities',
            'monthlyBars',
            'weeklySvgPoints',
            'weeklyCurve',
            'monthlySvgPoints',
            'monthlyCurve',
            'maxWeekly',
            'maxGrowth',
            'systemLogs'
        ));
    }

    /**
     * Update user account status (active, pending, inactive).
     */
    public function updateUserStatus(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'status' => 'required|string|in:active,pending,inactive,AKTIF,PENDING_VERIFIKASI,NONAKTIF',
        ]);

        $user = User::findOrFail($id);

        $mappedStatus = match (strtoupper($request->status)) {
            'AKTIF', 'ACTIVE' => 'active',
            'PENDING_VERIFIKASI', 'PENDING' => 'pending',
            default => 'inactive',
        };

        $user->status = $mappedStatus;
        $user->save();

        $displayStatus = match ($mappedStatus) {
            'active' => 'AKTIF',
            'pending' => 'PENDING_VERIFIKASI',
            default => 'NONAKTIF',
        };

        return redirect()->route('admin.dashboard', ['tab' => 'users'])
            ->with('success', "✓ Status akun {$user->email} berhasil diperbarui menjadi {$displayStatus}.");
    }
}
