<?php

declare(strict_types=1);

namespace StageArtCore\Infrastructure\Schema;

final class Schema
{
    public const DB_VERSION = '0.6.1';

    public static function activate(): void
    {
        global $wpdb;
        $c = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE {$p}stageart_plugin_members (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,name VARCHAR(191) NOT NULL,slug VARCHAR(191) NOT NULL,photo_id BIGINT UNSIGNED NULL,profile LONGTEXT NULL,status VARCHAR(20) NOT NULL DEFAULT 'draft',display_order INT UNSIGNED NOT NULL DEFAULT 0,release_at DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY slug(slug),KEY status_display_order(status,display_order),KEY release_at(release_at)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_member_roles (member_id BIGINT UNSIGNED NOT NULL,role_key VARCHAR(40) NOT NULL,PRIMARY KEY(member_id,role_key),KEY role_key(role_key)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_member_social_links (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,member_id BIGINT UNSIGNED NOT NULL,platform VARCHAR(30) NOT NULL,url VARCHAR(2048) NOT NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY member_platform(member_id,platform),KEY member_id(member_id)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_member_fields (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,field_key VARCHAR(191) NOT NULL,name VARCHAR(191) NOT NULL,description TEXT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,display_order INT UNSIGNED NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY field_key(field_key),KEY is_active_display_order(is_active,display_order)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_member_field_values (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,member_id BIGINT UNSIGNED NOT NULL,field_id BIGINT UNSIGNED NOT NULL,value LONGTEXT NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY member_field(member_id,field_id),KEY field_id(field_id),KEY member_id(member_id)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_member_slug_history (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,member_id BIGINT UNSIGNED NOT NULL,slug VARCHAR(191) NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY slug(slug),KEY member_id(member_id)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_production_credit_sections (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,production_id BIGINT UNSIGNED NOT NULL,name VARCHAR(191) NOT NULL,release_at DATETIME NULL,display_order INT UNSIGNED NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),KEY production_order(production_id,display_order),KEY production_release(production_id,release_at)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_production_credit_items (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,section_id BIGINT UNSIGNED NOT NULL,name VARCHAR(191) NOT NULL,url VARCHAR(2048) NULL,display_order INT UNSIGNED NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),KEY section_order(section_id,display_order)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_production_participants (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,production_id BIGINT UNSIGNED NOT NULL,kind VARCHAR(20) NOT NULL,name VARCHAR(191) NOT NULL,role VARCHAR(191) NULL,member_id BIGINT UNSIGNED NULL,auth_user_id BIGINT UNSIGNED NULL,display_order INT UNSIGNED NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),KEY production_kind_order(production_id,kind,display_order),KEY member_id(member_id),KEY auth_user_id(auth_user_id)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_performance_labels (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,production_id BIGINT UNSIGNED NOT NULL,symbol VARCHAR(30) NOT NULL,name VARCHAR(191) NULL,display_order INT UNSIGNED NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),KEY production_order(production_id,display_order)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_production_performances (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,production_id BIGINT UNSIGNED NOT NULL,performance_date DATE NOT NULL,start_time TIME NOT NULL,end_time TIME NULL,label_id BIGINT UNSIGNED NULL,release_at DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),KEY production_datetime(production_id,performance_date,start_time),KEY label_id(label_id),KEY release_at(release_at)) {$c};");
        dbDelta("CREATE TABLE {$p}stageart_plugin_production_ticket_prices (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,production_id BIGINT UNSIGNED NOT NULL,description VARCHAR(191) NOT NULL,amount BIGINT UNSIGNED NOT NULL DEFAULT 0,show_on_reservation TINYINT(1) NOT NULL DEFAULT 1,display_order INT UNSIGNED NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),KEY production_order(production_id,display_order)) {$c};");
        update_option('stageart_core_db_version', self::DB_VERSION, false);
    }
}
