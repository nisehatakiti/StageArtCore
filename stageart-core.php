<?php
/**
 * Plugin Name: StageArtCore
 * Description: 舞台芸術団体向けの公演・メンバー・アンケート管理基盤。
 * Version: 0.9.3
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
define('STAGEART_CORE_VERSION', '0.9.3');
define('STAGEART_CORE_FILE',__FILE__);
define('STAGEART_CORE_DIR',plugin_dir_path(__FILE__));
define('STAGEART_CORE_URL',plugin_dir_url(__FILE__));
spl_autoload_register(static function(string $class):void{$prefix='StageArtCore\\';if(!str_starts_with($class,$prefix))return;$relative=substr($class,strlen($prefix));$path=STAGEART_CORE_DIR.'src/'.str_replace('\\','/',$relative).'.php';if(is_file($path))require_once $path;});
register_activation_hook(STAGEART_CORE_FILE,static function():void{StageArtCore\Infrastructure\Schema\Schema::activate();StageArtCore\Infrastructure\Schema\ProductionMigration::ensure();StageArtCore\Infrastructure\Schema\SurveyMigration::ensure();StageArtCore\Infrastructure\RewriteManager::activate();});
add_action('plugins_loaded',static function():void{(new StageArtCore\Plugin())->boot();});
add_action('plugins_loaded',static function():void{(new StageArtCore\Presentation\Admin\HeroAdmin())->register();},20);
add_action('plugins_loaded',static function():void{(new StageArtCore\Presentation\Admin\ContentBlockAdmin())->register();},20);
add_action('plugins_loaded',static function():void{(new StageArtCore\Presentation\Admin\MenuLayoutAdmin())->register();},20);
add_action('plugins_loaded',static function():void{(new StageArtCore\Presentation\Admin\ProductionHeroAdmin())->register();},20);
add_action('plugins_loaded',static function():void{(new StageArtCore\Presentation\Admin\ProductionCrownAdmin())->register();},20);
add_action('plugins_loaded',static function():void{(new StageArtCore\Presentation\PublicSite\ProductionHeroPublic())->register();},20);
add_action('plugins_loaded',static function():void{(new StageArtCore\Presentation\PublicSite\OrganizationHeroPublic())->register();},20);
add_action('admin_enqueue_scripts',static function(string $hook):void{if(in_array(($_GET['page']??''),['stageart-homepage','stageart-organization'],true)&&current_user_can('manage_options'))wp_enqueue_media();});
add_action('wp_enqueue_scripts',static function():void{if(class_exists('StageArtCore\\Presentation\\PublicSite\\SiteStructure'))wp_enqueue_style('stageart-core-menu-layout',STAGEART_CORE_URL.'assets/menu-layout.css',[],STAGEART_CORE_VERSION);});
add_filter('body_class',static function(array $classes):array{if(class_exists('StageArtCore\\Presentation\\PublicSite\\SiteStructure'))$classes[]='stageart-menu-layout-'.StageArtCore\Presentation\PublicSite\SiteStructure::menuLayout();return$classes;});
// Schedule label persistence and size fixes are included in the 0.6.6 package.
