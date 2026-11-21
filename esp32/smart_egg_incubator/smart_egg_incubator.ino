#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <DHT.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <AccelStepper.h>
#include <ArduinoJson.h>
#include "soc/soc.h"
#include "soc/rtc_cntl_reg.h"

/* Hardware Pin Definitions */
#define DHTPIN               4
#define DHTTYPE              DHT11

#define I2C_SDA_PIN          21
#define I2C_SCL_PIN          22
#define LCD_I2C_ADDR         0x27

#define RELAY_HEATER_PIN     25
#define RELAY_FAN_PIN        26

#define STEPPER_PIN_IN1      16
#define STEPPER_PIN_IN2      17
#define STEPPER_PIN_IN3      18
#define STEPPER_PIN_IN4      19

/* Network Configuration */
const char* WIFI_SSID     = "zhen1th";
const char* WIFI_PASSWORD = "janganmeledakplis";

/* Server Telemetry Configuration */
// Set USE_HTTPS to true when hosting on internet with SSL certificate
const bool USE_HTTPS      = false; 
const char* SERVER_HOST   = "192.168.10.210"; // IP Laptop Anda
const int   SERVER_PORT   = 80;               // 80 for HTTP, 443 for HTTPS
const char* API_PATH      = "/smart-egg-incubator/website/api/telemetry.php";

const char* DEVICE_ID     = "INCUBATOR_01";
const char* API_KEY       = "INCUBATOR_SECRET_KEY_2026";

/* Root CA Certificate for production HTTPS (used when USE_HTTPS is true) */
const char* ROOT_CA_CERTIFICATE PROGMEM = \
"-----BEGIN CERTIFICATE-----\n" \
"MIIDdzCCAl+gAwIBAgIEAgAAuTANBgkqhkiG9w0BAQsFADBaMQswCQYDVQQGEwJJ\n" \
"RTESMBAGA1UEChMJQmFsdGltb3JlMRMwEQYDVQQLEwpDeWJlclRydXN0MSIwIAYD\n" \
"VQQDExlCYWx0aW1vcmUgQ3liZXJUcnVzdCBSb290MB4XDTAwMDUxMjE4NDYwMFoX\n" \
"DTI1MDUxMjIzNTkwMFowWjELMAkGA1UEBhMCSUUxEjAQBgNVBAoTCUJhbHRpbW9y\n" \
"ZTETMBEGA1UECxMKQ3liZXJUcnVzdDEiMCAGA1UEAxMZQmFsdGltb3JlIEN5YmVy\n" \
"VHJ1c3QgUm9vdDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAKMESTAP\n" \
"URioBnnhjR+/RozQIIegUmRCgCGbssoe2MdBhEGhTjxtUSTVegPnglMFe7SMAzXL\n" \
"GYLqpuIsKHgn0VLQEdAxQKCKqSu52DZ8h7EjJ5bS3B+/tKE2uneyFe9G9IvSh++6\n" \
"1PPagMxAfUsMdf6KYDx61QgMZ20bq0f5OAkSPAXl47PMFg//8bz9MUVJG8jvn8Xz\n" \
"BFWQKZ3R6s+0kqmsOxflfa5hQcdExt0K/J860n54999555555555555555555555\n" \
"-----END CERTIFICATE-----\n";

/* Hardware Instances */
DHT dht(DHTPIN, DHTTYPE);
LiquidCrystal_I2C lcd(LCD_I2C_ADDR, 16, 2);

// AccelStepper constructor pin order must be IN1, IN3, IN2, IN4
AccelStepper stepper(AccelStepper::HALF4WIRE, STEPPER_PIN_IN1, STEPPER_PIN_IN3, STEPPER_PIN_IN2, STEPPER_PIN_IN4);

/* Stepper Motor Operation Mode:
 * 1 = Putar terus-menerus (Continuous Rotation)
 * 2 = Bolak-balik terus-menerus (Continuous Oscillation)
 * 3 = Pembalik berkala (Periodic 30s)
 */
int currentStepperMode = 1;

/* Operating Variables */
unsigned long lastTelemetryMillis = 0;
const unsigned long TELEMETRY_INTERVAL_MS = 2000;

unsigned long lastServerContactMillis = 0;
const unsigned long SERVER_FAILSAFE_TIMEOUT_MS = 10000;

unsigned long lastStepperMoveMillis = 0;
const unsigned long STEPPER_TURN_INTERVAL_MS = 30000;
bool stepperDirectionForward = true;
long stepperOscillateTarget = 1024;

// Relay states: 1 = ON, 0 = OFF (physical pin is Active LOW)
int currentHeaterState = 0;
int currentFanState = 0;

