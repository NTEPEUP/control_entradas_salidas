<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IoT Access Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js"></script>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased" x-data="dashboard()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between gap-4 mb-6 border-b border-gray-700 pb-2">
            <h1 class="text-3xl font-bold">Panel de Control de Acceso</h1>
            <a href="{{ route('access-pins.index') }}" class="px-4 py-2 rounded bg-gray-700 hover:bg-gray-600 text-sm font-semibold">Gestionar PINs</a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <template x-for="stat in stats" :key="stat.label">
                <div class="bg-gray-800 p-4 rounded-lg shadow">
                    <p class="text-gray-400 text-sm" x-text="stat.label"></p>
                    <p class="text-2xl font-bold mt-1" x-text="stat.value"></p>
                </div>
            </template>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Chart -->
            <div class="lg:col-span-2 bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-4">Actividad por Estado</h3>
                <canvas id="statusChart" height="120"></canvas>
            </div>

            <!-- Live Log Table -->
            <div class="bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-4">📡 Últimos Accesos <span class="text-xs bg-green-600 px-2 py-0.5 rounded">LIVE</span></h3>
                <div class="overflow-y-auto max-h-80">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 border-b border-gray-700">
                            <tr><th class="py-2">Hora</th><th>PIN</th><th>Propietario</th><th>Estado</th></tr>
                        </thead>
                        <tbody id="logTable">
                            <template x-for="log in logs" :key="log.id">
                                <tr class="border-b border-gray-700/50">
                                    <td class="py-2" x-text="formatTime(log.created_at)"></td>
                                    <td class="py-2 font-mono" x-text="log.pin || 'N/A'"></td>
                                    <td class="py-2 font-mono" x-text="log.owner_name || 'N/A'"></td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded text-xs font-bold"
                                              :class="log.status === 'granted' ? 'bg-green-900 text-green-300' : 
                                                      log.status === 'denied' ? 'bg-red-900 text-red-300' : 'bg-yellow-900 text-yellow-300'"
                                              x-text="log.status.toUpperCase()"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function dashboard() {
            return {
                stats: [
                    { label: 'Total', value: @json($stats['total'] ?? 0) },
                    { label: 'Concedidos', value: @json($stats['granted'] ?? 0) },
                    { label: 'Denegados', value: @json($stats['denied'] ?? 0) },
                    { label: 'Tasa (%)', value: @json($stats['rate'] ?? 0) }
                ],
                logs: @json($recent),
                chart: null,
                init() {
                    // Para desarrollo local con driver 'log', haremos polling cada 5 segundos
                    @if (config('broadcasting.default') === 'log')
                        setInterval(() => this.refreshLogs(), 5000);
                    @else
                        // Configurar Laravel Echo para Pusher/Redis
                        if (window.Echo) {
                            window.Echo.channel('dashboard')
                                .listen('.access.new', (e) => {
                                    const newLog = e.log;
                                    this.logs.unshift(newLog);
                                    if (this.logs.length > 50) this.logs.pop();

                                    // Si el evento trae stats, sincronizamos exactamente
                                    if (e.stats) {
                                        this.stats[0].value = e.stats.total;
                                        this.stats[1].value = e.stats.granted;
                                        this.stats[2].value = e.stats.denied;
                                        this.stats[3].value = e.stats.rate;
                                    } else {
                                        // Fallback: incrementar localmente
                                        if (newLog.status === 'granted') this.stats[1].value++;
                                        if (newLog.status === 'denied') this.stats[2].value++;
                                        this.stats[0].value++;
                                        this.stats[3].value = Math.round((this.stats[1].value / this.stats[0].value) * 1000) / 10;
                                    }

                                    // Actualizar gráfico
                                    this.updateChart();
                                });
                        }
                    @endif

                    this.initChart();
                },
                initChart() {
                    const ctx = document.getElementById('statusChart').getContext('2d');
                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Concedido', 'Denegado', 'Error/Timeout'],
                            datasets: [{
                                label: 'Accesos',
                                data: [
                                    this.logs.filter(l => l.status === 'granted').length,
                                    this.logs.filter(l => l.status === 'denied').length,
                                    this.logs.filter(l => !['granted', 'denied'].includes(l.status)).length
                                ],
                                backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { display: false } } }
                    });
                },
                updateChart() {
                    if (!this.chart) return;
                    this.chart.data.datasets[0].data = [
                        this.logs.filter(l => l.status === 'granted').length,
                        this.logs.filter(l => l.status === 'denied').length,
                        this.logs.filter(l => !['granted', 'denied'].includes(l.status)).length
                    ];
                    this.chart.update();
                },
                formatTime(date) {
                    return new Date(date).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                },
                async refreshLogs() {
                    // Obtener logs actualizados mediante fetch (polling para desarrollo local)
                    try {
                        const response = await fetch('/api/access-logs');
                        if (!response.ok) return;
                        const data = await response.json();
                        this.logs = data.recent || [];
                        if (data.total !== undefined) {
                            this.stats[0].value = data.total;
                        }
                        if (data.granted !== undefined) {
                            this.stats[1].value = data.granted;
                        }
                        if (data.denied !== undefined) {
                            this.stats[2].value = data.denied;
                        }
                        if (data.total !== undefined && data.granted !== undefined) {
                            this.stats[3].value = data.total > 0 ? Math.round((data.granted / data.total) * 1000) / 10 : 0;
                        }
                        this.updateChart();
                    } catch (e) {
                        console.log('Error refreshing logs:', e);
                    }
                }
            }
        }
    </script>
</body>
</html>