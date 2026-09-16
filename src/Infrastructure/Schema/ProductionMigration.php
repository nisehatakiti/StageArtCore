<?php
declare(strict_types=1);
namespace StageArtCore\Infrastructure\Schema;
final class ProductionMigration{
 public const VERSION='1.0.2';
 public static function ensure():void{
  global $wpdb;
  $performances=$wpdb->prefix.'stageart_plugin_production_performances';
  $column=$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$performances} LIKE %s",'release_at'));
  $version=(string)get_option('stageart_core_production_schema_version','');
  if($version===self::VERSION && $column!==null)return;
  $table=$wpdb->prefix.'stageart_plugin_production_slug_history';
  $c=$wpdb->get_charset_collate();
  require_once ABSPATH.'wp-admin/includes/upgrade.php';
  dbDelta("CREATE TABLE {$table} (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,production_id BIGINT UNSIGNED NOT NULL,slug VARCHAR(191) NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY slug(slug),KEY production_id(production_id)) {$c};");
  if($column===null){
   $wpdb->query("ALTER TABLE {$performances} ADD COLUMN release_at DATETIME NULL AFTER label_id");
   $wpdb->query("ALTER TABLE {$performances} ADD KEY release_at (release_at)");
  }
  update_option('stageart_core_production_schema_version',self::VERSION,false);
 }
}
