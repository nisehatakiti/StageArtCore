<?php

declare(strict_types=1);

namespace StageArtCore\Infrastructure;

use StageArtCore\Presentation\PublicSite\MemberRouter;
use StageArtCore\Presentation\PublicSite\ProductionRouter;
use StageArtCore\Presentation\PublicSite\SurveyRouter;

final class RewriteManager
{
    public const VERSION = '12';

    public static function register(): void
    {
        add_action('wp_loaded', [self::class, 'maybeFlush'], 999);
        add_action('admin_notices', [self::class, 'adminNotice']);
    }

    public static function activate(): void
    {
        self::registerRoutes();
        self::loadRewriteWriter();
        flush_rewrite_rules(true);
        self::writeApacheFallbackRules();
        self::markReadyIfRoutesExist();
    }

    public static function maybeFlush(): void
    {
        if ((string) get_option('stageart_core_rewrite_version', '') === self::VERSION) {
            return;
        }

        // Ensure the StageArt routes are registered in this request before flushing.
        self::registerRoutes();
        self::loadRewriteWriter();
        flush_rewrite_rules(true);
        self::writeApacheFallbackRules();
        self::markReadyIfRoutesExist();
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

    private static function registerRoutes(): void
    {
        (new MemberRouter())->add_rewrite_rules();
        (new ProductionRouter())->rewrite();
        (new SurveyRouter())->rewrite();
    }

    private static function markReadyIfRoutesExist(): void
    {
        $rules = (array) get_option('rewrite_rules', []);
        if (self::hasRoute($rules, 'member/') && self::hasRoute($rules, 'production/')) {
            update_option('stageart_core_rewrite_version', self::VERSION, false);
        }
    }

    private static function loadRewriteWriter(): void
    {
        if (!function_exists('save_mod_rewrite_rules')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
    }

    private static function writeApacheFallbackRules(): void
    {
        if (!function_exists('insert_with_markers')) {
            return;
        }

        $htaccess = trailingslashit(ABSPATH) . '.htaccess';
        if (!file_exists($htaccess) || !is_writable($htaccess)) {
            return;
        }

        // The two-segment production rule must precede the one-segment rule.
        $rules = [
            'RewriteEngine On',
            'RewriteRule ^production/([^/]+)/([^/]+)/?$ index.php?stageart_production_slug=$1&stageart_survey_slug=$2 [QSA,L]',
            'RewriteRule ^production/([^/]+)/?$ index.php?stageart_production_slug=$1 [QSA,L]',
            'RewriteRule ^member/([^/]+)/?$ index.php?stageart_member_slug=$1 [QSA,L]',
        ];

        insert_with_markers($htaccess, 'StageArtCore', $rules);
    }

    private static function hasRoute(array $rules, string $prefix): bool
    {
        foreach (array_keys($rules) as $regex) {
            $regex = ltrim((string) $regex, '^');
            if (str_starts_with($regex, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
