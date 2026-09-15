<?php

declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;

use StageArtCore\Presentation\PublicSite\SiteStructure;
use StageArtCore\Domain\Member\MemberRepository;

final class SiteStructureAdmin
{
    public function register(): void
    {
        add_submenu_page('stageart-plugin','トップページ・メニュー','トップページ・メニュー','manage_options','stageart-site-structure',[$this,'render']);
    }

    public function __construct()
    {
        add_action('admin_post_stageart_save_site_structure',[$this,'save']);
    }

    public function save(): void
    {
        if(!current_user_can('manage_options'))wp_die('権限がありません。');
        check_admin_referer('stageart_site_structure');
        $sections=[];
        foreach((array)wp_unslash($_POST['sections']??[]) as $section){
            if(!is_array($section))continue;
            $slots=[];
            foreach((array)($section['slots']??[]) as $slot){
                if(!is_array($slot))continue;
                $slots[]=['type'=>sanitize_key((string)($slot['type']??'system')),'ref'=>sanitize_text_field((string)($slot['ref']??''))];
            }
            $sections[]=['section_id'=>sanitize_key((string)($section['section_id']??wp_generate_uuid4())),'heading'=>sanitize_text_field((string)($section['heading']??'')),'columns'=>max(1,min(6,(int)($section['columns']??1))),'layout'=>($section['layout']??'horizontal')==='vertical'?'vertical':'horizontal','slots'=>$slots];
        }
        update_option(SiteStructure::HOME_OPTION,$sections,false);
        $menu=[];
        foreach((array)wp_unslash($_POST['menu']??[]) as $item){
            if(!is_array($item))continue;
            $type=($item['type']??'content')==='folder'?'folder':'content';
            $refType=in_array(($item['ref_type']??'system'),['system','production','member','url'],true)?(string)$item['ref_type']:'system';
            $ref=(string)($item['ref_id']??'');
            $menu[]=['id'=>sanitize_key((string)($item['id']??wp_generate_uuid4())),'parent_id'=>sanitize_key((string)($item['parent_id']??'')),'type'=>$type,'ref_type'=>$refType,'ref_id'=>$refType==='url'?esc_url_raw($ref):sanitize_text_field($ref),'label'=>sanitize_text_field((string)($item['label']??'')),'order'=>max(1,(int)($item['order']??1))];
        }
        update_option(SiteStructure::MENU_OPTION,$menu,false);
        wp_safe_redirect(admin_url('admin.php?page=stageart-site-structure&saved=1'));exit;
    }

