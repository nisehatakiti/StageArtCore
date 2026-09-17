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

    private function presets(): array
    {
        $path = STAGEART_CORE_DIR . 'assets/hero/manifest.json';
        $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : [];
        if (!is_array($data)) return [];
        $base = trailingslashit(STAGEART_CORE_URL . 'assets/hero');
        $out = [];
        foreach ((array) ($data['presets'] ?? []) as $p) {
            if (!is_array($p)) continue;
            $id = sanitize_key((string) ($p['id'] ?? ''));
            $file = basename((string) ($p['file'] ?? ''));
            if ($id === '' || $file === '') continue;
            $out[] = ['id'=>$id,'label'=>sanitize_text_field((string) ($p['label'] ?? $id)),'url'=>$base . rawurlencode($file)];
        }
        return $out;
    }

    public function styles(): void
    {
        if (!current_user_can('manage_options') || ($_GET['page'] ?? '') !== 'stageart-homepage') return;
        echo '<style>.sa-hero-block{margin:22px 0;padding:18px;background:#fff;border:1px solid #ccd0d4}.sa-hero-block h2{margin-top:0}.sa-hero-presets{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:14px;margin:12px 0 18px}.sa-hero-preset{position:relative;padding:0;border:2px solid #dcdcde;background:#fff;cursor:pointer;border-radius:4px;overflow:hidden;text-align:left}.sa-hero-preset.is-selected{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.sa-hero-preset img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;background:#111}.sa-hero-preset span{display:block;padding:8px 10px;font-weight:600}.sa-hero-check{position:absolute!important;top:8px;right:8px;background:#2271b1;color:#fff;border-radius:50%;width:24px;padding:0!important;line-height:24px;text-align:center;display:none}.sa-hero-preset.is-selected .sa-hero-check{display:block}.sa-hero-preview{max-width:520px;margin-top:12px}.sa-hero-preview img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;background:#111;border:1px solid #ccd0d4}@media(max-width:900px){.sa-hero-presets{grid-template-columns:repeat(2,minmax(150px,1fr))}}</style>';
    }

    public function script(): void
    {
        if (!current_user_can('manage_options') || ($_GET['page'] ?? '') !== 'stageart-homepage') return;
        $presets = wp_json_encode($this->presets(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $saved = get_option(self::OPTION, []); if (!is_array($saved)) $saved = [];
        $state = wp_json_encode(['enabled'=>!empty($saved['enabled']),'type'=>($saved['background_type'] ?? 'preset'),'preset'=>sanitize_key((string)($saved['background_preset'] ?? 'stage')),'url'=>esc_url_raw((string)($saved['background_url'] ?? ''))], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        echo '<script>(function(){const form=document.querySelector("form[action*=\"admin-post.php\"] input[name=\"action\"][value=\"stageart_save_homepage\"]")?.form;if(!form)return;const ps='.$presets.',s='.$state.';const b=document.createElement("fieldset");b.className="sa-hero-block";b.innerHTML="<legend><strong>Hero</strong></legend><p class=\"description\">トップページの独立したHeroブロックです。</p><p><label><input type=\"checkbox\" class=\"sa-hero-enabled\"> Heroを表示する</label></p><h3>背景画像</h3><p class=\"description\">StageArt標準の背景から選択できます。自分で用意した画像はWordPressメディアから選択します。</p><div class=\"sa-hero-presets\"></div><p><button type=\"button\" class=\"button sa-hero-media\">自分で用意した画像を選択</button> <button type=\"button\" class=\"button-link-delete sa-hero-clear\">自前画像を解除</button></p><div class=\"sa-hero-preview\"></div>";const hidden=(n,v)=>{let e=form.querySelector(`[name=\"${n}\"]`);if(!e){e=document.createElement("input");e.type="hidden";e.name=n;form.appendChild(e)}e.value=v||"";return e};const en=b.querySelector(".sa-hero-enabled"),grid=b.querySelector(".sa-hero-presets"),preview=b.querySelector(".sa-hero-preview"),ht=hidden("stageart_hero_enabled",s.enabled?"1":"0"),ty=hidden("stageart_hero_background_type",s.type),pr=hidden("stageart_hero_background_preset",s.preset),ur=hidden("stageart_hero_background_url",s.url);en.checked=s.enabled;function previewImg(){preview.innerHTML="";const src=s.type==="custom"?s.url:(ps.find(x=>x.id===s.preset)?.url||"");if(src)preview.innerHTML=`<img src="${src}" alt="Hero背景プレビュー">`}function pick(id){s.type="preset";s.preset=id;s.url="";ty.value="preset";pr.value=id;ur.value="";grid.querySelectorAll("button").forEach(x=>x.classList.toggle("is-selected",x.dataset.id===id));previewImg()}ps.forEach(x=>{const q=document.createElement("button");q.type="button";q.className="sa-hero-preset";q.dataset.id=x.id;q.innerHTML=`<span class="sa-hero-check">✓</span><img src="${x.url}" alt=""><span>${x.label}</span>`;q.onclick=()=>pick(x.id);grid.appendChild(q)});if(s.type==="preset")pick(s.preset);else previewImg();en.onchange=()=>{s.enabled=en.checked;ht.value=s.enabled?"1":"0"};b.querySelector(".sa-hero-media").onclick=()=>{if(!window.wp?.media)return;const f=wp.media({title:"Hero背景画像を選択",button:{text:"この画像を使用"},multiple:false,library:{type:"image"}});f.on("select",()=>{const a=f.state().get("selection").first().toJSON();if(!a?.url)return;s.type="custom";s.url=a.url;ty.value="custom";ur.value=a.url;grid.querySelectorAll("button").forEach(x=>x.classList.remove("is-selected"));previewImg()});f.open()};b.querySelector(".sa-hero-clear").onclick=()=>pick(s.preset||ps[0]?.id||"");form.insertBefore(b,form.querySelector("#stageart-home-sections")||form.firstElementChild);form.addEventListener("submit",()=>{ht.value=en.checked?"1":"0";ty.value=s.type;pr.value=s.preset;ur.value=s.url})})();</script>';
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
        $ids = array_column($this->presets(), 'id'); if (!in_array($preset, $ids, true)) $preset = $ids[0] ?? '';
        if ($type === 'custom' && $url === '') $type = 'preset';
        update_option(self::OPTION, ['enabled'=>!empty($_POST['stageart_hero_enabled']),'background_type'=>$type,'background_preset'=>$preset,'background_url'=>$url], false);
    }
}
