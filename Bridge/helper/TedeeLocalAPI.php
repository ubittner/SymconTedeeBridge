<?php

/**
 * @project       SymconTedeeBridge/helper/
 * @file          TedeeLocalAPI.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnnecessaryStringCastInspection */
/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection HttpUrlsUsage */

declare(strict_types=1);

trait TedeeLocalAPI
{
    ##### Bridge

    /**
     * Gets detailed information about the Bridge.
     *
     * @return string
     */
    public function GetBridgeInfo(): string
    {
        $endpoint = '/' . self::API_VERSION . '/bridge';
        return $this->SendDataToBridge($endpoint, 'GET', '');
    }

    /**
     * Gets a list of Tedee Locks paired with the Bridge.
     *
     * @return string
     */
    public function GetPairedLocks(): string
    {
        $endpoint = '/' . self::API_VERSION . '/lock';
        return $this->SendDataToBridge($endpoint, 'GET', '');
    }

    ##### Locks

    /**
     * Gets details for the selected Tedee Lock.
     *
     * @param int $DeviceID
     * @return string
     */
    public function GetLockDetails(int $DeviceID): string
    {
        $endpoint = '/' . self::API_VERSION . '/lock/' . $DeviceID;
        return $this->SendDataToBridge($endpoint, 'GET', '');
    }

    /**
     * Sends 'Lock' command to the selected Tedee Lock.
     *
     * @param int $DeviceID
     * @return string
     */
    public function Lock(int $DeviceID): string
    {
        $endpoint = '/' . self::API_VERSION . '/lock/' . $DeviceID . '/lock';
        return $this->SendDataToBridge($endpoint, 'POST', '');
    }

    /**
     * Sends 'Unlock' command to the selected Tedee Lock.
     *
     * @param int $DeviceID
     * @return string
     */
    public function UnLock(int $DeviceID): string
    {
        $endpoint = '/' . self::API_VERSION . '/lock/' . $DeviceID . '/unlock';
        return $this->SendDataToBridge($endpoint, 'POST', '');
    }

    /**
     * Sends 'Pull Spring' command to the selected Tedee Lock.
     *
     * @param int $DeviceID
     * @return string
     */
    public function PullSpring(int $DeviceID): string
    {
        $endpoint = '/' . self::API_VERSION . '/lock/' . $DeviceID . '/pull';
        return $this->SendDataToBridge($endpoint, 'POST', '');
    }

    ##### Callback

    /**
     * Gets a list of predefined event servers (webhooks).
     *
     * @return string
     */
    public function GetCallbacks(): string
    {
        $endpoint = '/' . self::API_VERSION . '/callback';
        return $this->SendDataToBridge($endpoint, 'GET', '');
    }

    /**
     * Adds a new callback url to the Bridge.
     *
     * @return string
     */
    public function AddCallback(): string
    {
        $socketIP = $this->ReadPropertyString('SocketIP');
        $socketPort = $this->ReadPropertyInteger('SocketPort');
        if ($socketIP == '') {
            return json_encode(['Code' => 400, 'Header' => '', 'Body' => '']);
        }
        $endpoint = '/' . self::API_VERSION . '/callback';
        $postfields = '{"url": "http://' . $socketIP . ':' . $socketPort . '/hook/tedee/bridge/' . $this->InstanceID . '/", "headers": [{"header_name": "symcon_tedee_' . $this->InstanceID . '"}]}';
        return $this->SendDataToBridge($endpoint, 'POST', $postfields);
    }

    /**
     * Deletes selected event server (webhook) from the Bridge.
     *
     * @param int $CallbackID
     * @return string
     */
    public function DeleteCallback(int $CallbackID): string
    {
        $endpoint = '/' . self::API_VERSION . '/callback/' . $CallbackID;
        return $this->SendDataToBridge($endpoint, 'DELETE', '');
    }

    ##### Send data

