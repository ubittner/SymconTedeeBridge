<?php

/**
 * @project       SymconTedeeBridge/Configurator
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class TedeeConfiguratorLocalAPI extends IPSModuleStrict
{
    //Constants
    private const TEDEE_LIBRARY_GUID = '{588D956B-BB64-219C-D65D-4C0A3577DA4B}';
    private const TEDEE_BRIDGE_GUID = '{BE495811-E1CC-1BFE-E6AA-2E4A4707EC46}';
    private const TEDEE_BRIDGE_DATA_GUID = '{6140B1BD-648F-6D1A-62FC-DA55EC3D1D44}';
    private const TEDEE_SMARTLOCK_GUID = '{1B9EB8AC-6923-CDDA-625E-B2D765BC787E}';

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

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
        //Get all device instances first
        $connectedInstanceIDs = [];
        foreach (IPS_GetInstanceListByModuleID(self::TEDEE_SMARTLOCK_GUID) as $instanceID) {
            if (IPS_GetInstance($instanceID)['ConnectionID'] === IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                //Add the instance ID to a list for the given Lock ID. Even though Lock ID should be unique, users could break things by manually editing the settings
                $connectedInstanceIDs[IPS_GetProperty($instanceID, 'DeviceID')][] = $instanceID;
            }
        }
        $values = [];
        $devices = json_decode($this->GetPairedLocks(), true);
        if (is_array($devices)) {
            foreach ($devices as $device) {
                if (array_key_exists('id', $device)) {
                    $deviceID = $device['id'];
                    $deviceName = $device['name'];
                    $deviceSerialNumber = $device['serialNumber'];
                    $deviceType = $device['type'];
                    $value = [
                        'DeviceID'           => $deviceID,
                        'DeviceSerialNumber' => $deviceSerialNumber,
                        'DeviceType'         => $deviceType,
                        'create'             => [
                            'moduleID'      => self::TEDEE_SMARTLOCK_GUID,
                            'name'          => 'tedee ' . $deviceName . ' (Local API)',
                            'configuration' => [
                                'DeviceID'           => (integer) $deviceID,
                                'DeviceSerialNumber' => (string) $deviceSerialNumber,
                                'DeviceType'         => (integer) $deviceType,
                                'DeviceName'         => (string) $deviceName
                            ]
                        ]
                    ];
                    if (isset($connectedInstanceIDs[$deviceID])) {
                        $value['name'] = IPS_GetName($connectedInstanceIDs[$deviceID][0]);
                        $value['instanceID'] = $connectedInstanceIDs[$deviceID][0];
                    } else {
                        $value['name'] = $device['name'];
                        $value['instanceID'] = 0;
                    }
                    $values[] = $value;
                }
            }
        }
        foreach ($connectedInstanceIDs as $deviceID => $instanceIDs) {
            foreach ($instanceIDs as $index => $instanceID) {
                //The first entry for each device id was already added as valid value
                $existing = false;
                foreach ($devices as $device) {
                    if ($device['id'] == $deviceID) {
                        $existing = true;
                    }
                }
                if ($index === 0 && $existing) {
                    continue;
                }
                //However, if a device id is not found or has multiple instances, they are erroneous
                $values[] = [
                    'DeviceID'           => $deviceID,
                    'name'               => IPS_GetName($instanceID),
                    'DeviceSerialNumber' => IPS_GetProperty($instanceID, 'DeviceSerialNumber'),
                    'DeviceType'         => '2 = Lock',
                    'instanceID'         => $instanceID,
                ];
            }
        }
        $formData['actions'][0]['values'] = $values;
        return json_encode($formData);
    }

    public function GetPairedLocks(): string
    {
        $devices = [];
        if (!$this->HasActiveParent()) {
            return json_encode($devices);
        }
        $data = [];
        $buffer = [];
        $data['DataID'] = self::TEDEE_BRIDGE_DATA_GUID;
        $buffer['Command'] = 'GetPairedLocks';
        $buffer['Params'] = '';
        $data['Buffer'] = $buffer;
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        $response = $this->SendDataToParent($data);
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        if (is_array(json_decode($response, true)) && (json_last_error() == JSON_ERROR_NONE)) {
            $responseData = json_decode($response, true);
            if (array_key_exists('Code', $responseData)) {
                if ($responseData['Code'] != 200) {
                    return json_encode($devices);
                }
            }
            if (array_key_exists('Body', $responseData)) {
                if (is_string($responseData['Body']) && is_array(json_decode($responseData['Body'], true)) && (json_last_error() == JSON_ERROR_NONE)) {
                    $this->SendDebug(__FUNCTION__, 'Actual data: ' . $responseData['Body'], 0);
                    $actualData = json_decode($responseData['Body'], true);
                    foreach ($actualData as $element) {
                        $deviceType = $this->Translate('Unknown');
                        if (array_key_exists('type', $element)) {
                            switch ($element['type']) {
                                case 2:
                                    $deviceType = '2 = Lock PRO';
                                    break;

                                case 4:
                                    $deviceType = '2 = Lock GO';
                                    break;
                            }
                        }
                        if (array_key_exists('id', $element)) {
                            $devices[] = [
                                'id'           => $element['id'],
                                'serialNumber' => $element['serialNumber'],
                                'name'         => $element['name'],
                                'type'         => $deviceType];
                        }
                    }
                }
            }
        }
        return json_encode($devices);
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }
}

