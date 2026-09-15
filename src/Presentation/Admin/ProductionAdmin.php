<?php

declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;
use StageArtCore\Domain\Production\ProductionRepository;
use StageArtCore\Domain\Production\ProductionCreditRepository;
use StageArtCore\Domain\Member\MemberRepository;
final class ProductionAdmin{
 private ProductionRepository $repo;private ProductionCreditRepository $credits;
 public function __construct(){$this->repo=new ProductionRepository();$this->credits=new ProductionCreditRepository();add_action('admin_post_stageart_save_production',[$this,'save']);}
 public function register():void{register_post_type('stageart_production',['labels'=>['name'=>'公演','singular_name'=>'公演','add_new'=>'公演を追加','edit_item'=>'公演を編集'],'public'=>false,'show_ui'=>false,'show_in_menu'=>false,'supports'=>['title'],'rewrite'=>false]);add_submenu_page('stageart-plugin','公演','公演','manage_options','stageart-productions',[$this,'render']);add_action('admin_enqueue_scripts',[$this,'assets']);}
 public function assets(string$hook):void{if(str_contains($hook,'stageart-productions')){wp_enqueue_media();wp_enqueue_editor();}}
 private function guard():void{if(!current_user_can('manage_options'))wp_die('権限がありません。');check_admin_referer('stageart_production_action');}
 private function utc(?string$v):?string{if(!$v)return null;$d=\DateTimeImmutable::createFromFormat('Y-m-d H:i',str_replace('T',' ',trim($v)),new \DateTimeZone('Asia/Tokyo'));return$d?$d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'):null;}
 private function jst(?string$v):string{if(!$v)return'';try{$d=new \DateTimeImmutable($v,new \DateTimeZone('UTC'));return$d->setTimezone(new \DateTimeZone('Asia/Tokyo'))->format('Y-m-d\\TH:i');}catch(\Throwable){return'';}}
 private function meta(int$id,string$key,?string$value):void{if($value===null||$value==='')delete_post_meta($id,$key);else update_post_meta($id,$key,$value);}
 public function render():void{$id=(int)($_GET['id']??0);if(isset($_GET['new']))$id=0;if($id){$p=get_post($id);if(!$p||$p->post_type!=='stageart_production')$id=0;}echo'<div class="wrap"><h1>公演</h1>';if(isset($_GET['saved']))echo'<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';if($id||isset($_GET['new'])){$this->form($id);echo'</div>';return;}echo'<p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=stageart-productions&new=1')).'">＋ 公演を追加</a></p><table class="widefat striped"><thead><tr><th>公演名</th><th>状態</th><th>URL</th><th>操作</th></tr></thead><tbody>';$posts=get_posts(['post_type'=>'stageart_production','post_status'=>['publish','draft','private'],'numberposts'=>-1,'orderby'=>'date','order'=>'DESC']);foreach($posts as$p)echo'<tr><td><strong><a href="'.esc_url(admin_url('admin.php?page=stageart-productions&id='.$p->ID)).'">'.esc_html($p->post_title).'</a></strong></td><td>'.esc_html($p->post_status==='publish'?'公開':'下書き').'</td><td><code>'.esc_html(home_url('/production/'.($p->post_name?:$p->ID).'/')).'</code></td><td><a href="'.esc_url(admin_url('admin.php?page=stageart-productions&id='.$p->ID)).'">編集</a></td></tr>';if(!$posts)echo'<tr><td colspan="4">公演はまだ登録されていません。</td></tr>';echo'</tbody></table></div>';}
 private function input(string$label,string$name,string$value,string$type='text'):void{echo'<tr><th><label for="sa-'.esc_attr($name).'">'.esc_html($label).'</label></th><td><input id="sa-'.esc_attr($name).'" class="regular-text" type="'.esc_attr($type).'" name="'.esc_attr($name).'" value="'.esc_attr($value).'" /></td></tr>';}
