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
        add_action('admin_post_stageart_save_production', [$this, 'save'], -1);
        add_action('wp_head', [$this, 'publicStyles'], 30);
        add_action('template_redirect', [$this, 'startPublicBuffer'], 1);
    }

    public function assets(string $hook): void
    {
        if (!str_contains($hook, 'stageart-productions')) return;
        $id = (int) ($_GET['id'] ?? 0);
        wp_enqueue_media(['post' => $id ?: null]);
    }

    public function startPublicBuffer(): void
    {
        if (is_admin()) return;
        $slug = get_query_var('stageart_production_slug');
        if (!is_string($slug) || $slug === '') return;
        ob_start([$this, 'filterPublicHtml']);
    }

    public function filterPublicHtml(string $html): string
    {
        $slug = get_query_var('stageart_production_slug');
        if (!is_string($slug) || $slug === '') return $html;
        $q = new \WP_Query(['post_type'=>'stageart_production','name'=>$slug,'post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids']);
        if (!$q->posts || !class_exists('StageArtCore\Presentation\PublicSite\ProductionLayout')) return $html;
        $layout = \StageArtCore\Presentation\PublicSite\ProductionLayout::get((int)$q->posts[0]);
        $indents = [];
        foreach ($layout as $section) foreach ((array)($section['slots'] ?? []) as $slot) {
            if (($slot['type'] ?? 'link') === 'heading' || !empty($slot['ref'])) $indents[] = max(0,min(3,(int)($slot['indent'] ?? 0)));
        }
        if (!$indents) return $html;
        $i=0;
        return preg_replace_callback('/class="([^"]*stageart-production-layout-slot[^\"]*)"/', function($m) use (&$i,$indents){
            $indent=$indents[$i++] ?? 0;
            if (strpos($m[1],'stageart-indent-')===false && $indent>0) $m[1].=' stageart-indent-'.$indent;
            return 'class="'.$m[1].'"';
        }, $html) ?? $html;
    }

    public function publicStyles(): void
    {
        if (is_admin()) return;
        echo '<style>
        .stageart-home-slot>.stageart-production-grid{grid-template-columns:repeat(auto-fit,minmax(min(100%,520px),520px));justify-content:center}
        .stageart-home-slot>.stageart-production-grid .stageart-card{max-width:520px;width:100%;margin-left:auto;margin-right:auto}
        .stageart-home-slot>.stageart-card{max-width:520px;margin-left:auto;margin-right:auto}
        .stageart-performance-timeline--grid .stageart-performance-timeline-line{position:relative;display:flex;align-items:center;justify-content:center;min-height:17px}
        .stageart-performance-timeline--grid .stageart-performance-timeline-line:before{content:"";position:absolute;left:0;right:0;top:50%;border-top:1px solid var(--line)}
        .stageart-performance-timeline--grid .stageart-performance-timeline-line:after{content:"";position:absolute;left:50%;top:0;bottom:0;border-left:1px solid var(--line)}
        .stageart-performance-timeline--grid .stageart-performance-timeline-line:not(.has-marker):after{content:"";position:absolute;left:50%;top:0;bottom:0;border-left:1px solid var(--line)}
        .stageart-performance-timeline--grid .stageart-performance-timeline-line b{position:relative;z-index:2;background:var(--paper);padding:0 3px}
        .stageart-production--dark .stageart-performance-timeline--grid .stageart-performance-timeline-line b,.stageart-production--light .stageart-performance-timeline--grid .stageart-performance-timeline-line b{background:var(--production-bg)}
        </style>';
    }

    public function footer(): void
    {
        if (($_GET['page'] ?? '') !== 'stageart-productions' || !current_user_can('manage_options')) return;
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) return;
        $view = (string) get_post_meta($id, self::VIEW_META, true);
        if (!in_array($view, ['table','list','timeline_line','timeline_grid'], true)) $view='table';
        $psize=(string)get_post_meta($id,self::PERFORMANCE_SIZE_META,true); if(!in_array($psize,['l','m','s'],true))$psize='l';
        $tsize=(string)get_post_meta($id,self::TICKET_SIZE_META,true); if(!in_array($tsize,['l','m','s'],true))$tsize='l';
        $v=esc_js($view);$ps=esc_js($psize);$ts=esc_js($tsize);
        echo '<style>.stageart-performance-display-setting,.stageart-ticket-size-setting{margin:12px 0;padding:12px 14px;background:#f6f7f7;border:1px solid #dcdcde}.sa-credit-release-toggle{margin-left:10px}.sa-credit-release-toggle+input{margin-left:8px}</style>';
        echo '<script>(function(){const f=document.getElementById("stageart-production-form");if(!f)return;
        const perf=document.getElementById("sa-performances");
        function sel(n,opts,val){return "<select name=\""+n+"\">"+opts.map(o=>"<option value=\""+o[0]+"\">"+o[1]+"</option>").join("")+"</select>"}
        if(perf&&!f.querySelector(".stageart-performance-display-setting")){const b=document.createElement("div");b.className="stageart-performance-display-setting";b.innerHTML="<strong>公演回の表示方式</strong> "+sel("performance_view",[["table","表形式"],["list","一覧形式"],["timeline_line","タイムライン（線）"],["timeline_grid","タイムライン（区切り）"]],"' . $v . '")+"　<strong>サイズ</strong> "+sel("performance_size",[["l","L"],["m","M"],["s","S"]],"' . $ps . '");perf.parentNode.insertBefore(b,perf)}
        const tickets=document.getElementById("sa-tickets");if(tickets&&!f.querySelector(".stageart-ticket-size-setting")){const b=document.createElement("div");b.className="stageart-ticket-size-setting";b.innerHTML="<strong>チケット料金のサイズ</strong> "+sel("ticket_size",[["l","L"],["m","M"],["s","S"]],"' . $ts . '");tickets.parentNode.insertBefore(b,tickets)}
        function credits(){f.querySelectorAll(".sa-credit").forEach(function(card){const d=card.querySelector("input[type=datetime-local][name*=\"[release_at]\"]");if(!d||card.querySelector(".sa-credit-release-toggle"))return;const w=document.createElement("label");w.className="sa-credit-release-toggle";const c=document.createElement("input");c.type="checkbox";c.name=d.name.replace("[release_at]","[release_enabled]");c.value="1";c.checked=!!d.value;w.appendChild(c);w.appendChild(document.createTextNode(" 公開日時を使用する"));d.parentNode.insertBefore(w,d);d.disabled=!c.checked;c.addEventListener("change",function(){d.disabled=!c.checked;if(!c.checked)d.value=""})})}
        credits();f.addEventListener("click",function(e){if(e.target.closest("[data-add=credit]"))setTimeout(credits,0)});f.addEventListener("submit",credits);
        })();</script>';
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) return;
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id || get_post_type($id) !== 'stageart_production') return;

        // Resolve temporary label keys before ProductionAdmin saves labels/performances.
        if (isset($_POST['labels']) && is_array($_POST['labels'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'stageart_plugin_performance_labels';
            $map = [];
            $rows = [];
            foreach ($_POST['labels'] as $key => $row) {
                if (!is_array($row)) continue;
                $symbol = sanitize_text_field(wp_unslash($row['symbol'] ?? ''));
                if ($symbol === '') continue;
                $labelId = (int) ($row['id'] ?? 0);
                if ($labelId <= 0 && !ctype_digit((string) $key)) {
                    $name = sanitize_text_field(wp_unslash($row['name'] ?? ''));
                    $now = gmdate('Y-m-d H:i:s');
                    $wpdb->insert($table, [
                        'production_id' => $id, 'symbol' => $symbol, 'name' => $name,
                        'display_order' => count($rows), 'created_at' => $now, 'updated_at' => $now,
                    ], ['%d','%s','%s','%d','%s','%s']);
                    $labelId = (int) $wpdb->insert_id;
                    if ($labelId > 0) $map[(string) $key] = $labelId;
                }
                $row['id'] = $labelId;
                $rows[$key] = $row;
            }
            $_POST['labels'] = $rows;
            if (isset($_POST['performances']) && is_array($_POST['performances'])) {
                foreach ($_POST['performances'] as $pk => $pr) {
                    if (!is_array($pr)) continue;
                    $lk = (string) ($pr['label_id'] ?? '');
                    if ($lk !== '' && isset($map[$lk])) $_POST['performances'][$pk]['label_id'] = (string) $map[$lk];
                }
            }
        }

        // The checkbox controls whether a credit release timestamp is actually used.
        if (isset($_POST['credits']) && is_array($_POST['credits'])) {
            foreach ($_POST['credits'] as $ck => $credit) {
                if (!is_array($credit)) continue;
                if (empty($credit['release_enabled'])) $_POST['credits'][$ck]['release_at'] = '';
            }
        }

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
