# Smart Egg Incubator IoT System

Sistem inkubator telur berbasis Internet of Things (IoT) yang mengintegrasikan mikrokontroler ESP32 dengan web server berbasis PHP Native dan database MySQL. Seluruh logika pengambilan keputusan suhu dijalankan secara terpusat di backend server, dilengkapi dengan pengendalian **3 Mode Operasi Motor Stepper Pembalik Telur** secara *real-time* melalui website.

---

## 1. Tim Pengembang (Praktikum IoT)

- **Partawijaya Rihal Dariretci** (123240181)
- **Yohanes Herang Aji Dharma** (123240191)
- **Alvin Andhika Putra** (123240193)
- **Kafka Akmal Dani** (123240203)

---

## 2. Arsitektur Sistem

```
+-------------------------------------------------------------+
|                        ESP32 Device                         |
|  - Sensor DHT11 (GPIO 4)                                     |
|  - LCD I2C 16x2 (GPIO 21/22, Alamat 0x27)                    |
|  - Stepper Motor AccelStepper (GPIO 16, 17, 18, 19)          |
|  - Relay Heater/Lampu (GPIO 25, Active LOW)                 |
|  - Relay Fan (GPIO 26, Active LOW)                          |
+------------------------------+------------------------------+
                               |
                   HTTP POST / Telemetry JSON
                        (Setiap 2 Detik)
                               |
                               v
+-------------------------------------------------------------+
|                     Backend Server (PHP)                    |
|  - Endpoint: /api/telemetry.php & /api/set_mode.php         |
|  - Validasi Header API Key & Format Data JSON               |
|  - Evaluasi Pengendalian Suhu Terpusat:                     |
|      * Suhu < 37.0 °C  -> Lampu ON, Kipas OFF               |
|      * Suhu > 38.0 °C  -> Lampu OFF, Kipas ON               |
|      * 37.0°C - 38.0°C -> Pertahankan Status Sebelumnya     |
|  - Hardware Interlock (Cegah Lampu & Kipas Aktif Bersamaan) |
|  - Pengelolaan Perintah Mode Stepper (Mode 1, 2, atau 3)    |
|  - Pembersihan Otomatis Retensi Data Sensor                 |
+------------------------------+------------------------------+
                               |
                    Penyimpanan & Sinkronisasi
                               |
                               v
+-------------------------------------------------------------+
|                       Database MySQL                        |
|  - devices      : Status terkini perangkat & mode stepper   |
|  - sensor_logs  : Riwayat telemetri suhu & kelembapan        |
|  - control_logs : Audit log perubahan relay & pergantian mode|
|  - users        : Kredensial administrator web dashboard     |
+------------------------------+------------------------------+
                               |
                  Polling JSON (Setiap 2 Detik)
                               |
                               v
+-------------------------------------------------------------+
|                    Frontend Web Dashboard                   |
|  - Desain Modern, Bersih, Sederhana, dan Responsif          |
|  - 3 Tombol Pemilihan Mode Motor Stepper Interaktif         |
|  - Pemantauan Angka Suhu (°C) & Kelembapan (% RH)           |
|  - Status Aktual Lampu, Kipas, ESP32, & Motor Stepper       |
|  - Grafik Riwayat Sensor Chart.js (10m, 1h, 6h, 24h)        |
|  - Tabel Riwayat Aktivitas Kontrol Lengkap                  |
|  - Footer Profil Anggota Kelompok                           |
+-------------------------------------------------------------+
```

---

## 3. Mode Pengoperasian Motor Stepper (Egg Turning)

Sistem memiliki 3 mode gerakan motor stepper pembalik telur yang dapat diubah kapan saja melalui website:

| Mode | Nama Mode | Pola Gerakan Stepper | Pengaruh & Tujuan Biologis pada Telur |
|---|---|---|---|
| **Mode 1** | **Putar Terus (*Continuous*)** | Berputar satu arah terus-menerus (700 steps/dtk). | Menjaga kuning telur tetap mengapung dinamis di tengah cairan albumen; mencegah embrio menempel pada dinding cangkang di awal inkubasi. |
| **Mode 2** | **Bolak-Balik (*Swing*)** | Berputar bolak-balik $\pm 45^\circ$ tanpa jeda diam. | Mengalirkan cairan nutrisi bersentuhan dengan membran korioalantois (CAM); optimal untuk telur bercangkang tebal (seperti telur bebek). |
| **Mode 3** | **Berkala (*Periodic*)** | Berputar memiringkan baki, lalu **jeda diam 30 detik**. | Memberikan periode tenang bagi embrio agar kapiler darah tidak pecah, menstabilkan cairan amnion, dan memfasilitasi posisi paruh siap menetas. |

