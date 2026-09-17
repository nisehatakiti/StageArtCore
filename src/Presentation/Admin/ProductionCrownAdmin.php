<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

if (!defined('ABSPATH')) exit;

final class ProductionCrownAdmin
{
    public function register(): void
    {
        add_action('admin_footer', [$this, 'footer'], 30);
        add_action('save_post_stageart_production', [$this, 'save'], 20, 3);
    }

    public function save(int $postId, \WP_Post $post, bool $update): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($postId)) return;
        if (!current_user_can('edit_post', $postId)) return;
        if (!isset($_POST['stageart_production_crown_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stageart_production_crown_nonce'])), 'stageart_production_crown')) return;

        $crown = isset($_POST['production_crown']) ? sanitize_text_field(wp_unslash($_POST['production_crown'])) : '';
        if ($crown === '') delete_post_meta($postId, 'production_crown');
        else update_post_meta($postId, 'production_crown', $crown);
    }

    public function footer(): void
    {
        if (($_GET['page'] ?? '') !== 'stageart-productions') return;
        if (!current_user_can('manage_options')) return;
        $id = (int) ($_GET['id'] ?? 0);
        $value = $id > 0 ? (string) get_post_meta($id, 'production_crown', true) : '';
        $nonce = wp_create_nonce('stageart_production_crown');
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('stageart-production-form');
            if (!form) return;
            const tables = form.querySelectorAll('.form-table');
            const table = tables[0];
            if (!table || table.querySelector('[name="production_crown"]')) return;
            const slug = form.querySelector('[name="slug"]');
            const row = document.createElement('tr');
            row.innerHTML = '<th><label for="sa-production-crown">公演冠</label></th><td><input id="sa-production-crown" class="regular-text" type="text" name="production_crown" value="<?php echo esc_js($value); ?>" /><p class="description">沿革などで「公演冠＋公演タイトル（会場）」の形式で表示できます。</p></td>';
            if (slug) {
                const slugRow = slug.closest('tr');
                if (slugRow) slugRow.insertAdjacentElement('afterend', row);
                else table.querySelector('tbody')?.appendChild(row);
            } else {
                table.querySelector('tbody')?.appendChild(row);
            }
            const nonce = document.createElement('input');
            nonce.type = 'hidden';
            nonce.name = 'stageart_production_crown_nonce';
            nonce.value = '<?php echo esc_js($nonce); ?>';
            form.appendChild(nonce);
        });
        </script>
        <?php
    }
}
