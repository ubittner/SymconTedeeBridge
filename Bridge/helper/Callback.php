<?php

/**
 * @project       SymconTedeeBridge/helper/
 * @file          Callback.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUndefinedFieldInspection */

declare(strict_types=1);

trait Callback
{
    public function ManageCallback(): void
    {
        //Get all callbacks from bridge
        $host = $this->ReadPropertyString('SocketIP');
        $port = (string) $this->ReadPropertyInteger('SocketPort');
        $useCallback = $this->ReadPropertyBoolean('UseCallback');
        $url = 'http://' . $host . ':' . $port . '/hook/tedee/bridge/' . $this->InstanceID . '/';
        $result = json_decode($this->GetCallbacks(), true);
        $exists = false;
        if (array_key_exists('Body', $result)) {
            $callbacks = json_decode($result['Body'], true);
            //Check if callback already exits
            if (is_array($callbacks)) {
                foreach ($callbacks as $callback) {
                    if (array_key_exists('url', $callback)) {
                        if ($url == $callback['url']) {
                            $exists = true;
                        }
                    }
                }
            }
        }
        //Add callback
        if ($useCallback) {
            $this->RegisterHook('/hook/tedee/bridge/' . $this->InstanceID);
            if (!$exists) {
                $this->AddCallback();
            }
        } //Delete callback
        else {
            $this->UnregisterHook('/hook/tedee/bridge/' . $this->InstanceID);
            if (array_key_exists('Body', $result)) {
                $callbacks = json_decode($result['Body'], true);
                if (is_array($callbacks)) {
                    foreach ($callbacks as $callback) {
                        if (array_key_exists('url', $callback)) {
                            if ($url == $callback['url']) {
                                if (array_key_exists('id', $callback)) {
                                    $this->DeleteCallback($callback['id']);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}