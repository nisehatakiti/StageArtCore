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
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_footer', [$this, 'footer']);
    }

    public function assets(string $hook): void
    {
        if (str_contains($hook, 'stageart-productions')) {
            wp_enqueue_media();
        }
    }

    public function footer(): void
    {
        if (($_GET['page'] ?? '') !== 'stageart-productions' || !current_user_can('manage_options')) return;
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) return;

        $blocks = ProductionLayout::get($id);
        $labels = ProductionLayout::contentLabels();
        $nonce = wp_create_nonce('stageart_production_layout');

        $currentFreeIds = [];
        foreach ($blocks as $block) foreach ((array) ($block['sections'] ?? []) as $section)
            foreach ((array) ($section['slots'] ?? []) as $slot)
                if (($slot['type'] ?? '') === 'free_content') $currentFreeIds[] = absint($slot['ref'] ?? 0);
        $currentFreeIds = array_values(array_unique(array_filter($currentFreeIds)));

        $picker = '<option value="">選択してください</option>';
        foreach ($labels as $key => $value) {
            $picker .= '<option value="' . esc_attr($key) . '">' . esc_html($value) . '</option>';
        }
        foreach (FreeContentAdmin::choices($id, $currentFreeIds) as $freeId => $free) {
            $label = (string) $free['title'];
            if (empty($free['published'])) $label .= '（現在非公開）';
            $picker .= '<option value="free_content:' . (int) $freeId . '" data-stageart-published="' . (!empty($free['published']) ? '1' : '0') . '">' . esc_html($label) . '</option>';
        }

        $json = wp_json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pickerJson = wp_json_encode($picker, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $nonceJs = esc_js($nonce);

        echo '<style>
        .stageart-layout-box{margin:30px 0;padding:18px;background:#fff;border:1px solid #ccd0d4}
        .stageart-layout-box h2{margin-top:0}.stageart-layout-box .description{margin:6px 0 14px}
        .sa-pl-block{margin:18px 0;padding:18px;border:1px solid #ccd0d4;background:#f6f7f7}
        .sa-pl-block>h3{margin:0 0 14px;font-size:17px}.sa-pl-section{margin:14px 0;padding:16px;background:#fff;border:1px solid #dcdcde}
        .sa-pl-section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
        .sa-pl-fields{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start}.sa-pl-field{display:flex;flex-direction:column;gap:5px;min-width:180px}
        .sa-pl-field-wide{min-width:280px}.sa-pl-field select,.sa-pl-field input{box-sizing:border-box;margin:0}.sa-pl-field-wide select{width:100%}
        .sa-pl-slot{margin:14px 0;padding:10px 0;border-top:1px solid #eee}.sa-pl-slot-controls{display:flex;gap:12px;align-items:flex-end;flex-wrap:nowrap;overflow-x:auto}
        .sa-pl-slot-controls>label{display:flex;flex-direction:column;gap:4px;white-space:nowrap}.sa-pl-slot-controls>label:first-child{width:150px}
        .sa-pl-slot-controls .sa-pl-content-wrap,.sa-pl-slot-controls .sa-pl-heading-wrap{width:300px}
        .sa-pl-slot-controls .sa-pl-content,.sa-pl-slot-controls .sa-pl-heading{width:100%;max-width:none}.sa-pl-slot-controls .sa-pl-indent{width:100px}
        .sa-pl-free-warning{display:block;color:#b32d2e;font-weight:600;font-size:12px;margin-top:4px}
        .sa-pl-actions{margin-top:12px}.sa-pl-actions button{margin-right:6px}
        @media(max-width:900px){.sa-pl-slot-controls{flex-wrap:wrap;overflow-x:visible}.sa-pl-slot-controls .sa-pl-content-wrap,.sa-pl-slot-controls .sa-pl-heading-wrap{width:260px}}
        </style>';

        echo '<script>(function(){
const f=document.getElementById("stageart-production-form");if(!f)return;
let blocks=' . $json . ',picker=' . $pickerJson . ';
const box=document.createElement("div");box.className="stageart-layout-box";
box.innerHTML="<h2>公演ページ・コンテンツ配置</h2><p class=\"description\">トップページと同じく、コンテンツブロックの中に複数のセクションを配置して公演ページを構成します。ブロック、セクション、スロットの順序と表示数・表示方向・インデントを設定できます。</p>";
const root=document.createElement("div");box.appendChild(root);
const hidden=document.createElement("input");hidden.type="hidden";hidden.name="stageart_production_layout";box.appendChild(hidden);
const n=document.createElement("input");n.type="hidden";n.name="stageart_production_layout_nonce";n.value="' . $nonceJs . '";box.appendChild(n);
function sync(){hidden.value=JSON.stringify(blocks)}
function slotHtml(bi,si,i,slot){
 const type=slot.type||"none",heading=type==="heading",value=heading?"":(type==="free_content"?"free_content:"+String(slot.ref||""):String(slot.ref||""));
 return "<div class=\"sa-pl-slot\" data-slot><strong>"+(i+1)+"件目</strong><div class=\"sa-pl-slot-controls\"><label>項目種別<select data-type><option value=\"link\""+(!heading?" selected":"")+">コンテンツリンク</option><option value=\"heading\""+(heading?" selected":"")+">見出し</option></select></label><label class=\"sa-pl-content-wrap\" style=\"display:"+(heading?"none":"flex")+"\">コンテンツ<select class=\"sa-pl-content\">"+picker+"</select><span class=\"sa-pl-free-warning\" hidden>現在非公開</span></label><label class=\"sa-pl-heading-wrap\" style=\"display:"+(heading?"flex":"none")+"\">見出し<input class=\"sa-pl-heading\" value=\""+String(slot.ref||"").replace(/&/g,"&amp;").replace(/"/g,"&quot;")+"\"></label><label>インデント<select class=\"sa-pl-indent\"><option value=\"0\">なし</option><option value=\"1\">1段</option><option value=\"2\">2段</option><option value=\"3\">3段</option></select></label></div></div>";
}
function wireSection(sec,si){
 const cols=sec.querySelector("[data-columns]");const update=()=>sec.querySelectorAll("[data-slot]").forEach((r,i)=>r.hidden=i>=parseInt(cols.value||1,10));cols.addEventListener("change",update);update();
 sec.querySelectorAll("[data-type]").forEach(t=>t.addEventListener("change",function(){const r=this.closest("[data-slot]"),h=this.value==="heading";r.querySelector(".sa-pl-content-wrap").style.display=h?"none":"flex";r.querySelector(".sa-pl-heading-wrap").style.display=h?"flex":"none"}));
 sec.querySelectorAll("[data-slot]").forEach(r=>{const c=r.querySelector(".sa-pl-content"),warn=r.querySelector(".sa-pl-free-warning");if(!c||!warn)return;const refresh=()=>{const o=c.options[c.selectedIndex];warn.hidden=!(c.value.indexOf("free_content:")===0&&o&&o.dataset.stageartPublished==="0")};c.addEventListener("change",refresh);refresh()});
}
function draw(){
 root.innerHTML="";
 blocks.forEach((b,bi)=>{
  const block=document.createElement("section");block.className="sa-pl-block";
  block.innerHTML="<h3>コンテンツブロック <span class=\"sa-block-number\">"+(bi+1)+"</span></h3><div class=\"sa-pl-sections\"></div><p><button type=\"button\" class=\"button sa-add-section\">＋ セクションを追加</button> <button type=\"button\" class=\"button-link-delete sa-remove-block\">コンテンツブロックを削除</button></p>";
  const sw=block.querySelector(".sa-pl-sections");
  (b.sections||[]).forEach((s,si)=>drawSection(sw,b,bi,si,s));
  block.querySelector(".sa-add-section").onclick=()=>{b.sections=b.sections||[];b.sections.push({section_id:"new-"+Date.now(),heading:"",columns:3,layout:"horizontal",slots:[{type:"none",ref:"",indent:0}]});draw();sync()};
  block.querySelector(".sa-remove-block").onclick=()=>{blocks.splice(bi,1);draw();sync()};
  root.appendChild(block);
 });
}
function drawSection(sw,b,bi,si,s){
 const sec=document.createElement("fieldset");sec.className="sa-pl-section";
 const cols=Math.max(1,Math.min(20,parseInt(s.columns||1,10)));
 sec.innerHTML="<div class=\"sa-pl-section-header\"><strong>セクション <span class=\"sa-section-number\">"+(si+1)+"</span></strong><span><button type=\"button\" class=\"button sa-up\">↑ 上へ</button> <button type=\"button\" class=\"button sa-down\">↓ 下へ</button> <button type=\"button\" class=\"button-link-delete sa-remove-section\">削除</button></span></div><div class=\"sa-pl-fields\"><label class=\"sa-pl-field sa-pl-field-wide\">見出し（任意）<input data-heading></label><label class=\"sa-pl-field\">表示数<select data-columns>"+Array.from({length:20},(_,i)=>"<option value=\""+(i+1)+"\">"+(i+1)+"件</option>").join("")+"</select></label><label class=\"sa-pl-field\">表示方向<select data-layout><option value=\"horizontal\">横並び</option><option value=\"vertical\">縦並び</option></select></label></div><p class=\"description\">表示数を変更して保存すると、増えた項目は未設定、減った項目は削除されます。</p><div class=\"sa-pl-slots\"></div>";
 sec.querySelector("[data-heading]").value=s.heading||"";sec.querySelector("[data-columns]").value=String(cols);sec.querySelector("[data-layout]").value=s.layout||"horizontal";
 const slots=sec.querySelector(".sa-pl-slots");for(let i=0;i<20;i++){const sl=(s.slots||[])[i]||{type:"none",ref:"",indent:0};const temp=document.createElement("div");temp.innerHTML=slotHtml(bi,si,i,sl);const row=temp.firstElementChild;row.hidden=i>=cols;const c=row.querySelector(".sa-pl-content");if(c)c.value=sl.type==="free_content"?"free_content:"+String(sl.ref||""):String(sl.ref||"");row.querySelector(".sa-pl-indent").value=String(sl.indent||0);slots.appendChild(row);}
 sec.querySelector("[data-heading]").oninput=e=>{s.heading=e.target.value;sync()};sec.querySelector("[data-columns]").onchange=e=>{s.columns=+e.target.value;sync();wireSection(sec,si)};sec.querySelector("[data-layout]").onchange=e=>{s.layout=e.target.value;sync()};
 sec.querySelector(".sa-up").onclick=()=>{if(si>0){const x=b.sections.splice(si,1)[0];b.sections.splice(si-1,0,x);draw();sync()}};sec.querySelector(".sa-down").onclick=()=>{if(si<b.sections.length-1){const x=b.sections.splice(si,1)[0];b.sections.splice(si+1,0,x);draw();sync()}};
 sec.querySelector(".sa-remove-section").onclick=()=>{b.sections.splice(si,1);draw();sync()};sw.appendChild(sec);wireSection(sec,si);
}
root.insertAdjacentHTML("afterend","<p><button type=\"button\" class=\"button\" id=\"sa-add-block\">＋ コンテンツブロックを追加</button></p>");
document.getElementById("sa-add-block").onclick=()=>{blocks.push({block_id:"new-"+Date.now(),sections:[{section_id:"new-"+Date.now()+"-s",heading:"",columns:3,layout:"horizontal",slots:[{type:"none",ref:"",indent:0}]}]});draw();sync()};
draw();sync();
f.addEventListener("submit",function(){syncFromDom()});
function syncFromDom(){
 root.querySelectorAll(".sa-pl-block").forEach((be,bi)=>{const b=blocks[bi];if(!b)return;b.sections.forEach((s,si)=>{const se=be.querySelectorAll(".sa-pl-section")[si];if(!se)return;s.heading=se.querySelector("[data-heading]").value||"";s.columns=parseInt(se.querySelector("[data-columns]").value||1,10);s.layout=se.querySelector("[data-layout]").value||"horizontal";s.slots=[];se.querySelectorAll("[data-slot]").forEach(row=>{const type=row.querySelector("[data-type]").value;const c=row.querySelector(".sa-pl-content"),h=row.querySelector(".sa-pl-heading"),indent=parseInt(row.querySelector(".sa-pl-indent").value||0,10);if(type==="heading")s.slots.push({type:"heading",ref:h.value||"",indent});else if(c.value.indexOf("free_content:")===0)s.slots.push({type:"free_content",ref:c.value.substring(13),indent});else if(c.value)s.slots.push({type:"link",ref:c.value,indent});});s.slots=s.slots.slice(0,s.columns)})});sync()}
const credits=f.querySelector("#sa-credits");if(credits){const h=credits.previousElementSibling;if(h&&h.tagName==="H2"&&h.textContent.indexOf("公演クレジット")>=0)h.parentNode.insertBefore(box,h);else credits.parentNode.insertBefore(box,credits)}else f.appendChild(box);
})();</script>';
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) return;
        $nonce = sanitize_text_field(wp_unslash($_POST['stageart_production_layout_nonce'] ?? ''));
        if (!$nonce || !wp_verify_nonce($nonce, 'stageart_production_layout')) return;
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || get_post_type($id) !== 'stageart_production') return;
        $raw = json_decode(wp_unslash((string) ($_POST['stageart_production_layout'] ?? '')), true);
        if (is_array($raw)) ProductionLayout::save($id, $raw);
    }
}