void setStepperMode(int newMode) {
  if (newMode < 1 || newMode > 3) return;
  if (newMode == currentStepperMode) return;

  Serial.printf("[STEPPER] Switching mode from %d to %d\n", currentStepperMode, newMode);
  currentStepperMode = newMode;

  // Reset koordinat langkah ke 0 agar akumulasi langkah dari mode sebelumnya (terutama Mode 1)
  // tidak menyebabkan motor berputar balik ribuan langkah untuk mencari titik nol lama.
  stepper.setCurrentPosition(0);

  if (currentStepperMode == 1) {
    stepper.setSpeed(700.0);
  } else if (currentStepperMode == 2) {
    stepperOscillateTarget = 1024;
    stepper.moveTo(stepperOscillateTarget);
  } else if (currentStepperMode == 3) {
    stepper.moveTo(512);
    stepperDirectionForward = false;
    lastStepperMoveMillis = millis();
  }
}

void applyRelays(int heaterCmd, int fanCmd) {
  // Physical interlock: Never allow both heater and fan to be ON together
  if (heaterCmd == 1 && fanCmd == 1) {
    fanCmd = 0;
  }

  currentHeaterState = heaterCmd;
  currentFanState = fanCmd;

  // Active LOW logic: LOW = ON, HIGH = OFF
  digitalWrite(RELAY_HEATER_PIN, currentHeaterState ? LOW : HIGH);
  digitalWrite(RELAY_FAN_PIN, currentFanState ? LOW : HIGH);
}

void triggerEmergencyFailSafe(const char* reason) {
  // Emergency protection: Turn heater OFF immediately to prevent overheating
  digitalWrite(RELAY_HEATER_PIN, HIGH);
  digitalWrite(RELAY_FAN_PIN, HIGH);
  currentHeaterState = 0;
  currentFanState = 0;

  lcd.setCursor(0, 1);
  lcd.print("SAFE: ");
  lcd.print(reason);
}

void updateLcd(float temp, float hum, bool sensorOk, bool serverOk) {
  char line0[17];
  char line1[17];

  lcd.setCursor(0, 0);
  if (sensorOk) {
    snprintf(line0, sizeof(line0), "T:%.1fC  H:%.1f%%", temp, hum);
  } else {
    snprintf(line0, sizeof(line0), "SENSOR ERROR    ");
  }
  line0[16] = '\0';
  lcd.print(line0);

  lcd.setCursor(0, 1);
  if (!serverOk) {
    snprintf(line1, sizeof(line1), "ERR:OFFLINE  M:%d", currentStepperMode);
  } else {
    snprintf(line1, sizeof(line1), "L:%-3s F:%-3s  M:%d", 
      currentHeaterState ? "ON" : "OFF", 
      currentFanState ? "ON" : "OFF",
      currentStepperMode);
  }
  line1[16] = '\0';
  lcd.print(line1);
}

void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println("\n[SYSTEM] Starting Smart Egg Incubator...");

  // Disable brownout detector to prevent restart loops caused by USB voltage drops
  WRITE_PERI_REG(RTC_CNTL_BROWN_OUT_REG, 0);

  // Relay pin setup - default to HIGH (OFF) for Active LOW
  pinMode(RELAY_HEATER_PIN, OUTPUT);
  pinMode(RELAY_FAN_PIN, OUTPUT);
  digitalWrite(RELAY_HEATER_PIN, HIGH);
  digitalWrite(RELAY_FAN_PIN, HIGH);

  // I2C and LCD setup
  Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);
  lcd.init();
  lcd.backlight();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Smart Incubator");
  lcd.setCursor(0, 1);
  lcd.print("Connecting WiFi");

  // DHT11 initialization
  dht.begin();

  // Stepper motor configuration
  stepper.setMaxSpeed(1000.0);
  stepper.setSpeed(700.0);
  stepper.setAcceleration(400.0);

  // Clean WiFi connection initialization
  WiFi.disconnect(true);
  delay(200);
  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  Serial.print("[WIFI] Connecting to SSID: ");
  Serial.println(WIFI_SSID);

  int wifiAttempts = 0;
  while (WiFi.status() != WL_CONNECTED && wifiAttempts < 30) {
    delay(500);
    Serial.print(".");
    wifiAttempts++;
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[WIFI] Connected! ESP32 IP: " + WiFi.localIP().toString());
    lcd.setCursor(0, 1);
    lcd.print("WiFi Connected! ");
  } else {
    wl_status_t status = WiFi.status();
    Serial.printf("[WIFI] Connection failed. Status code: %d\n", status);
    if (status == WL_NO_SSID_AVAIL) {
      Serial.println("[WIFI] Catatan: SSID tidak ditemukan. Pastikan hotspot berada pada frekuensi 2.4 GHz!");
    } else if (status == WL_CONNECT_FAILED) {
      Serial.println("[WIFI] Catatan: Gagal autentikasi. Pastikan password Wi-Fi benar.");
    }
    lcd.setCursor(0, 1);
    lcd.print("WiFi Failed     ");
  }
  delay(1000);
  lcd.clear();

  lastServerContactMillis = millis();
  lastTelemetryMillis = millis();
}

