from pathlib import Path

root = Path(__file__).resolve().parents[1]
router = root / 'src/Presentation/PublicSite/ProductionRouter.php'
text = router.read_text(encoding='utf-8')
old = '''        if ($display === 'both' && !empty($x['symbol'])) {\n            return trim((string) $x['symbol'] . ' ' . (string) $x['label_name']);\n        }\n        return $display === 'symbol' && !empty($x['symbol']) ? (string) $x['symbol'] : $marker;'''
new = '''        // 公演スケジュール上はラベル名を付加せず、記号（A/Bなど）のみ表示する。\n        return !empty($x['symbol']) ? (string) $x['symbol'] : $marker;'''
if old not in text:
    raise SystemExit('ProductionRouter.php: performanceCell target not found')
router.write_text(text.replace(old, new, 1), encoding='utf-8')

admin = root / 'src/Presentation/Admin/ProductionAdmin.php'
text = admin.read_text(encoding='utf-8')
old_admin = '<option value="both" '.selected($g(\'label_display\'),\'both\',false).'>記号＋ラベル</option>'
if old_admin in text:
    text = text.replace(old_admin, '', 1)
admin.write_text(text, encoding='utf-8')

print('symbol-only schedule display patch applied')
