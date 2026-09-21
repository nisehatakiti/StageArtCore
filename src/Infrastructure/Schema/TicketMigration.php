<?php
declare(strict_types=1);
namespace StageArtCore\Infrastructure\Schema;
final class TicketMigration{
 public static function ensure():void{
  global $wpdb;$c=$wpdb->get_charset_collate();require_once ABSPATH.'wp-admin/includes/upgrade.php';$p=$wpdb->prefix;$performance=$p.'stageart_plugin_production_performances';
  $column=$wpdb->get_var("SHOW COLUMNS FROM {$performance} LIKE 'capacity'");if(!$column)$wpdb->query("ALTER TABLE {$performance} ADD capacity INT UNSIGNED NOT NULL DEFAULT 0");
  dbDelta("CREATE TABLE {$p}stageart_plugin_ticket_reservations (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,reservation_no VARCHAR(64) NOT NULL,production_id BIGINT UNSIGNED NOT NULL,performance_id BIGINT UNSIGNED NOT NULL,customer_name VARCHAR(191) NOT NULL,customer_name_kana VARCHAR(191) NOT NULL,customer_email VARCHAR(191) NOT NULL,quantity INT UNSIGNED NOT NULL,authcore_user_id BIGINT UNSIGNED NULL,source VARCHAR(20) NOT NULL DEFAULT 'online',status VARCHAR(20) NOT NULL DEFAULT 'confirmed',created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY reservation_no(reservation_no),KEY production_id(production_id),KEY performance_id(performance_id),KEY authcore_user_id(authcore_user_id),KEY status(status)) {$c};");
  dbDelta("CREATE TABLE {$p}stageart_plugin_ticket_checkins (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,reservation_id BIGINT UNSIGNED NOT NULL,checked_in_at DATETIME NOT NULL,checked_in_by BIGINT UNSIGNED NULL,created_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY reservation_id(reservation_id),KEY checked_in_at(checked_in_at)) {$c};");
  dbDelta("CREATE TABLE {$p}stageart_plugin_ticket_sales_links (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,production_id BIGINT UNSIGNED NOT NULL,authcore_user_id BIGINT UNSIGNED NOT NULL,slug VARCHAR(191) NOT NULL,status VARCHAR(20) NOT NULL DEFAULT 'publish',created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY production_user(production_id,authcore_user_id),UNIQUE KEY production_slug(production_id,slug),KEY authcore_user_id(authcore_user_id)) {$c};");
 }
}
