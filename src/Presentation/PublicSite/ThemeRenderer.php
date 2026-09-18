<?php
declare(strict_types=1);

namespace StageArtCore\Presentation\PublicSite;

use StageArtCore\Domain\Site\Theme;

if (!defined('ABSPATH')) exit;

final class ThemeRenderer
{
    public static function organizationCss(): string
    {
        $settings = get_option(Theme::ORG_OPTION, ['mode' => 'preset', 'preset' => 'light_standard', 'custom' => []]);
        return self::css(is_array($settings) ? $settings : [], 'organization');
    }

    public static function productionCss(int $productionId): string
    {
        $settings = get_post_meta($productionId, Theme::PRODUCTION_META, true);
        if (!is_array($settings)) $settings = ['mode' => 'preset', 'preset' => 'light_standard', 'custom' => []];
        return self::css($settings, 'production');
    }

    /** @param array<string,mixed> $settings */
    private static function css(array $settings, string $site): string
    {
        $presets = Theme::presets($site);
        $preset = (string)($settings['preset'] ?? 'light_standard');
        $values = $presets[$preset] ?? $presets['light_standard'];
        if (($settings['mode'] ?? 'preset') === 'custom' && is_array($settings['custom'] ?? null)) $values = array_merge($values, $settings['custom']);
        $font = static fn($name): string => match ((string)$name) {
            'serif' => 'Georgia, "Times New Roman", "Noto Serif JP", serif',
            'sans' => '-apple-system, BlinkMacSystemFont, "Noto Sans JP", "Yu Gothic", sans-serif',
            default => '-apple-system, BlinkMacSystemFont, "Noto Sans JP", "Yu Gothic", sans-serif',
        };
        $headerBg = ($values['mode'] ?? 'light') === 'dark' ? $values['surface'] : $values['primary']; $headerText = ($values['mode'] ?? 'light') === 'dark' ? $values['text'] : $values['surface']; $vars = '--stageart-bg:'.esc_attr((string)$values['bg']).';--stageart-surface:'.esc_attr((string)$values['surface']).';--stageart-text:'.esc_attr((string)$values['text']).';--stageart-primary:'.esc_attr((string)$values['primary']).';--stageart-accent:'.esc_attr((string)$values['accent']).';--stageart-border:'.esc_attr((string)$values['border']).';--stageart-header-bg:'.esc_attr((string)$headerBg).';--stageart-header-text:'.esc_attr((string)$headerText).';--stageart-muted:color-mix(in srgb, var(--stageart-text) 62%, transparent);--stageart-heading-font:'.$font($values['heading_font'] ?? 'system').';--stageart-body-font:'.$font($values['body_font'] ?? 'system').';';
        return ':root{'.$vars.'}body{background:var(--stageart-bg);color:var(--stageart-text);font-family:var(--stageart-body-font)}h1,h2,h3,h4,h5,h6{font-family:var(--stageart-heading-font);color:var(--stageart-primary)}a{color:var(--stageart-primary)}.stageart-card,.stageart-feature,.stageart-representative-greeting{background:var(--stageart-surface);border-color:var(--stageart-border)}.stageart-button{background:var(--stageart-primary);color:var(--stageart-surface)}';
    }
}
