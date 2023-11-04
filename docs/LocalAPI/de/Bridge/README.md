[![Image](../../../../imgs/tedee_logo.png)](https://tedee.com)

### Bridge Local API

Dieses Modul stellt die Kommunikation mit der [tedee Bridge Local API](https://docs.tedee.com/bridge-api#tag/Getting-started/Enabling-Bridge-API) her.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
   1. [Bridge Info](#71-bridge-info)
   2. [Vorhandene Geräte ermitteln](#72-vorhandene-geräte-ermitteln)
   3. [Gerätestatus](#73-gerätestatus)
   4. [Aufsperren](#74-ausperren)
   5. [Zusperren](#75-zusperren)
   6. [Entriegeln](#76-entriegeln)

### 1. Funktionsumfang

* Kommunikation mit der tedee Bridge Local API

### 2. Voraussetzungen

- IP-Symcon ab Version 7.0
- tedee Bridge
- tedee Smart Lock
- tedee Token

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Über den Module Store das `Tedee Bridge` Modul installieren.

### 4. Einrichten der Instanzen in IP-Symcon

- In IP-Symcon an beliebiger Stelle `Instanz hinzufügen` auswählen und `Tedee Bridge Local API` auswählen, welches unter dem Hersteller `tedee` aufgeführt ist.
- Es wird eine neue `Tedee Bridge Local API` Instanz angelegt.
- Weitere Informationen zum Hinzufügen von Instanzen finden Sie in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

| Name                             | Beschreibung                                        |
|----------------------------------|-----------------------------------------------------|
| Aktiv                            | Schaltet den Splitter in- bzw. aktiv                |
| IP-Adresse                       | IP-Adresse der Bridge                               |
| Port                             | Port der Bridge                                     |
| Netzwerk Timeout                 | Netzwerk Timeout für den Verbindungsaufbau          |
| Maximale Ausführungszeit         | Maximale Ausführungszeit für den Schaltbefehl       |
| Token verschlüsseln              | Verschlüsselt den Token                             |
| Token                            | Token aus der tedee App                             |
| Status automatisch aktualisieren | Aktualisiert automatisch den Status mittels Webhook |
| Host IP-Adresse (IP-Symcon)      | IP-Adresse des IP-Symcon Host für den Webhook       |
| Host Port (IP-Symcon)            | Port des IP-Symcon Host für den Webhook             |

Den Token entnehmen Sie bitte aus der tedee App (iOS/Android). 
Weitere Informationen zum Token finden Sie in der [Local API Dokumentation](https://docs.tedee.com/bridge-api#tag/Getting-started/Enabling-Bridge-API) vom Hersteller.

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt.  
Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Es werden keine Statusvariablen verwendet.

#### Profile

Es werden keine Profile verwendet.

### 6. WebFront

Der Splitter hat im WebFront keine Funktionalität.

### 7. PHP-Befehlsreferenz

#### 7.1 Bridge info

```text
TB_GetBridgeInfo(integer InstanzID);
```

Der Befehl liefert als Rückgabewert einen json-kodierten String mit Informationen über die Bridge.

| Parameter   | Beschreibung                            |
|-------------|-----------------------------------------|
| `InstanzID` | ID der Tedee Bridge (Local API) Instanz |


**Beispiel**:
```php
$data = TB_GetBridgeInfo(12345);
print_r(json_decode($data, true));
```
---

#### 7.2 Vorhandene Geräte ermitteln

```text
TB_GetPairedLocks(integer InstanzID);
```

Der Befehl liefert als Rückgabewert einen json-kodierten String mit Informationen über die gekoppelten Geräte mit der Bridge.

| Parameter   | Beschreibung                            |
|-------------|-----------------------------------------|
| `InstanzID` | ID der Tedee Bridge (Local API) Instanz |


**Beispiel**:
```php
$data = TB_GetPairedLocks(12345);
print_r(json_decode($data, true));
```

---

#### 7.3 Gerätestatus

```text
TB_GetLockDetails(integer InstanzID, integer $GeräteID);
```

Der Befehl liefert als Rückgabewert einen json-kodierten String mit Informationen über das Gerät.

| Parameter   | Beschreibung                            |
|-------------|-----------------------------------------|
| `InstanzID` | ID der Tedee Bridge (Local API) Instanz |
| `GeräteID`  | Geräte ID des Geräts (SmartLock).       |


**Beispiel**:
```php
$data = TB_GetLockDetails(12345, 98765);
print_r(json_decode($data, true));
```

---

#### 7.4 Aufsperren

```text
TB_UnLock(integer InstanzID, integer $GeräteID);
```

Der Befehl liefert als Rückgabewert einen json-kodierten String mit Ergebnis.

| Parameter   | Beschreibung                            |
|-------------|-----------------------------------------|
| `InstanzID` | ID der Tedee Bridge (Local API) Instanz |
| `GeräteID`  | Geräte ID des Geräts (SmartLock).       |


**Beispiel**:
```php
$data = TB_UnLock(12345, 98765);
print_r(json_decode($data, true));
```

---

#### 7.5 Zusperren

```text
TB_Lock(integer InstanzID, integer $GeräteID);
```

Der Befehl liefert als Rückgabewert einen json-kodierten String mit Ergebnis.

| Parameter   | Beschreibung                            |
|-------------|-----------------------------------------|
| `InstanzID` | ID der Tedee Bridge (Local API) Instanz |
| `GeräteID`  | Geräte ID des Geräts (SmartLock).       |


**Beispiel**:
```php
$data = TB_Lock(12345, 98765);
print_r(json_decode($data, true));
```

---

#### 7.6 Entriegeln

```text
TB_PullSpring(integer InstanzID, integer $GeräteID);
```

Der Befehl liefert als Rückgabewert einen json-kodierten String mit Ergebnis.

| Parameter   | Beschreibung                            |
|-------------|-----------------------------------------|
| `InstanzID` | ID der Tedee Bridge (Local API) Instanz |
| `GeräteID`  | Geräte ID des Geräts (SmartLock).       |


**Beispiel**:
```php
$data = TB_PullSpring(12345, 98765);
print_r(json_decode($data, true));
```

---