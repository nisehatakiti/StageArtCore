<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\PublicSite;

final class OrganizationHeroPublic
{
    public function register(): void
    {
        add_action('wp_head', [$this, 'styles'], 30);
    }

    private function presets(): array
    {
        $organizationDir = defined('STAGEART_HERO_ASSETS_DIR')
            ? STAGEART_HERO_ASSETS_DIR . 'assets/hero/organization/'
            : STAGEART_CORE_DIR . 'assets/hero/organization/';
        $organizationUrl = defined('STAGEART_HERO_ASSETS_URL')
            ? STAGEART_HERO_ASSETS_URL . 'assets/hero/organization'
            : STAGEART_CORE_URL . 'assets/hero/organization';

        $manifests = [
            [STAGEART_CORE_DIR . 'assets/hero/manifest.json', STAGEART_CORE_URL . 'assets/hero'],
            [$organizationDir . 'manifest.json', $organizationUrl],
        ];
        $out = [];
        foreach ($manifests as [$path, $baseUrl]) {
            if (!is_readable($path)) continue;
            $data = json_decode((string) file_get_contents($path), true);
            if (!is_array($data)) continue;
            foreach ((array) ($data['presets'] ?? []) as $item) {
                if (!is_array($item)) continue;
                $id = sanitize_key((string) ($item['id'] ?? ''));
                $file = basename((string) ($item['file'] ?? ''));
                if ($id === '' || $file === '' || isset($out[$id])) continue;
                $out[$id] = trailingslashit($baseUrl) . rawurlencode($file);
            }
        }
        return $out;
    }

    public function styles(): void
    {
        if (!is_front_page()) return;
        $saved = get_option('stageart_core_hero', []);
        if (!is_array($saved) || empty($saved['enabled'])) return;

        $url = '';
        $type = sanitize_key((string) ($saved['background_type'] ?? 'preset'));
        if ($type === 'custom') {
            $url = esc_url_raw((string) ($saved['background_url'] ?? ''));
        } else {
            $preset = sanitize_key((string) ($saved['background_preset'] ?? 'stage'));
            $url = $this->presets()[$preset] ?? '';
        }
        if ($url === '') return;

        echo '<style id="stageart-organization-hero-image">.stageart-hero{background-image:linear-gradient(rgba(0,0,0,.22),rgba(0,0,0,.22)),url("' . esc_url($url) . '");background-size:cover;background-position:center center;background-repeat:no-repeat}</style>';
    }
}
