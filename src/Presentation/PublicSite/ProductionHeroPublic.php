<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\PublicSite;

final class ProductionHeroPublic
{
    public function register(): void
    {
        add_action('wp_head', [$this, 'styles'], 30);
    }

    private function manifest(): array
    {
        $path = STAGEART_CORE_DIR . 'assets/hero/production/manifest.json';
        if (!is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) && is_array($data['presets'] ?? null) ? $data['presets'] : [];
    }

    public function styles(): void
    {
        $slug = get_query_var('stageart_production_slug');
        if (!is_string($slug) || $slug === '') {
            return;
        }
        $post = get_page_by_path($slug, OBJECT, 'stageart_production');
        if (!$post || $post->post_status !== 'publish') {
            return;
        }
        $type = sanitize_key((string) get_post_meta($post->ID, 'hero_image_type', true));
        $url = '';
        if ($type === 'preset') {
            $selected = sanitize_key((string) get_post_meta($post->ID, 'hero_image_preset', true));
            foreach ($this->manifest() as $item) {
                if (($item['id'] ?? '') === $selected && !empty($item['file'])) {
                    $url = STAGEART_CORE_URL . 'assets/hero/production/' . rawurlencode(basename((string) $item['file']));
                    break;
                }
            }
        } elseif ($type === 'custom') {
            $image = absint(get_post_meta($post->ID, 'hero_image_id', true));
            if ($image) {
                $url = (string) wp_get_attachment_image_url($image, 'full');
            }
        }
        if ($url === '') {
            return;
        }
        echo '<style id="stageart-production-hero-image">.stageart-production-hero-image{background-image:url("' . esc_url($url) . '");background-size:cover;background-position:center center}.stageart-production-hero-image-placeholder{display:none}</style>';
    }
}