void loop() {
  if (currentStepperMode == 1) {
    // Mode 1: Berputar satu arah terus-menerus tanpa henti
    stepper.setSpeed(700.0);
    stepper.runSpeed();
  } else if (currentStepperMode == 2) {
    // Mode 2: Berputar bolak-balik terus-menerus
    if (stepper.distanceToGo() == 0) {
      stepperOscillateTarget = (stepperOscillateTarget == 1024) ? 0 : 1024;
      stepper.moveTo(stepperOscillateTarget);
    }
    stepper.run();
  } else {
    // Mode 3: Pembalik periodik (jeda 30 detik)
    stepper.run();
    unsigned long currentMillis = millis();
    if (currentMillis - lastStepperMoveMillis >= STEPPER_TURN_INTERVAL_MS) {
      lastStepperMoveMillis = currentMillis;
      if (stepperDirectionForward) {
        stepper.moveTo(512);
      } else {
        stepper.moveTo(0);
      }
      stepperDirectionForward = !stepperDirectionForward;
    }
  }

  // Telemetry cycle every 2 seconds
  unsigned long currentMillis = millis();
  if (currentMillis - lastTelemetryMillis >= TELEMETRY_INTERVAL_MS) {
    lastTelemetryMillis = currentMillis;

    // Read sensor
    float temperature = dht.readTemperature();
    float humidity = dht.readHumidity();
    bool sensorValid = (!isnan(temperature) && !isnan(humidity) && temperature > 0.0 && temperature < 80.0);

    // Verify WiFi link
    if (WiFi.status() != WL_CONNECTED) {
      WiFi.reconnect();
    }

    bool serverSuccess = false;

    if (WiFi.status() == WL_CONNECTED) {
      HTTPClient http;
      WiFiClient standardClient;
      WiFiClientSecure secureClient;

      String endpointUrl = String(USE_HTTPS ? "https://" : "http://") + SERVER_HOST + ":" + String(SERVER_PORT) + API_PATH;

      if (USE_HTTPS) {
        secureClient.setCACert(ROOT_CA_CERTIFICATE);
        http.begin(secureClient, endpointUrl);
      } else {
        http.begin(standardClient, endpointUrl);
      }

      http.addHeader("Content-Type", "application/json");
      http.addHeader("X-API-Key", API_KEY);
      http.setTimeout(3000);

      // Construct JSON payload
      StaticJsonDocument<256> doc;
      doc["device_id"] = DEVICE_ID;
      doc["temperature"] = sensorValid ? temperature : 0.0;
      doc["humidity"] = sensorValid ? humidity : 0.0;
      doc["sensor_valid"] = sensorValid;
      doc["lamp_status"] = currentHeaterState;
      doc["fan_status"] = currentFanState;

      String requestBody;
      serializeJson(doc, requestBody);

      int httpResponseCode = http.POST(requestBody);

      if (httpResponseCode == HTTP_CODE_OK || httpResponseCode == 200) {
        String responsePayload = http.getString();
        StaticJsonDocument<512> responseDoc;
        DeserializationError error = deserializeJson(responseDoc, responsePayload);

        if (!error && responseDoc["success"] == true) {
          int cmdLamp = responseDoc["commands"]["lamp"] | 0;
          int cmdFan = responseDoc["commands"]["fan"] | 0;
          int cmdStepper = responseDoc["commands"]["stepper_mode"] | currentStepperMode;

          if (cmdStepper >= 1 && cmdStepper <= 3) {
            setStepperMode(cmdStepper);
          }

          // Apply decision made by PHP server backend
          applyRelays(cmdLamp, cmdFan);
          lastServerContactMillis = millis();
          serverSuccess = true;
        } else {
          Serial.println("[API] JSON parsing failed: " + responsePayload);
        }
      } else {
        Serial.printf("[API] HTTP POST error code: %d\n", httpResponseCode);
      }

      http.end();
    }

    // Safety checks
    if (millis() - lastServerContactMillis > SERVER_FAILSAFE_TIMEOUT_MS) {
      triggerEmergencyFailSafe("CONN TIMEOUT");
      serverSuccess = false;
    } else if (!sensorValid) {
      triggerEmergencyFailSafe("SENSOR FAIL");
    }

    updateLcd(temperature, humidity, sensorValid, serverSuccess);
  }
}

