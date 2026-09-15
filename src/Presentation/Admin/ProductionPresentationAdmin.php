<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;

final class ProductionPresentationAdmin{
 private const THEMES=['standard'=>'標準','dark'=>'ダーク','light'=>'ライト'];
 public function register():void{
  add_action('admin_post_stageart_save_production',[$this,'save'],0);
  add_action('admin_footer',[$this,'field']);
 }
 public function save():void{
  if(!current_user_can('manage_options'))return;
  $id=(int)($_POST['id']??0);
  if($id<=0)return;
  $theme=sanitize_key(wp_unslash($_POST['presentation_theme']??'standard'));
  if(!isset(self::THEMES[$theme]))$theme='standard';
  update_post_meta($id,'presentation_theme',$theme);
 }
 public function field():void{
  if(!is_admin()||($_GET['page']??'')!=='stageart-productions')return;
  $id=(int)($_GET['id']??0);
  $theme=$id?(string)get_post_meta($id,'presentation_theme',true):'standard';
  if(!isset(self::THEMES[$theme]))$theme='standard';
  $options='';
  foreach(self::THEMES as$value=>$label)$options.='<option value="'.esc_attr($value).'"'.selected($theme,$value,false).'>'.esc_html($label).'</option>';
  echo '<script>(function(){var form=document.getElementById("stageart-production-form");if(!form)return;var table=form.querySelector("h2")?.nextElementSibling;if(!table||table.tagName!=="TABLE")return;var row=document.createElement("tr");row.innerHTML="<th><label for=\"sa-presentation-theme\">公演ページ表示テーマ</label></th><td><select id=\"sa-presentation-theme\" name=\"presentation_theme\">'.esc_js($options).'</select><p class=\"description\">公開中の公演ページのデザインを切り替えます。テーマ側が対応する表示を行います。</p></td>";table.appendChild(row);})();</script>';
 }
}