    public function render(): void
    {
        $sections=SiteStructure::homepageSections();$menu=SiteStructure::menuItems();
        $productions=get_posts(['post_type'=>'stageart_production','post_status'=>['publish','draft'],'numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
        $members=(new MemberRepository())->all(false);
        echo '<div class="wrap"><h1>トップページ・メニュー</h1>';
        if(isset($_GET['saved']))echo '<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';
        echo '<p>Alumni方式と同じく、コンテンツそのものと「どこに表示するか」を分離して管理します。トップページのセクション配置と、サイトメニューは独立して設定できます。</p>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stageart_save_site_structure">';wp_nonce_field('stageart_site_structure');
        echo '<h2>トップページ設定</h2><div id="sa-home-sections">';
        foreach($sections as $si=>$section)$this->sectionRow($si,$section);
        echo '</div><p><button type="button" class="button" data-add-section>＋ セクションを追加</button></p>';
        echo '<h2>メニュー構成</h2><p>フォルダは階層を作るためだけの項目です。1つのコンテンツを複数のメニュー項目から参照できます。</p><div id="sa-menu-items">';
        foreach($menu as $mi=>$item)$this->menuRow($mi,$item,$menu,$productions,$members);
        echo '</div><p><button type="button" class="button" data-add-menu>＋ メニュー項目を追加</button></p>';
        submit_button('保存');echo '</form>';
        echo '<script>'. $this->script($productions,$members) .'</script></div>';
    }

    private function sectionRow(int $i,array $s):void
    {
        echo '<fieldset class="card sa-home-section" style="margin:15px 0;padding:15px"><legend><strong>セクション</strong></legend><input type="hidden" name="sections['.$i.'][section_id]" value="'.esc_attr($s['section_id']).'"><p><label>見出し <input class="regular-text" name="sections['.$i.'][heading]" value="'.esc_attr($s['heading']).'"></label> <label>列数 <select name="sections['.$i.'][columns]">';for($n=1;$n<=6;$n++)echo '<option value="'.$n.'"'.selected((int)$s['columns'],$n,false).'>'.$n.'</option>';echo '</select></label> <label>配置 <select name="sections['.$i.'][layout]"><option value="horizontal"'.selected($s['layout'],'horizontal',false).'>横並び</option><option value="vertical"'.selected($s['layout'],'vertical',false).'>縦並び</option></select></label> <button type="button" class="button-link-delete" data-remove-section>削除</button></p><table class="widefat"><thead><tr><th>種類</th><th>参照先</th><th></th></tr></thead><tbody>';
        foreach((array)$s['slots'] as $j=>$slot)$this->slotRow($i,$j,$slot);
        echo '</tbody></table><p><button type="button" class="button" data-add-slot>＋ コンテンツを追加</button></p></fieldset>';
    }

    private function slotRow(int $si,int $ji,array $slot):void
    {
        echo '<tr class="sa-slot"><td><select name="sections['.$si.'][slots]['.$ji.'][type]" class="sa-slot-type"><option value="system"'.selected($slot['type'],'system',false).'>システム</option><option value="production"'.selected($slot['type'],'production',false).'>公演</option><option value="member"'.selected($slot['type'],'member',false).'>メンバー</option><option value="url"'.selected($slot['type'],'url',false).'>URL</option></select></td><td><input class="large-text sa-slot-ref" name="sections['.$si.'][slots]['.$ji.'][ref]" value="'.esc_attr($slot['ref']).'" placeholder="参照IDまたはURL"></td><td><button type="button" class="button-link-delete" data-remove-slot>削除</button></td></tr>';
    }

    private function menuRow(int $i,array $item,array $all,array $productions,array $members):void
    {
        echo '<div class="card sa-menu-row" style="margin:10px 0;padding:12px"><input type="hidden" name="menu['.$i.'][id]" value="'.esc_attr($item['id']).'"><p><label>表示名 <input name="menu['.$i.'][label]" value="'.esc_attr($item['label']).'" required></label> <label>順序 <input type="number" min="1" name="menu['.$i.'][order]" value="'.esc_attr($item['order']).'" style="width:70px"></label> <label>種類 <select name="menu['.$i.'][type]" class="sa-menu-type"><option value="content"'.selected($item['type'],'content',false).'>コンテンツ</option><option value="folder"'.selected($item['type'],'folder',false).'>フォルダ</option></select></label> <label>親 <select name="menu['.$i.'][parent_id]"><option value="">（なし）</option>';
        foreach($all as $parent){if($parent['id']===$item['id'])continue;echo '<option value="'.esc_attr($parent['id']).'"'.selected($item['parent_id'],$parent['id'],false).'>'.esc_html($parent['label']?:'（無題）').'</option>';}
        echo '</select></label></p><p class="sa-menu-content"><label>参照種別 <select name="menu['.$i.'][ref_type]"><option value="system"'.selected($item['ref_type'],'system',false).'>システム</option><option value="production"'.selected($item['ref_type'],'production',false).'>公演</option><option value="member"'.selected($item['ref_type'],'member',false).'>メンバー</option><option value="url"'.selected($item['ref_type'],'url',false).'>URL</option></select></label> <label>参照ID / URL <input class="regular-text" name="menu['.$i.'][ref_id]" value="'.esc_attr($item['ref_id']).'"></label> <button type="button" class="button-link-delete" data-remove-menu>削除</button></p></div>';
    }

    private function script(array $productions,array $members):string
    {
        $productionOptions='<option value="">選択してください</option>';foreach($productions as $p)$productionOptions.='<option value="'.(int)$p->ID.'">'.esc_html($p->post_title).'</option>';
        $memberOptions='<option value="">選択してください</option>';foreach($members as $m)$memberOptions.='<option value="'.(int)$m['id'].'">'.esc_html($m['name']).'</option>';
        $systemOptions='';foreach(SiteStructure::systemLabels() as $k=>$v)$systemOptions.='<option value="'.esc_attr($k).'">'.esc_html($v).'</option>';
        return "(function(){var home=document.getElementById('sa-home-sections'),menu=document.getElementById('sa-menu-items');var po='".esc_js($productionOptions)."',mo='".esc_js($memberOptions)."',so='".esc_js($systemOptions)."';function bind(){home.onclick=function(e){if(e.target.matches('[data-remove-section]')){e.target.closest('.sa-home-section').remove();return;}if(e.target.matches('[data-remove-slot]')){e.target.closest('tr').remove();return;}if(e.target.matches('[data-add-slot]')){var sec=e.target.closest('.sa-home-section'),si=[].indexOf.call(home.children,sec),tb=sec.querySelector('tbody'),j=tb.children.length;tb.insertAdjacentHTML('beforeend','<tr class=\"sa-slot\"><td><select name=\"sections['+si+'][slots]['+j+'][type]\"><option value=\"system\">システム</option><option value=\"production\">公演</option><option value=\"member\">メンバー</option><option value=\"url\">URL</option></select></td><td><input class=\"large-text\" name=\"sections['+si+'][slots]['+j+'][ref]\" placeholder=\"参照IDまたはURL\"></td><td><button type=\"button\" class=\"button-link-delete\" data-remove-slot>削除</button></td></tr>');}};menu.onclick=function(e){if(e.target.matches('[data-remove-menu]'))e.target.closest('.sa-menu-row').remove();};document.querySelector('[data-add-section]').onclick=function(){var i=home.children.length;home.insertAdjacentHTML('beforeend','<fieldset class=\"card sa-home-section\" style=\"margin:15px 0;padding:15px\"><legend><strong>セクション</strong></legend><input type=\"hidden\" name=\"sections['+i+'][section_id]\" value=\"new-'+Date.now()+'\"><p><label>見出し <input class=\"regular-text\" name=\"sections['+i+'][heading]\"></label> <label>列数 <select name=\"sections['+i+'][columns]\"><option>1</option><option>2</option><option>3 selected>3</option><option>4</option><option>5</option><option>6</option></select></label> <label>配置 <select name=\"sections['+i+'][layout]\"><option value=\"horizontal\">横並び</option><option value=\"vertical\">縦並び</option></select></label> <button type=\"button\" class=\"button-link-delete\" data-remove-section>削除</button></p><table class=\"widefat\"><thead><tr><th>種類</th><th>参照先</th><th></th></tr></thead><tbody><tr class=\"sa-slot\"><td><select name=\"sections['+i+'][slots][0][type]\"><option value=\"system\">システム</option><option value=\"production\">公演</option><option value=\"member\">メンバー</option><option value=\"url\">URL</option></select></td><td><input class=\"large-text\" name=\"sections['+i+'][slots][0][ref]\"></td><td><button type=\"button\" class=\"button-link-delete\" data-remove-slot>削除</button></td></tr></tbody></table><p><button type=\"button\" class=\"button\" data-add-slot>＋ コンテンツを追加</button></p></fieldset>');};document.querySelector('[data-add-menu]').onclick=function(){var i=menu.children.length,id='new-'+Date.now();menu.insertAdjacentHTML('beforeend','<div class=\"card sa-menu-row\" style=\"margin:10px 0;padding:12px\"><input type=\"hidden\" name=\"menu['+i+'][id]\" value=\"'+id+'\"><p><label>表示名 <input name=\"menu['+i+'][label]\" required></label> <label>順序 <input type=\"number\" min=\"1\" name=\"menu['+i+'][order]\" value=\"'+(i+1)+'\" style=\"width:70px\"></label> <label>種類 <select name=\"menu['+i+'][type]\"><option value=\"content\">コンテンツ</option><option value=\"folder\">フォルダ</option></select></label> <label>親 <select name=\"menu['+i+'][parent_id]\"><option value=\"\">（なし）</option></select></label></p><p><label>参照種別 <select name=\"menu['+i+'][ref_type]\"><option value=\"system\">システム</option><option value=\"production\">公演</option><option value=\"member\">メンバー</option><option value=\"url\">URL</option></select></label> <label>参照ID / URL <input class=\"regular-text\" name=\"menu['+i+'][ref_id]\"></label> <button type=\"button\" class=\"button-link-delete\" data-remove-menu>削除</button></p></div>');};bind();})();";
    }
}
