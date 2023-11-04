<?php

/**
 * @project       SymconTedeeBridge/Bridge
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

include_once __DIR__ . '/helper/autoload.php';

class TedeeBridgeLocalAPI extends IPSModuleStrict
{
    //Helper
    use Callback;
    use TedeeLocalAPI;
    use WebHook;

    //Constants
    private const TEDEE_LIBRARY_GUID = '{588D956B-BB64-219C-D65D-4C0A3577DA4B}';
    private const CORE_WEBHOOK_GUID = '{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}';
    private const TEDEE_DEVICE_DATA_GUID = '{D4A652E3-83EF-3802-41DD-4CE89F1EA461}';
    private const MODULE_PREFIX = 'TB';
    private const API_VERSION = 'v1.0';

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        $this->RegisterPropertyBoolean('Active', true);
        $this->RegisterPropertyString('BridgeIP', '');
        $this->RegisterPropertyInteger('BridgePort', 80);
        $this->RegisterPropertyInteger('Timeout', 5000);
        $this->RegisterPropertyInteger('ExecutionTimeout', 60);
        $this->RegisterPropertyBoolean('UseEncryption', false);
        $this->RegisterPropertyString('Token', '');
        $this->RegisterPropertyBoolean('UseCallback', false);
        $this->RegisterPropertyString('SocketIP', (count(Sys_GetNetworkInfo()) > 0) ? Sys_GetNetworkInfo()[0]['IP'] : '');
        $this->RegisterPropertyInteger('SocketPort', 3777);
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

        $this->UnlockSemaphore('TedeeBridgeData');

        //Check configuration
        if ($this->ValidateConfiguration()) {
            $this->SendDebug(__FUNCTION__, 'Configuration is valid', 0);
            $this->ManageCallback();
        }
    }

    public function Destroy(): void
    {
        //Unregister WebHook
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterHook('/hook/tedee/bridge/' . $this->InstanceID);
        }

        // Never delete this line!
        parent::Destroy();
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

    public function ForwardData($JSONString): string
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);
        switch ($data->Buffer->Command) {
            case 'GetPairedLocks':
                $response = $this->GetPairedLocks();
                break;

            case 'GetLockDetails':
                $params = (array) $data->Buffer->Params;
                $response = $this->GetLockDetails($params['id']);
                break;

            case 'Lock':
                $params = (array) $data->Buffer->Params;
                $response = $this->Lock($params['id']);
                break;

            case 'Unlock':
                $params = (array) $data->Buffer->Params;
                $response = $this->UnLock($params['id']);
                break;

            case 'PullSpring':
                $params = (array) $data->Buffer->Params;
                $response = $this->PullSpring($params['id']);
                break;

            case 'CheckCallback':
                $response = json_encode($this->ReadPropertyBoolean('UseCallback'));
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data->Buffer->Command, 0);
                $response = json_encode(['Code' => 400, 'Header' => '', 'Body' => '']);
        }
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        return $response;
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function ValidateConfiguration(): bool
    {
        $status = 102;
        $result = true;
        if ($this->ReadPropertyString('Token') == '') {
            $this->SendDebug(__FUNCTION__, 'Token is missing!', 0);
            $status = 201;
            $result = false;
        }
        if ($this->ReadPropertyBoolean('UseCallback')) {
            if ($this->ReadPropertyString('SocketIP') == '') {
                $this->SendDebug(__FUNCTION__, 'Socket IP Address is missing!', 0);
                $status = 201;
                $result = false;
            }
        }
        if (!$this->ReadPropertyBoolean('Active')) {
            $this->SendDebug(__FUNCTION__, 'Instance is inactive!', 0);
            $result = false;
            $status = 104;
        }
        $this->SetStatus($status);
        return $result;
    }
}