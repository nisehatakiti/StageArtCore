<?php
declare(strict_types=1);
namespace StageArtCore\Infrastructure\Schema;
final class SurveyMigration{
 public const VERSION='1.1.0';
 public static function ensure():void{
  if((string)get_option('stageart_core_survey_schema_version','')===self::VERSION)return;
  global $wpdb;$c=$wpdb->get_charset_collate();$p=$wpdb->prefix;require_once ABSPATH.'wp-admin/includes/upgrade.php';
  dbDelta("CREATE TABLE {$p}stageart_plugin_production_surveys (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,production_id BIGINT UNSIGNED NOT NULL,title VARCHAR(191) NOT NULL,description LONGTEXT NULL,slug VARCHAR(191) NOT NULL,status VARCHAR(20) NOT NULL DEFAULT 'publish',created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY production_id(production_id),UNIQUE KEY slug(slug),KEY production_status(production_id,status)) {$c};");
  dbDelta("CREATE TABLE {$p}stageart_plugin_production_survey_questions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,survey_id BIGINT UNSIGNED NOT NULL,type VARCHAR(30) NOT NULL,label VARCHAR(255) NOT NULL,description TEXT NULL,is_required TINYINT(1) NOT NULL DEFAULT 0,display_order INT UNSIGNED NOT NULL DEFAULT 0,options_json LONGTEXT NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),KEY survey_order(survey_id,display_order)) {$c};");
  dbDelta("CREATE TABLE {$p}stageart_plugin_production_survey_responses (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,survey_id BIGINT UNSIGNED NOT NULL,performance_id BIGINT UNSIGNED NULL,submitted_at DATETIME NOT NULL,respondent_name VARCHAR(191) NULL,respondent_email VARCHAR(191) NULL,answers_json LONGTEXT NOT NULL,PRIMARY KEY(id),KEY survey_submitted(survey_id,submitted_at),KEY survey_performance(survey_id,performance_id)) {$c};");
  update_option('stageart_core_survey_schema_version',self::VERSION,false);
 }
}
