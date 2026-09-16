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

        $sections = ProductionLayout::get($id);
        $labels = ProductionLayout::contentLabels();
        $performanceView = get_post_meta($id, 'performance_view', true);
        if (!in_array($performanceView, ['table', 'list'], true)) $performanceView = 'table';
        $nonce = wp_create_nonce('stageart_production_layout');
        $picker = '<option value="">選択してください</option>';
        foreach ($labels as $key => $value) $picker .= '<option value="' . esc_attr($key) . '">' . esc_html($value) . '</option>';

        $json = wp_json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $labelsJson = wp_json_encode($labels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pickerJson = wp_json_encode($picker, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $nonceJs = esc_js($nonce);
        $viewJs = esc_js($performanceView);

        echo '<style>
        .stageart-layout-box{margin:30px 0;padding:18px;background:#fff;border:1px solid #ccd0d4}
        .stageart-layout-box h2{margin-top:0}
        .sa-pl-section{margin:18px 0;padding:16px;border:1px solid #dcdcde;background:#f6f7f7}
        .sa-pl-section legend{font-weight:600}
        .sa-pl-top{display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap}
        .sa-pl-slot{margin:12px 0;padding-top:10px;border-top:1px solid #ddd}
        .sa-pl-slot-label{font-weight:600;margin-bottom:7px}
        .sa-pl-controls{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .sa-pl-controls select{min-width:190px}
        .sa-pl-controls .sa-pl-content,.sa-pl-controls .sa-pl-heading{min-width:280px}
        .sa-pl-actions button{margin-right:6px}
        </style>';

        echo '<script>(function(){
            const f=document.getElementById("stageart-production-form");
            if(!f)return;
            let sections=' . $json . ',picker=' . $pickerJson . ';
            const box=document.createElement("div");box.className="stageart-layout-box";
            box.innerHTML="<h2>公演ページ・コンテンツ配置</h2><p>この公演ページだけの配置設定です。トップページ設定・メニュー構成とは独立しています。</p>";
            const viewLabel=document.createElement("label");viewLabel.innerHTML="公演回の表示形式<br><select id=\"sa-performance-view\"><option value=\"table\">表形式</option><option value=\"list\">一覧形式（コンパクト）</option></select></label>";
            const viewSelect=viewLabel.querySelector("select");viewSelect.value="' . $viewJs . '";
            box.appendChild(viewLabel);
            const viewHidden=document.createElement("input");viewHidden.type="hidden";viewHidden.name="stageart_performance_view";viewHidden.value=viewSelect.value;box.appendChild(viewHidden);
            viewSelect.onchange=function(){viewHidden.value=this.value};
            const wrap=document.createElement("div");box.appendChild(wrap);
            const hidden=document.createElement("input");hidden.type="hidden";hidden.name="stageart_production_layout";box.appendChild(hidden);
            const n=document.createElement("input");n.type="hidden";n.name="stageart_production_layout_nonce";n.value="' . $nonceJs . '";box.appendChild(n);
            function sync(){hidden.value=JSON.stringify(sections)}
            function wire(sec){
                const cols=sec.querySelector("[data-columns]");
                const update=function(){const count=+cols.value||1;sec.querySelectorAll(".sa-pl-slot").forEach((r,i)=>r.hidden=i>=count)};
                cols.addEventListener("change",update);update();
                sec.querySelectorAll(".sa-pl-slot").forEach(function(row){const t=row.querySelector("[data-type]"),c=row.querySelector(".sa-pl-content"),h=row.querySelector(".sa-pl-heading");const toggle=function(){const heading=t.value==="heading";c.style.display=heading?"none":"inline-block";h.style.display=heading?"inline-block":"none"};t.addEventListener("change",toggle);toggle()})
            }
            function draw(){
                wrap.innerHTML="";
                sections.forEach(function(s,si){
                    const sec=document.createElement("fieldset");sec.className="sa-pl-section";
                    sec.innerHTML="<legend>セクション "+(si+1)+"</legend><div class=\"sa-pl-top\"><label>見出し（任意）<br><input class=\"regular-text\" data-heading></label><label>表示数<br><select data-columns>"+Array.from({length:20},(_,i)=>"<option value=\""+(i+1)+"\">"+(i+1)+"件</option>").join("")+"</select></label><label>表示方向<br><select data-layout><option value=\"horizontal\">横並び</option><option value=\"vertical\">縦並び</option></select></label></div><p class=\"description\">表示数を変更して保存すると、増えた項目は未設定、減った項目は削除されます。</p><div class=\"sa-pl-slots\"></div><p class=\"sa-pl-actions\"><button type=\"button\" class=\"button\" data-up>↑ 上へ</button><button type=\"button\" class=\"button\" data-down>↓ 下へ</button><button type=\"button\" class=\"button-link-delete\" data-remove>削除</button></p>";
                    sec.querySelector("[data-heading]").value=s.heading||"";sec.querySelector("[data-columns]").value=String(s.columns||1);sec.querySelector("[data-layout]").value=s.layout||"vertical";
                    const slots=sec.querySelector(".sa-pl-slots");
                    for(let i=0;i<20;i++){
                        const sl=(s.slots||[])[i]||{type:"none",ref:"",indent:0};const r=document.createElement("div");r.className="sa-pl-slot";r.hidden=i>=+(s.columns||1);const heading=sl.type==="heading";
                        r.innerHTML="<div class=\"sa-pl-slot-label\">"+(i+1)+"件目</div><div class=\"sa-pl-controls\"><select data-type><option value=\"link\""+(sl.type!=="heading"?" selected":"")+">コンテンツリンク</option><option value=\"heading\""+(heading?" selected":"")+">見出し</option></select><select class=\"sa-pl-content\">"+picker+"</select><input class=\"sa-pl-heading\" value=\"\" placeholder=\"見出し\" style=\"display:none\"><select class=\"sa-pl-indent\"><option value=\"0\">インデントなし</option><option value=\"1\">1段</option><option value=\"2\">2段</option><option value=\"3\">3段</option></select></div>";
                        const c=r.querySelector(".sa-pl-content"),h=r.querySelector(".sa-pl-heading");if(heading)h.value=String(sl.ref||"");else c.value=sl.ref||"";r.querySelector(".sa-pl-indent").value=String(sl.indent||0);
                        r.querySelector("[data-type]").onchange=function(){sl.type=this.value;if(this.value!=="heading")sl.ref=c.value||"";sync()};c.onchange=function(){sl.type="link";sl.ref=this.value;sync()};h.oninput=function(){sl.type="heading";sl.ref=this.value;sync()};r.querySelector(".sa-pl-indent").onchange=function(){sl.indent=+this.value;sync()};slots.appendChild(r)
                    }
                    sec.querySelector("[data-heading]").oninput=e=>{s.heading=e.target.value;sync()};sec.querySelector("[data-columns]").onchange=e=>{s.columns=+e.target.value;sync();wire(sec)};sec.querySelector("[data-layout]").onchange=e=>{s.layout=e.target.value;sync()};
                    sec.querySelector("[data-up]").onclick=function(){if(si>0){const x=sections.splice(si,1)[0];sections.splice(si-1,0,x);draw();sync()}};sec.querySelector("[data-down]").onclick=function(){if(si<sections.length-1){const x=sections.splice(si,1)[0];sections.splice(si+1,0,x);draw();sync()}};sec.querySelector("[data-remove]").onclick=function(){sections.splice(si,1);draw();sync()};wrap.appendChild(sec);wire(sec)
                })
            }
            const add=document.createElement("p");add.innerHTML="<button type=\"button\" class=\"button\" data-add>＋ セクションを追加</button>";box.appendChild(add);
            add.querySelector("button").onclick=function(){sections.push({section_id:"new-"+Date.now(),heading:"",columns:1,layout:"vertical",slots:[{type:"none",ref:"",indent:0}]});draw();sync()};draw();sync();
            const submit=f.querySelector("button[type=submit]");if(submit)f.insertBefore(box,submit.parentNode);
            if(window.wp&&typeof wp.media==="function"){
                const mediaButton=document.getElementById("sa-media-picker"),mediaClear=document.getElementById("sa-media-clear"),mediaId=document.getElementById("sa-main-image-id"),mediaPreview=document.getElementById("sa-media-preview");
                if(mediaButton&&!mediaButton.dataset.stageartBound){mediaButton.dataset.stageartBound="1";mediaButton.addEventListener("click",function(e){e.preventDefault();const frame=wp.media({title:"メイン画像を選択",button:{text:"この画像を使用"},multiple:false});frame.on("select",function(){const a=frame.state().get("selection").first().toJSON();mediaId.value=a.id;mediaPreview.innerHTML=a.url?"<img src=\""+a.url+"\" style=\"max-width:320px;height:auto\">":""});frame.open()})}
                if(mediaClear&&!mediaClear.dataset.stageartBound){mediaClear.dataset.stageartBound="1";mediaClear.addEventListener("click",function(e){e.preventDefault();mediaId.value="";mediaPreview.innerHTML=""})}
            }
        })();</script>';
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) wp_die('権限がありません。');
        $nonce = sanitize_text_field(wp_unslash($_POST['stageart_production_layout_nonce'] ?? ''));
        if (!$nonce || !wp_verify_nonce($nonce, 'stageart_production_layout')) return;
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || get_post_type($id) !== 'stageart_production') return;
        $raw = json_decode(wp_unslash((string) ($_POST['stageart_production_layout'] ?? '')), true);
        if (is_array($raw)) ProductionLayout::save($id, $raw);
        $view = sanitize_key((string) ($_POST['stageart_performance_view'] ?? 'table'));
        update_post_meta($id, 'performance_view', in_array($view, ['table', 'list'], true) ? $view : 'table');
    }
}
