@props([
    'type' => 'line',          // line | bar | pie | doughnut | radar
    'labels' => [],
    'datasets' => [],          // format Chart.js
    'options' => [],           // optional override
    'legendPosition' => 'top',
])

<div x-data="{
        chartCanvas: null,
        chart: null,
        type: @js($type),
        labels: @js($labels),
        datasets: @js($datasets),
        customOptions: @js($options),
        legendPosition: @js($legendPosition),

        initChart() {
            if (this.chart) {
                this.chart.destroy();
            }

            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: this.legendPosition,
                    },
                    tooltip: {
                        enabled: true,
                    },
                },
                scales: ['pie', 'doughnut'].includes(this.type)
                    ? {}
                    : {
                        y: {
                            beginAtZero: true,
                        },
                    },
            };

            this.chart = new Chart(this.chartCanvas, {
                type: this.type,
                data: {
                    labels: this.labels,
                    datasets: this.datasets,
                },
                options: {
                    ...defaultOptions,
                    ...this.customOptions,
                },
            });
        },
    }" x-init="
        chartCanvas = $el.querySelector('canvas');
        initChart();
    " class="relative w-full h-80 dark:text-white">
    <canvas></canvas>
</div>

{{-- example --}}
{{-- pie --}}
{{-- <x-chart
    type="pie"
    :labels="$providers->pluck('name_provider')"
    :datasets="[
        [
            'data' => $providers->pluck('total'),
            'backgroundColor' => [
                '#2563eb',
                '#16a34a',
                '#f59e0b',
                '#dc2626',
            ],
        ]
    ]"
/> --}}

{{-- line --}}
{{-- <x-chart
    type="line"
    :labels="['Jan', 'Feb', 'Mar', 'Apr']"
    :datasets="[
        [
            'label' => 'Pengguna',
            'data' => [120, 150, 180, 220],
            'borderColor' => '#2563eb',
            'backgroundColor' => 'rgba(37,99,235,.2)',
            'tension' => 0.4,
        ]
    ]"
/>
 --}}

 {{-- bar --}}
 {{-- <x-chart
    type="bar"
    :labels="['Desa A', 'Desa B', 'Desa C']"
    :datasets="[
        [
            'label' => 'Fiber',
            'data' => [30, 50, 40],
            'backgroundColor' => '#16a34a',
        ],
        [
            'label' => 'Wireless',
            'data' => [20, 35, 25],
            'backgroundColor' => '#2563eb',
        ],
    ]"
/>
 --}}