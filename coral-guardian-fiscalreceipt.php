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

use D4rk0snet\FiscalReceipt\Plugin;

register_activation_hook(__FILE__, '\D4rk0snet\FiscalReceipt\Plugin::install');
register_uninstall_hook(__FILE__, '\D4rk0snet\FiscalReceipt\Plugin::uninstall');

add_action('plugins_loaded', 'D4rk0snet\FiscalReceipt\Plugin::launchActions');
add_action('cli_init', [Plugin::class,'addCliCommand']);
