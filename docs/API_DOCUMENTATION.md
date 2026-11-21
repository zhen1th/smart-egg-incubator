# Dokumentasi REST API: Smart Egg Incubator

Seluruh endpoint REST API menggunakan format pertukaran data JSON standar dengan header `Content-Type: application/json; charset=utf-8`.

---

## 1. Pemilihan Mode Motor Stepper (Operating Mode)

Mengubah mode perputaran motor stepper pembalik telur (Mode 1, Mode 2, atau Mode 3) melalui tombol pada dashboard website. Pilihan mode langsung disimpan ke database MySQL dan dicatat ke tabel riwayat aktivitas lengkap dengan suhu dan status aktuator saat itu.

- **Endpoint:** `/website/api/set_mode.php`
- **Metode:** `POST`
- **Format Data:** JSON

### Request Body
```json
{
  "device_id": "INCUBATOR_01",
  "mode": 2
}
```
*Catatan parameter `mode`:*
* `1`: Putar Terus (*Continuous Rotation*)
* `2`: Bolak-Balik (*Continuous Oscillation / Swing*)
* `3`: Berkala (*Periodic with 30s Pause*)

### Response Sukses (200 OK)
```json
{
  "success": true,
  "message": "Mode motor stepper berhasil diubah ke Mode 2: Bolak-Balik",
  "stepper_mode": 2,
  "mode_info": {
    "id": 2,
    "name": "Mode 2: Bolak-Balik",
    "desc": "Berputar bolak-balik terus-menerus tanpa jeda diam."
  },
  "telemetry": {
    "temperature": 37.4,
    "heater_status": 1,
    "fan_status": 0
  }
}
```

---

## 2. Ingest Telemetri Sensor & Penerimaan Perintah Perangkat

ESP32 mengirim data pembacaan sensor DHT11 dan status perangkat setiap 2 detik. Server memproses data tersebut, mengevaluasi suhu terhadap ambang batas baku, dan langsung mengembalikan perintah relay serta mode stepper dalam respon HTTP yang sama.

- **Endpoint:** `/website/api/telemetry.php`
- **Metode:** `POST`
- **Autentikasi:** Header `X-API-Key` atau parameter `api_key` pada payload JSON.

### Request Headers & Body
```http
POST /smart-egg-incubator/website/api/telemetry.php HTTP/1.1
Host: 192.168.10.210
Content-Type: application/json
X-API-Key: INCUBATOR_SECRET_KEY_2026

{
  "device_id": "INCUBATOR_01",
  "temperature": 36.8,
  "humidity": 64.2,
  "sensor_valid": true,
  "lamp_status": 0,
  "fan_status": 0
}
```

### Response Sukses (200 OK)
```json
{
  "success": true,
  "commands": {
    "lamp": 1,
    "fan": 0,
    "stepper_mode": 2
  },
  "server_time": "2026-11-21 11:20:00"
}
```

### Logika Keputusan Server pada Respon:
1. **Suhu < 37.0 °C:** `lamp = 1`, `fan = 0` (Lampu pemanas ON, Kipas OFF).
2. **Suhu > 38.0 °C:** `lamp = 0`, `fan = 1` (Lampu pemanas OFF, Kipas pendingin ON).
3. **37.0 °C – 38.0 °C:** Mempertahankan nilai `lamp` dan `fan` sebelumnya (*Histeresis*).
4. **`stepper_mode`:** Mengirimkan angka mode aktif (1, 2, atau 3) yang tersimpan di database.

---

## 3. Status Waktu Nyata Dashboard (Polling 2 Detik)

Digunakan oleh frontend JavaScript untuk memperbarui tampilan dashboard setiap 2 detik tanpa perlu memuat ulang seluruh halaman.

- **Endpoint:** `/website/api/dashboard.php`
- **Metode:** `GET`
- **Parameter URL:** `device_id` (opsional, default: `INCUBATOR_01`)

### Response Sukses (200 OK)
```json
{
  "success": true,
  "data": {
    "device": {
      "device_id": "INCUBATOR_01",
      "is_online": true,
      "seconds_since_seen": 1,
      "last_seen": "2026-11-21 11:20:00"
    },
    "stepper_mode": 2,
    "telemetry": {
      "temperature": 37.4,
      "humidity": 62.0,
      "temp_status": "OPTIMAL",
      "target_min": 37.0,
      "target_max": 38.0
    },
    "actuators": {
      "command_heater": 1,
      "command_fan": 0,
      "actual_lamp": 1,
      "actual_fan": 0,
      "stepper_mode": 2
    },
    "latest_activity": {
      "change_type": "STEPPER_MODE",
      "heater_status": 1,
      "fan_status": 0,
      "trigger_temperature": 37.4,
      "description": "Mode motor stepper diubah ke Mode 2: Bolak-Balik...",
      "created_at": "2026-11-21 11:18:24"
    },
    "server_time": "2026-11-21 11:20:01"
  }
}
```

---

## 4. Riwayat Sensor dan Aktivitas Kontrol

Menyediakan data historis pembacaan sensor untuk rendering grafik Chart.js dan tabel audit aktivitas.

- **Endpoint:** `/website/api/history.php`
- **Metode:** `GET`
- **Parameter URL:**
  - `device_id` (opsional, default: `INCUBATOR_01`)
  - `range` (`10m`, `1h`, `6h`, `24h`, `7d`, default: `1h`)
  - `type` (`sensor`, `control`, atau `all`, default: `all`)
  - `limit` (angka, default: `60`)

### Response Sukses (200 OK)
```json
{
  "success": true,
  "range": "1h",
  "sensor_history": [
    {
      "id": 201,
      "temperature": 37.2,
      "humidity": 61.5,
      "recorded_at": "2026-11-21 11:15:02",
      "time_label": "11:15:02"
    }
  ],
  "control_history": [
    {
      "id": 85,
      "change_type": "STEPPER_MODE",
      "heater_status": 1,
      "fan_status": 0,
      "trigger_temperature": 37.2,
      "description": "Mode motor stepper diubah ke Mode 1: Putar Terus...",
      "created_at": "2026-11-21 11:15:02",
      "formatted_time": "2026-11-21 11:15:02"
    }
  ]
}
```