    /**
     * Sends the data to the Bridge.
     *
     * @param string $Endpoint
     * @param string $CustomRequest
     * @param string $Postfields
     * @return string
     */
    public function SendDataToBridge(string $Endpoint, string $CustomRequest, string $Postfields): string
    {
        $result = json_encode(['Code' => 400, 'Header' => '', 'Body' => '']);
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $Endpoint, 0);
        $this->SendDebug(__FUNCTION__, 'Custom request: ' . $CustomRequest, 0);
        $this->SendDebug(__FUNCTION__, 'Postfields: ' . $Postfields, 0);
        $active = $this->ReadPropertyBoolean('Active');
        if (!$active) {
            $this->SendDebug(__FUNCTION__, 'Abort, instance is inactive!', 0);
            return $result;
        }
        $bridgeIP = $this->ReadPropertyString('BridgeIP');
        $bridgePort = $this->ReadPropertyInteger('BridgePort');
        if (empty($bridgeIP) || $bridgePort == 0) {
            $this->SendDebug(__FUNCTION__, 'Please check your configuration!', 0);
            return $result;
        }
        $token = $this->ReadPropertyString('Token');
        if (empty($token)) {
            $this->SendDebug(__FUNCTION__, 'Please check your configuration!', 0);
            return $result;
        }
        //Encrypted token
        if ($this->ReadPropertyBoolean('UseEncryption')) {
            $timestamp = floor(microtime(true) * 1000);
            $concatenate = $token . $timestamp;
            $token = hash('sha256', $concatenate);
            $token = $token . $timestamp;
        }
        $url = 'http://' . $bridgeIP . ':' . $bridgePort . $Endpoint;
        $this->SendDebug(__FUNCTION__, 'URL: ' . $url, 0);
        //Enter semaphore
        if (!$this->LockSemaphore('TedeeBridgeData')) {
            $this->SendDebug(__FUNCTION__, 'Abort, Semaphore reached!', 0);
            $this->UnlockSemaphore('TedeeBridgeData');
            return $result;
        }
        //Send data to endpoint
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST      => $CustomRequest,
            CURLOPT_URL                => $url,
            CURLOPT_HEADER             => true,
            CURLOPT_RETURNTRANSFER     => true,
            CURLOPT_FAILONERROR        => true,
            CURLOPT_CONNECTTIMEOUT_MS  => $this->ReadPropertyInteger('Timeout'),
            CURLOPT_TIMEOUT            => $this->ReadPropertyInteger('ExecutionTimeout'),
            CURLOPT_POSTFIELDS         => $Postfields,
            CURLOPT_HTTPHEADER         => [
                'accept: application/json',
                'api_token: ' . $token]]);
        $response = curl_exec($curl);
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $header = '';
        $body = '';
        if (!curl_errno($curl)) {
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
        } else {
            $error_msg = curl_error($curl);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        $this->SendDebug(__FUNCTION__, 'Code: ' . $code, 0);
        $this->SendDebug(__FUNCTION__, 'Header: ' . $header, 0);
        $this->SendDebug(__FUNCTION__, 'Body: ' . $body, 0);
        curl_close($curl);
        //Leave semaphore
        $this->UnlockSemaphore('TedeeBridgeData');
        //Return result
        $result = ['Code' => $code, 'Header' => $header, 'Body' => $body];
        $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($result), 0);
        return json_encode($result);
    }

    /**
     * Attempts to set a semaphore and repeats this up to 100 times if unsuccessful.
     *
     * @param string $Ident
     * @return bool
     */
    protected function LockSemaphore(string $Ident): bool
    {
        for ($i = 0; $i < 100; $i++) {
            if (IPS_SemaphoreEnter(__CLASS__ . '.' . (string) $this->InstanceID . '.' . $Ident, 1)) {
                $this->SendDebug(__FUNCTION__, 'Semaphore locked', 0);
                return true;
            } else {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    /**
     * Deletes a semaphore.
     *
     * @param string $Ident
     */
    protected function UnlockSemaphore(string $Ident): void
    {
        IPS_SemaphoreLeave(__CLASS__ . '.' . (string) $this->InstanceID . '.' . $Ident);
        $this->SendDebug(__FUNCTION__, 'Semaphore unlocked', 0);
    }
}