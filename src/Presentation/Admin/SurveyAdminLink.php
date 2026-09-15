<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;
final class SurveyAdminLink{public function register():void{add_action('admin_menu',[$this,'registerMenu'],40);}public function registerMenu():void{add_submenu_page(null,'アンケート','アンケート','manage_options','stageart-survey',[$this,'redirect']);}public function redirect():void{$pid=(int)($_GET['production_id']??0);wp_safe_redirect(admin_url('admin.php?page=stageart-surveys&production_id='.$pid));exit;}}
