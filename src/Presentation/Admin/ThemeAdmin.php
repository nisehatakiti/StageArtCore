<?php
declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

use StageArtCore\Domain\Site\Theme;

if (!defined('ABSPATH')) exit;

final class ThemeAdmin
{
    public function register(): void
    {
        add_submenu_page(
            'stageart-plugin',
            'テーマ',
            'テーマ',
            'manage_options',
            'stageart-theme',
            [$this, 'render']
        );
    }

    public function registerSettings(): void
    {
        register_setting('stageart_theme_organization', Theme::ORG_OPTION, ['sanitize_callback' => fn($v) => $this->sanitize($v, 'organization')]);
    }

    public function registerActions(): void
    {
        add_action('admin_post_stageart_save_production_theme', [$this, 'saveProduction']);
    }

    /** @param mixed $value */
    public function sanitize($value, string $site = 'organization'): array
    {
        $value = is_array($value) ? $value : [];
        $presets = Theme::presets($site);
        $mode = in_array(($value['mode'] ?? 'preset'), ['preset', 'custom'], true) ? $value['mode'] : 'preset';
        $preset = sanitize_key((string)($value['preset'] ?? 'light_standard'));
        if (!isset($presets[$preset])) $preset = 'light_standard';

        $custom = Theme::customDefaults();
        foreach ($custom as $key => $default) {
            if (str_ends_with($key, '_font')) {
                $custom[$key] = sanitize_text_field((string)($value['custom'][$key] ?? $default));
            } else {
                $candidate = sanitize_hex_color((string)($value['custom'][$key] ?? $default));
                $custom[$key] = $candidate ?: $default;
            }
        }

        return ['mode' => $mode, 'preset' => $preset, 'custom' => $custom];
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) return;

