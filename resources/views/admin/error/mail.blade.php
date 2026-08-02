{{--
    Письмо с отчётом об ошибке.

    Этой вьюхи в проекте не было вовсе, хотя контроллер ссылался на неё с
    самого начала: любая отправка формы падала с «View not found». Разметка
    намеренно простая и на таблицах — почтовые клиенты не поддерживают ни
    внешние стили, ни flex/grid.
--}}
<div style="font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; color: #111827; font-size: 14px; line-height: 1.55;">

    <h2 style="margin: 0 0 4px; font-size: 18px;">{{ __('admin.system.er_title') }}</h2>
    <p style="margin: 0 0 20px; color: #6b7280; font-size: 13px;">{{ config('app.name') }}</p>

    <div style="white-space: pre-wrap; background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px 16px; margin-bottom: 20px;">{{ $body }}</div>

    <table cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <tr>
            <td style="padding: 6px 12px 6px 0; color: #6b7280; white-space: nowrap;">{{ __('admin.system.er_ctx_user') }}</td>
            <td style="padding: 6px 0;">
                @if($user)
                    {{ $user->name }} &lt;{{ $user->email }}&gt;
                @else
                    —
                @endif
            </td>
        </tr>

        @if(!empty($email))
            <tr>
                <td style="padding: 6px 12px 6px 0; color: #6b7280; white-space: nowrap;">{{ __('admin.system.er_email') }}</td>
                <td style="padding: 6px 0;">{{ $email }}</td>
            </tr>
        @endif

        <tr>
            <td style="padding: 6px 12px 6px 0; color: #6b7280; white-space: nowrap;">{{ __('admin.system.er_ctx_ip') }}</td>
            <td style="padding: 6px 0; font-family: monospace;">{{ $ip ?: '—' }}</td>
        </tr>

        <tr>
            <td style="padding: 6px 12px 6px 0; color: #6b7280; white-space: nowrap; vertical-align: top;">{{ __('admin.system.er_ctx_ua') }}</td>
            <td style="padding: 6px 0; font-family: monospace; font-size: 12px; word-break: break-all;">{{ $user_agent ?: '—' }}</td>
        </tr>

        <tr>
            <td style="padding: 6px 12px 6px 0; color: #6b7280; white-space: nowrap; vertical-align: top;">{{ __('admin.system.er_ctx_url') }}</td>
            <td style="padding: 6px 0; word-break: break-all;">{{ $url ?: '—' }}</td>
        </tr>

        <tr>
            <td style="padding: 6px 12px 6px 0; color: #6b7280; white-space: nowrap;">{{ __('admin.system.f_time') }}</td>
            <td style="padding: 6px 0;">{{ now()->format('d.m.Y H:i:s') }}</td>
        </tr>
    </table>

    @if(!empty($file_path))
        <p style="margin: 20px 0 0; color: #6b7280; font-size: 13px;">
            {{ __('admin.system.er_file') }}: {{ basename($file_path) }}
        </p>
    @endif
</div>
