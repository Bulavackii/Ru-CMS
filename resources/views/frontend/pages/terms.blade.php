@extends('layouts.frontend')

@section('title', 'Пользовательское соглашение')

@push('styles')
<style>
  /* удобочитаемость и печать */
  .doc h2 { scroll-margin-top: 6rem; }
  .doc .section li + li{ margin-top:.25rem }
  .doc .lead { text-wrap: balance; }
  @media print{
    .no-print{ display:none !important; }
    .card{ box-shadow:none !important; border-color:#ddd !important; }
  }
</style>
@endpush

@php
  // Домен сайта берём динамически, чтобы не править вручную при переносах
  $siteHost = parse_url(url('/'), PHP_URL_HOST);
@endphp

@section('content')
<section class="doc max-w-5xl mx-auto bg-white border border-gray-200 rounded-2xl shadow-lg p-6 md:p-10 text-[15px] leading-relaxed text-gray-800 space-y-8">

  {{-- HERO --}}
  <header class="text-center space-y-3">
    <h1 class="text-3xl md:text-4xl font-extrabold text-blue-800">📄 Пользовательское соглашение</h1>
    <p class="lead text-gray-600">
      Документ регулирует условия доступа и использования сайта <span class="font-medium">{{ $siteHost }}</span>, {{-- было spil46.ru --}}
      форм обратной связи и публикуемого контента. Используя сайт, вы подтверждаете согласие с настоящими условиями.
    </p>

    <div class="inline-flex items-center gap-3 text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-full px-3 py-1">
      <span class="inline-flex items-center gap-1">@themeIcon('calendar','w-3.5 h-3.5') Обновлено: {{ date('d.m.Y') }}</span>
      <span class="hidden sm:inline">•</span>
      <span class="inline-flex items-center gap-1">@themeIcon('map','w-3.5 h-3.5') Юрисдикция: РФ, г.&nbsp;Курск</span>
    </div>
  </header>

  {{-- ОГЛАВЛЕНИЕ --}}
  <nav aria-label="Оглавление" class="no-print">
    <div class="card bg-white border border-gray-200 rounded-xl p-4">
      <h2 class="text-sm font-semibold text-gray-700 mb-2">@themeIcon('list','w-4 h-4') Содержание</h2>
      <ol class="grid sm:grid-cols-2 gap-1 text-[14px] list-decimal pl-5">
        <li><a class="text-blue-700 hover:underline" href="#def">Термины и акцепт</a></li>
        <li><a class="text-blue-700 hover:underline" href="#scope">Предмет и функции сайта</a></li>
        <li><a class="text-blue-700 hover:underline" href="#user">Права и обязанности пользователя</a></li>
        <li><a class="text-blue-700 hover:underline" href="#admin">Права и обязанности администрации</a></li>
        <li><a class="text-blue-700 hover:underline" href="#content">Контент, IP и модерация</a></li>
        <li><a class="text-blue-700 hover:underline" href="#forms">Заявки, переписка и уведомления</a></li>
        <li><a class="text-blue-700 hover:underline" href="#privacy">Персональные данные и cookies</a></li>
        <li><a class="text-blue-700 hover:underline" href="#liability">Гарантии и ответственность</a></li>
        <li><a class="text-blue-700 hover:underline" href="#law">Претензии, применимое право</a></li>
        <li><a class="text-blue-700 hover:underline" href="#final">Изменения и заключительные положения</a></li>
      </ol>
    </div>
  </nav>

  {{-- 1. Термины и акцепт --}}
  <section id="def" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">1. Термины и акцепт</h2>
    <ul class="list-disc pl-6">
      <li><strong>Сайт</strong> — интернет-ресурс <span class="font-medium">{{ $siteHost }}</span> и его поддомены.</li> {{-- было spil46.ru --}}
      <li><strong>Администрация</strong>, <strong>мы</strong> — владельцы и уполномоченные представители сайта.</li>
      <li><strong>Пользователь</strong>, <strong>вы</strong> — любое лицо, посещающее сайт или использующее его функциональность.</li>
      <li>Нажимая кнопки, отправляя формы и/или продолжая использовать сайт, вы совершаете <strong>акцепт</strong> настоящего соглашения.</li>
      <li>Если вы не согласны с условиями — прекратите использование сайта.</li>
      <li>Сайт предназначен для пользователей старше 18 лет; при использовании вы подтверждаете, что обладаете необходимой дееспособностью.</li>
    </ul>
  </section>

  {{-- 2. Предмет и функции сайта --}}
  <section id="scope" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">2. Предмет и функции сайта</h2>
    <ul class="list-disc pl-6">
      <li>Сайт предоставляет информацию об услугах по уходу за зелёными насаждениями (спил, расчистка, вывоз и пр.), примеры работ и контактные данные.</li>
      <li>Через формы сайта можно направлять заявки/вопросы. Заключение договоров и окончательные условия оказываемых услуг согласуются индивидуально по результатам осмотра и переписки.</li>
      <li>Сайт не является интернет-магазином и не осуществляет онлайн-оплату по умолчанию.</li>
    </ul>
  </section>

  {{-- 3. Права и обязанности пользователя --}}
  <section id="user" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">3. Права и обязанности пользователя</h2>
    <ul class="list-disc pl-6">
      <li>Предоставлять достоверные данные при отправке форм, не выдавать себя за иное лицо.</li>
      <li>Не совершать действий, способных нарушить работу сайта, распространить вредоносный код, ботов или спам.</li>
      <li>Соблюдать законодательство РФ, права третьих лиц и нормы сетевого этикета.</li>
      <li>Самостоятельно обеспечивать конфиденциальность своей переписки и устройств, с которых осуществляется доступ.</li>
      <li>Имеете право: получать информацию, отправлять обращения, отзывать согласие на обработку персональных данных (см. Политику конфиденциальности).</li>
    </ul>
  </section>

  {{-- 4. Права и обязанности администрации --}}
  <section id="admin" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">4. Права и обязанности администрации</h2>
    <ul class="list-disc pl-6">
      <li>Поддерживать работоспособность сайта в разумных пределах; возможны перерывы по техническим причинам.</li>
      <li>Модерировать и/или удалять материалы, нарушающие закон или настоящее соглашение.</li>
      <li>Ограничивать доступ к сайту пользователю при выявленных нарушениях, злоупотреблениях или угрозах безопасности.</li>
      <li>Обновлять содержание сайта и настоящее соглашение без предварительного уведомления; актуальная версия всегда доступна на этой странице.</li>
    </ul>
  </section>

  {{-- 5. Контент, IP и модерация --}}
  <section id="content" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">5. Контент, права интеллектуальной собственности и модерация</h2>
    <ul class="list-disc pl-6">
      <li>Тексты, фото, видео, графика и иные материалы сайта защищены законодательством об авторском праве и принадлежат Администрации либо используются на законных основаниях.</li>
      <li>Любое копирование и публикация материалов сайта допустимы только с предварительного согласия Администрации, если явно не указано иное.</li>
      <li>Размещая отзывы/сообщения через формы, вы предоставляете Администрации <em>безвозмездную неисключительную лицензию</em> на их использование, редактирование и публикацию на сайте, если иное не оговорено.</li>
      <li>Если вы считаете, что на сайте нарушены ваши права, направьте уведомление на
        <a class="text-blue-700 hover:underline" href="mailto:Suglobov2015@mail.ru">Suglobov2015@mail.ru</a>; {{-- было buchnev.ae@gmail.com --}}
        мы оперативно рассмотрим обращение.
      </li>
    </ul>
  </section>

  {{-- 6. Заявки, переписка и уведомления --}}
  <section id="forms" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">6. Заявки, переписка и уведомления</h2>
    <ul class="list-disc pl-6">
      <li>Отправляя форму, вы соглашаетесь на получение ответов по указанным контактам (телефон, e-mail, мессенджеры).</li>
      <li>Сроки ответа ориентировочные и зависят от нагрузки; Администрация не гарантирует доступность связи 24/7.</li>
      <li>Расчёты стоимости носят предварительный характер до осмотра объекта и подтверждения технической возможности выполнения работ.</li>
    </ul>
  </section>

  {{-- 7. Персональные данные и cookies --}}
  <section id="privacy" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">7. Персональные данные и cookies</h2>
    <p>
      Порядок и цели обработки персональных данных, а также сведения об используемых файлах cookies
      описаны в нашей <a class="text-blue-700 hover:underline" href="{{ url('/privacy') }}">Политике конфиденциальности</a>.
      Используя сайт, вы подтверждаете ознакомление с ней и согласие на обработку данных в указанных там целях.
    </p>
  </section>

  {{-- 8. Гарантии и ответственность --}}
  <section id="liability" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">8. Гарантии и ответственность</h2>
    <ul class="list-disc pl-6">
      <li>Сайт и его содержимое предоставляются по принципу «как есть» без каких-либо явно выраженных или подразумеваемых гарантий.</li>
      <li>Администрация не несёт ответственность за временную недоступность сайта, потерю данных вследствие действий третьих лиц, форс-мажор и иные обстоятельства, не зависящие от нас.</li>
      <li>В пределах, допустимых законом, совокупная ответственность Администрации ограничивается фактически понесёнными Пользователем прямыми убытками, но не более стоимости реально оказанных услуг (если такие оказывались).</li>
      <li>Пользователь несёт ответственность за достоверность предоставленных сведений и последствия действий, совершённых с его устройств/аккаунтов.</li>
    </ul>
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
      <p class="text-sm">
        <strong>Важно:</strong> фотографии, цены и сроки на сайте носят информационный характер. Итоговая смета формируется после очного осмотра и закрепляется в переписке/договоре.
      </p>
    </div>
  </section>

  {{-- 9. Претензионный порядок и право --}}
  <section id="law" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">9. Претензионный порядок и применимое право</h2>
    <ul class="list-disc pl-6">
      <li>
        Споры решаются в претензионном порядке: направьте суть требований на
        <a class="text-blue-700 hover:underline" href="mailto:Suglobov2015@mail.ru">Suglobov2015@mail.ru</a>. {{-- было buchnev.ae@gmail.com --}}
        Срок рассмотрения — до 10 рабочих дней.
      </li>
      <li>К отношениям сторон применяется право Российской Федерации; подсудность — по месту нахождения Администрации (г.&nbsp;Курск), если иное не предусмотрено императивными нормами.</li>
    </ul>
  </section>

  {{-- 10. Изменения и заключительные положения --}}
  <section id="final" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">10. Изменения и заключительные положения</h2>
    <ul class="list-disc pl-6">
      <li>Администрация вправе вносить изменения в соглашение. Новая редакция вступает в силу с момента публикации на этой странице.</li>
      <li>Недействительность отдельного положения не влияет на действительность остальных.</li>
      <li>
        По вопросам соглашения пишите на
        <a class="text-blue-700 hover:underline" href="mailto:Suglobov2015@mail.ru">Suglobov2015@mail.ru</a>
        или звоните
        <a class="text-blue-700 hover:underline" href="tel:+79300373536">8&nbsp;(930)&nbsp;037-35-36</a>
        <span class="text-gray-500">(городской: 305-008)</span>. {{-- было 8 (915) 515-25-94 --}}
      </li>
    </ul>
  </section>

  {{-- Кнопки --}}
  <div class="flex flex-col sm:flex-row items-center justify-center gap-3 no-print">
    <a href="{{ url('/') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg shadow transition">
      @themeIcon('home','w-4 h-4') На главную
    </a>
    <a href="#def" class="inline-flex items-center gap-2 px-5 py-3 rounded-lg border border-gray-300 hover:bg-gray-50 transition">
      @themeIcon('arrow-up','w-4 h-4') В начало документа
    </a>
    <button type="button" onclick="window.print()"
      class="inline-flex items-center gap-2 px-5 py-3 rounded-lg border border-gray-300 hover:bg-gray-50 transition">
      @themeIcon('eye','w-4 h-4') Версия для печати
    </button>
  </div>
</section>
@endsection
