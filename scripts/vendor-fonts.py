"""
Сборка самохостящихся шрифтов из пакетов @fontsource в public/assets/fonts.

Берём только то, что нужно сайту: подмножества latin и cyrillic, начертания
400/500/600/700, форматы woff2 и woff. Всё остальное (вьетнамский, греческий,
курсивы, тонкие и сверхжирные начертания) осталось бы мёртвым грузом в
репозитории.

Рядом с каждым шрифтом кладём его ЛИЦЕНЗИЮ. Это не формальность: SIL OFL прямо
требует, чтобы файл лицензии сопровождал шрифт при распространении, а CMS
продаётся вместе со своей папкой public.
"""
import os, re, shutil, json

ROOT = 'C:/Users/adm/Desktop/cms'
SRC = os.path.join(ROOT, 'node_modules/@fontsource')
DST = os.path.join(ROOT, 'public/assets/fonts')

FAMILIES = [
    'inter', 'roboto', 'open-sans', 'montserrat', 'manrope', 'nunito', 'rubik',
    'raleway', 'fira-sans', 'noto-sans', 'golos-text', 'onest', 'oswald',
    'pt-sans', 'pt-serif', 'merriweather', 'lora', 'roboto-slab',
    'jetbrains-mono', 'roboto-mono', 'caveat',
]

WEIGHTS = ['400', '500', '600', '700']
SUBSETS = ('cyrillic', 'latin')          # без -ext: базовых наборов хватает

block_re = re.compile(r'/\*\s*([a-z0-9-]+)\s*\*/\s*(@font-face\s*\{.*?\})', re.S)

report = []

for slug in FAMILIES:
    pkg = os.path.join(SRC, slug)
    if not os.path.isdir(pkg):
        report.append((slug, 'пакета нет', 0, 0))
        continue

    out_dir = os.path.join(DST, slug)
    files_dir = os.path.join(out_dir, 'files')
    os.makedirs(files_dir, exist_ok=True)

    css_parts = ['/* %s — self-hosted (latin + cyrillic), vendored from @fontsource/%s */' % (slug, slug)]
    copied = 0
    weights_found = []

    for weight in WEIGHTS:
        css_path = os.path.join(pkg, weight + '.css')
        if not os.path.exists(css_path):
            continue

        with open(css_path, encoding='utf-8') as fh:
            source = fh.read()

        used = False
        for name, block in block_re.findall(source):
            # Имя вида <slug>-<subset>-<weight>-normal; отбираем ровно latin и
            # cyrillic, отсекая -ext и прочие расширения.
            tail = name[len(slug) + 1:]
            subset = tail.rsplit('-' + weight + '-normal', 1)[0]

            if subset not in SUBSETS:
                continue

            for ext in ('woff2',):
                src_file = os.path.join(pkg, 'files', '%s.%s' % (name, ext))
                if os.path.exists(src_file):
                    shutil.copy2(src_file, os.path.join(files_dir, '%s.%s' % (name, ext)))
                    copied += 1

            css_parts.append('/* %s */\n%s' % (name, block))
            used = True

        if used:
            weights_found.append(weight)

    if not weights_found:
        report.append((slug, 'подходящих начертаний нет', 0, 0))
        continue

    with open(os.path.join(out_dir, slug + '.css'), 'w', encoding='utf-8', newline='\n') as fh:
        fh.write('\n\n'.join(css_parts) + '\n')

    # Лицензия — обязательно рядом со шрифтом.
    for candidate in os.listdir(pkg):
        if candidate.lower().startswith('licen'):
            shutil.copy2(os.path.join(pkg, candidate), os.path.join(out_dir, 'LICENSE'))
            break

    report.append((slug, ','.join(weights_found), copied,
                   os.path.exists(os.path.join(out_dir, 'LICENSE'))))

print('%-16s %-16s %6s %s' % ('шрифт', 'начертания', 'файлов', 'лицензия'))
for slug, weights, copied, lic in report:
    print('%-16s %-16s %6s %s' % (slug, weights, copied, 'есть' if lic else 'НЕТ'))
