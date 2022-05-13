<?php
/**
 * Plugin Name: Adopte un corail / recif = fiscalreceipt =
 * Plugin URI:
 * Description: Gestion des recus fiscaux
 * Version: 0.1
 * Requires PHP: 8.1
 * Author: Benoit DELBOE & Grégory COLLIN
 * Author URI:
 * Licence: GPLv2
 */
do_action(\Hyperion\RestAPI\Plugin::ADD_API_ENDPOINT_ACTION, new \D4rk0snet\FiscalReceipt\GetFiscalReceiptEndpoint());