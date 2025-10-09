<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Sholat - {{ $user->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .prayer-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
        .progress-ring-circle {
            transition: stroke-dashoffset 0.35s;
            stroke-dasharray: 251.2 251.2;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="prayer-card min-h-screen">
        <!-- Header -->
        <div class="container mx-auto px-4 py-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">Catatan Sholat</h1>
                <p class="text-white/80">{{ config('app.name', 'SNESA Apps') }}</p>
            </div>

            <!-- User Info Card -->
            <div class="max-w-4xl mx-auto mb-8">
                <div class="stat-card rounded-2xl p-6 text-white">
                    <div class="flex items-center space-x-4">
                        <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-3xl"></i>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold">{{ $user->name }}</h2>
                            <p class="text-white/80">{{ $user->email }}</p>
                            @if($user->nisn)
                                <p class="text-white/60 text-sm">NISN: {{ $user->nisn }}</p>
                            @endif
                            @if($user->rombonganBelajar)
                                <p class="text-white/60 text-sm">{{ $user->rombonganBelajar->nama }}</p>
                            @endif
                        </div>
                        <div class="text-center">
                            <div class="relative w-24 h-24">
                                <svg class="progress-ring w-24 h-24">
                                    <circle cx="48" cy="48" r="40" stroke="rgba(255,255,255,0.2)" stroke-width="8" fill="none"/>
                                    <circle class="progress-ring-circle" cx="48" cy="48" r="40" stroke="#10b981" stroke-width="8" fill="none"
                                            style="stroke-dashoffset: {{ 251.2 - (251.2 * $overallPercentage / 100) }}"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-2xl font-bold">{{ $overallPercentage }}%</span>
                                </div>
                            </div>
                            <p class="text-sm text-white/80 mt-1">Kehadiran</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Options -->
            <div class="max-w-4xl mx-auto mb-6">
                <div class="stat-card rounded-xl p-4">
                    <div class="flex flex-wrap gap-4 items-center justify-center">
                        <div class="flex items-center space-x-2">
                            <label class="text-white text-sm">Periode:</label>
                            <select id="periodFilter" class="bg-white/20 text-white rounded px-3 py-1 text-sm border border-white/30">
                                <option value="week" {{ $period == 'week' ? 'selected' : '' }}>Minggu Ini</option>
                                <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Bulan Ini</option>
                                <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Tahun Ini</option>
                            </select>
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-white text-sm">Tanggal:</label>
                            <input type="date" id="dateFilter" value="{{ $date }}" class="bg-white/20 text-white rounded px-3 py-1 text-sm border border-white/30">
                        </div>
                        <button onclick="applyFilter()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1 rounded text-sm transition-colors">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Prayer Statistics -->
            <div class="max-w-6xl mx-auto mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($statistics as $prayerType => $stats)
                    <div class="stat-card rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">{{ ucfirst($prayerType) }}</h3>
                            <div class="text-right">
                                <div class="text-2xl font-bold">{{ $stats['percentage'] }}%</div>
                                <div class="text-sm text-white/60">{{ $stats['present'] }}/{{ $stats['total'] }}</div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-white/20 rounded-full h-2 mb-4">
                            <div class="bg-green-400 h-2 rounded-full transition-all duration-300" style="width: {{ $stats['percentage'] }}%"></div>
                        </div>

                        <!-- Recent Records -->
                        @if($stats['records']->count() > 0)
                        <div class="space-y-2">
                            <p class="text-sm text-white/60 mb-2">7 hari terakhir:</p>
                            @foreach($stats['records'] as $record)
                            <div class="flex items-center justify-between text-sm">
                                <span>{{ \Carbon\Carbon::parse($record->date)->format('d/m') }}</span>
                                <span class="px-2 py-1 rounded text-xs {{ $record->status == 'sholat' ? 'bg-green-400/20 text-green-300' : ($record->status == 'halangan' ? 'bg-yellow-400/20 text-yellow-300' : 'bg-red-400/20 text-red-300') }}">
                                    {{ $record->status == 'sholat' ? 'Sholat' : ($record->status == 'halangan' ? 'Halangan' : 'Tidak Sholat') }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="max-w-4xl mx-auto">
                <div class="stat-card rounded-xl p-6 text-white">
                    <h3 class="text-xl font-semibold mb-4">Aktivitas Terbaru</h3>
                    @if($recentActivities->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentActivities as $activity)
                        <div class="flex items-center justify-between py-2 border-b border-white/10">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $activity->status == 'sholat' ? 'bg-green-400/20' : ($activity->status == 'halangan' ? 'bg-yellow-400/20' : 'bg-red-400/20') }}">
                                    <i class="fas {{ $activity->status == 'sholat' ? 'fa-check text-green-300' : ($activity->status == 'halangan' ? 'fa-exclamation text-yellow-300' : 'fa-times text-red-300') }} text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-medium">{{ ucfirst($activity->prayer_type) }}</p>
                                    <p class="text-sm text-white/60">{{ \Carbon\Carbon::parse($activity->date)->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs {{ $activity->status == 'sholat' ? 'bg-green-400/20 text-green-300' : ($activity->status == 'halangan' ? 'bg-yellow-400/20 text-yellow-300' : 'bg-red-400/20 text-red-300') }}">
                                {{ $activity->status == 'sholat' ? 'Sholat' : ($activity->status == 'halangan' ? 'Halangan' : 'Tidak Sholat') }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-white/60 text-center py-8">Belum ada catatan sholat</p>
                    @endif
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-12 text-white/60">
                <p class="text-sm">Diakses melalui NFC pada {{ now()->format('d/m/Y H:i') }}</p>
                <p class="text-xs mt-2">© {{ date('Y') }} {{ config('app.name', 'SNESA Apps') }}</p>
            </div>
        </div>
    </div>

    <script>
        function applyFilter() {
            const period = document.getElementById('periodFilter').value;
            const date = document.getElementById('dateFilter').value;
            const url = new URL(window.location.href);
            url.searchParams.set('period', period);
            url.searchParams.set('date', date);
            window.location.href = url.toString();
        }

        // Auto-refresh every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>