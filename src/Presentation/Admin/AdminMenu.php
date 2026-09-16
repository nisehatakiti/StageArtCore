<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;
final class AdminMenu{
 public function register():void{
  add_menu_page('StageArt','StageArt','manage_options','stageart-plugin',[$this,'render'],'dashicons-tickets-alt',30);
 }
 public function render():void{
  echo '<div class="wrap"><h1>StageArt</h1><p>舞台芸術団体のWordPressサイトを管理するためのStageArtCoreです。</p><div class="card"><h2>サイト</h2><p><a href="'.esc_url(admin_url('admin.php?page=stageart-site-settings')).'">団体基本情報</a><br>団体名・団体紹介・公式SNSを管理します。</p><p><a href="'.esc_url(admin_url('admin.php?page=stageart-contact')).'">連絡先</a><br>所在地・メールアドレス・電話番号を管理します。</p></div><div class="card"><h2>コンテンツ</h2><p><a href="'.esc_url(admin_url('admin.php?page=stageart-members')).'">メンバー</a><br>メンバー個人コンテンツと共通項目を管理します。</p><p><a href="'.esc_url(admin_url('admin.php?page=stageart-productions')).'">公演</a><br>公演情報・公演スケジュール・会場・クレジット・チケット料金の案内を管理します。</p></div></div>';
 }
}
