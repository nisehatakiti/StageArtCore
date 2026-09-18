<?php
/**
 * Plugin Name: StageArt Hero Assets
 * Description: StageArtCore の団体サイトHero用プリセット画像を提供するアセットパック。
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: nisehatakiti
 * Author URI: https://nisehatakiti.online/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stageart-hero-assets
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('STAGEART_HERO_ASSETS_FILE', __FILE__);
define('STAGEART_HERO_ASSETS_DIR', plugin_dir_path(__FILE__));
define('STAGEART_HERO_ASSETS_URL', plugin_dir_url(__FILE__));
define('STAGEART_HERO_ASSETS_VERSION', '1.0.0');
