/* ── Dashboard chart helpers & interactive functions ── */

/* ===================== GRADIENT HELPER ===================== */
function makeGradient(ctx, r, g, b) {
    const grad = ctx.createLinearGradient(0, 0, 0, 300);
    grad.addColorStop(0, `rgba(${r},${g},${b},0.35)`);
    grad.addColorStop(1, `rgba(${r},${g},${b},0.01)`);
    return grad;
}

/* ===================== SHARED LINE OPTIONS ===================== */
const lineOptions = (unit) => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1a1a3e',
            titleColor: '#fff',
            bodyColor: '#ccc',
            padding: 10,
            callbacks: { label: c => '  ' + c.parsed.y + ' ' + unit }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { color: '#aaa', font: { size: 11 } },
            grid: { color: 'rgba(0,0,0,0.05)', borderDash: [4,4] },
            border: { display: false }
        },
        x: {
            ticks: { color: '#aaa', maxRotation: 30, font: { size: 11 } },
            grid: { display: false },
            border: { display: false }
        }
    }
});

const lineDataset = (data, grad, color) => ({
    data,
    borderColor: color,
    backgroundColor: grad,
    pointBackgroundColor: color,
    pointBorderColor: '#fff',
    pointBorderWidth: 2,
    pointRadius: 5,
    pointHoverRadius: 7,
    borderWidth: 2.5,
    tension: 0.4,
    cubicInterpolationMode: 'monotone',
    fill: true
});

/* ===================== CHART 4: Usage Trend by Material ===================== */
let trendChart;
function loadMaterialTrend() {
    const m = document.getElementById('materialSelect').value;
    const f = document.getElementById('trendFrom').value;
    const t = document.getElementById('trendTo').value;

    let url = `get_material_trend.php?material_id=${m}`;
    if (f && t) url += `&from=${f}&to=${t}`;

    fetch(url).then(r => r.json()).then(d => {
        const labels = d.map(x => x.month_label);
        const values = d.map(x => parseInt(x.total_issued));

        if (trendChart) trendChart.destroy();

        const barCount = labels.length || 1;
        const dynamicHeight = Math.max(100, barCount * 32 + 44);
        document.getElementById('trendChart').parentElement.style.height = dynamicHeight + 'px';

        const ctx = document.getElementById('trendChart').getContext('2d');
        trendChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels.length ? labels : ['No data'],
                datasets: [{
                    data: values.length ? values : [0],
                    backgroundColor: 'rgba(232,160,0,0.85)',
                    hoverBackgroundColor: 'rgba(232,160,0,1)',
                    borderRadius: 4,
                    borderSkipped: false,
                    barThickness: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    onComplete(anim) {
                        const chart = anim.chart;
                        setTimeout(() => { chart.reset(); chart.update(); }, 2500);
                    }
                },
                animations: {
                    x: {
                        duration: 6500,
                        easing: 'easeInOutSine',
                        delay(context) {
                            return context.dataIndex * 500;
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#2d3142',
                        titleColor: '#fff',
                        bodyColor: '#ccc',
                        padding: 10,
                        callbacks: { label: c => '  ' + c.parsed.x + ' units withdrawn' }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        position: 'bottom',
                        ticks: { color: '#aaa', font: { size: 11 }, padding: 2 },
                        grid: { color: 'rgba(0,0,0,0.05)', borderDash: [4,4] },
                        border: { display: false }
                    },
                    y: {
                        ticks: { color: '#768192', font: { size: 11 }, padding: 6 },
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    });
}

function initDefaultDates() {
    const year = new Date().getFullYear();
    document.getElementById('trendFrom').value = `${year}-01-01`;
    document.getElementById('trendTo').value   = `${year}-12-31`;
}
