<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

final class ProductionHeroAdmin
{
    private const OPTION_ACTION = 'stageart_production_action';
    private const META_TYPE = 'hero_image_type';
    private const META_PRESET = 'hero_image_preset';
    private const META_IMAGE = 'hero_image_id';

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_footer', [$this, 'footer'], 30);
        add_action('admin_post_stageart_save_production', [$this, 'save'], 5);
    }

    public function assets(string $hook): void
    {
        if (($this->page() === 'stageart-productions') && current_user_can('manage_options')) {
            wp_enqueue_media();
        }
    }

    private function page(): string
    {
        return (string) ($_GET['page'] ?? '');
    }

    private function isProductionEditor(): bool
    {
        return $this->page() === 'stageart-productions'
            && (isset($_GET['id']) || isset($_GET['new']))
            && current_user_can('manage_options');
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

    public function save(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || get_post_type($id) !== 'stageart_production') {
            return;
        }
        $type = sanitize_key((string) ($_POST['hero_image_type'] ?? ''));
        $preset = sanitize_key((string) ($_POST['hero_image_preset'] ?? ''));
        $image = absint($_POST['hero_image_id'] ?? 0);
        $valid = [];
        foreach ($this->manifest() as $item) {
            if (!empty($item['id'])) {
                $valid[(string) $item['id']] = true;
            }
        }

        if ($type === 'preset' && isset($valid[$preset])) {
            update_post_meta($id, self::META_TYPE, 'preset');
            update_post_meta($id, self::META_PRESET, $preset);
            delete_post_meta($id, self::META_IMAGE);
            return;
        }
        if ($type === 'custom' && $image > 0 && get_post_type($image) === 'attachment') {
            update_post_meta($id, self::META_TYPE, 'custom');
            update_post_meta($id, self::META_IMAGE, (string) $image);
            delete_post_meta($id, self::META_PRESET);
            return;
        }
        delete_post_meta($id, self::META_TYPE);
        delete_post_meta($id, self::META_PRESET);
        delete_post_meta($id, self::META_IMAGE);
    }

    public function footer(): void
    {
        if (!$this->isProductionEditor()) {
            return;
        }
        $id = (int) ($_GET['id'] ?? 0);
        $type = (string) get_post_meta($id, self::META_TYPE, true);
        $preset = (string) get_post_meta($id, self::META_PRESET, true);
        $image = absint(get_post_meta($id, self::META_IMAGE, true));
        $presets = $this->manifest();
        ?>
        <style>
            #stageart-production-hero-settings .sa-production-presets{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;max-width:900px}
            #stageart-production-hero-settings .sa-production-preset{border:1px solid #c3c4c7;background:#fff;padding:0;cursor:pointer;text-align:left;border-radius:3px;overflow:hidden}
            #stageart-production-hero-settings .sa-production-preset.is-selected{outline:3px solid #2271b1}
            #stageart-production-hero-settings .sa-production-preset img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover}
            #stageart-production-hero-settings .sa-production-preset span{display:block;padding:7px 9px;font-weight:600}
            #stageart-production-hero-settings .sa-production-mode{margin:0 0 12px}
            #stageart-production-hero-settings .sa-production-custom{margin-top:12px}
            #stageart-production-hero-settings .sa-production-custom img{display:block;max-width:360px;height:auto;margin:8px 0}
            @media(max-width:900px){#stageart-production-hero-settings .sa-production-presets{grid-template-columns:repeat(2,minmax(0,1fr))}}
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('stageart-production-form');
            if (!form || document.getElementById('stageart-production-hero-settings')) return;
            const row = document.createElement('tr');
            row.innerHTML = '<th>公演Hero画像</th><td><div id="stageart-production-hero-settings"></div></td>';
            const target = Array.from(form.querySelectorAll('tr')).find(function (tr) {
                return tr.querySelector('th') && tr.querySelector('th').textContent.trim() === 'メイン画像';
            });
            if (target) target.parentNode.insertBefore(row, target.nextSibling);
            const root = row.querySelector('#stageart-production-hero-settings');
            if (!root) return;
            root.innerHTML = <?php echo wp_json_encode($this->html($type, $preset, $image, $presets)); ?>;
            const typeInputs = root.querySelectorAll('input[name="hero_image_type"]');
            const presetInput = root.querySelector('input[name="hero_image_preset"]');
            const customId = root.querySelector('input[name="hero_image_id"]');
            const customPreview = root.querySelector('.sa-production-custom-preview');
            function refresh() {
                const type = root.querySelector('input[name="hero_image_type"]:checked')?.value || '';
                root.querySelector('.sa-production-presets-wrap').style.display = type === 'preset' ? '' : 'none';
                root.querySelector('.sa-production-custom').style.display = type === 'custom' ? '' : 'none';
            }
            typeInputs.forEach(function (input) { input.addEventListener('change', refresh); });
            root.querySelectorAll('.sa-production-preset').forEach(function (button) {
                button.addEventListener('click', function () {
                    presetInput.value = button.dataset.preset;
                    root.querySelectorAll('.sa-production-preset').forEach(function (item) { item.classList.remove('is-selected'); });
                    button.classList.add('is-selected');
                });
            });
            const picker = root.querySelector('.sa-production-media-picker');
            if (picker) picker.addEventListener('click', function () {
                const frame = wp.media({title:'公演Hero画像を選択',button:{text:'この画像を使用'},multiple:false,library:{type:'image'}});
                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    customId.value = attachment.id;
                    customPreview.innerHTML = attachment.url ? '<img src="' + attachment.url.replace(/"/g, '&quot;') + '" alt="">' : '';
                });
                frame.open();
            });
            const clear = root.querySelector('.sa-production-media-clear');
            if (clear) clear.addEventListener('click', function () { customId.value = ''; customPreview.innerHTML = ''; });
            refresh();
        });
        </script>
        <?php
    }

    private function html(string $type, string $selected, int $image, array $presets): string
    {
        $html = '<div class="sa-production-mode">';
        $html .= '<label><input type="radio" name="hero_image_type" value="preset" ' . checked($type ?: 'preset', 'preset', false) . '> プリセットから選択</label>　';
        $html .= '<label><input type="radio" name="hero_image_type" value="custom" ' . checked($type, 'custom', false) . '> 独自画像を使用</label></div>';
        $html .= '<div class="sa-production-presets-wrap"><p>公演の世界観に合わせた抽象イメージを選択できます。</p><div class="sa-production-presets">';
        foreach ($presets as $item) {
            $id = sanitize_key((string) ($item['id'] ?? ''));
            $file = basename((string) ($item['file'] ?? ''));
            $label = (string) ($item['label'] ?? $id);
            $category = (string) ($item['category'] ?? '');
            $src = STAGEART_CORE_URL . 'assets/hero/production/' . rawurlencode($file);
            $class = $selected === $id ? ' is-selected' : '';
            $html .= '<button type="button" class="sa-production-preset' . $class . '" data-preset="' . esc_attr($id) . '"><img src="' . esc_url($src) . '" alt=""><span>' . esc_html($category . ' / ' . $label) . '</span></button>';
        }
        $html .= '</div><input type="hidden" name="hero_image_preset" value="' . esc_attr($selected) . '"></div>';
        $html .= '<div class="sa-production-custom"><input type="hidden" name="hero_image_id" value="' . (int) $image . '"><button type="button" class="button sa-production-media-picker">メディアライブラリから画像を選択</button> <button type="button" class="button sa-production-media-clear">クリア</button><div class="sa-production-custom-preview">' . ($image ? wp_get_attachment_image($image, 'medium') : '') . '</div></div>';
        return $html;
    }
}
