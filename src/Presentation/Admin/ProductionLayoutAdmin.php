<?php
declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

use StageArtCore\Presentation\PublicSite\ProductionLayout;

final class ProductionLayoutAdmin
{
    public function __construct()
    {
        add_action('admin_post_stageart_save_production', [$this, 'save'], 1);
    }

    public function register(): void
    {
        add_action('admin_head', [$this, 'styles'], 30);
        add_action('admin_footer', [$this, 'footer'], 100);
    }

    private function enabled(): bool
    {
        return current_user_can('manage_options') && ($_GET['page'] ?? '') === 'stageart-productions' && !empty($_GET['id']);
    }

    public function styles(): void
    {
        if (!$this->enabled()) return;
        echo '<style>
        #stageart-production-layout{margin:28px 0;padding:20px;background:#fff;border:1px solid #ccd0d4}
        #stageart-production-layout h2{margin:0 0 8px}
        .sa-pl-block{margin:18px 0;padding:16px;border:1px solid #ccd0d4;background:#f6f7f7}
        .sa-pl-block>h3{margin:0 0 12px}
        .sa-pl-section{margin:14px 0;padding:14px;background:#fff;border:1px solid #dcdcde}
        .sa-pl-section-head{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:12px}
        .sa-pl-fields{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:10px}
        .sa-pl-field{display:flex;flex-direction:column;gap:4px;min-width:180px}
        .sa-pl-field.wide{min-width:300px}
        .sa-pl-slots{display:grid;gap:8px}
        .sa-pl-slot{display:flex;gap:10px;align-items:flex-end;padding:10px;border-top:1px solid #eee}
        .sa-pl-slot label{display:flex;flex-direction:column;gap:4px}
        .sa-pl-slot .slot-type{width:150px}.sa-pl-slot .slot-content{min-width:300px;flex:1}.sa-pl-slot .slot-indent{width:110px}
        .sa-pl-slot .slot-heading{min-width:300px;flex:1}
        .sa-pl-free-warning{display:block;color:#b32d2e;font-weight:600;font-size:12px}
        @media(max-width:900px){.sa-pl-slot{flex-wrap:wrap}.sa-pl-slot .slot-content,.sa-pl-slot .slot-heading{min-width:240px}}
        </style>';
    }

    public function footer(): void
    {
        if (!$this->enabled()) return;
        $id=(int)$_GET['id'];
        $blocks=ProductionLayout::get($id);
        $labels=ProductionLayout::contentLabels();
        $currentFreeIds=[];
        foreach($blocks as $block) foreach((array)($block['sections']??[]) as $section) foreach((array)($section['slots']??[]) as $slot)
            if(($slot['type']??'')==='free_content') $currentFreeIds[]=absint($slot['ref']??0);
        $currentFreeIds=array_values(array_unique(array_filter($currentFreeIds)));
        $choices=FreeContentAdmin::choices($id,$currentFreeIds);
        $picker='<option value="">選択してください</option>';
        foreach($labels as $key=>$label)$picker.='<option value="'.esc_attr($key).'">'.esc_html($label).'</option>';
        foreach($choices as $freeId=>$free){
            $label=(string)$free['title'];
            if(empty($free['published']))$label.='（現在非公開）';
            $picker.='<option value="free_content:'.(int)$freeId.'" data-published="'.(!empty($free['published'])?'1':'0').'">'.esc_html($label).'</option>';
        }
        $json=wp_json_encode($blocks,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $pickerJson=wp_json_encode($picker,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $nonce=wp_create_nonce('stageart_production_layout');

        echo '<div id="stageart-production-layout"><h2>公演ページ・コンテンツ配置</h2><p class="description">公演ページに表示する項目を、ブロック・セクション・スロットの順に配置します。スタッフ・出演者などの標準項目に加え、自由コンテンツも配置できます。</p><div id="sa-pl-root"></div><p><button type="button" class="button" id="sa-pl-add-block">＋ コンテンツブロックを追加</button></p></div>';
        echo '<script>(function(){
const form=document.getElementById("stageart-production-form"),mount=document.getElementById("stageart-production-layout"),root=document.getElementById("sa-pl-root");
if(!form||!mount||!root)return;
let blocks='.$json.';
const picker='.$pickerJson.';
const nonce="'.esc_js($nonce).'";
const hidden=document.createElement("input");hidden.type="hidden";hidden.name="stageart_production_layout";hidden.value="";form.appendChild(hidden);
const nonceInput=document.createElement("input");nonceInput.type="hidden";nonceInput.name="stageart_production_layout_nonce";nonceInput.value=nonce;form.appendChild(nonceInput);
function esc(v){return String(v??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}
function sync(){
 root.querySelectorAll(".sa-pl-block").forEach((be,bi)=>{
  const b=blocks[bi];if(!b)return;
  b.sections=[...be.querySelectorAll(":scope > .sa-pl-sections > .sa-pl-section")].map((se,si)=>{
   const cols=Math.max(1,Math.min(20,parseInt(se.querySelector("[data-columns]").value||"1",10)));
   const slots=[...se.querySelectorAll(".sa-pl-slot")].slice(0,cols).map(row=>{
    const type=row.querySelector("[data-type]").value;
    const indent=parseInt(row.querySelector("[data-indent]").value||"0",10);
    if(type==="heading")return{type:"heading",ref:row.querySelector("[data-heading]").value||"",indent};
    const value=row.querySelector("[data-content]").value||"";
    if(!value)return{type:"none",ref:"",indent};
    if(value.indexOf("free_content:")===0)return{type:"free_content",ref:value.substring(13),indent};
    return{type:"link",ref:value,indent};
   });
   return{section_id:se.dataset.sectionId||("new-"+Date.now()+"-"+si),heading:se.querySelector("[data-heading]").value||"",columns:cols,layout:se.querySelector("[data-layout]").value||"horizontal",slots};
  });
 });
 hidden.value=JSON.stringify(blocks);
}
function slotHtml(slot){
 const type=slot?.type||"none", heading=type==="heading", value=heading?"":(type==="free_content"?"free_content:"+String(slot?.ref||""):String(slot?.ref||""));
 return '<div class="sa-pl-slot"><label class="slot-type">項目種別<select data-type><option value="link" '+(!heading?"selected":"")+'>コンテンツ</option><option value="heading" '+(heading?"selected":"")+'>見出し</option></select></label><label class="slot-content" style="display:'+(heading?"none":"flex")+'">コンテンツ<select data-content>'+picker+'</select><span class="sa-pl-free-warning" hidden>現在非公開</span></label><label class="slot-heading" style="display:'+(heading?"flex":"none")+'">見出し<input data-heading value="'+esc(slot?.ref||"")+'"></label><label class="slot-indent">インデント<select data-indent><option value="0">なし</option><option value="1">1段</option><option value="2">2段</option><option value="3">3段</option></select></label><button type="button" class="button-link-delete sa-remove-slot">削除</button></div>';
}
function wireSlot(row,slot){
 const c=row.querySelector("[data-content]"),t=row.querySelector("[data-type]"),warn=row.querySelector(".sa-pl-free-warning"),indent=row.querySelector("[data-indent]");
 if(c)c.value=slot?.type==="free_content"?"free_content:"+String(slot?.ref||""):String(slot?.ref||"");
 if(indent)indent.value=String(slot?.indent||0);
 const refresh=()=>{const o=c?.options[c.selectedIndex];if(warn)warn.hidden=!(c&&c.value.indexOf("free_content:")===0&&o&&o.dataset.published==="0");};
 t?.addEventListener("change",()=>{const h=t.value==="heading";row.querySelector(".slot-content").style.display=h?"none":"flex";row.querySelector(".slot-heading").style.display=h?"flex":"none";});
 c?.addEventListener("change",refresh);refresh();
 row.querySelector(".sa-remove-slot").onclick=()=>{row.remove();sync();};
}
function draw(){
 root.innerHTML="";
 blocks.forEach((b,bi)=>{
  const block=document.createElement("section");block.className="sa-pl-block";
  block.innerHTML='<h3>コンテンツブロック '+(bi+1)+'</h3><div class="sa-pl-sections"></div><p><button type="button" class="button sa-add-section">＋ セクションを追加</button> <button type="button" class="button-link-delete sa-remove-block">コンテンツブロックを削除</button></p>';
  const wrap=block.querySelector(".sa-pl-sections");
  (b.sections||[]).forEach((s,si)=>{
   const sec=document.createElement("fieldset");sec.className="sa-pl-section";sec.dataset.sectionId=s.section_id||"";
   sec.innerHTML='<div class="sa-pl-section-head"><strong>セクション '+(si+1)+'</strong><span><button type="button" class="button sa-up">↑ 上へ</button> <button type="button" class="button sa-down">↓ 下へ</button> <button type="button" class="button-link-delete sa-remove-section">削除</button></span></div><div class="sa-pl-fields"><label class="sa-pl-field wide">見出し（任意）<input data-heading></label><label class="sa-pl-field">表示数<select data-columns>'+Array.from({length:20},(_,i)=>'<option value="'+(i+1)+'">'+(i+1)+'件</option>').join("")+'</select></label><label class="sa-pl-field">表示方向<select data-layout><option value="horizontal">横並び</option><option value="vertical">縦並び</option></select></label></div><div class="sa-pl-slots"></div><p><button type="button" class="button sa-add-slot">＋ 項目を追加</button></p>';
   sec.querySelector("[data-heading]").value=s.heading||"";sec.querySelector("[data-columns]").value=String(s.columns||1);sec.querySelector("[data-layout]").value=s.layout||"horizontal";
   const slots=sec.querySelector(".sa-pl-slots"),count=Math.max(1,Math.min(20,parseInt(s.columns||1,10)));
   for(let i=0;i<count;i++){const row=document.createElement("div");row.innerHTML=slotHtml((s.slots||[])[i]||{type:"none",ref:"",indent:0});const el=row.firstElementChild;slots.appendChild(el);wireSlot(el,(s.slots||[])[i]||{});}
   sec.querySelector("[data-columns]").onchange=()=>{let n=parseInt(sec.querySelector("[data-columns]").value||"1",10),rows=slots.querySelectorAll(".sa-pl-slot");while(rows.length<n){const holder=document.createElement("div");holder.innerHTML=slotHtml({type:"none",ref:"",indent:0});const el=holder.firstElementChild;slots.appendChild(el);wireSlot(el,{});rows=slots.querySelectorAll(".sa-pl-slot");}Array.from(rows).forEach((row,i)=>row.hidden=i>=n);sync();};
   sec.querySelector("[data-heading]").oninput=sync;sec.querySelector("[data-layout]").onchange=sync;
   sec.querySelector(".sa-add-slot").onclick=()=>{const n=slots.querySelectorAll(".sa-pl-slot").length+1;if(n>20)return;const holder=document.createElement("div");holder.innerHTML=slotHtml({type:"none",ref:"",indent:0});const el=holder.firstElementChild;slots.appendChild(el);wireSlot(el,{});sec.querySelector("[data-columns]").value=String(n);sync();};
   sec.querySelector(".sa-up").onclick=()=>{if(si>0){const x=b.sections.splice(si,1)[0];b.sections.splice(si-1,0,x);draw();sync();}};
   sec.querySelector(".sa-down").onclick=()=>{if(si<b.sections.length-1){const x=b.sections.splice(si,1)[0];b.sections.splice(si+1,0,x);draw();sync();}};
   sec.querySelector(".sa-remove-section").onclick=()=>{b.sections.splice(si,1);draw();sync();};
   wrap.appendChild(sec);
  });
  block.querySelector(".sa-add-section").onclick=()=>{b.sections=b.sections||[];b.sections.push({section_id:"new-"+Date.now(),heading:"",columns:1,layout:"horizontal",slots:[{type:"none",ref:"",indent:0}]});draw();sync();};
  block.querySelector(".sa-remove-block").onclick=()=>{blocks.splice(bi,1);draw();sync();};
  root.appendChild(block);
 });
}
document.getElementById("sa-pl-add-block").onclick=()=>{blocks.push({block_id:"new-"+Date.now(),sections:[{section_id:"new-"+Date.now()+"-s",heading:"",columns:1,layout:"horizontal",slots:[{type:"none",ref:"",indent:0}]}]});draw();sync();};
form.addEventListener("submit",sync);
draw();sync();
})();</script>';
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) return;
        $nonce=sanitize_text_field(wp_unslash($_POST['stageart_production_layout_nonce']??''));
        if (!$nonce || !wp_verify_nonce($nonce,'stageart_production_layout')) return;
        $id=(int)($_POST['id']??0);
        if (!$id || get_post_type($id)!=='stageart_production') return;
        $raw=json_decode(wp_unslash((string)($_POST['stageart_production_layout']??'')),true);
        if (is_array($raw)) ProductionLayout::save($id,$raw);
    }
}
