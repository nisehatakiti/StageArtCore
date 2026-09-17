<?php
declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

use StageArtCore\Domain\Site\Theme;

if (!defined('ABSPATH')) exit;

final class ThemeAdmin
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'register']);
    }

    public function register(): void
    {
        register_setting('stageart_theme_organization', Theme::ORG_OPTION, ['sanitize_callback' => fn($v) => $this->sanitize($v, 'organization')]);
        register_setting('stageart_theme_production', Theme::PRODUCTION_META, ['sanitize_callback' => fn($v) => $this->sanitize($v, 'production')]);
        add_submenu_page(
            'stageart-plugin',
            'テーマ',
            'テーマ',
            'manage_options',
            'stageart-theme',
            [$this, 'render']
        );
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

        $this->renderForm(
            'organization',
            Theme::ORG_OPTION,
            '団体サイトのテーマ',
            get_option(Theme::ORG_OPTION, ['mode' => 'preset', 'preset' => 'light_standard', 'custom' => Theme::customDefaults()])
        );

        $this->renderForm(
            'production',
            Theme::PRODUCTION_META,
            '公演ページのテーマ',
            get_option(Theme::PRODUCTION_META, ['mode' => 'preset', 'preset' => 'light_standard', 'custom' => Theme::customDefaults()])
        );
    }

    /** @param mixed $saved */
    private function renderForm(string $site, string $option, string $title, $saved): void
    {
        $saved = is_array($saved) ? $saved : [];
        $preset = (string)($saved['preset'] ?? 'light_standard');
        $custom = array_merge(Theme::customDefaults(), is_array($saved['custom'] ?? null) ? $saved['custom'] : []);

        echo '<div class="wrap"><h1>テーマ設定</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields($site === 'production' ? 'stageart_theme_production' : 'stageart_theme_organization');
        echo '<h2>' . esc_html($title) . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>テーマ</th><td><select name="' . esc_attr($option) . '[preset]">';
        foreach (Theme::presets($site) as $key => $theme) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($preset, $key, false) . '>' . esc_html($theme['label']) . '</option>';
        }
        echo '</select><p class="description">この対象の基本テーマを選択します。</p></td></tr>';
        echo '<tr><th>カスタムモード</th><td><label><input type="checkbox" name="' . esc_attr($option) . '[mode]" value="custom" ' . checked(($saved['mode'] ?? 'preset'), 'custom', false) . '> カスタム配色・フォントを使用する</label></td></tr>';
        echo '</table>';
        echo '<h3>カスタム配色</h3><table class="form-table">';
        foreach (['bg'=>'背景色','surface'=>'カード・面の色','text'=>'本文色','primary'=>'メイン色','accent'=>'アクセント色','border'=>'境界線色'] as $key => $label) {
            echo '<tr><th>' . esc_html($label) . '</th><td><input type="color" name="' . esc_attr($option) . '[custom][' . esc_attr($key) . ']" value="' . esc_attr($custom[$key]) . '"></td></tr>';
        }
        foreach (['heading_font'=>'見出しフォント','body_font'=>'本文フォント'] as $key => $label) {
            echo '<tr><th>' . esc_html($label) . '</th><td><select name="' . esc_attr($option) . '[custom][' . esc_attr($key) . ']">';
            foreach (['system'=>'システム標準','sans'=>'ゴシック系','serif'=>'明朝・セリフ系'] as $font => $fontLabel) {
                echo '<option value="' . esc_attr($font) . '" ' . selected($custom[$key], $font, false) . '>' . esc_html($fontLabel) . '</option>';
            }
            echo '</select></td></tr>';
        }
        echo '</table>';
        submit_button('保存');
        echo '</form></div>';
    }
}
