<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de PINs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold">Gestión de PINs</h1>
                <p class="text-gray-400 mt-1">Crea y administra los PINs autorizados del sistema.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded bg-gray-700 hover:bg-gray-600">Volver al dashboard</a>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded border border-green-700 bg-green-900/40 px-4 py-3 text-green-200">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded border border-red-700 bg-red-900/40 px-4 py-3 text-red-200">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Nuevo PIN</h2>
                <form method="POST" action="{{ route('access-pins.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-400 mb-2" for="pin">PIN</label>
                        <input id="pin" name="pin" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" required
                            class="w-full rounded bg-gray-900 border border-gray-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-600"
                            placeholder="1234">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-2" for="owner_name">Nombre del propietario</label>
                        <input id="owner_name" name="owner_name" type="text" required
                            class="w-full rounded bg-gray-900 border border-gray-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-600"
                            placeholder="Admin Principal">
                    </div>
                    <label class="flex items-center gap-3 text-sm text-gray-300">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-600 bg-gray-900 text-green-600">
                        PIN activo
                    </label>
                    <button type="submit" class="w-full rounded bg-green-600 hover:bg-green-500 px-4 py-2 font-semibold">
                        Guardar PIN
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-gray-800 rounded-lg shadow p-6 overflow-x-auto">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">PINs registrados</h2>
                    <span class="text-sm text-gray-400">Total: {{ $pins->count() }}</span>
                </div>

                <table class="w-full text-sm text-left">
                    <thead class="text-gray-400 border-b border-gray-700">
                        <tr>
                            <th class="py-2">PIN</th>
                            <th>Propietario</th>
                            <th>Estado</th>
                            <th class="text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pins as $pin)
                            <tr class="border-b border-gray-700/50">
                                <td class="py-3 font-mono">{{ $pin->pin }}</td>
                                <td>{{ $pin->owner_name ?? 'Sin nombre' }}</td>
                                <td>
                                    <span class="px-2 py-1 rounded text-xs font-bold {{ $pin->is_active ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                                        {{ $pin->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="py-3 text-right">
                                    <form method="POST" action="{{ route('access-pins.toggle', $pin) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3 py-2 rounded bg-gray-700 hover:bg-gray-600">
                                            {{ $pin->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-gray-400">No hay PINs registrados todavía.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
