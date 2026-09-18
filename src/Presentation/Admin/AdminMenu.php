<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;
final class AdminMenu{
 public function register():void{
  add_menu_page('StageArt','StageArt','manage_options','stageart-plugin',[$this,'render'],'dashicons-tickets-alt',30);
 }
 public function render():void{
  echo '<div class="wrap"><h1>StageArt</h1><p>舞台芸術団体のWordPressサイトを管理するためのStageArtCoreです。</p><div class="card"><h2>団体</h2><p><a href="'.esc_url(admin_url('admin.php?page=stageart-organization')).'">団体ページ</a><br>団体の基本情報・コンテンツ・表示設定をまとめて管理します。</p><p><a href="'.esc_url(admin_url('admin.php?page=stageart-menu')).'">メニュー構成</a><br>サイトのナビゲーション構造を管理します。</p></div><div class="card"><h2>公演</h2><p><a href="'.esc_url(admin_url('admin.php?page=stageart-productions')).'">公演</a><br>公演情報・公演コンテンツ・表示設定を管理します。</p></div></div>';
 }
}
