# Panduan Instalasi dan Pengujian: Smart Egg Incubator

Panduan langkah demi langkah untuk menginstal backend, database, website, dan mengunggah kode firmware ke ESP32.

---

## 1. Tim Pengembang (Praktikum IoT)

- **Partawijaya Rihal Dariretci** (123240181)
- **Yohanes Herang Aji Dharma** (123240191)
- **Alvin Andhika Putra** (123240193)
- **Kafka Akmal Dani** (123240203)

---

## 2. Persiapan Server Lokal (XAMPP di Windows)

1. **Jalankan XAMPP Control Panel:**
   - Aktifkan modul **Apache**.
   - Aktifkan modul **MySQL**.
2. **Pastikan Folder Proyek Berada di Direktori htdocs:**
   - Lokasi folder: `C:\xampp\htdocs\smart-egg-incubator`
3. **Impor Skema Database:**
   - Buka browser dan akses **phpMyAdmin**: `http://localhost/phpmyadmin`
   - Klik menu **Import**.
   - Pilih file: `C:\xampp\htdocs\smart-egg-incubator\database\smart_egg_incubator.sql`
   - Klik tombol **Import / Go**.
   - Skema database `smart_egg_incubator` beserta tabel `devices`, `sensor_logs`, `control_logs`, dan `users` akan dibuat otomatis.
4. **Kredensial Default:**
   - **Database:** Host `127.0.0.1`, User `root`, Password kosong `""`.
   - **Login Admin Website:** Username `admin`, Password `admin123`.
   - **API Key ESP32:** `INCUBATOR_SECRET_KEY_2026`.

---

## 3. Pengujian Website Secara Lokal

1. Buka browser dan akses alamat:
   `http://localhost/smart-egg-incubator/website/`
2. Masukkan username `admin` dan password `admin123`.
3. Anda akan diarahkan ke dashboard monitoring. Awalnya ESP32 berstatus **OFFLINE** karena perangkat belum terhubung.
4. Uji endpoint API telemetri menggunakan PowerShell atau curl untuk memastikan backend berfungsi:
   ```powershell
   Invoke-RestMethod -Uri "http://localhost/smart-egg-incubator/website/api/telemetry.php" -Method Post -Headers @{"Content-Type"="application/json"; "X-API-Key"="INCUBATOR_SECRET_KEY_2026"} -Body '{"device_id":"INCUBATOR_01","temperature":36.2,"humidity":64.0,"sensor_valid":true,"lamp_status":0,"fan_status":0}'
   ```
   Server akan merespons dengan keputusan pemanas dan mode stepper aktif:
   ```json
   {"success":true,"commands":{"lamp":1,"fan":0,"stepper_mode":1},"server_time":"..."}
   ```
5. Refresh dashboard website, indikator ESP32 akan langsung berubah menjadi **ONLINE**, suhu menunjukkan 36.2°C, dan lampu berstatus **ON**.
6. Uji pemilihan mode motor stepper dengan mengklik tombol **Mode 1**, **Mode 2**, atau **Mode 3** pada kartu *Mode Operasi Motor Stepper*. Mode aktif akan langsung diperbarui di database dan tercatat di tabel riwayat aktivitas beserta suhu dan status aktuator saat itu.

---

## 4. Konfigurasi dan Pengunggahan Firmware ESP32

### A. Persiapan Library Arduino IDE
Buka **Arduino IDE** -> **Sketch** -> **Include Library** -> **Manage Libraries...**
Instal library berikut:
1. **DHT sensor library** (oleh Adafruit)
2. **Adafruit Unified Sensor**
3. **LiquidCrystal_I2C** (oleh Frank de Brabander / Marco Schwartz)
4. **AccelStepper** (oleh Mike McCauley)
5. **ArduinoJson** (oleh Benoit Blanchon, versi 6 atau 7)

### B. Konfigurasi Firmware
Buka file `esp32/smart_egg_incubator/smart_egg_incubator.ino` pada Arduino IDE:
1. **Wi-Fi Settings:**
   - Ubah `WIFI_SSID` dan `WIFI_PASSWORD` sesuai hotspot Wi-Fi Anda.
   - *Penting:* ESP32 hanya mendukung pita frekuensi **2.4 GHz**. Pastikan hotspot tidak di-set khusus 5 GHz (pada iPhone, aktifkan *Maximize Compatibility*).
2. **Server Host:**
   - Cari IP laptop Anda di jaringan Wi-Fi lokal dengan perintah `ipconfig` (misalnya `192.168.10.210`).
   - Ganti `SERVER_HOST` menjadi IP laptop Anda.
   - Pastikan laptop dan ESP32 terhubung ke router / hotspot yang sama.
