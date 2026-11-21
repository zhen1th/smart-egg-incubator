<?php
/**
 * Smart Egg Incubator - Simple & Modern IoT Dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireAuth();

$pageTitle = 'Dashboard';
$extraScripts = ['assets/js/dashboard.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-0">
    <!-- SECTION 1: MODE MOTOR STEPPER (3 Tombol Pilihan Sederhana) -->
    <section class="mb-4" id="stepper-mode-section">
        <div class="card clean-card">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-repeat text-amber"></i>
                        <span>Mode Motor Stepper</span>
                    </h5>
                    <span class="small text-secondary" id="stepper-status-note">Status: Mode 1 Aktif</span>
                </div>

                <div class="row g-2 g-md-3">
                    <!-- Mode 1 Button -->
                    <div class="col-12 col-md-4">
                        <button type="button" class="btn btn-stepper-mode w-100 active" id="btn-mode-1" data-stepper-mode="1">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-clockwise fs-5"></i>
                                <div class="text-start">
                                    <div class="fw-bold">Mode 1</div>
                                    <div class="small text-mode-sub">Putar Terus (Continuous)</div>
                                </div>
                            </div>
                            <span class="badge badge-active-pill">Aktif</span>
                        </button>
                    </div>

                    <!-- Mode 2 Button -->
                    <div class="col-12 col-md-4">
                        <button type="button" class="btn btn-stepper-mode w-100" id="btn-mode-2" data-stepper-mode="2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-left-right fs-5"></i>
                                <div class="text-start">
                                    <div class="fw-bold">Mode 2</div>
                                    <div class="small text-mode-sub">Bolak-Balik (Swing)</div>
                                </div>
                            </div>
                            <span class="badge badge-active-pill d-none">Aktif</span>
                        </button>
                    </div>

                    <!-- Mode 3 Button -->
                    <div class="col-12 col-md-4">
                        <button type="button" class="btn btn-stepper-mode w-100" id="btn-mode-3" data-stepper-mode="3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-hourglass-split fs-5"></i>
                                <div class="text-start">
                                    <div class="fw-bold">Mode 3</div>
                                    <div class="small text-mode-sub">Berkala (Jeda 30 Detik)</div>
                                </div>
                            </div>
                            <span class="badge badge-active-pill d-none">Aktif</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: SENSOR TELEMETRY -->
    <section class="mb-4" id="monitoring-section">
        <div class="row g-3">
            <!-- Suhu Card -->
            <div class="col-12 col-md-6">
                <div class="card clean-card h-100">
                    <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary small fw-semibold text-uppercase">Suhu Inkubator</span>
                            <span class="badge condition-badge optimal" id="temp-condition-badge">Ideal (37 - 38°C)</span>
                        </div>
                        <div class="d-flex align-items-baseline my-2">
                            <span class="display-5 fw-bold text-slate-900" id="telemetry-temp-val">--</span>
                            <span class="fs-4 text-secondary ms-1">°C</span>
                        </div>
                        <div class="text-secondary small pt-2 border-top">
                            Target Otomatis: <strong>37.0°C - 38.0°C</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kelembapan Card -->
            <div class="col-12 col-md-6">
                <div class="card clean-card h-100">
                    <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary small fw-semibold text-uppercase">Kelembapan Udara</span>
                            <span class="badge condition-badge optimal">Normal (55% - 65%)</span>
                        </div>
                        <div class="d-flex align-items-baseline my-2">
                            <span class="display-5 fw-bold text-slate-900" id="telemetry-hum-val">--</span>
                            <span class="fs-4 text-secondary ms-1">% RH</span>
                        </div>
                        <div class="text-secondary small pt-2 border-top">
                            Sensor: <strong>DHT11</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: DEVICE STATUS -->
    <section class="mb-4">
        <div class="row g-3">
            <!-- Lampu Pemanas -->
            <div class="col-6 col-lg-3">
                <div class="card clean-card p-3 h-100">
                    <div class="text-secondary small fw-semibold mb-2">Lampu Pemanas</div>
                    <div class="mb-2">
                        <span class="status-indicator-badge off" id="heater-indicator-badge">
                            <span class="indicator-dot"></span>
                            <span id="heater-indicator-text">OFF</span>
                        </span>
                    </div>
                    <div class="small text-secondary">Pemanas Ruang</div>
                </div>
            </div>

            <!-- Kipas Pendingin -->
            <div class="col-6 col-lg-3">
                <div class="card clean-card p-3 h-100">
                    <div class="text-secondary small fw-semibold mb-2">Kipas Pendingin</div>
                    <div class="mb-2">
                        <span class="status-indicator-badge off" id="fan-indicator-badge">
                            <span class="indicator-dot"></span>
                            <span id="fan-indicator-text">OFF</span>
                        </span>
                    </div>
                    <div class="small text-secondary">Pendingin / Sirkulasi</div>
                </div>
            </div>

            <!-- Koneksi ESP32 -->
            <div class="col-6 col-lg-3">
                <div class="card clean-card p-3 h-100">
                    <div class="text-secondary small fw-semibold mb-2">Koneksi ESP32</div>
                    <div class="mb-2">
                        <span class="status-indicator-badge off" id="esp-indicator-badge">
                            <span class="indicator-dot"></span>
                            <span id="esp-indicator-text">OFFLINE</span>
                        </span>
                    </div>
                    <div class="small text-secondary" id="esp-last-ping">Menunggu data...</div>
                </div>
            </div>

            <!-- Motor Stepper -->
            <div class="col-6 col-lg-3">
                <div class="card clean-card p-3 h-100">
                    <div class="text-secondary small fw-semibold mb-2">Motor Stepper</div>
                    <div class="mb-2">
                        <span class="status-indicator-badge on" id="stepper-indicator-badge">
                            <span class="indicator-dot"></span>
                            <span id="stepper-indicator-text">700 steps/s</span>
                        </span>
                    </div>
                    <div class="small text-secondary" id="stepper-mode-label">Mode 1: Putar Terus</div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: REAL-TIME CHART -->
    <section class="mb-4" id="chart-section">
        <div class="card clean-card">
            <div class="card-header bg-white py-3 px-3 px-md-4 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="fw-bold mb-0">Grafik Suhu & Kelembapan</h5>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-filter" data-range-filter="10m">10m</button>
                    <button type="button" class="btn btn-filter active" data-range-filter="1h">1h</button>
                    <button type="button" class="btn btn-filter" data-range-filter="6h">6h</button>
                    <button type="button" class="btn btn-filter" data-range-filter="24h">24h</button>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div style="height: 300px; position: relative;">
                    <canvas id="sensorChart"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: ACTIVITY LOG -->
    <section class="mb-4" id="activity-log-section">
        <div class="card clean-card">
            <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom">
                <h5 class="fw-bold mb-0">Riwayat Aktivitas Kontrol</h5>
            </div>
            <div class="table-responsive">
                <table class="table modern-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 170px;">Waktu</th>
                            <th style="width: 100px;">Suhu</th>
                            <th style="width: 90px;">Lampu</th>
                            <th style="width: 90px;">Kipas</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="control-history-tbody">
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-3">Memuat riwayat...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
