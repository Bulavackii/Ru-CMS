<?php

namespace Modules\Forms\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Services\FormService;

/**
 * Приём заявок с сайта.
 *
 * Маршрут публичный — его дёргает посетитель. Всё, что приходит с формы,
 * считается недоверенным: правила проверки строятся из описания формы на
 * сервере, а не из полей запроса.
 */
class FormSubmissionController extends Controller
{
    public function __construct(private readonly FormService $forms)
    {
    }

    public function store(Request $request, string $slug)
    {
        $form = Form::findActive($slug);

        if (! $form) {
            abort(404);
        }

        $back = $this->safeReturnUrl($request);

        // Ловушка. Отвечаем как при успехе: сообщать боту, что его узнали, —
        // значит помочь обойти проверку в следующий раз. Заявка при этом
        // никуда не пишется.
        if (filled($request->input(FormService::HONEYPOT))) {
            return redirect($back)->with('form_sent', $form->slug);
        }

        $throttleKey = 'form:' . $form->id . ':' . $request->ip();
        $limit = (array) config('forms.throttle', []);

        if (RateLimiter::tooManyAttempts($throttleKey, (int) ($limit['attempts'] ?? 5))) {
            return redirect($back)->with('form_errors_' . $form->slug, [
                __('forms.too_often', ['minutes' => (int) ($limit['minutes'] ?? 10)]),
            ]);
        }

        try {
            $this->checkCaptcha($form, $request);
            $values = $this->forms->validate($form, $this->payload($form, $request));
            $values = $this->storeUploads($form, $request, $values);
        } catch (ValidationException $exception) {
            // Ошибки уезжают в сессию под ключом с именно этой формы: на
            // странице их может быть несколько, и общий errors-мешок показал
            // бы чужие ошибки над каждой.
            return redirect($back)
                ->with('form_errors_' . $form->slug, $exception->validator->errors()->all())
                ->with('form_old_' . $form->slug, (array) $request->input('fields', []));
        }

        RateLimiter::hit($throttleKey, (int) ($limit['minutes'] ?? 10) * 60);

        $submission = FormSubmission::create([
            'form_id'    => $form->id,
            'data'       => $values,
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'page'       => substr((string) $request->input('_return', ''), 0, 255),
        ]);

        $form->increment('submissions_count');

        $this->notify($form, $submission);

        // Своя страница благодарности, если задана; иначе возврат к форме с
        // сообщением — посетитель остаётся там, где заполнял.
        $redirect = $form->setting('redirect_url');

        return $redirect
            ? redirect($redirect)
            : redirect($back)->with('form_sent', $form->slug);
    }

    /**
     * Ответы вместе с вложениями.
     *
     * `input()` файлы НЕ содержит — они лежат отдельно, в `file()`. Без этого
     * поле с файлом проверялось как пустое, и обязательное вложение отбивалось
     * сообщением «поле обязательно» ровно тогда, когда файл был приложен.
     */
    private function payload(Form $form, Request $request): array
    {
        $payload = (array) $request->input('fields', []);

        foreach ($form->normalizedFields() as $field) {
            if ($field['type'] !== 'file') {
                continue;
            }

            $file = $request->file('fields.' . $field['name']);

            if ($file) {
                $payload[$field['name']] = $file;
            } else {
                unset($payload[$field['name']]);
            }
        }

        return $payload;
    }

    /**
     * Возврат туда, откуда пришли, — но только на свой сайт.
     *
     * Адрес приезжает полем формы, то есть его подставит кто угодно: без этой
     * проверки форма стала бы открытым перенаправлением на чужой сайт.
     */
    private function safeReturnUrl(Request $request): string
    {
        $candidate = (string) $request->input('_return', '');

        if ($candidate !== '' && str_starts_with($candidate, url('/'))) {
            return $candidate;
        }

        return url()->previous();
    }

    /** Проверка каптчи, если форма её требует. */
    private function checkCaptcha(Form $form, Request $request): void
    {
        if (! $form->setting('captcha') || ! app()->bound('captcha')) {
            return;
        }

        Validator::make($request->all(), ['captcha' => 'required|captcha'])->validate();
    }

    /**
     * Вложения кладутся на ПРИВАТНЫЙ диск.
     *
     * В заявке может приехать паспорт или договор, а всё, что попадает в
     * storage/app/public, доступно по прямой ссылке без авторизации. Скачивают
     * их из панели отдельным маршрутом под auth+admin.
     */
    private function storeUploads(Form $form, Request $request, array $values): array
    {
        foreach ($form->normalizedFields() as $field) {
            if ($field['type'] !== 'file') {
                continue;
            }

            $file = $request->file('fields.' . $field['name']);

            if (! $file) {
                unset($values[$field['name']]);
                continue;
            }

            $path = $file->store(
                trim((string) config('forms.upload_dir', 'form-uploads'), '/') . '/' . $form->id,
                config('forms.upload_disk', 'local')
            );

            // В заявке храним и путь, и исходное имя: путь обезличен (Laravel
            // даёт случайное имя), а показать в панели нужно то, как файл
            // назывался у отправителя.
            $values[$field['name']] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];
        }

        return $values;
    }

    /**
     * Оповещение на почту.
     *
     * Заявка уже сохранена, поэтому сбой почты не должен ронять ответ
     * посетителю: он свою часть выполнил. Ошибка уходит в журнал.
     */
    private function notify(Form $form, FormSubmission $submission): void
    {
        $to = array_filter(array_map('trim', explode(',', (string) $form->setting('notify_email'))));

        if ($to === []) {
            return;
        }

        try {
            Mail::send('Forms::mail.submission', [
                'form'       => $form,
                'submission' => $submission,
                'rows'       => $submission->readableData(),
            ], function ($message) use ($to, $form) {
                $message->to($to)->subject(__('forms.mail_subject', ['form' => $form->title]));
            });
        } catch (\Throwable $exception) {
            Log::warning('Не удалось отправить оповещение о заявке', [
                'form'  => $form->slug,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