3. **Upload Firmware:**
   - Sambungkan ESP32 ke laptop via kabel data micro USB.
   - Pilih board **ESP32 Dev Module**, pilih port COM yang sesuai (cek Device Manager jika ragu).
   - Klik **Upload** (tahan tombol **BOOT** pada ESP32 jika proses upload tertahan di `Connecting........_____`).

### C. Pinout Hardware ESP32
- **DHT11 Data:** GPIO 4
- **LCD I2C SDA:** GPIO 21 (Alamat `0x27`)
- **LCD I2C SCL:** GPIO 22
- **Relay Lampu (Heater):** GPIO 25 (Active LOW: `LOW` = ON, `HIGH` = OFF)
- **Relay Kipas (Fan):** GPIO 26 (Active LOW: `LOW` = ON, `HIGH` = OFF)
- **Stepper Driver ULN2003:** IN1 -> GPIO 16, IN2 -> GPIO 17, IN3 -> GPIO 18, IN4 -> GPIO 19
  - *Constructor:* `AccelStepper stepper(AccelStepper::HALF4WIRE, 16, 18, 17, 19);` (urutan pin IN1, IN3, IN2, IN4).

---

## 5. Matriks Pengujian Kondisi Suhu, Stepper, & Fail-Safe

| Skenario | Nilai / Kondisi Uji | Perilaku Backend Server | Respon Hardware ESP32 | Tampilan LCD 16x2 |
|---|---|---|---|---|
| **1. Suhu Rendah** | 35.8 °C (< 37.0°C) | Server memerintahkan `lamp: 1, fan: 0` | Relay Lampu ON (GPIO 25 LOW), Relay Fan OFF | `T:35.8C  H:64.0%`<br>`L:ON  F:OFF  M:1` |
| **2. Suhu Optimal** | 37.5 °C (37.0 - 38.0°C) | Server mempertahankan status sebelumnya (Histeresis) | Status relay tidak berubah | `T:37.5C  H:62.0%`<br>`L:ON  F:OFF  M:1` |
| **3. Suhu Tinggi** | 38.6 °C (> 38.0°C) | Server memerintahkan `lamp: 0, fan: 1` | Relay Lampu OFF, Relay Fan ON (GPIO 26 LOW) | `T:38.6C  H:60.0%`<br>`L:OFF F:ON   M:1` |
| **4. Interlock Safety** | Cek simultaneous state | Logika PHP mencegah kedua relay aktif bersamaan | Lampu dan Kipas tidak pernah menyala bersamaan | Status interlock terjaga |
| **5. Stepper Mode 1** | Pilih Mode 1 di web | Server kirim `stepper_mode: 1` | Motor stepper berputar kontinu satu arah (700 steps/dtk) | Baris 2 ujung kanan: `M:1` |
| **6. Stepper Mode 2** | Pilih Mode 2 di web | Server kirim `stepper_mode: 2` | Motor stepper berputar bolak-balik ($\pm 45^\circ$) tanpa jeda diam | Baris 2 ujung kanan: `M:2` |
| **7. Stepper Mode 3** | Pilih Mode 3 di web | Server kirim `stepper_mode: 3` | Motor stepper bergerak berkala lalu diam selama 30 detik | Baris 2 ujung kanan: `M:3` |
| **8. Sensor Error** | Kabel DHT11 lepas | ESP32 kirim `sensor_valid: false` | Server & ESP32 matikan pemanas demi keamanan | `T:--.-C  H:--.-%`<br>`L:OFF F:OFF  M:x` |
| **9. Koneksi Putus** | Wi-Fi / Laptop mati > 10 dtk | ESP32 mendeteksi timeout lokal | Fail-safe: Heater otomatis dimatikan | `T:37.2C  H:65.0%`<br>`ERR:OFFLINE  M:x` |

---

## 6. Panduan Hosting Website (Akses Publik Tanpa Laptop)

Agar sistem dapat diakses secara publik melalui internet 24/7 tanpa perlu laptop menyala:

1. **Sewa Hosting cPanel / VPS:**
   - Gunakan shared hosting cPanel atau VPS (misalnya Niagahoster, DomaiNesia, IDCloudHost).
2. **Impor Database:**
   - Buat database MySQL di cPanel via menu **MySQL Databases**.
   - Buka **phpMyAdmin** cPanel dan impor file `smart_egg_incubator.sql`.
3. **Unggah File Website:**
   - Unggah seluruh isi folder `website/` ke direktori `public_html/`.
   - Sesuaikan konfigurasi database pada `website/config/database.php` (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
4. **Aktifkan SSL (HTTPS):**
   - Aktifkan Let's Encrypt / AutoSSL gratis pada domain Anda.
5. **Konfigurasi Firmware ESP32:**
   - Ubah `USE_HTTPS = true`.
   - Ubah `SERVER_HOST = "domain-anda.com"`.
   - Ubah `SERVER_PORT = 443`.
   - Masukkan Root CA sertifikat SSL pada variabel `ROOT_CA_CERTIFICATE`.
