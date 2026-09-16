from pathlib import Path

path = Path('src/Presentation/Admin/ProductionAdmin.php')
text = path.read_text(encoding='utf-8')
old = 'echo<<<JS\n'
new = '$script=<<<JS\n'
if old not in text:
    raise SystemExit('ProductionAdmin.php: broken heredoc marker not found')
path.write_text(text.replace(old, new, 1), encoding='utf-8')
print('ProductionAdmin.php heredoc assignment repaired')
