<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 h-full">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-900">Doanh thu 7 ngày qua</h3>
        <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">Live Data</span>
    </div>

    <div id="revenueChart" style="min-height: 300px;"></div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const options = {
                chart: {
                    type: 'area', // Loại biểu đồ vùng
                    height: 320,
                    toolbar: { show: false },
                    fontFamily: 'Inter, sans-serif',
                },
                series: [{
                    name: 'Doanh thu',
                    data: @json($data) // Dữ liệu từ PHP
                }],
                xaxis: {
                    categories: @json($labels), // Nhãn ngày
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                        }
                    }
                },
                colors: ['#4f46e5'], // Màu tím Indigo chủ đạo
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                    }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                grid: { borderColor: '#f3f4f6', strokeDashArray: 4 }
            };

            const chart = new ApexCharts(document.querySelector("#revenueChart"), options);
            chart.render();
        });
    </script>
</div>
