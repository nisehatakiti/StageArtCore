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
        register_setting('stageart_theme', Theme::ORG_OPTION, ['sanitize_callback' => [$this, 'sanitize']]);
    }

    /** @return array<string,mixed> */
    public function sanitize($value): array
    {
        $value = is_array($value) ? $value : [];
        $mode = in_array(($value['mode'] ?? 'preset'), ['preset', 'custom'], true) ? $value['mode'] : 'preset';
        $preset = sanitize_key((string)($value['preset'] ?? 'light_standard'));
        if (!isset(Theme::presets('organization')[$preset])) $preset = 'light_standard';
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
        $saved = get_option(Theme::ORG_OPTION, ['mode' => 'preset', 'preset' => 'light_standard', 'custom' => Theme::customDefaults()]);
        $saved = is_array($saved) ? $saved : [];
        $preset = (string)($saved['preset'] ?? 'light_standard');
        $custom = array_merge(Theme::customDefaults(), is_array($saved['custom'] ?? null) ? $saved['custom'] : []);
        echo '<div class="wrap"><h1>サイトテーマ</h1><form method="post" action="options.php">';
        settings_fields('stageart_theme');
        echo '<table class="form-table"><tr><th>テーマ</th><td><select name="'.esc_attr(Theme::ORG_OPTION).'[preset]">';
        foreach (Theme::presets('organization') as $key => $theme) echo '<option value="'.esc_attr($key).'" '.selected($preset, $key, false).'>'.esc_html($theme['label']).'</option>';
        echo '</select><p class="description">団体ホームページの基本テーマを選択します。カスタムは下の設定を使用します。</p></td></tr>';
        echo '<tr><th>カスタムモード</th><td><label><input type="checkbox" name="'.esc_attr(Theme::ORG_OPTION).'[mode]" value="custom" '.checked(($saved['mode'] ?? 'preset'), 'custom', false).'> カスタム配色・フォントを使用する</label></td></tr>';
        echo '</table><h2>カスタム配色</h2><table class="form-table">';
        foreach (['bg'=>'背景色','surface'=>'カード・面の色','text'=>'本文色','primary'=>'メイン色','accent'=>'アクセント色','border'=>'境界線色'] as $key => $label) echo '<tr><th>'.esc_html($label).'</th><td><input type="color" name="'.esc_attr(Theme::ORG_OPTION).'[custom]['.esc_attr($key).']" value="'.esc_attr($custom[$key]).'"></td></tr>';
        echo '<tr><th>見出しフォント</th><td><select name="'.esc_attr(Theme::ORG_OPTION).'[custom][heading_font]">'; foreach (['system'=>'システム標準','sans'=>'ゴシック系','serif'=>'明朝・セリフ系'] as $key=>$label) echo '<option value="'.esc_attr($key).'" '.selected($custom['heading_font'],$key,false).'>'.esc_html($label).'</option>'; echo '</select></td></tr>';
        echo '<tr><th>本文フォント</th><td><select name="'.esc_attr(Theme::ORG_OPTION).'[custom][body_font]">'; foreach (['system'=>'システム標準','sans'=>'ゴシック系','serif'=>'明朝・セリフ系'] as $key=>$label) echo '<option value="'.esc_attr($key).'" '.selected($custom['body_font'],$key,false).'>'.esc_html($label).'</option>'; echo '</select></td></tr></table><p><button class="button button-primary">保存</button></p></form></div>';
    }
}
