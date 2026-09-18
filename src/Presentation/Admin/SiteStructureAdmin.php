<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;
use StageArtCore\Presentation\PublicSite\SiteStructure;
use StageArtCore\Domain\Member\MemberRepository;
use StageArtCore\Presentation\Admin\FreeContentAdmin;

final class SiteStructureAdmin
{
    public function __construct(){add_action('admin_post_stageart_save_homepage',[$this,'saveHomepage']);add_action('admin_post_stageart_save_menu',[$this,'saveMenu']);}
    public function register():void{add_submenu_page('stageart-plugin','トップページ設定','トップページ設定','manage_options','stageart-homepage',[$this,'renderHomepage']);add_submenu_page('stageart-plugin','メニュー構成','メニュー構成','manage_options','stageart-menu',[$this,'renderMenu']);}
    private function data():array{return[get_posts(['post_type'=>'stageart_production','post_status'=>['publish','draft'],'numberposts'=>-1,'orderby'=>'title','order'=>'ASC']),(new MemberRepository())->all(false)];}
    private function picker(array $p,array $m,string $selected=''):string
    {
        $h='<option value="">選択してください</option><optgroup label="団体">'; 
        foreach(SiteStructure::systemLabels() as $k=>$v)$h.='<option value="system:'.esc_attr($k).'"'.selected($selected,'system:'.$k,false).'>'.esc_html($v).'</option>';
        $h.='</optgroup><optgroup label="公演">';
        foreach($p as $x)$h.='<option value="production:'.(int)$x->ID.'"'.selected($selected,'production:'.$x->ID,false).'>'.esc_html($x->post_title).'</option>';
        $h.='</optgroup><optgroup label="メンバー">';
        foreach($m as $x)$h.='<option value="member:'.(int)$x['id'].'"'.selected($selected,'member:'.$x['id'],false).'>'.esc_html($x['name']).'</option>';
        $h.='</optgroup><optgroup label="自由コンテンツ">';
        foreach(FreeContentAdmin::choices(null) as $id=>$x)$h.='<option value="free_content:'.(int)$id.'"'.selected($selected,'free_content:'.$id,false).'>'.esc_html($x['title']).'</option>';
        $h.='</optgroup><optgroup label="外部リンク"><option value="url:"'.(str_starts_with($selected,'url:')?' selected':'').'>外部URL</option></optgroup>';
        return $h;
    }
    public function renderHomepage():void
    {
        $blocks=SiteStructure::homepageContentBlocks();[$p,$m]=$this->data();
        $organizationPage = (($_GET['page'] ?? '') === 'stageart-organization');
        echo'<div class="wrap stageart-homepage-admin"><style>
        .sa-content-block{margin:18px 0;padding:18px;background:#f6f7f7;border:1px solid #ccd0d4}
        .sa-content-block>h2{margin:0 0 14px;font-size:18px}
        .sa-section{margin:14px 0;padding:16px;background:#fff;border:1px solid #dcdcde}
        .sa-section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
        .sa-section-fields{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start}
        .sa-field{display:flex;flex-direction:column;gap:5px;min-width:180px}
        .sa-field.sa-field-wide{min-width:280px}
        .sa-field-label{font-weight:600;line-height:1.4}
        .sa-field select,.sa-field input{margin:0;box-sizing:border-box}
        .sa-field-wide select{width:100%}
        .sa-section-actions{margin-top:12px}
        </style><h1>トップページ設定</h1>';
        if(isset($_GET['saved']))echo'<div class="notice notice-success"><p>保存しました。</p></div>';
        echo'<p>トップページは「コンテンツブロック」の中に複数の「セクション」を配置して構成します。セクションごとに表示する項目数と表示方向を設定できます。</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        wp_nonce_field('stageart_homepage');echo'<input type="hidden" name="action" value="stageart_save_homepage"><div id="stageart-content-blocks">';
        foreach($blocks as$bi=>$block)$this->contentBlock($bi,$block,$p,$m);
        echo'</div><p><button type="button" class="button" id="stageart-add-block">＋ コンテンツブロックを追加</button></p>';
        submit_button('すべての設定を保存');echo'</form>'.$this->homepageJs($p,$m).'</div>';
    }
    private function contentBlock(int$bi,array$block,array$p,array$m):void
    {
        $bid=sanitize_key((string)($block['block_id']??wp_generate_uuid4()));
        echo'<section class="sa-content-block" data-block="'.$bid.'"><h2>コンテンツブロック <span class="sa-block-number">'.($bi+1).'</span></h2><div class="sa-sections">';
        foreach(array_values((array)($block['sections']??[])) as$si=>$section)$this->section($bi,$si,$section,$p,$m);
        echo'</div><p><button type="button" class="button sa-add-section">＋ セクションを追加</button> <button type="button" class="button-link-delete sa-remove-block">コンテンツブロックを削除</button></p></section>';
    }
    private function section(int$bi,int$si,array$s,array$p,array$m):void
    {
        $id=sanitize_key((string)($s['section_id']??wp_generate_uuid4()));$columns=max(1,min(20,(int)$s['columns']));$slots=array_values((array)($s['slots']??[]));
        echo'<fieldset class="sa-section" data-section="'.$id.'"><div class="sa-section-header"><strong>セクション <span class="sa-section-number">'.($si+1).'</span></strong></div><div class="sa-section-fields">';
        echo'<div class="sa-field sa-field-wide"><span class="sa-field-label">見出し（任意）</span><input type="text" class="regular-text" name="blocks['.$bi.'][sections]['.$si.'][heading]" value="'.esc_attr($s['heading']).'" placeholder="例：私たちについて"></div>';
        echo'<div class="sa-field"><span class="sa-field-label">表示数</span><select class="sa-columns" name="blocks['.$bi.'][sections]['.$si.'][columns]">';for($i=1;$i<=20;$i++)echo'<option value="'.$i.'"'.selected($columns,$i,false).'>'.$i.'件</option>';echo'</select></div>';
        echo'<div class="sa-field"><span class="sa-field-label">表示方向</span><select name="blocks['.$bi.'][sections]['.$si.'][layout]"><option value="horizontal"'.selected($s['layout'],'horizontal',false).'>横並び</option><option value="vertical"'.selected($s['layout'],'vertical',false).'>縦並び</option></select></div></div>';
        echo'<div class="sa-home-slots">';
        for($i=0;$i<20;$i++)$this->slot($bi,$si,$i,$slots[$i]??['type'=>'none','ref'=>'','indent'=>0],$p,$m,$i<$columns);
        echo'</div><div class="sa-section-actions"><button type="button" class="button-link-delete sa-remove-section">セクションを削除</button></div></fieldset>';
    }
    private function slot(int$bi,int$si,int$i,array$slot,array$p,array$m,bool$visible):void
    {
        $type=(string)($slot['type']??'none');$heading=$type==='heading';$selected=$heading||$type==='none'?'':$type.':'.($slot['ref']??'');$indent=max(0,min(3,absint($slot['indent']??0)));
        echo'<div class="sa-home-slot"'.($visible?'':' hidden').' style="margin:14px 0;padding:10px 0;border-top:1px solid #eee"><strong>'.($i+1).'件目</strong><div class="sa-section-fields" style="margin-top:8px">';
        echo'<div class="sa-field"><span class="sa-field-label">項目種別</span><select name="blocks['.$bi.'][sections]['.$si.'][slots]['.$i.'][type]" class="sa-slot-type"><option value="link"'.selected(!$heading,true,false).'>コンテンツリンク</option><option value="heading"'.selected($heading,true,false).'>見出し</option></select></div>';
        echo'<div class="sa-field sa-field-wide sa-slot-content-wrap" style="display:'.($heading?'none':'flex').';"><span class="sa-field-label">コンテンツ</span><select name="blocks['.$bi.'][sections]['.$si.'][slots]['.$i.'][content]" class="sa-slot-content">'.$this->picker($p,$m,$selected).'</select></div>';
        echo'<div class="sa-field sa-field-wide sa-slot-heading-wrap" style="display:'.($heading?'flex':'none').';"><span class="sa-field-label">見出し</span><input type="text" name="blocks['.$bi.'][sections]['.$si.'][slots]['.$i.'][heading]" class="regular-text sa-slot-heading" value="'.esc_attr($heading?(string)($slot['ref']??''):'').'"></div>';
        echo'<div class="sa-field"><span class="sa-field-label">インデント</span><select name="blocks['.$bi.'][sections]['.$si.'][slots]['.$i.'][indent]" class="sa-slot-indent"><option value="0"'.selected($indent,0,false).'>なし</option><option value="1"'.selected($indent,1,false).'>1段</option><option value="2"'.selected($indent,2,false).'>2段</option><option value="3"'.selected($indent,3,false).'>3段</option></select></div></div></div>';
    }
    private function homepageJs(array$p,array$m):string
    {
        $picker=wp_json_encode($this->picker($p,$m,''),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
        return'<script>(function(){const root=document.getElementById("stageart-content-blocks"),addBlock=document.getElementById("stageart-add-block"),picker='.$picker.';if(!root)return;
function wireSection(sec){const cols=sec.querySelector(".sa-columns");if(cols){const update=()=>sec.querySelectorAll(".sa-home-slot").forEach((r,i)=>r.hidden=i>=parseInt(cols.value||1,10));cols.addEventListener("change",update);update()}
sec.querySelectorAll(".sa-slot-type").forEach(t=>t.addEventListener("change",function(){const r=this.closest(".sa-home-slot"),h=this.value==="heading";r.querySelector(".sa-slot-content-wrap").style.display=h?"none":"flex";r.querySelector(".sa-slot-heading-wrap").style.display=h?"flex":"none"}))}
function renumber(){root.querySelectorAll(".sa-content-block").forEach((b,bi)=>{b.querySelector(".sa-block-number").textContent=bi+1;b.querySelectorAll(".sa-section").forEach((s,si)=>s.querySelector(".sa-section-number").textContent=si+1)})}
root.querySelectorAll(".sa-section").forEach(wireSection);
root.querySelectorAll(".sa-content-block").forEach(b=>b.querySelector(".sa-add-section")?.addEventListener("click",()=>addSection(b)));
function addSection(block){const bi=[...root.children].indexOf(block),wrap=block.querySelector(".sa-sections"),si=wrap.children.length,id="new-"+Date.now()+"-"+Math.random().toString(36).slice(2);const fs=document.createElement("fieldset");fs.className="sa-section";fs.dataset.section=id;fs.innerHTML="<div class=\"sa-section-header\"><strong>セクション <span class=\"sa-section-number\"></span></strong></div><div class=\"sa-section-fields\"><div class=\"sa-field sa-field-wide\"><span class=\"sa-field-label\">見出し（任意）</span><input type=\"text\" class=\"regular-text\" name=\"blocks["+bi+"][sections]["+si+"][heading]\"></div><div class=\"sa-field\"><span class=\"sa-field-label\">表示数</span><select class=\"sa-columns\" name=\"blocks["+bi+"][sections]["+si+"][columns]\"><option value=\"1\">1件</option><option value=\"2\">2件</option><option value=\"3\" selected>3件</option><option value=\"4\">4件</option><option value=\"5\">5件</option></select></div><div class=\"sa-field\"><span class=\"sa-field-label\">表示方向</span><select name=\"blocks["+bi+"][sections]["+si+"][layout]\"><option value=\"horizontal\">横並び</option><option value=\"vertical\">縦並び</option></select></div></div><div class=\"sa-home-slots\"></div><div class=\"sa-section-actions\"><button type=\"button\" class=\"button-link-delete sa-remove-section\">セクションを削除</button></div>";const slots=fs.querySelector(".sa-home-slots");for(let i=0;i<20;i++){const r=document.createElement("div");r.className="sa-home-slot";r.hidden=i>=3;r.style="margin:14px 0;padding:10px 0;border-top:1px solid #eee";r.innerHTML="<strong>"+(i+1)+"件目</strong><div class=\"sa-section-fields\" style=\"margin-top:8px\"><div class=\"sa-field\"><span class=\"sa-field-label\">項目種別</span><select name=\"blocks["+bi+"][sections]["+si+"][slots]["+i+"][type]\" class=\"sa-slot-type\"><option value=\"link\">コンテンツリンク</option><option value=\"heading\">見出し</option></select></div><div class=\"sa-field sa-field-wide sa-slot-content-wrap\"><span class=\"sa-field-label\">コンテンツ</span><select name=\"blocks["+bi+"][sections]["+si+"][slots]["+i+"][content]\" class=\"sa-slot-content\">"+picker+"</select></div><div class=\"sa-field sa-field-wide sa-slot-heading-wrap\" style=\"display:none\"><span class=\"sa-field-label\">見出し</span><input type=\"text\" name=\"blocks["+bi+"][sections]["+si+"][slots]["+i+"][heading]\" class=\"regular-text sa-slot-heading\"></div><div class=\"sa-field\"><span class=\"sa-field-label\">インデント</span><select name=\"blocks["+bi+"][sections]["+si+"][slots]["+i+"][indent]\" class=\"sa-slot-indent\"><option value=\"0\">なし</option><option value=\"1\">1段</option><option value=\"2\">2段</option><option value=\"3\">3段</option></select></div></div>";slots.appendChild(r)}wrap.appendChild(fs);wireSection(fs);renumber()}
addBlock.addEventListener("click",function(){const bi=root.children.length,b=document.createElement("section");b.className="sa-content-block";b.innerHTML="<h2>コンテンツブロック <span class=\"sa-block-number\"></span></h2><div class=\"sa-sections\"></div><p><button type=\"button\" class=\"button sa-add-section\">＋ セクションを追加</button> <button type=\"button\" class=\"button-link-delete sa-remove-block\">コンテンツブロックを削除</button></p>";root.appendChild(b);b.querySelector(".sa-add-section").addEventListener("click",()=>addSection(b));addSection(b);renumber()});
root.addEventListener("click",function(e){if(e.target.matches(".sa-remove-section")){e.target.closest(".sa-section").remove();renumber()}if(e.target.matches(".sa-remove-block")){e.target.closest(".sa-content-block").remove();renumber()}});
renumber()})();</script>';
    }
    public function saveHomepage():void
    {
        if(!current_user_can('manage_options'))wp_die('権限がありません。');check_admin_referer('stageart_homepage');$out=[];
        foreach((array)wp_unslash($_POST['blocks']??[])as$bi=>$block){if(!is_array($block))continue;$sections=[];
            foreach((array)($block['sections']??[])as$si=>$s){if(!is_array($s))continue;$columns=max(1,min(20,(int)($s['columns']??1)));$slots=[];
                foreach(array_slice((array)($s['slots']??[]),0,$columns)as$slot){if(!is_array($slot))continue;$indent=max(0,min(3,absint($slot['indent']??0)));$type=($slot['type']??'link')==='heading'?'heading':'link';
                    if($type==='heading'){$text=sanitize_text_field((string)($slot['heading']??''));if($text!=='')$slots[]=['type'=>'heading','ref'=>$text,'indent'=>$indent];continue;}
                    [$kind,$ref]=array_pad(explode(':',(string)($slot['content']??''),2),2,'');if(!in_array($kind,['system','production','member','free_content','url'],true)||$ref==='')continue;if($kind==='free_content'&&!FreeContentAdmin::canReference((int)$ref,null))continue;$slots[]=['type'=>$kind,'ref'=>$kind==='url'?esc_url_raw($ref):sanitize_text_field($ref),'indent'=>$indent];
                }
                $sections[]=['section_id'=>sanitize_key((string)($s['section_id']??wp_generate_uuid4())),'heading'=>sanitize_text_field((string)($s['heading']??'')),'columns'=>$columns,'layout'=>($s['layout']??'horizontal')==='vertical'?'vertical':'horizontal','slots'=>$slots];
            }
            $out[]=['block_id'=>sanitize_key((string)($block['block_id']??wp_generate_uuid4())),'sections'=>$sections];
        }
        update_option(SiteStructure::HOME_OPTION,$out,false);wp_safe_redirect(admin_url('admin.php?page=stageart-homepage&saved=1'));exit;
    }
    public function renderMenu():void{$menu=SiteStructure::menuItems();[$p,$m]=$this->data();echo'<div class="wrap"><h1>メニュー構成</h1>';if(isset($_GET['saved']))echo'<div class="notice notice-success"><p>保存しました。</p></div>';echo'<p>トップページのページ配置とは独立したサイトメニューです。</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('stageart_menu');echo'<input type="hidden" name="action" value="stageart_save_menu"><div id="stageart-menu-items">';foreach($menu as$i=>$item)$this->menuRow($i,$item,$menu,$p,$m);echo'</div><p><button type="button" class="button" id="stageart-add-menu">＋ メニュー項目を追加</button></p>';submit_button('メニューを保存');echo'</form><script>'.$this->menuJs($p,$m).'</script></div>';}
    private function menuRow(int$i,array$item,array$all,array$p,array$m):void{$sel=$item['ref_type'].':'.$item['ref_id'];echo'<div class="card sa-menu-row" style="margin:10px 0;padding:12px"><input type="hidden" name="menu['.$i.'][id]" value="'.esc_attr($item['id']).'"><p><label>表示名 <input name="menu['.$i.'][label]" value="'.esc_attr($item['label']).'" required></label>　<label>順序 <input type="number" min="1" name="menu['.$i.'][order]" value="'.esc_attr($item['order']).'" style="width:70px"></label>　<label>項目種別 <select name="menu['.$i.'][type]"><option value="content"'.selected($item['type'],'content',false).'>コンテンツリンク</option><option value="folder"'.selected($item['type'],'folder',false).'>フォルダ</option></select></label>　<label>親 <select name="menu['.$i.'][parent_id]"><option value="">（なし）</option>';foreach($all as$x){if($x['id']===$item['id'])continue;echo'<option value="'.esc_attr($x['id']).'"'.selected($item['parent_id'],$x['id'],false).'>'.esc_html($x['label']?:'（無題）').'</option>';}echo'</select></label></p><p><label>コンテンツリンク <select name="menu['.$i.'][content]">'.$this->picker($p,$m,$sel).'</select></label> <button type="button" class="button-link-delete" data-remove-menu>削除</button></p></div>';}
    private function menuJs(array$p,array$m):string{$opts=wp_json_encode($this->picker($p,$m,''),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);return'<script>(function(){const root=document.getElementById("stageart-menu-items"),add=document.getElementById("stageart-add-menu"),opts='.$opts.';if(!root||!add)return;add.addEventListener("click",function(){const i=root.children.length,row=document.createElement("div");row.className="card sa-menu-row";row.style="margin:10px 0;padding:12px";row.innerHTML="<input type=\"hidden\" name=\"menu["+i+"][id]\" value=\"new-"+Date.now()+"\"><p><label>表示名 <input name=\"menu["+i+"][label]\" required></label>　<label>順序 <input type=\"number\" min=\"1\" name=\"menu["+i+"][order]\" value=\""+(i+1)+"\"></label>　<label>項目種別 <select name=\"menu["+i+"][type]\"><option value=\"content\">コンテンツリンク</option><option value=\"folder\">フォルダ</option></select></label>　<label>親 <select name=\"menu["+i+"][parent_id]\"><option value=\"\">（なし）</option></select></label></p><p><label>コンテンツリンク <select name=\"menu["+i+"][content]\">"+opts+"</select></label> <button type=\"button\" class=\"button-link-delete\" data-remove-menu>削除</button></p>";root.appendChild(row)});root.addEventListener("click",function(e){if(e.target.matches("[data-remove-menu]"))e.target.closest(".sa-menu-row").remove()})})();</script>';}
    public function saveMenu():void{if(!current_user_can('manage_options'))wp_die('権限がありません。');check_admin_referer('stageart_menu');$out=[];foreach((array)wp_unslash($_POST['menu']??[])as$i=>$item){if(!is_array($item))continue;$type=($item['type']??'content')==='folder'?'folder':'content';$content=(string)($item['content']??'');[$rt,$ref]=array_pad(explode(':',$content,2),2,'');if($rt==='free_content'&&!FreeContentAdmin::canReference((int)$ref,null)){$rt='system';$ref='';}if(!in_array($rt,['system','production','member','free_content','url'],true)){$rt='system';$ref='';}$out[]=['id'=>sanitize_key((string)($item['id']??wp_generate_uuid4())),'parent_id'=>sanitize_key((string)($item['parent_id']??'')),'type'=>$type,'ref_type'=>$rt,'ref_id'=>$rt==='url'?esc_url_raw($ref):sanitize_text_field($ref),'label'=>sanitize_text_field((string)($item['label']??'')),'order'=>max(1,(int)($item['order']??$i+1))];}update_option(SiteStructure::MENU_OPTION,$out,false);wp_safe_redirect(admin_url('admin.php?page=stageart-menu&saved=1'));exit;}
}
