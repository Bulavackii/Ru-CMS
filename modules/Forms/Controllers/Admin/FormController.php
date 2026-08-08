<?php

namespace Modules\Forms\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Services\FormService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Конструктор форм.
 *
 * Устроен как конструктор каптчи: сборка мышью слева, живое превью справа
 * (строится тем же сервисом, что выводит форму на сайте, — значит в превью
 * видно ровно то, что получит посетитель), ниже список сохранённых форм с
 * готовыми сниппетами для вставки.
 */
class FormController extends Controller
{
    public function __construct(private readonly FormService $forms)
    {
    }

    public function index(): View
    {
        $forms = Form::query()
            ->withCount(['submissions as unread_count' => fn ($query) => $query->where('is_read', false)])
            ->orderBy('title')
            ->get();

        return view('Forms::admin.index', [
            'forms'      => $forms,
            'fieldTypes' => Form::FIELD_TYPES,
            'captchas'   => $this->captchaPresets(),
            'blank'      => $this->blank(),
            'starters'   => $this->starters(),
        ]);
    }

    /**
     * Живое превью.
     *
     * Форма НЕ сохраняется: собирается временный объект и отдаётся тому же
     * шаблону, что работает на сайте. Иначе превью показывало бы отдельную,
     * «примерную» разметку, и расхождение с реальностью нашлось бы уже у
     * посетителя.
     */
    public function preview(Request $request): JsonResponse
    {
        $form = new Form([
            'title'       => (string) $request->input('title', ''),
            'description' => (string) $request->input('description', ''),
            'fields'      => (array) $request->input('fields', []),
            'settings'    => (array) $request->input('settings', []),
        ]);

        // Слаг нужен разметке (якорь, адрес отправки), но записи ещё нет.
        $form->slug = 'preview';
        $form->exists = true;

        return response()->json(['html' => $this->forms->render($form, ['preview' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $form = Form::create($data);

        return redirect()
            ->route('admin.forms.index')
            ->with('success', __('admin.forms.created', ['title' => $form->title]));
    }

    public function update(Request $request, Form $form)
    {
        $form->update($this->validated($request));

        return redirect()
            ->route('admin.forms.index')
            ->with('success', __('admin.forms.updated', ['title' => $form->title]));
    }

    public function duplicate(Form $form)
    {
        $copy = $form->replicate(['submissions_count']);
        $copy->title = $form->title . ' — ' . __('admin.forms.copy_suffix');
        $copy->slug = null;
        $copy->submissions_count = 0;
        $copy->save();

        return redirect()
            ->route('admin.forms.index')
            ->with('success', __('admin.forms.duplicated', ['title' => $copy->title]));
    }

    public function destroy(Form $form)
    {
        $title = $form->title;
        $form->delete();

        return redirect()
            ->route('admin.forms.index')
            ->with('success', __('admin.forms.deleted', ['title' => $title]));
    }

    /** Заявки по форме. */
    public function submissions(Request $request, Form $form): View
    {
        $submissions = $form->submissions()
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('Forms::admin.submissions', compact('form', 'submissions'));
    }

    public function markRead(Form $form, FormSubmission $submission): JsonResponse
    {
        $this->assertBelongs($form, $submission);

        $submission->update(['is_read' => ! $submission->is_read]);

        return response()->json(['success' => true, 'is_read' => $submission->is_read]);
    }

    public function destroySubmission(Form $form, FormSubmission $submission)
    {
        $this->assertBelongs($form, $submission);

        $this->deleteUploads($submission);
        $submission->delete();

        return redirect()
            ->route('admin.forms.submissions', $form)
            ->with('success', __('admin.forms.submission_deleted'));
    }

    /**
     * Скачивание вложения.
     *
     * Файл лежит на приватном диске, путь берётся из заявки, а не из запроса —
     * подставить чужой путь и вытащить произвольный файл нельзя.
     */
    public function download(Form $form, FormSubmission $submission, string $field): StreamedResponse
    {
        $this->assertBelongs($form, $submission);

        $attachment = data_get($submission->data, $field);

        if (! is_array($attachment) || empty($attachment['path'])) {
            abort(404);
        }

        $disk = Storage::disk(config('forms.upload_disk', 'local'));

        if (! $disk->exists($attachment['path'])) {
            abort(404);
        }

        return $disk->download($attachment['path'], $attachment['name'] ?? basename($attachment['path']));
    }

    /**
     * Проверка данных конструктора.
     *
     * Имена полей приводятся к безопасному виду здесь, а не в браузере: они
     * становятся ключами массива запроса и подписями в заявке.
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:500',
            'is_active'          => 'nullable|boolean',
            'fields'             => 'required|array|min:1',
            'fields.*.type'      => 'required|string|in:' . implode(',', Form::FIELD_TYPES),
            'fields.*.label'     => 'nullable|string|max:255',
            'fields.*.name'      => 'nullable|string|max:64',
            'fields.*.icon'      => 'nullable|string|max:40',
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.hint'      => 'nullable|string|max:255',
            'fields.*.value'     => 'nullable|string|max:255',
            'fields.*.required'  => 'nullable|boolean',
            'fields.*.width'     => 'nullable|string|in:full,half',
            'fields.*.options'   => 'nullable|array|max:60',
            'fields.*.options.*' => 'nullable|string|max:255',
            'settings'           => 'nullable|array',
            'settings.submit_label'    => 'nullable|string|max:64',
            'settings.success_message' => 'nullable|string|max:500',
            'settings.note'            => 'nullable|string|max:255',
            'settings.notify_email'    => 'nullable|string|max:255',
            'settings.redirect_url'    => 'nullable|string|max:255',
            'settings.captcha'         => 'nullable|string|max:64',
            'settings.icon'            => 'nullable|string|max:40',
            'settings.columns'         => 'nullable|boolean',
            'settings.show_title'      => 'nullable|boolean',
        ]);

        $used = [];
        $fields = [];
        $hasUpload = false;

        foreach ($validated['fields'] as $index => $field) {
            $name = $this->safeName($field['name'] ?? '', $field['label'] ?? '', $index, $used);
            $used[] = $name;

            if (($field['type'] ?? '') === 'file') {
                $hasUpload = true;
            }

            $fields[] = [
                'type'        => $field['type'],
                'name'        => $name,
                'icon'        => Form::safeIcon($field['icon'] ?? ''),
                'label'       => (string) ($field['label'] ?? ''),
                'placeholder' => (string) ($field['placeholder'] ?? ''),
                'hint'        => (string) ($field['hint'] ?? ''),
                'value'       => (string) ($field['value'] ?? ''),
                'required'    => (bool) ($field['required'] ?? false),
                'width'       => $field['width'] ?? 'full',
                'options'     => array_values(array_filter(
                    array_map(fn ($option) => trim((string) $option), (array) ($field['options'] ?? [])),
                    fn ($option) => $option !== ''
                )),
            ];
        }

        $settings = (array) ($validated['settings'] ?? []);
        $settings['icon'] = Form::safeIcon($settings['icon'] ?? '');
        // Признак вложений считаем сами: на нём стоит enctype формы, и
        // полагаться на галочку, которую можно забыть, нельзя — без
        // multipart файл просто не доедет.
        $settings['has_upload'] = $hasUpload;

        return [
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_active'   => (bool) ($validated['is_active'] ?? false),
            'fields'      => $fields,
            'settings'    => $settings,
        ];
    }

    /**
     * Имя поля: латиница, цифры и подчёркивание.
     *
     * Из него собирается `fields[имя]` в запросе. Кириллица и пробелы там
     * работают не везде одинаково, а совпадающие имена молча затирали бы друг
     * друга — второе поле «Телефон» перезаписывало бы первое.
     */
    private function safeName(string $name, string $label, int $index, array $used): string
    {
        $candidate = $name !== '' ? $name : $label;
        $candidate = \Illuminate\Support\Str::slug($candidate, '_');
        $candidate = preg_replace('~[^a-z0-9_]~', '', strtolower($candidate)) ?: '';

        if ($candidate === '' || $candidate === '_') {
            $candidate = 'field_' . ($index + 1);
        }

        $base = $candidate;
        $suffix = 2;

        while (in_array($candidate, $used, true)) {
            $candidate = $base . '_' . $suffix++;
        }

        return $candidate;
    }

    private function assertBelongs(Form $form, FormSubmission $submission): void
    {
        abort_unless($submission->form_id === $form->id, 404);
    }

    private function deleteUploads(FormSubmission $submission): void
    {
        $disk = Storage::disk(config('forms.upload_disk', 'local'));

        foreach ((array) $submission->data as $value) {
            if (is_array($value) && ! empty($value['path'])) {
                $disk->delete($value['path']);
            }
        }
    }

    /** Сборки каптчи для выпадающего списка настроек. */
    private function captchaPresets(): array
    {
        if (! class_exists(\Modules\Captcha\Models\CaptchaPreset::class)) {
            return [];
        }

        return \Modules\Captcha\Models\CaptchaPreset::activeList()
            ->map(fn ($preset) => ['slug' => $preset->slug, 'name' => $preset->name])
            ->values()->all();
    }

    /**
     * Готовые наборы полей для быстрого старта.
     *
     * Берутся из ОПРЕДЕЛЕНИЙ форм по умолчанию — тех же, что заводит сидер при
     * установке. Второй список «примерных» форм здесь неизбежно разошёлся бы с
     * первым, а собирать анкету с нуля — самый долгий путь.
     */
    private function starters(): array
    {
        $icons = ['obratnaya-svyaz' => 'fa-envelope', 'zayavka' => 'fa-file-signature', 'zapis-na-priem' => 'fa-calendar-check'];

        return array_map(fn (array $definition) => [
            'key'    => $definition['slug'],
            'title'  => $definition['title'],
            'icon'   => $icons[$definition['slug']] ?? 'fa-list-check',
            'fields' => array_map(fn (array $field) => $field + [
                'placeholder' => '', 'hint' => '', 'value' => '',
                'required' => false, 'width' => 'full', 'options' => [],
            ], $definition['fields']),
        ], \Modules\Forms\Console\Commands\SeedDefaultFormsCommand::definitions());
    }

    /** Заготовка новой формы для конструктора. */
    private function blank(): array
    {
        return [
            'title'       => '',
            'description' => '',
            'is_active'   => true,
            // Пусто намеренно: рядом стоит быстрый старт готовыми наборами,
            // а три поля «на всякий случай» пришлось бы сначала удалять.
            'fields'      => [],
            'settings'    => [
                'submit_label'    => '',
                'success_message' => '',
                'note'            => '',
                'notify_email'    => '',
                'redirect_url'    => '',
                'captcha'         => '',
                'icon'            => '',
                'columns'         => true,
                'show_title'      => true,
            ],
        ];
    }
}
