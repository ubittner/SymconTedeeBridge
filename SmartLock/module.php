<?php

/**
 * @project       SymconTedeeBridge/SmartLock
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class TedeeSmartLockLocalAPI extends IPSModuleStrict
{
    //Constants
    private const TEDEE_LIBRARY_GUID = '{588D956B-BB64-219C-D65D-4C0A3577DA4B}';
    private const MODULE_PREFIX = 'TBSL';
    private const TEDEE_BRIDGE_GUID = '{BE495811-E1CC-1BFE-E6AA-2E4A4707EC46}';
    private const TEDEE_BRIDGE_DATA_GUID = '{6140B1BD-648F-6D1A-62FC-DA55EC3D1D44}';

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        $this->RegisterPropertyInteger('DeviceID', 0);
        $this->RegisterPropertyString('DeviceSerialNumber', '');
        $this->RegisterPropertyInteger('DeviceType', 0);
        $this->RegisterPropertyString('DeviceName', '');
        $this->RegisterPropertyInteger('UpdateInterval', 0);
        $this->RegisterPropertyBoolean('UseActivityLog', true);
        $this->RegisterPropertyInteger('ActivityLogMaximumEntries', 10);
        $this->RegisterPropertyBoolean('UseDailyLock', false);
        $this->RegisterPropertyString('DailyLockTime', '{"hour":23,"minute":0,"second":0}');
        $this->RegisterPropertyBoolean('UseDailyUnlock', false);
        $this->RegisterPropertyString('DailyUnlockTime', '{"hour":6,"minute":0,"second":0}');

        ########## Variables

        //Smart Lock
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SmartLock';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Lock'), 'LockClosed', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Unlock'), 'LockOpen', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Pull'), 'Door', 0x0000FF);
        $this->RegisterVariableInteger('SmartLock', $this->Translate('Smart lock'), $profile, 10);
        $this->EnableAction('SmartLock');

        //Device state
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DeviceState';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Uncalibrated'), 'Warning', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Calibration'), 'TurnLeft', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Open'), 'LockOpen', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, $this->Translate('Partially open'), 'LockOpen', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 4, $this->Translate('Opening'), 'LockOpen', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 5, $this->Translate('Closing'), 'LockClosed', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 6, $this->Translate('Closed'), 'LockClosed', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 7, $this->Translate('Pull spring'), 'Door', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 8, $this->Translate('Pulling'), 'Door', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 9, $this->Translate('Unknown'), 'Information', -1);
        IPS_SetVariableProfileAssociation($profile, 255, $this->Translate('Unpulling'), 'Gear', 0x00FF00);
        $id = @$this->GetIDForIdent('DeviceState');
        $this->RegisterVariableInteger('DeviceState', $this->Translate('Device state'), $profile, 20);
        if (!$id) {
            $this->SetValue('DeviceState', 9);
        }

        //Connection
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Connection';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, false, $this->Translate('Not connected'), 'Warning', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, true, $this->Translate('Connected'), 'Ok', 0x00FF00);
        $this->RegisterVariableBoolean('Connection', $this->Translate('Connection'), $profile, 30);

        //Battery level
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.BatteryLevel';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 0, 100, 1);
        IPS_SetVariableProfileText($profile, '', '%');
        IPS_SetVariableProfileIcon($profile, 'Battery');
        $this->RegisterVariableInteger('BatteryLevel', $this->Translate('Battery level'), $profile, 40);

        //Battery charging
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.BatteryCharging';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileIcon($profile, 'Battery');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Inactive'), '', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Active'), '', 0x00FF00);
        $this->RegisterVariableBoolean('BatteryCharging', $this->Translate('Battery charging'), $profile, 50);

        ##### Timer
        $this->RegisterTimer('Update', 0, self::MODULE_PREFIX . '_UpdateDeviceData(' . $this->InstanceID . ');');
        $this->RegisterTimer('DailyLock', 0, self::MODULE_PREFIX . '_SetLockAction(' . $this->InstanceID . ', 0);');
        $this->RegisterTimer('DailyUnlock', 0, self::MODULE_PREFIX . '_SetLockAction(' . $this->InstanceID . ', 1);');

        ##### Attribute
        $this->RegisterAttributeString('ActivityLog', '[]');

        ##### Splitter

        //Connect to parent (Tedee Bridge Local API)
        $this->ConnectParent(self::TEDEE_BRIDGE_GUID);
    }

    public function ApplyChanges(): void
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check kernel runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        ##### Maintain variables

        //Activity log
        if ($this->ReadPropertyBoolean('UseActivityLog')) {
            $id = @$this->GetIDForIdent('ActivityLog');
            $this->MaintainVariable('ActivityLog', $this->Translate('Activity log'), 3, 'HTMLBox', 60, true);
            if (!$id) {
                IPS_SetIcon($this->GetIDForIdent('ActivityLog'), 'Database');
            }
        } else {
            $this->MaintainVariable('ActivityLog', $this->Translate('Activity log'), 3, '', 0, false);
            $this->WriteAttributeString('ActivityLog', '[]');
        }

        $this->SetDailyLockTimer();
        $this->SetDailyUnlockTimer();
        $this->UpdateDeviceData();
    }

    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['SmartLock', 'Connection', 'DeviceState', 'BatteryLevel', 'BatteryCharging'];
        foreach ($profiles as $profile) {
            $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profile)) {
                IPS_DeleteVariableProfile($profile);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        if ($Message == IPS_KERNELSTARTED) {
            $this->KernelReady();
        }
    }

    public function GetConfigurationForm(): string
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $library = IPS_GetLibrary(self::TEDEE_LIBRARY_GUID);
        $formData['elements'][2]['caption'] = 'ID: ' . $this->InstanceID . ', Version: ' . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date']);
        return json_encode($formData);
    }

    public function ReceiveData($JSONString): string
    {
        //Received data from splitter
        $this->SendDebug(__FUNCTION__, 'Incoming data: ' . $JSONString, 0);
        $data = json_decode($JSONString, true);
        $this->SendDebug(__FUNCTION__, 'Buffer:  ' . json_encode($data['Buffer']), 0);
        //Check if the incoming data is for this device
        $update = false;
        $buffer = $data['Buffer'];
        if (array_key_exists('data', $buffer)) {
            if (array_key_exists('deviceId', $buffer['data'])) {
                if ($this->ReadPropertyInteger('DeviceID') == $buffer['data']['deviceId']) {
                    $update = true;
                } else {
                    $this->SendDebug(__FUNCTION__, 'Abort, data is not for this device!', 0);
                    return '';
                }
            }
        }

        if ($update) {
            ##### Timestamp

            if (array_key_exists('timestamp', $buffer)) {
                $date = $buffer['timestamp'];
                $date = new DateTime($date, new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
                $date = $date->format('d.m.Y H:i:s');
                $this->SendDebug(__FUNCTION__, 'Date: ' . $date, 0);
            }

            ##### Events

            if (array_key_exists('event', $buffer)) {
                switch ($buffer['event']) {
                    case 'device-battery-start-charging':
                        /*
                        {
                            "event": "device-battery-start-charging",
                            "timestamp": "2023-07-25T14:41:48.825Z",
                            "data": {
                                "deviceType": 2,
                                "deviceId": 33819,
                                "serialNumber": "19420103-000006"
                            }
                        }

                        The deviceType field may have the following values:
                        2 for Lock PRO
                        4 for Lock GO
                         */

                        $this->SetValue('BatteryCharging', true);
                        if (isset($date)) {
                            $this->UpdateActivityLog($date, $this->Translate('Battery charging started'));
                        }
                        break;

                    case 'device-battery-stop-charging':
                        /*
                        {
                            "event": "device-battery-stop-charging",
                            "timestamp": "2023-07-25T14:41:48.825Z",
                            "data": {
                                "deviceType": 2,
                                "deviceId": 33819,
                                "serialNumber": "19420103-000006"
                            }
                        }

                        The deviceType field may have the following values:
                        2 for Lock PRO
                        4 for Lock GO
                         */

                        $this->SetValue('BatteryCharging', false);
                        if (isset($date)) {
                            $this->UpdateActivityLog($date, $this->Translate('Battery charging stopped'));
                        }
                        break;
                }
            }

            ##### Data

            if (array_key_exists('data', $buffer)) {
                ##### Device connection changed

                /*
                {
                    "event": "device-connection-changed",
                    "timestamp": "2023-07-25T14:41:48.825Z",
                    "data": {
                        "deviceType": 2,
                        "deviceId": 33819,
                        "serialNumber": "19420103-000006",
                        "isConnected": 1
                    }
                }

                The deviceType field may have the following values:
                2 for Lock PRO
                4 for Lock GO

                The isConnected field may have the following values:
                0 - disconnected
                1 - connected
                 */

                if (array_key_exists('isConnected', $buffer['data'])) {
                    $this->SetValue('Connection', $buffer['data']['isConnected']);
                }

                ##### Lock status changed

                /*
                {
                    "event": "lock-status-changed",
                    "timestamp": "2023-07-25T14:41:48.825Z",
                    "data": {
                        "deviceType": 2,
                        "deviceId": 33819,
                        "serialNumber": "19420103-000006",
                        "state": 6,
                        "jammed": 0
                    }
                }

                The deviceType field may have the following values:
                2 for Lock PRO
                4 for Lock GO

                The state field may have the following values:
                0 -     uncalibrated
                1 -     calibration
                2 -     open
                3 -     partially_open
                4 -     opening
                5 -     closing
                6 -     closed
                7 -     pull_spring
                8 -     pulling
                9 -     unknown
                255 -   unpulling

                The jammed field may have the following values:
                0 - not jammed
                1 - jammed
                 */

                if (array_key_exists('state', $buffer['data'])) {
                    $this->SetValue('DeviceState', $buffer['data']['state']);
                    if (isset($date)) {
                        switch ($buffer['data']['state']) {
                            case 0:
                                $action = 'uncalibrated';
                                break;

                            case 1:
                                $action = 'calibration';
                                break;

                            case 2:
                                $action = 'open';
                                break;

                            case 3:
                                $action = 'partially_open';
                                break;

                            case 4:
                                $action = 'opening';
                                break;

                            case 5:
                                $action = 'closing';
                                break;

                            case 6:
                                $action = 'closed';
                                break;

                            case 7:
                                $action = 'pull_spring';
                                break;

                            case 8:
                                $action = 'pulling';
                                break;

                            case 9:
                                $action = 'unknown';
                                break;

                            case 255:
                                $action = 'unpulling';
                                break;
                        }
                        if (isset($action)) {
                            $this->UpdateActivityLog($date, $this->Translate($action));
                        }
                    }
                }

                ##### Device battery level changed

                /*
                {
                    "event": "device-battery-level-changed",
                    "timestamp": "2023-07-25T14:41:48.825Z",
                    "data": {
                        "deviceType": 2,
                        "deviceId": 33819,
                        "serialNumber": "19420103-000006",
                        "batteryLevel": 90
                     }
                }

                The deviceType field may have the following values:
                2 for Lock PRO
                4 for Lock GO

                The batteryLevel field may have the following values:
                0-100 battery level in percentage
                255 if battery level is not known
                 */

                if (array_key_exists('batteryLevel', $buffer['data'])) {
                    $batteryLevel = $buffer['data']['batteryLevel'];
                    if ($batteryLevel == 255) {
                        $batteryLevel = 0;
                    }
                    $this->SetValue('BatteryLevel', $batteryLevel);
                    if (isset($date)) {
                        $this->UpdateActivityLog($date, $this->Translate('Battery level') . $batteryLevel . '%');
                    }
                }
            }
        }
        return '';
    }

    #################### Request Action

    public function RequestAction($Ident, $Value): void
    {
        if ($Ident == 'SmartLock') {
            $this->SetLockAction($Value);
        }
    }

    public function UpdateDeviceData(): void
    {
        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
        $this->GetLockDetails();
    }

    public function SetLockAction(int $Action): string
    {
        $result = json_encode(['Code' => 400, 'Header' => '', 'Body' => '']);
        $callback = false;
        if ($this->CheckCallback()) {
            $callback = true;
        }
        $this->SendDebug(__FUNCTION__, 'Callback: ' . $callback, 0);
        $this->SetDailyLockTimer();
        $this->SetDailyUnlockTimer();
        $deviceID = $this->ReadPropertyInteger('DeviceID');
        if (!$this->HasActiveParent() || $deviceID == 0) {
            return $result;
        }
        $this->SetTimerInterval('Update', 0);
        $this->SetValue('SmartLock', $Action);
        switch ($Action) {
            case 0: # Lock
                $command = 'Lock';
                $actionText = 'locked';
                break;

            case 1: # Unlock
                $command = 'Unlock';
                $actionText = 'unlocked';
                break;

            case 2: # Pull
                $command = 'PullSpring';
                $actionText = 'door opened';
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Unknown action: ' . $Action, 0);
                $this->SetTimerInterval('Update', 5000);
                return $result;
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = self::TEDEE_BRIDGE_DATA_GUID;
        $buffer['Command'] = $command;
        $buffer['Params'] = ['id' => $deviceID];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $response = $this->SendDataToParent($data);
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        if (!$callback) {
            if (is_array(json_decode($response, true)) && (json_last_error() == JSON_ERROR_NONE)) {
                $responseData = json_decode($response, true);
                if (array_key_exists('Code', $responseData)) {
                    $code = $responseData['Code'];
                    if ($code < 300) {
                        $this->SendDebug(__FUNCTION__, 'Callback is false!', 0);
                        $date = new DateTime('now', new DateTimeZone('UTC'));
                        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
                        $date = $date->format('d.m.Y H:i:s');
                        $this->SendDebug(__FUNCTION__, 'Date: ' . $date, 0);
                        $this->SendDebug(__FUNCTION__, 'Action: ' . $this->Translate($actionText), 0);
                        $this->UpdateActivityLog($date, $this->Translate($actionText));
                        $this->SetTimerInterval('Update', 5000);
                    }
                }
            }
            $this->SetTimerInterval('Update', 5000);
        }
        return $response;
    }

    public function CheckCallback(): bool
    {
        $deviceID = $this->ReadPropertyInteger('DeviceID');
        if (!$this->HasActiveParent() || $deviceID == 0) {
            return false;
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = self::TEDEE_BRIDGE_DATA_GUID;
        $buffer['Command'] = 'CheckCallback';
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $response = $this->SendDataToParent($data);
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        $result = false;
        if ($response == 'true') {
            $result = true;
        }
        return $result;
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function GetLockDetails(): void
    {
        $deviceID = $this->ReadPropertyInteger('DeviceID');
        if (!$this->HasActiveParent() || $deviceID == 0) {
            return;
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = self::TEDEE_BRIDGE_DATA_GUID;
        $buffer['Command'] = 'GetLockDetails';
        $buffer['Params'] = ['id' => $deviceID];
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data :' . $data, 0);
        $response = $this->SendDataToParent($data);
        if (is_array(json_decode($response, true)) && (json_last_error() == JSON_ERROR_NONE)) {
            $responseData = json_decode($response, true);
            if (array_key_exists('Code', $responseData)) {
                $code = $responseData['Code'];
                if ($code != 200) {
                    $this->SendDebug(__FUNCTION__, 'An error has occurred!' . ' Code: ' . $code, 0);
                    return;
                }
                if (array_key_exists('Body', $responseData)) {
                    if (is_string($responseData['Body']) && is_array(json_decode($responseData['Body'], true)) && (json_last_error() == JSON_ERROR_NONE)) {
                        $this->SendDebug(__FUNCTION__, 'Actual data: ' . $responseData['Body'], 0);
                        /*
                            Example:
                           {
                              "id": 10,
                              "name": "Lock-9A25",
                              "type": 4,
                              "serialNumber": "22510401-000050",
                              "isConnected": 1,
                              "rssi": -45,
                              "deviceRevision": 18,
                              "version": "2.0.10724",
                              "state": 6,
                              "jammed": 0,
                              "batteryLevel": 90,
                              "isCharging": 0,
                              "deviceSettings": {
                                "autoLockEnabled": 0,
                                "autoLockDelay": 60,
                                "autoLockImplicitEnabled": 0,
                                "autoLockImplicitDelay": 5,
                                "pullSpringEnabled": 0,
                                "pullSpringDuration": 2,
                                "autoPullSpringEnabled": 0,
                                "postponedLockEnabled": 1,
                                "postponedLockDelay": 5,
                                "buttonLockEnabled": 1,
                                "buttonUnlockEnabled": 1
                              }
                            }
                         */
                        $actualData = json_decode($responseData['Body'], true);
                        if (!empty($actualData)) {
                            //Device ID
                            if (array_key_exists('id', $actualData)) {
                                if ($this->ReadPropertyInteger('DeviceID') != $actualData['id']) {
                                    $this->SendDebug(__FUNCTION__, 'Abort, data is not for this device!', 0);
                                    return;
                                }
                            }
                            //Connection
                            if (array_key_exists('isConnected', $actualData)) {
                                $this->SetValue('Connection', $actualData['isConnected']);
                            }
                            //Charging
                            if (array_key_exists('isCharging', $actualData)) {
                                $this->SetValue('BatteryCharging', $actualData['isCharging']);
                            }
                            //Battery level
                            if (array_key_exists('batteryLevel', $actualData)) {
                                $this->SetValue('BatteryLevel', $actualData['batteryLevel']);
                            }
                            //State
                            if (array_key_exists('state', $actualData)) {
                                $this->SetValue('DeviceState', $actualData['state']);
                                /*
                                    lock state:
                                    0-uncalibrated,
                                    1-calibration,
                                    2-open,
                                    3-partially_open,
                                    4-opening,
                                    5-closing,
                                    6-closed,
                                    7-pull_spring,
                                    8-pulling,
                                    9-unknown,
                                    255-unpulling
                                 */
                                switch ($actualData['state']) {
                                    case 2:
                                    case 3:
                                    case 4:
                                        $this->SetValue('SmartLock', 1);
                                        break;

                                    case 5:
                                    case 6:
                                        $this->SetValue('SmartLock', 0);
                                        break;

                                    case 7:
                                    case 8:
                                        $this->SetValue('SmartLock', 2);
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function UpdateActivityLog(string $Date, string $Action): void
    {
        $deviceID = $this->ReadPropertyInteger('DeviceID');
        if (!$this->HasActiveParent() || $deviceID == 0) {
            return;
        }
        if (!$this->ReadPropertyBoolean('UseActivityLog')) {
            return;
        }

        $logEntries = json_decode($this->ReadAttributeString('ActivityLog'), true);
        $newEntry = ['date' => $Date, 'action' => $Action];
        array_unshift($logEntries, $newEntry);
        $logEntries = array_values($logEntries);

        $maximumEntries = $this->readPropertyInteger('ActivityLogMaximumEntries');
        foreach ($logEntries as $key => $logEntry) {
            if ($key + 1 > $maximumEntries) {
                unset($logEntries[$key]);
            }
        }
        $this->WriteAttributeString('ActivityLog', json_encode($logEntries));
        //Header
        $string = "<table style='width: 100%; border-collapse: collapse;'>";
        $string .= '<tr> <td><b> ' . $this->Translate('Date') . '</b></td> <td><b>' . $this->Translate('Action') . '</b></td></tr>';
        if (!empty($logEntries)) {
            foreach ($logEntries as $logEntry) {
                $string .= '<tr><td>' . $logEntry['date'] . '</td><td>' . $logEntry['action'] . '</td><td></tr>';
            }
        }
        $string .= '</table>';
        $this->SetValue('ActivityLog', $string);
    }

    private function SetDailyLockTimer(): void
    {
        $now = time();
        $lockTime = json_decode($this->ReadPropertyString('DailyLockTime'));
        $hour = $lockTime->hour;
        $minute = $lockTime->minute;
        $second = $lockTime->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        $interval = ($timestamp - $now) * 1000;
        if (!$this->ReadPropertyBoolean('UseDailyLock')) {
            $interval = 0;
        }
        $this->SetTimerInterval('DailyLock', $interval);
    }

    private function SetDailyUnlockTimer(): void
    {
        $now = time();
        $unlockTime = json_decode($this->ReadPropertyString('DailyUnlockTime'));
        $hour = $unlockTime->hour;
        $minute = $unlockTime->minute;
        $second = $unlockTime->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        $interval = ($timestamp - $now) * 1000;
        if (!$this->ReadPropertyBoolean('UseDailyUnlock')) {
            $interval = 0;
        }
        $this->SetTimerInterval('DailyUnlock', $interval);
    }
}