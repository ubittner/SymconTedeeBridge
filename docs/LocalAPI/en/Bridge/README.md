[![Image](../../../../imgs/tedee_logo.png)](https://tedee.com)

### Bridge Local API

This module manages the communication with the [tedee Bridge Local API](https://docs.tedee.com/bridge-api#tag/Getting-started/Enabling-Bridge-API).  

For this module there is no claim for further development, other support or can include errors.  
Before installing the module, a backup of IP-Symcon should be performed.  
The developer is not liable for any data loss or other damages.  
The user expressly agrees to the above conditions, as well as the license conditions.

### Table of contents

1. [Scope of functions](#1-scope-of-functions)
2. [Requirements](#2-requirements)
3. [Software installation](#3-software-installation)
4. [Setting up the instance](#4-setting-up-the-instance)
5. [Statevariables and profiles](#5-statevariables-and-profiles)
6. [WebFront](#6-webfront)
7. [PHP command reference](#7-php-command-reference)
   1. [Bridge Info](#71-bridge-info)
   2. [Paired devices](#72-paired-devices)
   3. [Device state](#73-device-state)
   4. [Unlock](#74-unlock)
   5. [Lock](#75-lock)
   6. [Pull spring](#76-pull-spring)

### 1. Scope of functions

* Communication with the tedee Bridge Local API

### 2. Requirements

- IP-Symcon at least version 7.0
- tedee Smart Lock
- tedee Bridge
- Token

### 3. Software installation

* For commercial use (e.g. as an integrator), please contact the author first.
* Use the `Module Store` for installing the `Tedee Bridge` Module.

### 4. Setting up the instance

- In IP-Symcon select `Add instance` at any place and select `Tedee Bridge Local API` which is listed under the manufacturer `tedee`.
- A new `Tedee Bridge Local API` instance will be created.

__Configuration__:

| Name                        | Beschreibung                                     |
|-----------------------------|--------------------------------------------------|
| Active                      | De- / Activates the splitter                     |
| IP-Address                  | IP-Address of the Bridge                         |
| Port                        | Port of the Bridge                               |
| Network Timeout             | Network Timeout for the connection               |
| Execution timeout           | Maximum execution time                           |
| Encrypt Token               | Encrypts the token                               |
| Token                       | Token from the tedee App                         |
| Use automatic update        | Updates the states automatically via a Webhook   |
| Host IP-Address (IP-Symcon) | IP-Address of the IP-Symcon Host for the Webhook |
| Host Port (IP-Symcon)       | Port of the IP-Symcon Host for the Webhook       |

You will find your Token in the tedee App (iOS/Android).
For more information on how to generate the personal access key, see the [Local API Dokumentation](https://docs.tedee.com/bridge-api#tag/Getting-started/Enabling-Bridge-API) of the vendor.

### 5. Statevariables and profiles

The state variables/categories are created automatically.  
Deleting individual ones can lead to malfunctions.

##### Statevariables

No status variables are used.

##### Profile:

No prfiles are used.

### 6. WebFront

The splitter has no functionality in the WebFront.

### 7. PHP command reference


#### 7.1 Bridge info

```text
TB_GetBridgeInfo(integer InstanceID);
```

You will receive a json-coded string of the result with information from the bridge.

| Parameter    | Description                                 |
|--------------|---------------------------------------------|
| `InstanceID` | ID of the Tedee Bridge (Local API) Instance |


**Example**:
```php
$data = TB_GetBridgeInfo(12345);
print_r(json_decode($data, true));
```
---

#### 7.2 Paired devices

```text
TB_GetPairedLocks(integer InstanceID);
```

You will receive a json-coded string of the result with information of the paired devices.

| Parameter    | Description                                 |
|--------------|---------------------------------------------|
| `InstanceID` | ID of the Tedee Bridge (Local API) Instance |


**Example**:
```php
$data = TB_GetPairedLocks(12345);
print_r(json_decode($data, true));
```

---

#### 7.3 Device state

```text
TB_GetLockDetails(integer InstanceID, integer $DeviceID);
```

You will receive a json-coded string of the result with information of the device.

| Parameter    | Description                                 |
|--------------|---------------------------------------------|
| `InstanceID` | ID of the Tedee Bridge (Local API) Instance |
| `DeviceID`   | Device ID of the device (SmartLock).        |


**Example**:
```php
$data = TB_GetLockDetails(12345, 98765);
print_r(json_decode($data, true));
```

---

#### 7.4 Unlock

```text
TB_UnLock(integer InstanceID, integer $DeviceID);
```

You will receive a json-coded string of the result.

| Parameter    | Description                                 |
|--------------|---------------------------------------------|
| `InstanceID` | ID of the Tedee Bridge (Local API) Instance |
| `DeviceID`   | Device ID of the device (SmartLock).        |


**Example**:
```php
$data = TB_UnLock(12345, 98765);
print_r(json_decode($data, true));
```

---

#### 7.5 Lock

```text
TB_Lock(integer InstanceID, integer $DeviceID);
```

You will receive a json-coded string of the result.

| Parameter    | Description                                 |
|--------------|---------------------------------------------|
| `InstanceID` | ID of the Tedee Bridge (Local API) Instance |
| `DeviceID`   | Device ID of the device (SmartLock).        |


**Example**:
```php
$data = TB_Lock(12345, 98765);
print_r(json_decode($data, true));
```

---

#### 7.6 Pull spring

```text
TB_PullSpring(integer InstanceID, integer $DeviceID);
```

You will receive a json-coded string of the result.

| Parameter    | Description                                 |
|--------------|---------------------------------------------|
| `InstanceID` | ID of the Tedee Bridge (Local API) Instance |
| `DeviceID`   | Device ID of the device (SmartLock).        |


**Example**:
```php
$data = TB_PullSpring(12345, 98765);
print_r(json_decode($data, true));
```

---