---

## 4. Format Tampilan Layar LCD I2C 16x2

Layar LCD 16x2 menampilkan parameter penting secara ringkas menggunakan singkatan bahasa Inggris standar, pas tepat 16 karakter di setiap baris:

```text
+----------------+
|T:37.2C  H:65.0%|  <- Baris 1: Temperature & Humidity
|L:ON  F:OFF  M:1|  <- Baris 2: Lamp, Fan, & Mode Stepper
+----------------+
```

* **`T`**: Temperature (Suhu terkini dari sensor DHT11 dalam °C).
* **`H`**: Humidity (Kelembapan udara terkini dari sensor DHT11 dalam % RH).
* **`L`**: Lamp / Heater (`ON` / `OFF`).
* **`F`**: Fan / Kipas (`ON` / `OFF`).
* **`M`**: Mode Stepper yang sedang berjalan (`1`, `2`, atau `3`).
* **Saat Offline**: Baris kedua otomatis menampilkan `ERR:OFFLINE  M:x`.

---

## 5. Spesifikasi Pinout Hardware ESP32

| Komponen Hardware | Pin ESP32 | Keterangan & Logika |
|---|---|---|
| **DHT11 Data** | GPIO 4 | Pembacaan suhu dan kelembapan setiap 2 detik |
| **LCD I2C SDA** | GPIO 21 | Komunikasi serial data I2C (Alamat: `0x27`) |
| **LCD I2C SCL** | GPIO 22 | Komunikasi serial clock I2C |
| **Relay Lampu Pemanas** | GPIO 25 | Active LOW: `LOW` = ON, `HIGH` = OFF |
| **Relay Kipas Pendingin**| GPIO 26 | Active LOW: `LOW` = ON, `HIGH` = OFF |
| **Stepper Motor IN1** | GPIO 16 | AccelStepper HALF4WIRE (Pin urutan 1) |
| **Stepper Motor IN2** | GPIO 17 | AccelStepper HALF4WIRE (Pin urutan 3) |
| **Stepper Motor IN3** | GPIO 18 | AccelStepper HALF4WIRE (Pin urutan 2) |
| **Stepper Motor IN4** | GPIO 19 | AccelStepper HALF4WIRE (Pin urutan 4) |

> **Catatan Constructor Stepper:**
> Susunan constructor AccelStepper pada ESP32 tetap mengikuti urutan:
> `AccelStepper stepper(AccelStepper::HALF4WIRE, 16, 18, 17, 19);` (IN1, IN3, IN2, IN4).

---

## 6. Struktur Direktori Proyek

```text
smart-egg-incubator/
|-- database/
|   |-- smart_egg_incubator.sql        # Skema database MySQL lengkap
|-- website/
|   |-- index.php                      # Entry point router
|   |-- login.php                      # Halaman login administrator
|   |-- dashboard.php                  # Web dashboard pemantauan & tombol mode
|   |-- logout.php                     # Handler keluar sesi
|   |-- api/
|   |   |-- telemetry.php              # Ingest telemetri ESP32 & kirim perintah
|   |   |-- set_mode.php               # Endpoint pergantian mode stepper
|   |   |-- dashboard.php              # Polling real-time telemetri & mode
|   |   |-- history.php                # Riwayat sensor untuk Chart.js & log audit
|   |-- config/
|   |   |-- database.php               # Koneksi database PDO
|   |   |-- device_config.php          # Konfigurasi batas suhu & mode stepper
|   |-- includes/
|   |   |-- auth.php                   # Proteksi sesi login
|   |   |-- header.php                 # Header & navigasi sidebar
|   |   |-- footer.php                 # Footer profil anggota kelompok & toast
|   |   |-- temperature_engine.php     # Evaluasi logika suhu baku di backend
|   |-- assets/
|       |-- css/
|       |   |-- style.css              # Stylesheet tampilan bersih dan modern
|       |-- js/
|           |-- dashboard.js           # Polling real-time, event tombol, & Chart.js
|-- esp32/
|   |-- smart_egg_incubator.ino        # Kode firmware lengkap mikrokontroler ESP32
|   |-- smart_egg_incubator/
|       |-- smart_egg_incubator.ino    # Salinan sketsa untuk folder Arduino IDE
|-- docs/
    |-- README.md                      # Dokumentasi utama proyek
    |-- API_DOCUMENTATION.md           # Spesifikasi lengkap REST API
    |-- INSTALLATION_GUIDE.md          # Panduan instalasi dan pengujian
```
