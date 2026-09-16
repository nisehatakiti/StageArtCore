<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

use StageArtCore\Domain\Production\ProductionRepository;
use StageArtCore\Domain\Release\ReleaseDate;

final class PerformanceReleaseAdmin
{
    public function register(): void
    {
        add_action('admin_post_stageart_save_production', [$this, 'normalizePost'], 0);
        add_action('admin_footer', [$this, 'scripts'], 25);
    }

    public function normalizePost(): void
    {
        if (!current_user_can('manage_options')) return;
        $rows = (array) ($_POST['performances'] ?? []);
        foreach ($rows as $i => $row) {
            if (!is_array($row)) continue;
            $rows[$i]['release_at'] = ReleaseDate::toUtc(sanitize_text_field(wp_unslash($row['release_at'] ?? '')));
        }
        $_POST['performances'] = $rows;
    }

    public function scripts(): void
    {
        if (!is_admin() || ($_GET['page'] ?? '') !== 'stageart-productions') return;
        $id = (int) ($_GET['id'] ?? 0);
        $values = [];
        if ($id > 0) {
            foreach ((new ProductionRepository())->performances($id) as $row) {
                $values[(int) $row['id']] = ReleaseDate::fromUtc($row['release_at'] ?? null);
            }
        }
        $json = wp_json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo '<style>.sa-performance-release{display:flex;flex-direction:column;gap:4px;min-width:220px}.sa-performance-release label{white-space:nowrap}.sa-performance-release input[type=datetime-local]{width:220px}.sa-performance-release input[type=datetime-local]:disabled{opacity:.55}</style>';
        echo '<script>(function(){var releases=' . $json . ';function init(){var table=document.getElementById("sa-performances");if(!table)return;var head=table.querySelector("thead tr"),body=table.querySelector("tbody");if(!head||!body)return;if(!head.querySelector(".sa-performance-release-head")){var th=document.createElement("th");th.className="sa-performance-release-head";th.textContent="情報解禁";head.insertBefore(th,head.lastElementChild);}function add(row){if(!row||row.querySelector(".sa-performance-release"))return;var id=row.querySelector("input[name*=\"[id]\"]"),name=row.querySelector("input[name*=\"[date]\"]");if(!name)return;var m=name.name.match(/performances\[([^\]]+)\]\[date\]/);if(!m)return;var key=m[1],release="";if(id&&id.value&&Object.prototype.hasOwnProperty.call(releases,id.value))release=releases[id.value];var td=document.createElement("td");td.innerHTML="<div class=\"sa-performance-release\"><label><input type=\"checkbox\" class=\"sa-performance-release-toggle\"> 情報解禁日を設定する</label><input type=\"datetime-local\" class=\"sa-performance-release-input\" name=\"performances["+key+"][release_at]\"></div>";var toggle=td.querySelector(".sa-performance-release-toggle"),input=td.querySelector(".sa-performance-release-input");input.value=release;toggle.checked=release!=="";input.disabled=!toggle.checked;toggle.addEventListener("change",function(){input.disabled=!toggle.checked;if(toggle.checked)input.focus();});row.insertBefore(td,row.lastElementChild);}Array.prototype.forEach.call(body.querySelectorAll("tr"),add);new MutationObserver(function(ms){ms.forEach(function(x){x.addedNodes.forEach(function(n){if(n.nodeType===1){if(n.matches("tr"))add(n);if(n.querySelectorAll)n.querySelectorAll("tr").forEach(add);}});});}).observe(body,{childList:true});}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();})();</script>';
    }
}
