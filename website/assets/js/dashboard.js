/**
 * Smart Egg Incubator - Dashboard Controller
 * Simple, Clean, and Real-time
 */

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        deviceId: 'INCUBATOR_01',
        pollIntervalMs: 2000,
        currentRange: '1h',
        currentStepperMode: 1,
    };

    let sensorChart = null;
    let toastInstance = null;

    initToast();
    initSidebar();
    initChart();
    initStepperModeButtons();
    initRangeFilters();

    fetchDashboardState();
    fetchHistoryData();

    setInterval(fetchDashboardState, config.pollIntervalMs);

    function initToast() {
        const toastEl = document.getElementById('appToast');
        if (toastEl && window.bootstrap) {
            toastInstance = new bootstrap.Toast(toastEl, { delay: 3000 });
        }
    }

    function showToast(message, isSuccess = true) {
        const toastEl = document.getElementById('appToast');
        const toastMsg = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');

        if (!toastEl || !toastMsg) return;

        toastMsg.textContent = message;
        toastEl.className = isSuccess
            ? 'toast align-items-center text-white bg-success border-0'
            : 'toast align-items-center text-white bg-danger border-0';

        if (toastIcon) {
            toastIcon.className = isSuccess ? 'bi bi-check-circle-fill fs-5' : 'bi bi-exclamation-triangle-fill fs-5';
        }

        if (toastInstance) toastInstance.show();
    }

    function initSidebar() {
        const sidebar = document.getElementById('appSidebar');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        const backdrop = document.getElementById('sidebarBackdrop');

        const openSidebar = () => {
            if (sidebar) sidebar.classList.add('show');
            if (backdrop) backdrop.classList.add('show');
        };

        const closeSidebar = () => {
            if (sidebar) sidebar.classList.remove('show');
            if (backdrop) backdrop.classList.remove('show');
        };

        if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        document.querySelectorAll('.sidebar-nav .nav-item').forEach(link => {
            link.addEventListener('click', (e) => {
                const targetId = link.getAttribute('data-nav-target');
                if (targetId) {
                    const targetEl = document.getElementById(targetId);
                    if (targetEl) {
                        e.preventDefault();
                        document.querySelectorAll('.sidebar-nav .nav-item').forEach(l => l.classList.remove('active'));
                        link.classList.add('active');
                        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        closeSidebar();
                    }
                }
            });
        });
    }

    function initStepperModeButtons() {
        document.querySelectorAll('[data-stepper-mode]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const modeVal = parseInt(btn.getAttribute('data-stepper-mode'), 10);
                if (!modeVal || modeVal === config.currentStepperMode) return;

                btn.disabled = true;

                try {
                    const response = await fetch('api/set_mode.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            device_id: config.deviceId,
                            mode: modeVal
                        })
                    });

                    const res = await response.json();
                    if (res.success) {
                        config.currentStepperMode = modeVal;
                        updateStepperModeUI(modeVal);
                        showToast(`Mode Stepper diubah ke Mode ${modeVal}`, true);
                        fetchDashboardState();
                        fetchHistoryData();
                    } else {
                        showToast(res.error || 'Gagal mengubah mode.', false);
                    }
                } catch (err) {
                    showToast('Gagal terhubung ke server.', false);
                } finally {
                    btn.disabled = false;
                }
            });
        });
    }

    function updateStepperModeUI(modeVal) {
        document.querySelectorAll('[data-stepper-mode]').forEach(b => {
            const m = parseInt(b.getAttribute('data-stepper-mode'), 10);
            const pill = b.querySelector('.badge-active-pill');

            if (m === modeVal) {
                b.classList.add('active');
                if (pill) pill.classList.remove('d-none');
            } else {
                b.classList.remove('active');
                if (pill) pill.classList.add('d-none');
            }
        });

        const statusNote = document.getElementById('stepper-status-note');
        const headerPill = document.getElementById('header-mode-name');
        const stepperModeLabel = document.getElementById('stepper-mode-label');

        const names = {
            1: 'Mode 1: Putar Terus',
            2: 'Mode 2: Bolak-Balik',
            3: 'Mode 3: Berkala'
        };

        const label = names[modeVal] || `Mode ${modeVal}`;
        if (statusNote) statusNote.textContent = `Status: ${label} Aktif`;
        if (headerPill) headerPill.textContent = label;
        if (stepperModeLabel) stepperModeLabel.textContent = label;
    }

    function initRangeFilters() {
        document.querySelectorAll('[data-range-filter]').forEach(button => {
            button.addEventListener('click', (e) => {
                document.querySelectorAll('[data-range-filter]').forEach(b => b.classList.remove('active'));
                e.currentTarget.classList.add('active');
                config.currentRange = e.currentTarget.getAttribute('data-range-filter');
                fetchHistoryData();
            });
        });
    }

    async function fetchDashboardState() {
        try {
            const response = await fetch(`api/dashboard.php?device_id=${encodeURIComponent(config.deviceId)}`);
            if (!response.ok) return;

            const res = await response.json();
            if (!res.success || !res.data) return;

            updateDashboardUI(res.data);
        } catch (error) {
            console.error('Fetch error:', error);
        }
    }

    function updateDashboardUI(data) {
        const { device, stepper_mode, telemetry, actuators } = data;

        // 1. Connection indicators
        const connDot = document.getElementById('header-conn-dot');
        const connText = document.getElementById('header-conn-text');
        const espBadge = document.getElementById('esp-indicator-badge');
        const espText = document.getElementById('esp-indicator-text');
        const espLastPing = document.getElementById('esp-last-ping');

        if (device.is_online) {
            if (connDot) connDot.className = 'status-dot online';
            if (connText) connText.textContent = 'ONLINE';
            if (espBadge) espBadge.className = 'status-indicator-badge on';
            if (espText) espText.textContent = 'ONLINE';
        } else {
            if (connDot) connDot.className = 'status-dot offline';
            if (connText) connText.textContent = 'OFFLINE';
            if (espBadge) espBadge.className = 'status-indicator-badge off';
            if (espText) espText.textContent = 'OFFLINE';
        }

        if (espLastPing) {
            espLastPing.textContent = device.last_seen 
                ? `${device.seconds_since_seen}s lalu`
                : 'Belum terhubung';
        }

        // 2. Stepper mode state
        if (stepper_mode) {
            config.currentStepperMode = stepper_mode;
            updateStepperModeUI(stepper_mode);
        }

        // 3. Telemetry (Temperature & Humidity)
        const tempVal = document.getElementById('telemetry-temp-val');
        const humVal = document.getElementById('telemetry-hum-val');
        const tempBadge = document.getElementById('temp-condition-badge');

        if (telemetry.temperature !== null) {
            const t = Number(telemetry.temperature);
            if (tempVal) tempVal.textContent = t.toFixed(1);

            if (tempBadge) {
                if (telemetry.temp_status === 'COLD') {
                    tempBadge.className = 'badge condition-badge cold';
                    tempBadge.textContent = 'Dingin (< 37°C)';
                } else if (telemetry.temp_status === 'HOT') {
                    tempBadge.className = 'badge condition-badge hot';
                    tempBadge.textContent = 'Panas (> 38°C)';
                } else {
                    tempBadge.className = 'badge condition-badge optimal';
                    tempBadge.textContent = 'Ideal (37 - 38°C)';
                }
            }
        } else {
            if (tempVal) tempVal.textContent = '--';
        }

        if (telemetry.humidity !== null) {
            if (humVal) humVal.textContent = Number(telemetry.humidity).toFixed(1);
        } else {
            if (humVal) humVal.textContent = '--';
        }

        // 4. Actuators
        renderActuator('heater-indicator-badge', 'heater-indicator-text', actuators.actual_lamp);
        renderActuator('fan-indicator-badge', 'fan-indicator-text', actuators.actual_fan);

        // 5. Chart append
        if (sensorChart && telemetry.temperature !== null && telemetry.humidity !== null) {
            appendChartData(telemetry.temperature, telemetry.humidity);
        }
    }

    function renderActuator(badgeId, textId, actual) {
        const badge = document.getElementById(badgeId);
        const text = document.getElementById(textId);
        if (!badge || !text) return;

        if (actual === 1) {
            badge.className = 'status-indicator-badge on';
            text.textContent = 'ON';
        } else {
            badge.className = 'status-indicator-badge off';
            text.textContent = 'OFF';
        }
    }

    function initChart() {
        const ctx = document.getElementById('sensorChart');
        if (!ctx) return;

        sensorChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Suhu (°C)',
                        data: [],
                        borderColor: '#d97706',
                        backgroundColor: 'rgba(217, 119, 6, 0.08)',
                        fill: true,
                        borderWidth: 2,
                        yAxisID: 'yTemp',
                        tension: 0.3,
                        pointRadius: 2,
                    },
                    {
                        label: 'Kelembapan (%)',
                        data: [],
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2, 132, 199, 0.05)',
                        fill: true,
                        borderWidth: 2,
                        yAxisID: 'yHum',
                        tension: 0.3,
                        pointRadius: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 8, font: { family: 'Inter', size: 11 } }
                    },
                    yTemp: {
                        type: 'linear',
                        position: 'left',
                        title: { display: true, text: 'Suhu (°C)' },
                        min: 30,
                        max: 45,
                        grid: { color: '#f1f5f9' },
                    },
                    yHum: {
                        type: 'linear',
                        position: 'right',
                        title: { display: true, text: 'Kelembapan (%)' },
                        min: 30,
                        max: 90,
                        grid: { drawOnChartArea: false },
                    }
                },
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12 } }
                }
            }
        });
    }

    async function fetchHistoryData() {
        try {
            const url = `api/history.php?device_id=${encodeURIComponent(config.deviceId)}&range=${config.currentRange}&type=all&limit=60`;
            const response = await fetch(url);
            if (!response.ok) return;

            const res = await response.json();
            if (!res.success) return;

            if (res.sensor_history && sensorChart) {
                sensorChart.data.labels = res.sensor_history.map(row => row.time_label);
                sensorChart.data.datasets[0].data = res.sensor_history.map(row => row.temperature);
                sensorChart.data.datasets[1].data = res.sensor_history.map(row => row.humidity);
                sensorChart.update('none');
            }

            if (res.control_history) {
                renderControlLogs(res.control_history);
            }
        } catch (error) {
            console.error('History fetch error:', error);
        }
    }

    function appendChartData(newTemp, newHum) {
        if (!sensorChart || sensorChart.data.labels.length === 0) return;

        const now = new Date();
        const timeLabel = now.toTimeString().split(' ')[0];
        const lastLabel = sensorChart.data.labels[sensorChart.data.labels.length - 1];

        if (lastLabel === timeLabel) return;

        sensorChart.data.labels.push(timeLabel);
        sensorChart.data.datasets[0].data.push(newTemp);
        sensorChart.data.datasets[1].data.push(newHum);

        if (sensorChart.data.labels.length > 50) {
            sensorChart.data.labels.shift();
            sensorChart.data.datasets[0].data.shift();
            sensorChart.data.datasets[1].data.shift();
        }

        sensorChart.update('none');
    }

    function renderControlLogs(logs) {
        const tbody = document.getElementById('control-history-tbody');
        if (!tbody) return;

        if (logs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-secondary py-3">Belum ada riwayat aktivitas.</td></tr>`;
            return;
        }

        tbody.innerHTML = logs.slice(0, 15).map(log => {
            const heaterBadge = log.heater_status === 1 
                ? '<span class="status-indicator-badge on py-0 px-2" style="font-size: 0.7rem;">ON</span>' 
                : '<span class="status-indicator-badge off py-0 px-2" style="font-size: 0.7rem;">OFF</span>';

            const fanBadge = log.fan_status === 1 
                ? '<span class="status-indicator-badge on py-0 px-2" style="font-size: 0.7rem;">ON</span>' 
                : '<span class="status-indicator-badge off py-0 px-2" style="font-size: 0.7rem;">OFF</span>';

            const tempDisplay = log.trigger_temperature !== null 
                ? `${Number(log.trigger_temperature).toFixed(1)}°C` 
                : '-';

            return `
                <tr>
                    <td class="text-nowrap text-secondary font-monospace small">${log.formatted_time}</td>
                    <td><strong>${tempDisplay}</strong></td>
                    <td>${heaterBadge}</td>
                    <td>${fanBadge}</td>
                    <td>${escapeHtml(log.description)}</td>
                </tr>
            `;
        }).join('');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
