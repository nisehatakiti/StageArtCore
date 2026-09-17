<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;
use StageArtCore\Presentation\PublicSite\SiteStructure;
final class MenuLayoutAdmin
{
    public const OPTION='stageart_core_menu_layout';
    public function register():void{add_action('admin_footer',[$this,'ui']);add_action('admin_post_stageart_save_menu',[$this,'save'],5);}
    public function ui():void{if(($_GET['page']??'')!=='stageart-menu'||!current_user_can('manage_options'))return;$layout=SiteStructure::menuLayout();echo '<script>(function(){const f=document.querySelector("form[action*=admin-post.php]");if(!f)return;const b=document.createElement("div");b.style="padding:14px 16px;margin:16px 0;border:1px solid #ccd0d4;background:#fff";b.innerHTML="<strong>メニュー表示位置</strong><p style=\"margin:6px 0 10px\">メニュー構造は共通のまま、テーマ上の表示位置を選択します。</p><label style=\"margin-right:20px\"><input type=\"radio\" name=\"stageart_menu_layout\" value=\"top\" '+("top"===layout?"checked":"")+'> 上部メニュー</label><label><input type=\"radio\" name=\"stageart_menu_layout\" value=\"left\" '+("left"===layout?"checked":"")+'> 左メニュー</label>";f.insertBefore(b,f.querySelector("#stageart-menu-items")||f.firstChild);})();</script>';}
    public function save():void{if(!current_user_can('manage_options')||!isset($_POST['stageart_menu_layout']))return;$layout=(string)wp_unslash($_POST['stageart_menu_layout']);if(in_array($layout,['top','left'],true))update_option(self::OPTION,$layout,false);}
}
