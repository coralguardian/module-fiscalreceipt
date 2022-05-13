<?php

namespace D4rk0snet\FiscalReceipt;

class Plugin
{
    public static function launchActions()
    {
        do_action(\Hyperion\RestAPI\Plugin::ADD_API_ENDPOINT_ACTION, new \D4rk0snet\FiscalReceipt\Endpoint\GetFiscalReceiptEndpoint());
    }
}