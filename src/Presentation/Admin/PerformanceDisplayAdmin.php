<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

final class PerformanceDisplayAdmin
{
    private const META = 'performance_view';

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'assets'], 30);
        add_action('admin_footer', [$this, 'footer'], 30);
        add_action('admin_post_stageart_save_production', [$this, 'save'], 0);
    }

    public function assets(string $hook): void
    {
        if (!str_contains($hook, 'stageart-productions')) {
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);
        wp_enqueue_media(['post' => $id ?: null]);
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

        $current = (string) get_post_meta($id, self::META, true);
        if (!in_array($current, ['table', 'list', 'timeline_line', 'timeline_grid'], true)) {
            $current = 'table';
        }

        $currentJs = esc_js($current);
        echo '<script>(function(){
            const table=document.getElementById("sa-performances");
            if(!table)return;
            const box=document.createElement("div");
            box.className="stageart-performance-display-setting";
            box.style.cssText="margin:12px 0 20px;padding:12px 14px;background:#f6f7f7;border:1px solid #dcdcde";
            box.innerHTML="<strong>公演回の表示形式</strong> <select name=\"performance_view\" form=\"stageart-production-form\" style=\"margin-left:8px\"><option value=\"table\">表形式（コンパクト）</option><option value=\"list\">一覧形式</option><option value=\"timeline_line\">タイムライン（線）</option><option value=\"timeline_grid\">タイムライン（区切り）</option></select><p class=\"description\" style=\"margin:6px 0 0\">一覧形式は同日の公演回を「11:00 / 15:00」のようにまとめます。タイムラインは日付×開演時刻で位置関係を表示します。</p>";
            table.insertAdjacentElement("afterend",box);
            const select=box.querySelector("select");
            select.value="' . $currentJs . '";

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
                        preview.innerHTML=a.url?"<img src=\""+a.url+"\" style=\"max-width:320px;height:auto\">":"";
                    });
                    frame.open();
                });
                if(clear&&!clear.dataset.stageartMediaBound){
                    clear.dataset.stageartMediaBound="1";
                    clear.addEventListener("click",function(e){
                        e.preventDefault();
                        mediaId.value="";
                        preview.innerHTML="";
                    });
                }
                return true;
            }

            if(!bindMediaPicker()){
                let tries=0;
                const timer=setInterval(function(){
                    if(bindMediaPicker()||++tries>=20)clearInterval(timer);
                },100);
            }
        })();</script>';
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || get_post_type($id) !== 'stageart_production') {
            return;
        }

        $value = sanitize_key(wp_unslash($_POST['performance_view'] ?? 'table'));
        if (!in_array($value, ['table', 'list', 'timeline_line', 'timeline_grid'], true)) {
            $value = 'table';
        }

        update_post_meta($id, self::META, $value);
    }
}
