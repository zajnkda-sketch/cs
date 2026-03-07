(function($) {
    'use strict';

    function isMobile() {
        return window.innerWidth < 768;
    }

    function formatDateLabel(dateStr) {
        if (!dateStr) return '';
        const m = dateStr.match(/(\d{4})[-\/](\d{2})[-\/](\d{2})/);
        if (m) return m[2] + '-' + m[3];
        return dateStr;
    }

    function initPriceChart() {
        const ctx = document.getElementById('priceChart');
        if (!ctx) return;

        let currentProduct = 'urea';
        let currentDays = 30;
        let chart = null;

        function loadChartData() {
            $.ajax({
                url: fertilizer_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_price_chart_data',
                    product: currentProduct,
                    days: currentDays,
                    nonce: fertilizer_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateChart(response.data);
                    }
                }
            });
        }

        function updateChart(data) {
            if (chart) { chart.destroy(); }

            const labels = data.map(item => formatDateLabel(item.date));
            const prices = data.map(item => parseFloat(item.price));

            const validPrices = prices.filter(v => !isNaN(v) && isFinite(v));
            let yMin = undefined, yMax = undefined;
            if (validPrices.length > 0) {
                const dataMin = Math.min(...validPrices);
                const dataMax = Math.max(...validPrices);
                const padding = (dataMax - dataMin) * 0.15 || dataMin * 0.05 || 50;
                yMin = Math.floor((dataMin - padding) / 50) * 50;
                yMax = Math.ceil((dataMax + padding) / 50) * 50;
            }

            const mobile = isMobile();

            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '价格趋势 (元/吨)',
                        data: prices,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: mobile ? 3 : 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: mobile ? 4 : 3,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#fff',
                        pointBorderWidth: mobile ? 2 : 1.5,
                        pointHoverRadius: mobile ? 6 : 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: mobile ? 'bottom' : 'top',
                            labels: {
                                font: { size: mobile ? 12 : 14 },
                                padding: mobile ? 12 : 20
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                maxTicksLimit: mobile ? 6 : 10,
                                font: { size: mobile ? 10 : 12 },
                                maxRotation: mobile ? 45 : 0,
                                minRotation: mobile ? 45 : 0
                            }
                        },
                        y: {
                            beginAtZero: false,
                            min: yMin,
                            max: yMax,
                            title: {
                                display: !mobile,
                                text: '价格 (元/吨)'
                            },
                            ticks: {
                                font: { size: mobile ? 10 : 12 },
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        $('.trend-section').on('click', '[data-product]', function() {
            currentProduct = $(this).data('product');
            $(this).siblings().removeClass('active');
            $(this).addClass('active');
            loadChartData();
        });

        $('.trend-section').on('click', '[data-days]', function() {
            currentDays = $(this).data('days');
            $(this).siblings().removeClass('active');
            $(this).addClass('active');
            loadChartData();
        });

        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() { loadChartData(); }, 300);
        });

        loadChartData();
    }

    function initEmailSubscribe() {
        $('.subscribe-form').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const email = $form.find('input[name="email"]').val();
            const nonce = $form.find('input[name="subscribe_nonce"]').val();
            $.ajax({
                url: fertilizer_ajax.ajax_url,
                type: 'POST',
                data: { action: 'subscribe_email', email: email, nonce: nonce },
                success: function(response) {
                    if (response.success) {
                        alert('订阅成功！');
                        $form.find('input[name="email"]').val('');
                    } else {
                        alert('订阅失败，请重试');
                    }
                }
            });
        });
    }

    function initSearch() {
        const $searchForm = $('form[method="get"]');
        if ($searchForm.length) {
            $searchForm.on('submit', function() {
                const query = $(this).find('input[name="s"]').val().trim();
                if (!query) { return false; }
            });
        }
    }

    $(document).ready(function() {
        initPriceChart();
        initEmailSubscribe();
        initSearch();

        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({ scrollTop: target.offset().top - 100 }, 1000);
            }
        });

        $(document).on('ajaxStart', function() {
            $('body').addClass('loading-active');
        }).on('ajaxStop', function() {
            $('body').removeClass('loading-active');
        });
    });

})(jQuery);