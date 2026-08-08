"""
Собирает один CSS со всеми гарнитурами содержимого.

Двадцать один отдельный файл на каждой странице сайта — двадцать один запрос
ради того, что почти всегда не понадобится. В одном файле это один запрос, а
сами woff2 браузер скачивает только для той гарнитуры, которая реально
встретилась в тексте.
"""
import io, os, re

ROOT = 'C:/Users/adm/Desktop/cms'
FONTS = os.path.join(ROOT, 'public/assets/fonts')
OUT = os.path.join(ROOT, 'public/assets/css/content-fonts.css')

order = [
    'inter', 'roboto', 'open-sans', 'montserrat', 'manrope', 'nunito', 'rubik',
    'raleway', 'fira-sans', 'noto-sans', 'golos-text', 'onest', 'pt-sans', 'oswald',
    'pt-serif', 'merriweather', 'lora', 'roboto-slab',
    'jetbrains-mono', 'roboto-mono', 'caveat',
]

parts = [
    '/*',
    ' * Гарнитуры для содержимого материалов.',
    ' *',
    ' * Собран из public/assets/fonts/<slug>/<slug>.css скриптом, руками не',
    ' * правится: правка исчезнет при следующей пересборке. Один файл вместо',
    ' * двадцати одного — это один запрос на страницу вместо двадцати одного,',
    ' * а сами woff2 браузер тянет только для той гарнитуры, которая реально',
    ' * встретилась в тексте.',
    ' *',
    ' * Лицензии: SIL Open Font License 1.1 у всех, кроме Roboto Slab',
    ' * (Apache License 2.0). Файлы лицензий лежат рядом со шрифтами, в',
    ' * public/assets/fonts/<slug>/LICENSE — удалять их нельзя, OFL требует',
    ' * распространять шрифт вместе с лицензией.',
    ' */',
    '',
]

total = 0

for slug in order:
    css_path = os.path.join(FONTS, slug, slug + '.css')

    if not os.path.exists(css_path):
        print('пропущен (нет файла):', slug)
        continue

    with io.open(css_path, encoding='utf-8') as fh:
        css = fh.read()

    # Пути внутри собранного файла считаются от public/assets/css/
    css = css.replace('url(./files/', 'url(../fonts/%s/files/' % slug)
    # Заголовок каждого исходника не нужен — он про отдельный файл
    css = re.sub(r'^/\* %s — self-hosted[^\n]*\n' % re.escape(slug), '', css)

    parts.append(css.strip())
    parts.append('')
    total += css.count('@font-face')

with io.open(OUT, 'w', encoding='utf-8', newline='\n') as fh:
    fh.write('\n'.join(parts))

print('собрано гарнитур:', len(order), 'начертаний:', total,
      'размер:', '{:,}'.format(os.path.getsize(OUT)), 'байт')
