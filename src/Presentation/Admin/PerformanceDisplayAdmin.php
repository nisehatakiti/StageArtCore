<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

final class PerformanceDisplayAdmin
{
    private const VIEW_META = 'performance_view';
    private const PERFORMANCE_SIZE_META = 'performance_size';
    private const TICKET_SIZE_META = 'ticket_size';

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'assets'], 30);
        add_action('admin_footer', [$this, 'footer'], 30);
        add_action('admin_post_stageart_save_production', [$this, 'save'], 0);
    }

    public function assets(string $hook): void
    {
        if (!str_contains($hook, 'stageart-productions')) return;
        $id = (int) ($_GET['id'] ?? 0);
        wp_enqueue_media(['post' => $id ?: null]);
    }

    public function footer(): void
    {
        if (($_GET['page'] ?? '') !== 'stageart-productions' || !current_user_can('manage_options')) return;
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) return;

        $view = (string) get_post_meta($id, self::VIEW_META, true);
        if (!in_array($view, ['table', 'list', 'timeline_line', 'timeline_grid'], true)) $view = 'table';
        $performanceSize = (string) get_post_meta($id, self::PERFORMANCE_SIZE_META, true);
        if (!in_array($performanceSize, ['l', 'm', 's'], true)) $performanceSize = 'l';
        $ticketSize = (string) get_post_meta($id, self::TICKET_SIZE_META, true);
        if (!in_array($ticketSize, ['l', 'm', 's'], true)) $ticketSize = 'l';

        $viewJs = esc_js($view);
        $performanceSizeJs = esc_js($performanceSize);
        $ticketSizeJs = esc_js($ticketSize);
        echo '<script>(function(){
            const form=document.getElementById("stageart-production-form");
            const performances=document.getElementById("sa-performances");
            if(!form)return;

            function selectHtml(name, value, options){
                return "<select name=\\\""+name+"\\\" form=\\\"stageart-production-form\\\">"+options.map(function(o){return "<option value=\\\""+o[0]+"\\\">"+o[1]+"</option>";}).join("")+"</select>";
            }

            if(performances){
                const box=document.createElement("div");
                box.className="stageart-performance-display-setting";
                box.style.cssText="margin:12px 0 20px;padding:12px 14px;background:#f6f7f7;border:1px solid #dcdcde";
                box.innerHTML="<strong>公演回の表示形式</strong> "+selectHtml("performance_view","",[["table","表形式"],["list","一覧形式"],["timeline_line","タイムライン（線）"],["timeline_grid","タイムライン（区切り）"]])+" <label style=\\\"margin-left:18px\\\">サイズ "+selectHtml("performance_size","",[["l","L"],["m","M"],["s","S"]])+"</label><p class=\\\"description\\\" style=\\\"margin:6px 0 0\\\">表形式のLを基準に、M/Sは文字・余白・全体幅を小さくします。</p>";
                performances.insertAdjacentElement("afterend",box);
                const selects=box.querySelectorAll("select");
                selects[0].value="' . $viewJs . '";
                selects[1].value="' . $performanceSizeJs . '";
            }

            const tickets=document.getElementById("sa-tickets");
            if(tickets){
                const box=document.createElement("div");
                box.className="stageart-ticket-size-setting";
                box.style.cssText="margin:12px 0 12px;padding:10px 14px;background:#f6f7f7;border:1px solid #dcdcde";
                box.innerHTML="<strong>チケット料金のサイズ</strong> "+selectHtml("ticket_size","",[["l","L"],["m","M"],["s","S"]]);
                tickets.insertAdjacentElement("beforebegin",box);
                box.querySelector("select").value="' . $ticketSizeJs . '";
            }

            function bindMediaPicker(){
                const button=document.getElementById("sa-media-picker");
                const clear=document.getElementById("sa-media-clear");
                const mediaId=document.getElementById("sa-main-image-id");
                const preview=document.getElementById("sa-media-preview");
                if(!button||!mediaId||!preview||button.dataset.stageartMediaBound)return false;
                if(!window.wp||typeof window.wp.media!=="function")return false;
                button.dataset.stageartMediaBound="1";
                button.addEventListener("click",function(e){
                    e.preventDefault();
                    const frame=window.wp.media({title:"メイン画像を選択",button:{text:"この画像を使用"},multiple:false});
                    frame.on("select",function(){
                        const a=frame.state().get("selection").first().toJSON();
                        mediaId.value=a.id||"";
                        preview.innerHTML=a.url?"<img src=\\\""+a.url+"\\\" style=\\\"max-width:320px;height:auto\\\">":"";
                    });
                    frame.open();
                });
                if(clear&&!clear.dataset.stageartMediaBound){
                    clear.dataset.stageartMediaBound="1";
                    clear.addEventListener("click",function(e){e.preventDefault();mediaId.value="";preview.innerHTML="";});
                }
                return true;
            }
            if(!bindMediaPicker()){
                let tries=0;const timer=setInterval(function(){if(bindMediaPicker()||++tries>=20)clearInterval(timer);},100);
            }
        })();</script>';
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) return;
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || get_post_type($id) !== 'stageart_production') return;

        $view = sanitize_key(wp_unslash($_POST['performance_view'] ?? 'table'));
        if (!in_array($view, ['table', 'list', 'timeline_line', 'timeline_grid'], true)) $view = 'table';
        $performanceSize = sanitize_key(wp_unslash($_POST['performance_size'] ?? 'l'));
        if (!in_array($performanceSize, ['l', 'm', 's'], true)) $performanceSize = 'l';
        $ticketSize = sanitize_key(wp_unslash($_POST['ticket_size'] ?? 'l'));
        if (!in_array($ticketSize, ['l', 'm', 's'], true)) $ticketSize = 'l';

        update_post_meta($id, self::VIEW_META, $view);
        update_post_meta($id, self::PERFORMANCE_SIZE_META, $performanceSize);
        update_post_meta($id, self::TICKET_SIZE_META, $ticketSize);
    }
}
