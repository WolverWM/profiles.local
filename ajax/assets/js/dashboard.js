/**
 * ============================================================
 * dashboard.js — график статистики (лайки/подписки за неделю)
 * Использует Chart.js, данные берёт из ajax/get_stats.php
 * ============================================================
 */
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('statsChart');
    if (!canvas || typeof Chart === 'undefined') return;

    fetch('ajax/get_stats.php')
        .then(r => r.json())
        .then(data => {
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Лайки',
                            data: data.likes,
                            borderColor: '#ff6b6b',
                            backgroundColor: 'rgba(255,107,107,0.15)',
                            tension: 0.35,
                            fill: true,
                        },
                        {
                            label: 'Новые подписчики',
                            data: data.follows,
                            borderColor: '#4dd0e1',
                            backgroundColor: 'rgba(77,208,225,0.15)',
                            tension: 0.35,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { labels: { color: '#fff' } } },
                    scales: {
                        x: { ticks: { color: '#fff' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                        y: { ticks: { color: '#fff', stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.1)' }, beginAtZero: true },
                    },
                },
            });
        });
});
