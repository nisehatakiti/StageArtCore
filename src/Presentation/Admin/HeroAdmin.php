<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;

final class HeroAdmin
{
    public const OPTION = 'stageart_core_hero';

    public function register(): void
    {
        add_action('admin_head', [$this, 'styles']);
        add_action('admin_footer', [$this, 'script']);
        add_action('admin_post_stageart_save_homepage', [$this, 'save'], 1);
    }

    private function readManifest(string $path, string $baseUrl, string $defaultCategory): array
    {
        $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : [];
        if (!is_array($data)) return [];
        $out = [];
        foreach ((array) ($data['presets'] ?? []) as $p) {
            if (!is_array($p)) continue;
            $id = sanitize_key((string) ($p['id'] ?? ''));
            $file = basename((string) ($p['file'] ?? ''));
            if ($id === '' || $file === '') continue;
            $out[] = [
                'id' => $id,
                'label' => sanitize_text_field((string) ($p['label'] ?? $id)),
                'category' => sanitize_text_field((string) ($p['category'] ?? $defaultCategory)),
                'url' => trailingslashit($baseUrl) . rawurlencode($file),
            ];
        }
        return $out;
    }

    private function presets(): array
    {
        $out = $this->readManifest(
            STAGEART_CORE_DIR . 'assets/hero/manifest.json',
            STAGEART_CORE_URL . 'assets/hero',
            'StageArt標準'
        );

        $organizationDir = defined('STAGEART_HERO_ASSETS_DIR')
            ? STAGEART_HERO_ASSETS_DIR . 'assets/hero/organization/'
            : STAGEART_CORE_DIR . 'assets/hero/organization/';
        $organizationUrl = defined('STAGEART_HERO_ASSETS_URL')
            ? STAGEART_HERO_ASSETS_URL . 'assets/hero/organization'
            : STAGEART_CORE_URL . 'assets/hero/organization';

        $organization = $this->readManifest(
            $organizationDir . 'manifest.json',
            $organizationUrl,
            '団体'
        );
        $seen = array_column($out, 'id');
        foreach ($organization as $preset) {
            if (!in_array($preset['id'], $seen, true)) {
                $out[] = $preset;
                $seen[] = $preset['id'];
            }
        }
        return $out;
    }

    public function styles(): void
    {
        if (!current_user_can('manage_options') || !in_array(($_GET['page'] ?? ''), ['stageart-homepage','stageart-organization'], true)) return;
        echo '<style>.sa-hero-block{margin:22px 0;padding:18px;background:#fff;border:1px solid #ccd0d4}.sa-hero-group{margin:18px 0}.sa-hero-group-title{margin:0 0 10px;font-size:15px}.sa-hero-presets{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:14px}.sa-hero-preset{position:relative;padding:0;border:2px solid #dcdcde;background:#fff;cursor:pointer;border-radius:4px;overflow:hidden;text-align:left}.sa-hero-preset.is-selected{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.sa-hero-preset img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;background:#111}.sa-hero-preset-label{display:block;padding:8px 10px;font-weight:600}.sa-hero-check{position:absolute!important;top:8px;right:8px;background:#2271b1;color:#fff;border-radius:50%;width:24px;padding:0!important;line-height:24px;text-align:center;display:none}.sa-hero-preset.is-selected .sa-hero-check{display:block}.sa-hero-preview{max-width:520px;margin-top:12px}.sa-hero-preview img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;background:#111;border:1px solid #ccd0d4}@media(max-width:900px){.sa-hero-presets{grid-template-columns:repeat(2,minmax(150px,1fr))}}</style>';
    }

