<x-app-layout>

    <x-page-header title="Design System Playground">
        <x-slot name="actions">
            <button class="h-[var(--rutx-height-button)] px-6 bg-rutx-primary text-white rounded-lg text-sm font-medium hover:bg-rutx-primary-dark transition-colors shadow-sm">
                Acción Principal
            </button>
        </x-slot>
    </x-page-header>

    <x-breadcrumb :extra="['Playground', 'Design System']" />

    <div class="space-y-8 mt-8">

        <section>
            <h2 class="text-xl font-bold text-rutx-text mb-4 border-b border-rutx-border pb-2">1. KPI Cards</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- value como float: kpi-card aplica Money::format internamente (H-04) --}}
                <x-kpi-card
                    title="Venta del Día"
                    :value="12450.00"
                    delta="+5.2%"
                    status="success">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                    </x-slot>
                </x-kpi-card>

                <x-kpi-card
                    title="Clientes Visitados"
                    value="42 / 50"
                    delta="-2"
                    status="warning">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    </x-slot>
                </x-kpi-card>

                <x-kpi-card
                    title="Devoluciones"
                    value="3"
                    delta="Alerta"
                    status="error">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </x-slot>
                </x-kpi-card>

                <x-kpi-card
                    title="Efectividad"
                    value="84%"
                    status="unknown">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    </x-slot>
                </x-kpi-card>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-rutx-text mb-4 border-b border-rutx-border pb-2">2. Filter Bar & Alerts</h2>

            <x-alert type="warning" message="Atención: Esta es una alerta de prueba. Por favor revise los parámetros de configuración." />
            <div class="h-4"></div>
            <x-alert type="error" message="Error de conexión con el Sincronizador." />
            <div class="h-4"></div>
            <x-alert type="success" message="Los datos se han guardado correctamente." />

            <div class="mt-6">
                <x-filter-bar>
                    <div class="w-64">
                        <label class="block text-xs font-semibold text-rutx-text-muted mb-1">Ruta</label>
                        <select class="w-full h-[var(--rutx-height-input)] border border-rutx-border rounded-lg bg-rutx-surface px-3 text-sm focus:ring-2 focus:ring-rutx-primary focus:border-rutx-primary outline-none transition-shadow">
                            <option>Ruta Norte</option>
                            <option>Ruta Sur</option>
                        </select>
                    </div>
                    <div class="w-64">
                        <label class="block text-xs font-semibold text-rutx-text-muted mb-1">Estado</label>
                        <select class="w-full h-[var(--rutx-height-input)] border border-rutx-border rounded-lg bg-rutx-surface px-3 text-sm focus:ring-2 focus:ring-rutx-primary focus:border-rutx-primary outline-none transition-shadow">
                            <option>Todos</option>
                            <option>Activo</option>
                        </select>
                    </div>
                </x-filter-bar>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-rutx-text mb-4 border-b border-rutx-border pb-2">3. Data Table & Badges</h2>

            @php
                $dummyData = [
                    ['id' => '1001', 'cliente' => 'Cliente de ejemplo A', 'ruta' => 'Norte', 'monto' => 4500.50, 'estado' => 'success', 'estadoLabel' => 'Activo'],
                    ['id' => '1002', 'cliente' => 'Cliente de ejemplo B', 'ruta' => 'Sur', 'monto' => 1250.00, 'estado' => 'warning', 'estadoLabel' => 'Pendiente'],
                    ['id' => '1003', 'cliente' => 'Cliente de ejemplo C', 'ruta' => 'Norte', 'monto' => 8400.00, 'estado' => 'error', 'estadoLabel' => 'Rechazado'],
                    ['id' => '1004', 'cliente' => 'Cliente de ejemplo D', 'ruta' => 'Este', 'monto' => 300.00, 'estado' => 'unknown', 'estadoLabel' => 'Inactivo'],
                ];
            @endphp

            <x-data-table :headers="['ID', 'Cliente', 'Ruta', 'Estado', 'Monto']" :items="$dummyData">
                <x-slot:row>
                    @foreach($dummyData as $item)
                        <tr class="border-b border-rutx-border hover:bg-rutx-secondary/10 transition-colors {{ $loop->even ? 'bg-rutx-surface-grey' : 'bg-rutx-surface' }}">
                            <td class="px-4 py-3 text-sm text-rutx-text-muted">{{ $item['id'] }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-rutx-text">{{ $item['cliente'] }}</td>
                            <td class="px-4 py-3 text-sm text-rutx-text">{{ $item['ruta'] }}</td>
                            <td class="px-4 py-3 text-sm">
                                <x-status-badge :status="$item['estado']" :label="$item['estadoLabel']" />
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-mono">
                                <x-currency :amount="$item['monto']" />
                            </td>
                        </tr>
                    @endforeach
                </x-slot:row>

                <x-slot:pagination>
                    <div class="flex justify-between items-center text-sm text-rutx-text-muted">
                        <span>Mostrando 1 a 4 de 4 registros</span>
                        <div class="flex space-x-2">
                            <button class="px-3 py-1 rounded border border-rutx-border opacity-50 cursor-not-allowed">Anterior</button>
                            <button class="px-3 py-1 rounded border border-rutx-border bg-white text-rutx-primary font-medium hover:bg-rutx-surface-grey">Siguiente</button>
                        </div>
                    </div>
                </x-slot:pagination>
            </x-data-table>

            <div class="mt-8">
                <h3 class="text-sm font-bold text-rutx-text-muted mb-2">Estado Vacío</h3>
                <x-data-table :headers="['Documento', 'Fecha', 'Referencia', 'Importe']" :items="[]" />
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-rutx-text mb-4 border-b border-rutx-border pb-2">4. Loading State</h2>
            <div class="bg-rutx-surface border border-rutx-border rounded-lg min-h-[300px] flex items-center justify-center relative">
                <x-loading-state message="Procesando solicitud, por favor espere..." />
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-rutx-text mb-4 border-b border-rutx-border pb-2">5. Gráficas (Chart.js)</h2>

            {{--
                Test de re-render / idempotencia (H-01 / guidelines §4):
                Este div usa Alpine setInterval en lugar de wire:poll porque el
                playground no es un componente Livewire. Cada 5 s despacha el
                evento rutx:refresh-charts, que chart.js captura y re-inicializa
                las gráficas de forma idempotente (WeakMap destruye + recrea).
                En producción, Livewire dispara el mismo evento desde wire:poll.
            --}}
            <div
                x-data
                x-init="setInterval(() => $dispatch('rutx:refresh-charts'), 5000)"
            >
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <x-chart
                        summary="Ventas de la semana actual y anterior"
                        :labels="['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']"
                        :datasets="[
                            ['label' => 'Venta', 'data' => [1200, 1800, 1500, 2100, 2400, 1900, 2600]],
                            ['label' => 'Semana anterior', 'data' => [1000, 1400, 1600, 1700, 2000, 2200, 2300]],
                        ]"
                    />

                    <x-chart
                        type="bar"
                        summary="Piezas vendidas por día"
                        :labels="['Lun', 'Mar', 'Mié', 'Jue', 'Vie']"
                        :datasets="[
                            ['label' => 'Piezas', 'data' => [32, 45, 38, 51, 60], 'colorToken' => '--rutx-chart-cyan'],
                        ]"
                    />
                </div>

                <div class="mt-6">
                    <h3 class="text-sm font-bold text-rutx-text-muted mb-2">Estado vacío</h3>
                    <x-chart summary="Sin datos para el período seleccionado" :labels="[]" :datasets="[]" height="220" />
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-rutx-text mb-4 border-b border-rutx-border pb-2">6. Mapa (Leaflet + OpenStreetMap)</h2>

            <x-map-view
                height="420px"
                :markers="[
                    ['lat' => 20.6597, 'lon' => -103.3496, 'status' => 'active',  'label' => 'Ruta Centro — Vendedor activo'],
                    ['lat' => 20.6773, 'lon' => -103.3914, 'status' => 'delayed', 'label' => 'Ruta Norte — Retraso en visita'],
                    ['lat' => 20.6406, 'lon' => -103.3222, 'status' => 'stopped', 'label' => 'Ruta Sur — Jornada detenida'],
                ]"
            />

            <div class="mt-6">
                <h3 class="text-sm font-bold text-rutx-text-muted mb-2">Estado vacío</h3>
                <x-map-view :markers="[]" height="260px" />
            </div>
        </section>

    </div>
</x-app-layout>
