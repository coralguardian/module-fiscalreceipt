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
add_action('plugins_loaded', 'D4rk0snet\FiscalReceipt\Plugin::launchActions');
