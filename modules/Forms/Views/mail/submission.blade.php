{{--
    Письмо с заявкой.

    Простая таблица без внешних картинок и скриптов: почтовые клиенты режут
    и то и другое, а письмо должно читаться в любом.
--}}
<div style="font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#1f2430;line-height:1.55">
    <h2 style="margin:0 0 4px;font-size:18px">{{ $form->title }}</h2>
    <p style="margin:0 0 16px;color:#6b7280;font-size:13px">
        {{ __('forms.mail_intro', ['date' => $submission->created_at->format('d.m.Y H:i')]) }}
    </p>

    <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px">
        @foreach($rows as $row)
            <tr>
                <td style="padding:8px 12px;border:1px solid #e5e7eb;background:#f9fafb;width:34%;vertical-align:top">
                    <strong>{{ $row['label'] }}</strong>
                </td>
                <td style="padding:8px 12px;border:1px solid #e5e7eb;vertical-align:top">
                    {{ $row['value'] !== '' ? $row['value'] : '—' }}
                </td>
            </tr>
        @endforeach
    </table>

    <p style="margin:16px 0 0;color:#6b7280;font-size:12px">
        {{ __('forms.mail_footer', ['ip' => $submission->ip ?: '—']) }}
    </p>
</div>
