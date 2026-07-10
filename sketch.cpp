#include <Arduino.h>
#include <DHT.h>
#include <ArduinoJson.h>
#include "secrets.h"

#define DHT_PIN 15
#define DHT_TYPE DHT22
#define VOLTMETER_PIN 34
#define SWITCH_PIN 4

DHT dht(DHT_PIN, DHT_TYPE);

void setup() {
  Serial.begin(115200);
  dht.begin();
  pinMode(SWITCH_PIN, INPUT_PULLUP);
}

void loop() {
  bool isSwitchedOn = (digitalRead(SWITCH_PIN) == LOW);
  float temperature = dht.readTemperature();
  int analogValue = analogRead(VOLTMETER_PIN);
  float voltage = (analogValue / 4095.0) * 3.3;

  StaticJsonDocument<300> doc;
  
  doc["Nom_eqm"] = NOM_EQM;
  doc["Id"] = ID_EQM;
  doc["API_key"] = API_KEY;
  doc["API_URL"] = API_URL;
  
  doc["status"] = isSwitchedOn ? "ON" : "OFF";
  doc["v_volts"] = voltage;

  if (isnan(temperature)) {
    doc["temp_c"] = nullptr;
  } else {
    doc["temp_c"] = temperature;
  }

  serializeJson(doc, Serial);
  Serial.println();

  delay(500);
}
