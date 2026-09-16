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
        if (($_GET['page'] ?? '') !== 'stageart-productions' || !current_user_can('manage_options')) {
            return;
        }
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            return;
        }

        $sections = ProductionLayout::get($id);
        $labels = ProductionLayout::contentLabels();
        $nonce = wp_create_nonce('stageart_production_layout');
        $picker = '<option value="">選択してください</option>';
        foreach ($labels as $key => $value) {
            $picker .= '<option value="' . esc_attr($key) . '">' . esc_html($value) . '</option>';
        }

        $json = wp_json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pickerJson = wp_json_encode($picker, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $nonceJs = esc_js($nonce);

        echo '<style>
        .stageart-layout-box{margin:30px 0;padding:18px;background:#fff;border:1px solid #ccd0d4}
        .stageart-layout-box h2{margin-top:0}
        .stageart-layout-box .description{margin:6px 0 14px}
        .sa-pl-section{margin:18px 0;padding:16px;border:1px solid #dcdcde;background:#f6f7f7}
        .sa-pl-section legend{font-weight:600}
        .sa-pl-top{display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap}
        .sa-pl-slot{margin:12px 0;padding:10px 0;border-top:1px solid #ddd}
        .sa-pl-slot-label{font-weight:600;margin-bottom:7px}
        .sa-pl-controls{display:flex;gap:12px;align-items:flex-end;flex-wrap:nowrap;overflow-x:auto;padding-bottom:2px}
        .sa-pl-controls>label{display:flex;flex-direction:column;align-items:flex-start;gap:4px;white-space:nowrap}
        .sa-pl-controls>label:first-child{width:150px}
        .sa-pl-controls .sa-pl-content-wrap,.sa-pl-controls .sa-pl-heading-wrap{width:300px}
        .sa-pl-controls .sa-pl-content,.sa-pl-controls .sa-pl-heading{width:100%;max-width:none}
        .sa-pl-controls .sa-pl-indent{width:100px}
        .sa-pl-actions button{margin-right:6px}
        .sa-credit-release{display:inline-flex;align-items:center;gap:8px;margin:8px 0}
        .sa-credit-release label{white-space:nowrap}
        .sa-credit-release input[type=datetime-local]{min-width:220px}
        @media(max-width:900px){
            .sa-pl-controls{flex-wrap:wrap;overflow-x:visible}
            .sa-pl-controls .sa-pl-content-wrap,.sa-pl-controls .sa-pl-heading-wrap{width:260px}
        }
        </style>';

        echo '<script>(function(){
            const f=document.getElementById("stageart-production-form");
            if(!f)return;
            let sections=' . $json . ',picker=' . $pickerJson . ';
            const box=document.createElement("div");
            box.className="stageart-layout-box";
            box.innerHTML="<h2>公演ページ・コンテンツ配置</h2><p class=\"description\">トップページと同じ考え方で、公演ページのコンテンツをセクション・スロット単位で配置します。表示数、横並び／縦並び、インデント、セクション順を設定できます。</p>";
            const wrap=document.createElement("div");
            box.appendChild(wrap);
            const hidden=document.createElement("input");
            hidden.type="hidden";
            hidden.name="stageart_production_layout";
            box.appendChild(hidden);
            const n=document.createElement("input");
            n.type="hidden";
            n.name="stageart_production_layout_nonce";
            n.value="' . $nonceJs . '";
            box.appendChild(n);

            function sync(){hidden.value=JSON.stringify(sections)}

            function syncFromDom(){
                wrap.querySelectorAll(".sa-pl-section").forEach(function(sec,si){
                    const s=sections[si];
                    if(!s)return;
                    s.heading=sec.querySelector("[data-heading]")?.value||"";
                    s.columns=+(sec.querySelector("[data-columns]")?.value||1);
                    s.layout=sec.querySelector("[data-layout]")?.value||"vertical";
                    s.slots=[];
                    sec.querySelectorAll(".sa-pl-slot").forEach(function(row){
                        const type=row.querySelector("[data-type]")?.value||"link";
                        const content=row.querySelector(".sa-pl-content")?.value||"";
                        const heading=row.querySelector(".sa-pl-heading")?.value||"";
                        const indent=+(row.querySelector(".sa-pl-indent")?.value||0);
                        s.slots.push({type:type==="heading"?"heading":"link",ref:type==="heading"?heading:content,indent:indent});
                    });
                    s.slots=s.slots.slice(0,s.columns);
                });
                sync();
            }

            function wire(sec){
                const cols=sec.querySelector("[data-columns]");
                const update=function(){const count=+cols.value||1;sec.querySelectorAll(".sa-pl-slot").forEach((r,i)=>r.hidden=i>=count)};
                cols.addEventListener("change",update);update();
                sec.querySelectorAll(".sa-pl-slot").forEach(function(row){
                    const t=row.querySelector("[data-type]"),c=row.querySelector(".sa-pl-content"),h=row.querySelector(".sa-pl-heading"),hw=row.querySelector(".sa-pl-heading-wrap");
                    const toggle=function(){const heading=t.value==="heading";c.style.display=heading?"none":"inline-block";hw.style.display=heading?"flex":"none"};
                    t.addEventListener("change",toggle);toggle();
                });
            }

            function draw(){
                wrap.innerHTML="";
                sections.forEach(function(s,si){
                    const sec=document.createElement("fieldset");
                    sec.className="sa-pl-section";
                    sec.innerHTML="<legend>セクション <span class=\"sa-pl-section-number\">"+(si+1)+"</span></legend><div class=\"sa-pl-top\"><label>見出し（任意）<br><input class=\"regular-text\" data-heading></label><label>表示数<br><select data-columns>"+Array.from({length:20},(_,i)=>"<option value=\""+(i+1)+"\">"+(i+1)+"件</option>").join("")+"</select></label><label>表示方向<br><select data-layout><option value=\"horizontal\">横並び</option><option value=\"vertical\">縦並び</option></select></label></div><p class=\"description\">表示数を変更して保存すると、増えた項目は未設定、減った項目は削除されます。</p><div class=\"sa-pl-slots\"></div><p class=\"sa-pl-actions\"><button type=\"button\" class=\"button\" data-up>↑ 上へ</button><button type=\"button\" class=\"button\" data-down>↓ 下へ</button><button type=\"button\" class=\"button-link-delete\" data-remove>削除</button></p>";
                    sec.querySelector("[data-heading]").value=s.heading||"";
                    sec.querySelector("[data-columns]").value=String(s.columns||1);
                    sec.querySelector("[data-layout]").value=s.layout||"vertical";
                    const slots=sec.querySelector(".sa-pl-slots");
                    for(let i=0;i<20;i++){
                        const sl=(s.slots||[])[i]||{type:"none",ref:"",indent:0};
                        const r=document.createElement("div");
                        r.className="sa-pl-slot";
                        r.hidden=i>=+(s.columns||1);
                        const heading=sl.type==="heading";
                        r.innerHTML="<div class=\"sa-pl-slot-label\">"+(i+1)+"件目</div><div class=\"sa-pl-controls\"><label>項目種別<select data-type><option value=\"link\""+(sl.type!=="heading"?" selected":"")+">コンテンツリンク</option><option value=\"heading\""+(heading?" selected":"")+">見出し</option></select></label><label class=\"sa-pl-content-wrap\">コンテンツ<select class=\"sa-pl-content\">"+picker+"</select></label><label class=\"sa-pl-heading-wrap\">見出し<input class=\"sa-pl-heading\" value=\"\" placeholder=\"見出し\"></label><label>インデント<select class=\"sa-pl-indent\"><option value=\"0\">インデントなし</option><option value=\"1\">1段</option><option value=\"2\">2段</option><option value=\"3\">3段</option></select></label></div>";
                        const c=r.querySelector(".sa-pl-content"),h=r.querySelector(".sa-pl-heading"),hw=r.querySelector(".sa-pl-heading-wrap");
                        if(heading){h.value=String(sl.ref||"")}else{c.value=sl.ref||""}
                        r.querySelector(".sa-pl-indent").value=String(sl.indent||0);
                        if(!heading)hw.style.display="none";
                        slots.appendChild(r);
                    }
                    sec.querySelector("[data-heading]").oninput=e=>{s.heading=e.target.value;sync()};
                    sec.querySelector("[data-columns]").onchange=e=>{s.columns=+e.target.value;sync();wire(sec)};
                    sec.querySelector("[data-layout]").onchange=e=>{s.layout=e.target.value;sync()};
                    sec.querySelector("[data-up]").onclick=function(){if(si>0){const x=sections.splice(si,1)[0];sections.splice(si-1,0,x);draw();sync()}};
                    sec.querySelector("[data-down]").onclick=function(){if(si<sections.length-1){const x=sections.splice(si,1)[0];sections.splice(si+1,0,x);draw();sync()}};
                    sec.querySelector("[data-remove]").onclick=function(){sections.splice(si,1);draw();sync()};
                    wrap.appendChild(sec);
                    wire(sec);
                });
            }

            const add=document.createElement("p");
            add.innerHTML="<button type=\"button\" class=\"button\" data-add>＋ セクションを追加</button>";
            box.appendChild(add);
            add.querySelector("button").onclick=function(){sections.push({section_id:"new-"+Date.now(),heading:"",columns:1,layout:"vertical",slots:[{type:"none",ref:"",indent:0}]});draw();sync()};
            draw();
            sync();
            f.addEventListener("submit",function(){syncFromDom()});

            const credits=f.querySelector("#sa-credits");
            if(credits){credits.insertAdjacentElement("beforebegin",box)}else{const submit=f.querySelector("button[type=submit]");if(submit)f.insertBefore(box,submit.parentNode);else f.appendChild(box)}
        })();</script>';
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['stageart_production_layout_nonce'] ?? ''));
        if (!$nonce || !wp_verify_nonce($nonce, 'stageart_production_layout')) {
            return;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || get_post_type($id) !== 'stageart_production') {
            return;
        }
        $raw = json_decode(wp_unslash((string) ($_POST['stageart_production_layout'] ?? '')), true);
        if (is_array($raw)) {
            ProductionLayout::save($id, $raw);
        }
    }
}
