<?php

declare(strict_types=1);

namespace StageArtCore\Infrastructure;

use StageArtCore\Presentation\PublicSite\MemberRouter;
use StageArtCore\Presentation\PublicSite\ProductionRouter;
use StageArtCore\Presentation\PublicSite\SurveyRouter;

final class RewriteManager
{
    public const VERSION = '9';

    public static function register(): void
    {
        add_action('wp_loaded', [self::class, 'maybeFlush'], 999);
        add_action('admin_notices', [self::class, 'adminNotice']);
    }

    public static function activate(): void
    {
        (new MemberRouter())->add_rewrite_rules();
        (new ProductionRouter())->rewrite();
        (new SurveyRouter())->rewrite();
        self::loadRewriteWriter();
        flush_rewrite_rules(true);
        if (function_exists('save_mod_rewrite_rules')) {
            save_mod_rewrite_rules();
        }
        update_option('stageart_core_rewrite_version', self::VERSION, false);
    }

    public static function maybeFlush(): void
    {
        if ((string) get_option('stageart_core_rewrite_version', '') === self::VERSION) {
            return;
        }

        self::loadRewriteWriter();
        flush_rewrite_rules(true);
        if (function_exists('save_mod_rewrite_rules')) {
            save_mod_rewrite_rules();
        }

        $rules = (array) get_option('rewrite_rules', []);
        if (self::hasRoute($rules, '^member/') && self::hasRoute($rules, '^production/')) {
            update_option('stageart_core_rewrite_version', self::VERSION, false);
        }
    }

    public static function adminNotice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        if ((string) get_option('stageart_core_rewrite_version', '') === self::VERSION) {
            return;
        }

        echo '<div class="notice notice-warning"><p><strong>StageArtCore:</strong> 公開ページのURLルールを自動更新できませんでした。WordPressの「設定 → パーマリンク」を開いて「変更を保存」を一度実行してください。</p></div>';
    }

    private static function loadRewriteWriter(): void
    {
        if (!function_exists('save_mod_rewrite_rules')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
    }

    private static function hasRoute(array $rules, string $prefix): bool
    {
        foreach (array_keys($rules) as $regex) {
            if (str_starts_with((string) $regex, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
