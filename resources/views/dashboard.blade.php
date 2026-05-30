<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de accesos</title>
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

        <div class="fixed top-4 right-4 z-50 space-y-3 w-full max-w-sm pointer-events-none">
            <template x-for="toast in notifications" :key="toast.id">
                <div class="pointer-events-auto rounded-lg border px-4 py-3 shadow-lg bg-gray-800 text-sm" :class="toast.classes">
                    <p class="font-semibold" x-text="toast.title"></p>
                    <p class="text-gray-200/90 mt-1" x-text="toast.message"></p>
                </div>
            </template>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <template x-for="stat in stats" :key="stat.label">
                <div class="bg-gray-800 p-4 rounded-lg shadow">
                    <p class="text-gray-400 text-sm" x-text="stat.label"></p>
                    <p class="text-2xl font-bold mt-1" x-text="stat.value"></p>
                </div>
            </template>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-4">Actividad por Estado</h3>
                <canvas id="statusChart" height="120"></canvas>
            </div>

            <div class="bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-4"> Últimos Accesos <span class="text-xs bg-green-600 px-2 py-0.5 rounded">LIVE</span></h3>
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

    <script type="application/json" id="dashboard-data">
        {!! json_encode([
            'stats' => [
                'total' => $stats['total'] ?? 0,
                'granted' => $stats['granted'] ?? 0,
                'denied' => $stats['denied'] ?? 0,
                'rate' => $stats['rate'] ?? 0,
            ],
            'recent' => $recent,
            'broadcast' => [
                'driver' => config('broadcasting.default'),
                'pusherKey' => config('broadcasting.connections.pusher.key'),
                'pusherCluster' => config('broadcasting.connections.pusher.options.cluster'),
                'pusherHost' => env('PUSHER_HOST'),
                'pusherPort' => env('PUSHER_PORT'),
                'pusherScheme' => env('PUSHER_SCHEME', 'https'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script>
        const dashboardData = JSON.parse(document.getElementById('dashboard-data').textContent);

        function dashboard() {
            return {
                stats: [
                    { label: 'Total', value: dashboardData.stats.total },
                    { label: 'Concedidos', value: dashboardData.stats.granted },
                    { label: 'Denegados', value: dashboardData.stats.denied },
                    { label: 'Tasa (%)', value: dashboardData.stats.rate }
                ],
                logs: dashboardData.recent,
                notifications: [],
                chart: null,
                echo: null,
                init() {
                    this.refreshLogs();

                    if (dashboardData.broadcast.driver === 'log') {
                        setInterval(() => this.refreshLogs(), 5000);
                    } else {
                        this.initEcho();
                        setInterval(() => this.refreshLogs(), 30000);
                    }

                    this.renderChart();

                    if ('Notification' in window && Notification.permission === 'default') {
                        Notification.requestPermission().catch(() => {});
                    }
                },
                initEcho() {
                    const pusherKey = dashboardData.broadcast.pusherKey;
                    const pusherCluster = dashboardData.broadcast.pusherCluster;
                    const pusherHost = dashboardData.broadcast.pusherHost;
                    const pusherPort = dashboardData.broadcast.pusherPort;
                    const pusherScheme = dashboardData.broadcast.pusherScheme;

                    if (!pusherKey && !pusherHost) {
                        console.warn('Echo no se inicializó: faltan credenciales o host de websocket.');
                        return;
                    }

                    const options = {
                        broadcaster: 'pusher',
                        key: pusherKey,
                        cluster: pusherCluster || 'mt1',
                        forceTLS: pusherScheme !== 'http'
                    };

                    if (pusherHost) {
                        options.wsHost = pusherHost;
                        if (pusherPort) {
                            options.wsPort = Number(pusherPort);
                            options.wssPort = Number(pusherPort);
                        }
                        options.enabledTransports = ['ws', 'wss'];
                    }

                    this.echo = new Echo(options);
                    this.echo.channel('dashboard')
                        .listen('.access.new', (e) => this.handleRealtimeUpdate(e));
                },
                handleRealtimeUpdate(e) {
                    this.syncDashboard(e);
                    this.pushToastFromEvent(e);
                },
                syncDashboard(payload) {
                    const newLog = payload.log || payload;

                    if (payload.recent) {
                        this.logs = payload.recent;
                    } else if (newLog && newLog.id) {
                        this.logs = [newLog, ...this.logs.filter((log) => log.id !== newLog.id)].slice(0, 10);
                    }

                    const stats = payload.stats || payload;
                    if (stats.total !== undefined) this.stats[0].value = stats.total;
                    if (stats.granted !== undefined) this.stats[1].value = stats.granted;
                    if (stats.denied !== undefined) this.stats[2].value = stats.denied;
                    if (stats.rate !== undefined) this.stats[3].value = stats.rate;

                    this.updateChart();
                },
                renderChart() {
                    const ctx = document.getElementById('statusChart').getContext('2d');
                    if (this.chart) {
                        this.chart.destroy();
                    }

                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Concedido', 'Denegado', 'Error/Timeout'],
                            datasets: [{
                                label: 'Accesos',
                                data: this.getChartData(),
                                backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { display: false } } }
                    });
                },
                updateChart() {
                    this.renderChart();
                },
                getChartData() {
                    const total = Number(this.stats[0].value) || 0;
                    const granted = Number(this.stats[1].value) || 0;
                    const denied = Number(this.stats[2].value) || 0;
                    const remaining = Math.max(total - granted - denied, 0);

                    return [granted, denied, remaining];
                },
                formatTime(date) {
                    return new Date(date).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                },
                pushToastFromEvent(payload) {
                    const log = payload.log || payload;
                    const status = (payload.status || log.status || 'info').toLowerCase();
                    const title = this.getToastTitle(status);
                    const message = payload.message || this.getToastMessage(log, title);
                    const toastId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;

                    this.notifications.unshift({
                        id: toastId,
                        title,
                        message,
                        classes: this.getToastClasses(status)
                    });

                    if (this.notifications.length > 3) {
                        this.notifications.pop();
                    }

                    setTimeout(() => {
                        this.notifications = this.notifications.filter((toast) => toast.id !== toastId);
                    }, 5000);

                    this.pushBrowserNotification(message, status);
                },
                getToastTitle(status) {
                    if (status === 'granted') return 'Acceso concedido';
                    if (status === 'denied') return 'Acceso denegado';
                    if (status === 'error') return 'Error de acceso';
                    if (status === 'timeout') return 'Tiempo agotado';
                    return 'Nueva actividad';
                },
                getToastMessage(log, title) {
                    if (!log) {
                        return 'Se registró una nueva actividad.';
                    }

                    const pin = log.pin ? `PIN ${log.pin}` : 'PIN desconocido';
                    const owner = log.owner_name ? ` - ${log.owner_name}` : '';

                    return `${title} (${pin})${owner}`;
                },
                getToastClasses(status) {
                    if (status === 'granted') return 'border-green-700 bg-green-950/95';
                    if (status === 'denied') return 'border-red-700 bg-red-950/95';
                    if (status === 'error' || status === 'timeout') return 'border-amber-700 bg-amber-950/95';
                    return 'border-gray-700 bg-gray-800';
                },
                pushBrowserNotification(message, status) {
                    if (!('Notification' in window) || Notification.permission !== 'granted') {
                        return;
                    }

                    new Notification('Control de accesos', {
                        body: message,
                        tag: status,
                    });
                },
                async refreshLogs() {
                    try {
                        const response = await fetch('/api/access-logs');
                        if (!response.ok) return;
                        const data = await response.json();

                        this.syncDashboard(data);
                    } catch (e) {
                        console.log('Error refreshing logs:', e);
                    }
                }
            }
        }
    </script>
</body>
</html>