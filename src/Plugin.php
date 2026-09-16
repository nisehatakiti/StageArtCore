<?php

declare(strict_types=1);

namespace StageArtCore;

use StageArtCore\Infrastructure\RewriteManager;
use StageArtCore\Infrastructure\Schema\Schema;
use StageArtCore\Infrastructure\Schema\ProductionMigration;
use StageArtCore\Infrastructure\Schema\SurveyMigration;
use StageArtCore\Domain\Production\ProductionValidator;
use StageArtCore\Presentation\Admin\AdminMenu;
use StageArtCore\Presentation\Admin\MemberAdmin;
use StageArtCore\Presentation\Admin\PerformanceReleaseAdmin;
use StageArtCore\Presentation\Admin\ProductionAdmin;
use StageArtCore\Presentation\Admin\ProductionLayoutAdmin;
use StageArtCore\Presentation\Admin\ProductionPresentationAdmin;
use StageArtCore\Presentation\Admin\ProductionTimeAdmin;
use StageArtCore\Presentation\Admin\RepresentativeGreetingAdmin;
use StageArtCore\Presentation\Admin\SiteSettingsAdmin;
use StageArtCore\Presentation\Admin\SiteStructureAdmin;
use StageArtCore\Presentation\Admin\SurveyAdmin;
use StageArtCore\Presentation\Admin\SurveyResultsAdmin;
use StageArtCore\Presentation\Admin\SurveyQrAdmin;
use StageArtCore\Presentation\PublicSite\MemberRouter;
use StageArtCore\Presentation\PublicSite\MemberShortcodes;
use StageArtCore\Presentation\PublicSite\ProductionRouter;
use StageArtCore\Presentation\PublicSite\SurveyRouter;
use StageArtCore\Presentation\Rest\HealthController;
use StageArtCore\Presentation\Rest\MemberController;

final class Plugin
{
    public function boot(): void
    {
        add_action('init', static function (): void {
            if (get_option('stageart_core_db_version') !== Schema::DB_VERSION) {
                Schema::activate();
            }
            ProductionMigration::ensure();
            SurveyMigration::ensure();
            SiteSettingsAdmin::migrate();
        }, 1);

        add_action('init', static function (): void {
            register_post_type('stageart_production', ['labels'=>['name'=>'公演','singular_name'=>'公演'],'public'=>false,'show_ui'=>false,'supports'=>['title'],'rewrite'=>false]);
        }, 5);
        add_action('admin_menu', [new AdminMenu(), 'register']);
        add_action('admin_menu', [new SiteSettingsAdmin(), 'register'], 20);
        add_action('admin_menu', [new SiteStructureAdmin(), 'register'], 21);
        add_action('admin_menu', [new MemberAdmin(), 'register'], 20);
        (new PerformanceReleaseAdmin())->register();
        (new RepresentativeGreetingAdmin())->register();
        (new ProductionPresentationAdmin())->register();
        (new ProductionTimeAdmin())->register();
        (new ProductionLayoutAdmin())->register();
        add_action('admin_menu', [new ProductionAdmin(), 'register'], 20);
        add_action('admin_menu', [new SurveyAdmin(), 'register'], 30);
        add_action('admin_menu', [new SurveyResultsAdmin(), 'register'], 31);
        add_action('admin_menu', [new SurveyQrAdmin(), 'register'], 32);
        add_action('admin_post_stageart_save_production', static function (): void {
            if (!current_user_can('manage_options')) wp_die('権限がありません。');
            $postedLabels = (array) ($_POST['stageart_performance_labels'] ?? []);
            if ($postedLabels) {
                $performances = (array) ($_POST['performances'] ?? []);
                foreach ($postedLabels as $rowKey => $labelId) {
                    if (!isset($performances[$rowKey]) || !is_array($performances[$rowKey])) continue;
                    $performances[$rowKey]['label_id'] = sanitize_text_field(wp_unslash((string) $labelId));
                }
                $_POST['performances'] = $performances;
            }
            (new ProductionValidator())->validate(wp_unslash($_POST));
        }, 1);
        add_action('admin_head', static function (): void {
            if (($_GET['page'] ?? '') !== 'stageart-productions') return;
            echo '<script>(function(){document.addEventListener("DOMContentLoaded",function(){var f=document.getElementById("stageart-production-form");if(!f)return;f.addEventListener("submit",function(){document.querySelectorAll("#sa-performances tbody tr select[name*=\\"[label_id]\\"]").forEach(function(s){var m=s.name.match(/performances\\[([^\\]]+)\\]\\[label_id\\]/);if(!m)return;var n="stageart_performance_labels["+m[1]+"]",h=f.querySelector("input[name=\\""+n+"\\"]");if(!h){h=document.createElement("input");h.type="hidden";h.name=n;f.appendChild(h);}h.value=s.value||"";});});});})();</script>';
        }, 0);
        add_action('admin_head', static function (): void {
            if (($_GET['page'] ?? '') !== 'stageart-homepage') return;
            echo '<style>.stageart-homepage-admin .sa-slot-controls{display:flex;align-items:flex-end;gap:12px;flex-wrap:nowrap}.stageart-homepage-admin .sa-slot-controls>label{display:flex;flex-direction:column;align-items:flex-start;gap:4px;white-space:nowrap}.stageart-homepage-admin .sa-slot-controls>label br{display:none}.stageart-homepage-admin .sa-slot-controls>label:first-child{width:150px}.stageart-homepage-admin .sa-slot-controls .sa-slot-content-wrap,.stageart-homepage-admin .sa-slot-controls .sa-slot-heading-wrap{width:300px}.stageart-homepage-admin .sa-slot-controls .sa-slot-content,.stageart-homepage-admin .sa-slot-controls .sa-slot-heading{width:100%;max-width:none}.stageart-homepage-admin .sa-slot-controls .sa-slot-indent{width:100px}.stageart-homepage-admin .sa-home-slot{overflow-x:auto}.stageart-homepage-admin .sa-slot-controls select,.stageart-homepage-admin .sa-slot-controls input{margin:0}@media(max-width:900px){.stageart-homepage-admin .sa-slot-controls{flex-wrap:wrap}.stageart-homepage-admin .sa-slot-controls .sa-slot-content-wrap,.stageart-homepage-admin .sa-slot-controls .sa-slot-heading-wrap{width:260px}}</style>';
            echo '<script>(function(){document.querySelectorAll(".stageart-homepage-admin .sa-columns").forEach(function(select){for(var i=6;i<=20;i++){if(!select.querySelector("option[value=\\""+i+"\\"]")){var option=document.createElement("option");option.value=i;option.textContent=i+"件";select.appendChild(option);}}});})();</script>';
        });
        add_action('rest_api_init', static function (): void {
            (new HealthController())->register_routes();
            (new MemberController())->register_routes();
        });
        (new MemberShortcodes())->register();
        (new MemberRouter())->register();
        (new ProductionRouter())->register();
        (new SurveyRouter())->register();
        RewriteManager::register();
    }
}
