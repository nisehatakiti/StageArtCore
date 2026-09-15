<?php

declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;
use StageArtCore\Presentation\PublicSite\ProductionLayout;

final class ProductionLayoutAdmin
{
    public function register(): void
    {
        add_action('admin_footer', [$this, 'footer']);
        add_action('save_post_stageart_production', [$this, 'save'], 20, 3);
    }

    public function footer(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'stageart-productions') return;
        if (!current_user_can('manage_options')) return;
        $id = (int)($_GET['id'] ?? 0);
        $rows = ProductionLayout::get($id);
        $json = wp_json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $nonce = wp_create_nonce('stageart_production_layout');
        echo '<style>.stageart-layout-box{margin:20px 0;padding:16px;background:#fff;border:1px solid #ccd0d4}.stageart-layout-row{display:flex;align-items:center;gap:10px;padding:8px;margin:4px 0;background:#f6f7f7;border:1px solid #dcdcde;cursor:move}.stageart-layout-row .dashicons{cursor:grab}.stageart-layout-row label{margin-left:auto}.stageart-layout-row select{min-width:90px}</style>';
        echo '<script>(function(){const form=document.getElementById("stageart-production-form");if(!form)return;const rows='.($json?:'[]').';const nonce='.wp_json_encode($nonce).';const box=document.createElement("div");box.className="stageart-layout-box";box.innerHTML="<h2>トップ画面・コンテンツ配置</h2><p>この公演ページだけの表示順・表示/非表示・列数を設定できます。公演データそのものは変更されません。</p><div id=\"stageart-production-layout\"></div><input type=\"hidden\" name=\"stageart_production_layout\" id=\"stageart-production-layout-json\"><input type=\"hidden\" name=\"stageart_production_layout_nonce\" value=\""+nonce+"\">";const wrap=box.querySelector("#stageart-production-layout");function draw(){wrap.innerHTML="";rows.forEach((r,i)=>{const el=document.createElement("div");el.className="stageart-layout-row";el.draggable=true;el.dataset.i=i;el.innerHTML="<span class=\"dashicons dashicons-menu\"></span><strong>"+String(r.label).replace(/[&<>\"']/g,s=>({"&":"&amp;","<":"&lt;",">":"&gt;","\\\"":"&quot;","\\\'":"&#039;"}[s]))+"</strong><label><input type=\"checkbox\" class=\"sa-layout-enabled\" "+(r.enabled?"checked":"")+"> 表示</label><label>列 <select class=\"sa-layout-columns\"><option value=\"1\" "+(r.columns===1?"selected":"")+">1列</option><option value=\"2\" "+(r.columns===2?"selected":"")+">2列</option></select></label>";el.addEventListener("dragstart",e=>e.dataTransfer.setData("text/plain",String(i)));el.addEventListener("dragover",e=>e.preventDefault());el.addEventListener("drop",e=>{e.preventDefault();const from=Number(e.dataTransfer.getData("text/plain")),to=Number(el.dataset.i);if(from===to)return;const x=rows.splice(from,1)[0];rows.splice(to,0,x);draw()});wrap.appendChild(el)});document.getElementById("stageart-production-layout-json").value=JSON.stringify(rows)}draw();wrap.addEventListener("change",e=>{const row=e.target.closest(".stageart-layout-row");if(!row)return;const r=rows[Number(row.dataset.i)];if(e.target.classList.contains("sa-layout-enabled"))r.enabled=e.target.checked;if(e.target.classList.contains("sa-layout-columns"))r.columns=Number(e.target.value);document.getElementById("stageart-production-layout-json").value=JSON.stringify(rows)});const submit=form.querySelector("button[type=submit]");if(submit)form.insertBefore(box,submit.closest("p")||null)})()</script>';
    }

    public function save(int $postId, \WP_Post $post, bool $update): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('manage_options')) return;
        if (($post->post_type ?? '') !== 'stageart_production') return;
        $nonce = isset($_POST['stageart_production_layout_nonce']) ? sanitize_text_field(wp_unslash($_POST['stageart_production_layout_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'stageart_production_layout')) return;
        $raw = isset($_POST['stageart_production_layout']) ? json_decode(wp_unslash((string)$_POST['stageart_production_layout']), true) : [];
        if (!is_array($raw)) return;
        ProductionLayout::save($postId, $raw);
    }
}
