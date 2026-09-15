<?php
/**
 * Plugin Name: StageArtCore
 * Description: 舞台芸術団体向けの公演・メンバー・アンケート管理基盤。
 * Version: 0.5.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: nisehatakiti
 * Author URI: https://nisehatakiti.online/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stageart-core
 */
declare(strict_types=1);
if(!defined('ABSPATH'))exit;
define('STAGEART_CORE_VERSION','0.5.0');
define('STAGEART_CORE_FILE',__FILE__);
define('STAGEART_CORE_DIR',plugin_dir_path(__FILE__));
define('STAGEART_CORE_URL',plugin_dir_url(__FILE__));
spl_autoload_register(static function(string $class):void{$prefix='StageArtCore\\';if(!str_starts_with($class,$prefix))return;$relative=substr($class,strlen($prefix));$path=STAGEART_CORE_DIR.'src/'.str_replace('\\','/',$relative).'.php';if(is_file($path))require_once $path;});
register_activation_hook(STAGEART_CORE_FILE,static function():void{StageArtCore\Infrastructure\Schema\Schema::activate();(new StageArtCore\Presentation\PublicSite\MemberRouter())->add_rewrite_rules();(new StageArtCore\Presentation\PublicSite\ProductionRouter())->rewrite();flush_rewrite_rules();});
add_action('plugins_loaded',static function():void{(new StageArtCore\Plugin())->boot();});
