<?php

/**
 * @project       SymconTedeeBridge/helper/
 * @file          WebHook.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection SpellCheckingInspection */

declare(strict_types=1);

trait WebHook
{
    /**
     * This function will be called by the hook control. It will forward the incoming data to all children.
     */
    protected function ProcessHookData(): void
    {
        $this->SendDebug(__FUNCTION__ . ' ' . 'Incoming Data: ', print_r($_SERVER, true), 0);
        //Get content
        $data = file_get_contents('php://input');
        $this->SendDebug(__FUNCTION__ . ' ' . 'Data: ', $data, 0);
        //Send data to children
        $forwardData = [];
        $forwardData['DataID'] = self::TEDEE_DEVICE_DATA_GUID;
        $forwardData['Buffer'] = json_decode($data);
        $forwardData = json_encode($forwardData);
        $this->SendDebug(__FUNCTION__ . ' Forward Data: ', $forwardData, 0);
        $this->SendDataToChildren($forwardData);
    }

    /**
     * Registers a WebHook to the WebHook control instance.
     *
     * @param $WebHook
     */
    protected function RegisterHook($WebHook): void
    {
        $ids = IPS_GetInstanceListByModuleID(self::CORE_WEBHOOK_GUID);
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
                $this->SendDebug(__FUNCTION__, 'WebHook was successfully registered', 0);
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    /**
     * Unregisters the webhook from the WebHook control instance.
     *
     * @param $WebHook
     */
    private function UnregisterHook($WebHook): void
    {
        $ids = IPS_GetInstanceListByModuleID(self::CORE_WEBHOOK_GUID);
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            $index = null;
            foreach ($hooks as $key => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    $found = true;
                    $index = $key;
                    break;
                }
            }
            if ($found === true && !is_null($index)) {
                array_splice($hooks, $index, 1);
                IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
                IPS_ApplyChanges($ids[0]);
                $this->SendDebug(__FUNCTION__, 'WebHook was successfully unregistered', 0);
            }
        }
    }
}