        $this->renderOrganizationForm();
        $this->renderProductionForm();
    }

    private function renderOrganizationForm(): void
    {
        $saved = get_option(Theme::ORG_OPTION, ['mode' => 'preset', 'preset' => 'light_standard', 'custom' => Theme::customDefaults()]);
        $this->renderForm(
            'organization',
            Theme::ORG_OPTION,
            '団体サイトのテーマ',
            is_array($saved) ? $saved : []
        );
    }

    private function renderProductionForm(): void
    {
        $productionId = max(0, (int)($_GET['production_id'] ?? 0));
        $productions = get_posts([
            'post_type' => 'stageart_production',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        echo '<div class="wrap"><form method="get">';
        echo '<input type="hidden" name="page" value="stageart-theme">';
        echo '<h2>公演ページのテーマ</h2>';
        echo '<p><label for="stageart-production-selector"><strong>公演を選択</strong></label> ';
        echo '<select id="stageart-production-selector" name="production_id" onchange="this.form.submit()">';
        echo '<option value="0">公演を選択してください</option>';
        foreach ($productions as $production) {
            echo '<option value="' . esc_attr((string)$production->ID) . '"' . selected($productionId, $production->ID, false) . '>' . esc_html($production->post_title) . '</option>';
        }
        echo '</select></p></form>';

        if ($productionId <= 0) {
            echo '<p class="description">公演を選択すると、その公演専用のテーマ設定を編集できます。</p></div>';
            return;
        }

        $production = get_post($productionId);
        if (!$production || $production->post_type !== 'stageart_production') {
            echo '<div class="notice notice-error"><p>指定された公演が見つかりません。</p></div></div>';
            return;
        }

        $saved = get_post_meta($productionId, Theme::PRODUCTION_META, true);
        $this->renderProductionSettingsForm($productionId, is_array($saved) ? $saved : []);
        echo '</div>';
    }

    /** @param array<string,mixed> $saved */
    private function renderProductionSettingsForm(int $productionId, array $saved): void
    {
        $preset = (string)($saved['preset'] ?? 'light_standard');
        $custom = array_merge(Theme::customDefaults(), is_array($saved['custom'] ?? null) ? $saved['custom'] : []);
        $option = 'production_theme';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('stageart_save_production_theme');
        echo '<input type="hidden" name="action" value="stageart_save_production_theme">';
        echo '<input type="hidden" name="production_id" value="' . esc_attr((string)$productionId) . '">';
        echo '<h3>「' . esc_html(get_the_title($productionId)) . '」のテーマ</h3>';
        echo '<table class="form-table">';
        echo '<tr><th>テーマ</th><td><select name="' . $option . '[preset]">';
        foreach (Theme::presets('production') as $key => $theme) {
            echo '<option value="' . esc_attr($key) . '"' . selected($preset, $key, false) . '>' . esc_html($theme['label']) . '</option>';
        }
        echo '</select><p class="description">この公演ページだけに適用されるテーマを選択します。</p></td></tr>';
        echo '<tr><th>カスタムモード</th><td><label><input type="checkbox" name="' . $option . '[mode]" value="custom"' . checked(($saved['mode'] ?? 'preset'), 'custom', false) . '> カスタム配色・フォントを使用する</label></td></tr>';
        echo '</table><h3>カスタム配色</h3><table class="form-table">';
        foreach (['bg'=>'背景色','surface'=>'カード・面の色','text'=>'本文色','primary'=>'メイン色','accent'=>'アクセント色','border'=>'境界線色'] as $key => $label) {
            echo '<tr><th>' . esc_html($label) . '</th><td><input type="color" name="' . $option . '[custom][' . esc_attr($key) . ']" value="' . esc_attr($custom[$key]) . '"></td></tr>';
        }
        foreach (['heading_font'=>'見出しフォント','body_font'=>'本文フォント'] as $key => $label) {
            echo '<tr><th>' . esc_html($label) . '</th><td><select name="' . $option . '[custom][' . esc_attr($key) . ']">';
            foreach (['system'=>'システム標準','sans'=>'ゴシック系','serif'=>'明朝・セリフ系'] as $font => $fontLabel) {
                echo '<option value="' . esc_attr($font) . '"' . selected($custom[$key], $font, false) . '>' . esc_html($fontLabel) . '</option>';
            }
            echo '</select></td></tr>';
        }
        echo '</table>';
        submit_button('この公演のテーマを保存');
        echo '</form>';
    }

    /** @param array<string,mixed> $saved */
    private function renderForm(string $site, string $option, string $title, array $saved): void
    {
        $preset = (string)($saved['preset'] ?? 'light_standard');
        $custom = array_merge(Theme::customDefaults(), is_array($saved['custom'] ?? null) ? $saved['custom'] : []);

        echo '<div class="wrap"><h1>テーマ設定</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('stageart_theme_organization');
        echo '<h2>' . esc_html($title) . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>テーマ</th><td><select name="' . esc_attr($option) . '[preset]">';
        foreach (Theme::presets($site) as $key => $theme) {
            echo '<option value="' . esc_attr($key) . '"' . selected($preset, $key, false) . '>' . esc_html($theme['label']) . '</option>';
        }
        echo '</select><p class="description">団体サイト全体の基本テーマを選択します。</p></td></tr>';
        echo '<tr><th>カスタムモード</th><td><label><input type="checkbox" name="' . esc_attr($option) . '[mode]" value="custom"' . checked(($saved['mode'] ?? 'preset'), 'custom', false) . '> カスタム配色・フォントを使用する</label></td></tr>';
        echo '</table><h3>カスタム配色</h3><table class="form-table">';
        foreach (['bg'=>'背景色','surface'=>'カード・面の色','text'=>'本文色','primary'=>'メイン色','accent'=>'アクセント色','border'=>'境界線色'] as $key => $label) {
            echo '<tr><th>' . esc_html($label) . '</th><td><input type="color" name="' . esc_attr($option) . '[custom][' . esc_attr($key) . ']" value="' . esc_attr($custom[$key]) . '"></td></tr>';
        }
        foreach (['heading_font'=>'見出しフォント','body_font'=>'本文フォント'] as $key => $label) {
            echo '<tr><th>' . esc_html($label) . '</th><td><select name="' . esc_attr($option) . '[custom][' . esc_attr($key) . ']">';
            foreach (['system'=>'システム標準','sans'=>'ゴシック系','serif'=>'明朝・セリフ系'] as $font => $fontLabel) {
                echo '<option value="' . esc_attr($font) . '"' . selected($custom[$key], $font, false) . '>' . esc_html($fontLabel) . '</option>';
            }
            echo '</select></td></tr>';
        }
        echo '</table>';
        submit_button('保存');
        echo '</form></div>';
    }

    public function saveProduction(): void
    {
        if (!current_user_can('manage_options')) wp_die('権限がありません。');
        check_admin_referer('stageart_save_production_theme');

        $productionId = max(0, (int)($_POST['production_id'] ?? 0));
        $production = get_post($productionId);
        if (!$production || $production->post_type !== 'stageart_production') wp_die('公演が見つかりません。');

        $raw = is_array($_POST['production_theme'] ?? null) ? wp_unslash($_POST['production_theme']) : [];
        $settings = $this->sanitize($raw, 'production');
        update_post_meta($productionId, Theme::PRODUCTION_META, $settings);

        wp_safe_redirect(add_query_arg([
            'page' => 'stageart-theme',
            'production_id' => $productionId,
            'saved' => '1',
        ], admin_url('admin.php')));
        exit;
    }
}