    public function script(): void
    {
        if (!current_user_can('manage_options') || !in_array(($_GET['page'] ?? ''), ['stageart-homepage','stageart-organization'], true)) return;
        $presets = wp_json_encode($this->presets(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) $saved = [];
        $state = wp_json_encode([
            'enabled' => !empty($saved['enabled']),
            'type' => ($saved['background_type'] ?? 'preset'),
            'preset' => sanitize_key((string)($saved['background_preset'] ?? 'stage')),
            'url' => esc_url_raw((string)($saved['background_url'] ?? '')),
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $script = <<<'JS'
<script>(function(){
const organization=location.search.indexOf('page=stageart-organization')>=0;
let form=document.querySelector('form[action*="admin-post.php"] input[name="action"][value="stageart_save_homepage"]')?.form;
if(organization){
  form=document.createElement('form');
  form.method='post';form.action='__ADMIN_POST__';
  form.innerHTML='<input type="hidden" name="action" value="stageart_save_homepage">';
  const nonce=document.createElement('input');nonce.type='hidden';nonce.name="_wpnonce";nonce.value='__NONCE__';form.appendChild(nonce);
}
if(!form)return;
const ps=__PRESETS__,s=__STATE__;
const b=document.createElement('fieldset');
b.className='sa-hero-block';
b.innerHTML='<legend><strong>Hero</strong></legend><p class="description">'+(organization?'団体ページのHeroです。':'団体トップページの独立したHeroブロックです。')+'</p><p><label><input type="checkbox" class="sa-hero-enabled"> Heroを表示する</label></p><h3>背景画像</h3><p class="description">StageArt標準の背景から選択できます。自分で用意した画像はWordPressメディアから選択します。</p><div class="sa-hero-groups"></div><p><button type="button" class="button sa-hero-media">自分で用意した画像を選択</button> <button type="button" class="button-link-delete sa-hero-clear">自前画像を解除</button></p><div class="sa-hero-preview"></div>';
const hidden=(n,v)=>{let e=form.querySelector('[name="'+n+'"]');if(!e){e=document.createElement('input');e.type='hidden';e.name=n;form.appendChild(e)}e.value=v||'';return e};
const en=b.querySelector('.sa-hero-enabled'),groups=b.querySelector('.sa-hero-groups'),preview=b.querySelector('.sa-hero-preview');
const ht=hidden('stageart_hero_enabled',s.enabled?'1':'0'),ty=hidden('stageart_hero_background_type',s.type),pr=hidden('stageart_hero_background_preset',s.preset),ur=hidden('stageart_hero_background_url',s.url);
en.checked=s.enabled;
function previewImg(){preview.innerHTML='';const p=ps.find(x=>x.id===s.preset),src=s.type==='custom'?s.url:(p?.url||'');if(src){const img=document.createElement('img');img.src=src;img.alt='Hero背景プレビュー';preview.appendChild(img)}}
function pick(id){s.type='preset';s.preset=id;s.url='';ty.value='preset';pr.value=id;ur.value='';groups.querySelectorAll('button.sa-hero-preset').forEach(x=>x.classList.toggle('is-selected',x.dataset.id===id));previewImg()}
const grouped={};ps.forEach(x=>{(grouped[x.category]??=[]).push(x)});
Object.entries(grouped).forEach(([category,items])=>{const g=document.createElement('div');g.className='sa-hero-group';const title=document.createElement('h4');title.className='sa-hero-group-title';title.textContent=category;const grid=document.createElement('div');grid.className='sa-hero-presets';g.appendChild(title);g.appendChild(grid);items.forEach(x=>{const q=document.createElement('button');q.type='button';q.className='sa-hero-preset';q.dataset.id=x.id;const check=document.createElement('span');check.className='sa-hero-check';check.textContent='✓';const img=document.createElement('img');img.src=x.url;img.alt='';const label=document.createElement('span');label.className='sa-hero-preset-label';label.textContent=x.label;q.appendChild(check);q.appendChild(img);q.appendChild(label);q.onclick=()=>pick(x.id);grid.appendChild(q)});groups.appendChild(g)});
if(s.type==='preset')pick(s.preset);else previewImg();
en.onchange=()=>{s.enabled=en.checked;ht.value=s.enabled?'1':'0'};
b.querySelector('.sa-hero-media').onclick=()=>{if(!window.wp?.media)return;const f=wp.media({title:'Hero背景画像を選択',button:{text:'この画像を使用'},multiple:false,library:{type:'image'}});f.on('select',()=>{const a=f.state().get('selection').first().toJSON();if(!a?.url)return;s.type='custom';s.url=a.url;ty.value='custom';ur.value=a.url;groups.querySelectorAll('button.sa-hero-preset').forEach(x=>x.classList.remove('is-selected'));previewImg()});f.open()};
b.querySelector('.sa-hero-clear').onclick=()=>pick(s.preset||ps[0]?.id||'');
if(organization){form.appendChild(b);const panel=document.querySelector('.sa-org-panel');const cards=panel?.querySelectorAll('.sa-org-card');const theme=cards?.[0];if(panel)panel.insertBefore(form,theme||null)}else{form.insertBefore(b,form.querySelector('#stageart-home-sections')||form.firstElementChild);}
form.addEventListener('submit',()=>{ht.value=en.checked?'1':'0';ty.value=s.type;pr.value=s.preset;ur.value=s.url});
})();</script>
JS;
        $script = str_replace(['__PRESETS__', '__STATE__', '__NONCE__', '__ADMIN_POST__'], [$presets, $state, wp_create_nonce('stageart_homepage'), esc_url_raw(admin_url('admin-post.php'))], $script);
        echo $script;
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) return;
        $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? ''));
        if (!$nonce || !wp_verify_nonce($nonce, 'stageart_homepage')) return;
        $type = sanitize_key((string)($_POST['stageart_hero_background_type'] ?? 'preset'));
        if (!in_array($type, ['preset','custom'], true)) $type = 'preset';
        $preset = sanitize_key((string)($_POST['stageart_hero_background_preset'] ?? 'stage'));
        $url = esc_url_raw((string)($_POST['stageart_hero_background_url'] ?? ''));
        $ids = array_column($this->presets(), 'id');
        if (!in_array($preset, $ids, true)) $preset = $ids[0] ?? '';
        if ($type === 'custom' && $url === '') $type = 'preset';
        update_option(self::OPTION, [
            'enabled' => !empty($_POST['stageart_hero_enabled']),
            'background_type' => $type,
            'background_preset' => $preset,
            'background_url' => $url,
        ], false);
    }
}
