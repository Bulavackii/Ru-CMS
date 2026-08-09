# -*- coding: utf-8 -*-
"""
Скачивание наборов значков к себе на сервер.

Зачем скрипт, а не «однажды скачали руками»: набор надо будет обновлять, а
через полгода никто не вспомнит, откуда он взялся и какой был версии. Здесь
записаны и адрес, и версия, и лицензия — по нему набор восстанавливается с
нуля одной командой.

Правило проекта: наружу в рантайме не уходит НИ ОДИН запрос, поэтому вместе
с таблицей стилей качаются и файлы шрифта, а пути в стилях переписываются на
свои. Лицензия кладётся рядом: набор раздаётся с сайта, и условия должны
лежать там же, где файлы.

    python scripts/vendor-icons.py
"""

import io
import os
import re
import sys
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CSS_DIR = os.path.join(ROOT, 'public', 'assets', 'css')
FONT_DIR = os.path.join(ROOT, 'public', 'assets', 'icons')
LIC_DIR = os.path.join(ROOT, 'public', 'assets', 'icons', 'licenses')

# Только разрешительные лицензии: MIT и ISC позволяют использовать набор в
# закрытом продукте при сохранении текста лицензии. Наборы под платной или
# ограничивающей лицензией сюда не берём — репозиторий публичный, а CMS
# продаётся закрытой.
SETS = [
    {
        'slug': 'phosphor',
        'title': 'Phosphor Icons',
        'license': 'MIT',
        'version': '2.1.1',
        'css': 'https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css',
        'fonts': {
            'Phosphor.woff2': 'https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/Phosphor.woff2',
        },
        'license_url': 'https://raw.githubusercontent.com/phosphor-icons/homepage/master/LICENSE',
        'css_name': 'phosphor-icons.css',
    },
    {
        'slug': 'boxicons',
        'title': 'Boxicons',
        'license': 'MIT',
        'version': '2.1.4',
        'css': 'https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css',
        'fonts': {
            'boxicons.woff2': 'https://unpkg.com/boxicons@2.1.4/fonts/boxicons.woff2',
        },
        'license_url': 'https://raw.githubusercontent.com/atisawd/boxicons/master/LICENSE',
        'css_name': 'boxicons.css',
    },
]


def fetch(url):
    request = urllib.request.Request(url, headers={'User-Agent': 'ru-cms-vendor'})

    with urllib.request.urlopen(request, timeout=60) as response:
        return response.read()


def main():
    for folder in (CSS_DIR, FONT_DIR, LIC_DIR):
        os.makedirs(folder, exist_ok=True)

    for item in SETS:
        print('=' * 60)
        print(item['title'], item['version'], '·', item['license'])

        css = fetch(item['css']).decode('utf-8')

        for name, url in item['fonts'].items():
            data = fetch(url)
            path = os.path.join(FONT_DIR, name)

            with open(path, 'wb') as handle:
                handle.write(data)

            print('  шрифт %s — %d КБ' % (name, len(data) // 1024))

        # Все адреса шрифтов переписываем на свой каталог. В исходных стилях
        # они относительные и указывают внутрь чужого пакета — на сервере по
        # ним ничего нет, и значки молча не отрисуются.
        def rewrite(match):
            url = match.group(1).strip('\'"')
            base = os.path.basename(url.split('?')[0].split('#')[0])

            for name in item['fonts']:
                if base.lower() == name.lower():
                    return 'url(/assets/icons/%s)' % name

            return 'url(/assets/icons/%s)' % base

        css = re.sub(r'url\(([^)]+)\)', rewrite, css)

        # Оставляем только те начертания, файлы которых мы действительно
        # скачали: ссылка на отсутствующий eot или ttf даёт 404 в консоли на
        # каждой странице.
        keep = set(name.lower() for name in item['fonts'])
        css = re.sub(
            r'url\(/assets/icons/([^)]+)\)\s*format\([^)]+\)[,;]?',
            lambda m: m.group(0) if m.group(1).lower() in keep else '',
            css,
        )
        # Отдельно убираем src без format() — так записан устаревший eot.
        # Он остаётся первым в списке источников и даёт 404 на каждой
        # странице: файл мы не качаем, он нужен только очень старым браузерам.
        css = re.sub(
            r'url\(/assets/icons/([^)]+\.eot[^)]*)\)[,;]?',
            lambda m: '' if m.group(1).split('.')[0].lower() + '.eot' not in keep else m.group(0),
            css,
        )
        css = re.sub(r'src\s*:\s*,', 'src:', css)
        css = re.sub(r',\s*;', ';', css)
        css = re.sub(r'src:\s*;', '', css)

        header = (
            '/* %s %s — %s.\n'
            '   Источник: %s\n'
            '   Лицензия: /assets/icons/licenses/%s.txt\n'
            '   Файл получен скриптом scripts/vendor-icons.py — правки руками потеряются. */\n'
        ) % (item['title'], item['version'], item['license'], item['css'], item['slug'])

        with io.open(os.path.join(CSS_DIR, item['css_name']), 'w', encoding='utf-8') as handle:
            handle.write(header + css)

        print('  стили %s — %d КБ' % (item['css_name'], len(css) // 1024))

        try:
            text = fetch(item['license_url']).decode('utf-8', 'replace')
        except Exception as error:
            print('  ЛИЦЕНЗИЯ НЕ СКАЧАЛАСЬ:', error)
            sys.exit(1)

        with io.open(os.path.join(LIC_DIR, item['slug'] + '.txt'), 'w', encoding='utf-8') as handle:
            handle.write(text)

        print('  лицензия сохранена (%d символов)' % len(text))

    print('=' * 60)
    print('готово')


if __name__ == '__main__':
    main()
