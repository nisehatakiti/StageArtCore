from pathlib import Path

path = Path('theme/stageart/functions.php')
text = path.read_text(encoding='utf-8')
old = "if($type==='system'&&$ref==='about'){$desc=stageart_theme_org('description');if($desc)echo'<div class=\"stageart-feature\"><div class=\"stageart-feature-copy\"><p class=\"stageart-kicker\">About Us</p><h2>舞台をつくる。</h2><p>'.esc_html($desc).'</p><a class=\"stageart-button stageart-button--light\" href=\"'.esc_url(home_url('/contact/')).'\">お問い合わせ</a></div><div class=\"stageart-feature-image\" aria-hidden=\"true\"></div></div>';return;}"
new = "if($type==='system'&&$ref==='about'){$desc=stageart_theme_org('description');if($desc)echo'<div class=\"stageart-feature\"><div class=\"stageart-feature-copy\"><div>'.wp_kses_post(wpautop($desc)).'</div></div></div>';return;}"
if old not in text:
    raise SystemExit('target about renderer not found')
path.write_text(text.replace(old, new, 1), encoding='utf-8')
print('updated', path)
