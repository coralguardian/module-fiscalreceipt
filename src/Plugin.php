<?php

namespace D4rk0snet\FiscalReceipt;

class Plugin
{
    public const NEXT_RECEIPT_NUM = 'next_fiscal_receipt_num';

    public static function install()
    {
        add_option(self::NEXT_RECEIPT_NUM, 1);
    }

    public static function uninstall()
    {
        delete_option(self::NEXT_RECEIPT_NUM);
    }

    public static function launchActions()
    {
        do_action(\Hyperion\RestAPI\Plugin::ADD_API_ENDPOINT_ACTION, new \D4rk0snet\FiscalReceipt\Endpoint\GetFiscalReceiptEndpoint());
    }

    public static function addCliCommand()
    {
        WP_CLI::add_command('send_annual_fiscal_receipts',
            ['\D4rk0snet\FiscalReceipt\Command\SendAnnualFiscalReceipt','runCommand']);
    }
}