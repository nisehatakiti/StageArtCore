from pathlib import Path

router = Path('src/Presentation/PublicSite/ProductionRouter.php')
p = router.read_text(encoding='utf-8')
# Match the ticket typography exactly for list/timeline schedule output.
p = p.replace('.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-list{font-size:.92rem}', '.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-list{font-size:1rem}')
p = p.replace('.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-list{font-size:.78rem}', '.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-list{font-size:.84rem}')
p = p.replace('.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-list{font-size:.68rem}', '.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-list{font-size:.72rem}')
needle = '.stageart-production-layout-slot--tickets.stageart-size-l .stageart-ticket-list{font-size:1rem;line-height:1.8}'
if '.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline th' not in p:
    insert = '.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline td{font-size:1rem}.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline td{font-size:.84rem}.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline td{font-size:.72rem}'
    if needle not in p: raise SystemExit('ticket css anchor missing')
    p = p.replace(needle, insert + needle, 1)
router.write_text(p, encoding='utf-8')

# Compact the home-page production cards as well.
theme = Path('theme/stageart/style.css')
t = theme.read_text(encoding='utf-8')
if '.stageart-home-slot>.stageart-production-grid .stageart-card' not in t:
    anchor = '.stageart-home-slot>.stageart-production-grid{'
    if anchor not in t: raise SystemExit('theme production grid anchor missing')
    t = t.replace(anchor, anchor + 'grid-template-columns:repeat(auto-fit,minmax(min(100%,520px),520px));justify-content:center;', 1)
    t += '\n.stageart-home-slot>.stageart-production-grid .stageart-card{max-width:520px;width:100%;margin-left:auto;margin-right:auto}\n'
theme.write_text(t, encoding='utf-8')

# Bump theme asset version to prevent stale browser cache.
fn = Path('theme/stageart/functions.php')
f = fn.read_text(encoding='utf-8')
f = f.replace("$version='1.3.3'", "$version='1.3.4'")
fn.write_text(f, encoding='utf-8')

# Remove obsolete failed patch payloads so future builds have one source of truth.
for path in [Path('.github/stageart-finalize-schedule.py'), Path('.github/stageart-fix-script.b64')]:
    if path.exists(): path.unlink()
print('StageArt schedule finalization applied')
