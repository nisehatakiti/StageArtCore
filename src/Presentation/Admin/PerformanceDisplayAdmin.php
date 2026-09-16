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
            const wrap=table.parentElement;
            const box=document.createElement("div");
            box.className="stageart-performance-display-setting";
            box.style.cssText="margin:12px 0 20px;padding:12px 14px;background:#f6f7f7;border:1px solid #dcdcde";
            box.innerHTML="<strong>公演回の表示形式</strong> <select name=\"performance_view\" form=\"stageart-production-form\" style=\"margin-left:8px\"><option value=\"table\">表形式（コンパクト）</option><option value=\"list\">一覧形式</option><option value=\"timeline_line\">タイムライン（線）</option><option value=\"timeline_grid\">タイムライン（区切り）</option></select><p class=\"description\" style=\"margin:6px 0 0\">一覧形式は同日の公演回を「11:00 / 15:00」のようにまとめます。タイムラインは日付×開演時刻で位置関係を表示します。</p>";
            table.insertAdjacentElement("afterend",box);
            const select=box.querySelector("select");
            select.value="' . $currentJs . '";
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
