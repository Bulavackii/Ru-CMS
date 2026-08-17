<#
.SYNOPSIS
    Nexum Core: переименование роли и базы PostgreSQL под новое имя проекта.

.DESCRIPTION
    Переименовать роль и базу может только суперпользователь, а его пароль
    утерян. Единственный способ восстановить доступ — временно разрешить
    локальный вход без пароля (`trust` в pg_hba.conf).

    Скрипт делает это на минимальное время и САМ возвращает настройки обратно:
    восстановление стоит в блоке finally, то есть отработает даже если
    переименование упадёт с ошибкой или скрипт прервут.

    Пароль роли приложения при переименовании НЕ теряется: в pg_hba.conf стоит
    scram-sha-256, а он переживает смену имени (обнулялся бы только старый md5).
    Значит, DB_PASSWORD в .env менять не придётся.

.NOTES
    🛑 Пока действует trust, локальная база открыта БЕЗ ПАРОЛЯ любому
       пользователю этой машины. Окно — несколько секунд, но запускать стоит
       тогда, когда за компьютером больше никого нет.

    ⚠️ Требуются права администратора: pg_hba.conf лежит в Program Files, а
       службу надо перезапустить.

    ⚠️ Закройте `php artisan serve` и всё, что держит соединение с базой:
       переименовать базу с активными подключениями PostgreSQL не даст.

.EXAMPLE
    # Запуск из каталога проекта, в консоли «от имени администратора»:
    pwsh -ExecutionPolicy Bypass -File scripts/переименовать-базу.ps1

.EXAMPLE
    # Со своими именами
    pwsh -File scripts/переименовать-базу.ps1 -OldName rucms_dev -NewName nexum_core
#>

[CmdletBinding()]
param(
    [string] $OldName = 'rucms_dev',
    [string] $NewName = 'nexum_core',
    [string] $PgRoot  = 'C:\Program Files\PostgreSQL\18',
    [string] $Service = 'postgresql-x64-18'
)

$ErrorActionPreference = 'Stop'

# ── Проверки до того, как что-то менять ────────────────────────────────
$admin = ([Security.Principal.WindowsPrincipal] `
    [Security.Principal.WindowsIdentity]::GetCurrent()
).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $admin) {
    Write-Error 'Нужны права администратора: pg_hba.conf лежит в Program Files, а службу надо перезапустить.'
    exit 1
}

$hba  = Join-Path $PgRoot 'data\pg_hba.conf'
$psql = Join-Path $PgRoot 'bin\psql.exe'

foreach ($p in @($hba, $psql)) {
    if (-not (Test-Path $p)) {
        Write-Error "Не нашёл: $p. Укажите свой путь через -PgRoot."
        exit 1
    }
}

# Копия рядом с оригиналом, с отметкой времени: если что-то пойдёт не так,
# восстановить можно вручную и через месяц.
$backup = "$hba.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Copy-Item $hba $backup
Write-Host "Копия pg_hba.conf: $backup" -ForegroundColor DarkGray

$restored = $false

function Restore-Hba {
    if ($script:restored) { return }
    Copy-Item $backup $hba -Force
    & sc.exe stop $Service   | Out-Null
    Start-Sleep -Seconds 2
    & sc.exe start $Service  | Out-Null
    Start-Sleep -Seconds 3
    $script:restored = $true
    Write-Host 'pg_hba.conf возвращён, вход снова только по паролю.' -ForegroundColor Green
}

try {
    # ── 1. Временно разрешаем вход без пароля ──────────────────────────
    Write-Host ''
    Write-Host 'Временно включаю trust (несколько секунд)...' -ForegroundColor Yellow

    # ⚠️ Пишем через WriteAllLines с явной кодировкой БЕЗ BOM.
    #    `Set-Content -Encoding UTF8` в Windows PowerShell 5.1 добавляет BOM,
    #    а PostgreSQL, встретив его в первой строке pg_hba.conf, откажется
    #    запускаться — и сервер уже не поднимется, чтобы это исправить.
    #
    #    Проверено на настоящем файле: правило попадает ровно в три строки
    #    (local, host 127.0.0.1, host ::1), строки репликации остаются под
    #    паролем.
    $изменённые = (Get-Content $hba) `
        -replace '^(local\s+all\s+all\s+)scram-sha-256', '$1trust' `
        -replace '^(host\s+all\s+all\s+127\.0\.0\.1/32\s+)scram-sha-256', '$1trust' `
        -replace '^(host\s+all\s+all\s+::1/128\s+)scram-sha-256', '$1trust'

    [System.IO.File]::WriteAllLines($hba, $изменённые, (New-Object System.Text.UTF8Encoding($false)))

    & sc.exe stop $Service  | Out-Null
    Start-Sleep -Seconds 2
    & sc.exe start $Service | Out-Null
    Start-Sleep -Seconds 4

    # ── 2. Что есть сейчас ─────────────────────────────────────────────
    Write-Host ''
    Write-Host 'Состояние до правки:' -ForegroundColor Cyan
    & $psql -U postgres -h 127.0.0.1 -d postgres -tAc `
        "select 'база: '||datname from pg_database where datname in ('$OldName','$NewName')"
    & $psql -U postgres -h 127.0.0.1 -d postgres -tAc `
        "select 'роль: '||rolname from pg_roles where rolname in ('$OldName','$NewName')"

    # ── 3. Роль ────────────────────────────────────────────────────────
    $roleExists = & $psql -U postgres -h 127.0.0.1 -d postgres -tAc `
        "select 1 from pg_roles where rolname='$OldName'"

    if ($roleExists -eq '1') {
        Write-Host ''
        Write-Host "Переименовываю роль $OldName -> $NewName" -ForegroundColor Cyan
        & $psql -U postgres -h 127.0.0.1 -d postgres -v ON_ERROR_STOP=1 -c `
            "ALTER ROLE `"$OldName`" RENAME TO `"$NewName`""
    } else {
        Write-Host "Роли $OldName нет — пропускаю" -ForegroundColor DarkGray
    }

    # ── 4. База ────────────────────────────────────────────────────────
    $dbExists = & $psql -U postgres -h 127.0.0.1 -d postgres -tAc `
        "select 1 from pg_database where datname='$OldName'"

    if ($dbExists -eq '1') {
        Write-Host "Переименовываю базу $OldName -> $NewName (данные сохраняются)" -ForegroundColor Cyan

        # Обрываем чужие соединения: иначе переименование не пройдёт
        & $psql -U postgres -h 127.0.0.1 -d postgres -tAc `
            "select pg_terminate_backend(pid) from pg_stat_activity where datname='$OldName' and pid <> pg_backend_pid()" | Out-Null

        & $psql -U postgres -h 127.0.0.1 -d postgres -v ON_ERROR_STOP=1 -c `
            "ALTER DATABASE `"$OldName`" RENAME TO `"$NewName`""
    } else {
        Write-Host "Базы $OldName нет — пропускаю" -ForegroundColor DarkGray
    }

    # ── 5. Владение и права ────────────────────────────────────────────
    & $psql -U postgres -h 127.0.0.1 -d postgres -v ON_ERROR_STOP=1 `
        -c "ALTER DATABASE `"$NewName`" OWNER TO `"$NewName`"" `
        -c "ALTER ROLE `"$NewName`" CREATEDB" | Out-Null

    # С PostgreSQL 15 без этого миграции падают на схеме public
    & $psql -U postgres -h 127.0.0.1 -d "$NewName" -v ON_ERROR_STOP=1 `
        -c "GRANT ALL ON SCHEMA public TO `"$NewName`"" `
        -c "ALTER SCHEMA public OWNER TO `"$NewName`"" | Out-Null

    Write-Host ''
    Write-Host 'Состояние после правки:' -ForegroundColor Cyan
    & $psql -U postgres -h 127.0.0.1 -d postgres -tAc `
        "select datname||' (владелец '||pg_get_userbyid(datdba)||')' from pg_database where datname='$NewName'"
}
finally {
    # Возврат настроек — ВСЕГДА, даже если выше что-то упало.
    Write-Host ''
    Restore-Hba
}

# ── 6. Проверка, что пароль по-прежнему требуется ──────────────────────
$env:PGPASSWORD = 'заведомо-неверный-пароль'
& $psql -U postgres -h 127.0.0.1 -d postgres -tAc 'select 1' 2>&1 | Out-Null
$env:PGPASSWORD = $null

if ($LASTEXITCODE -eq 0) {
    Write-Host ''
    Write-Warning 'ВНИМАНИЕ: вход всё ещё проходит без пароля. Проверьте pg_hba.conf вручную!'
    Write-Host "Копия исходного файла: $backup"
} else {
    Write-Host 'Проверено: без пароля вход не проходит.' -ForegroundColor Green
}

# ── 7. Правим .env ─────────────────────────────────────────────────────
#
# ⚠️ Только ПОСЛЕ проверки, что под новым именем реально можно подключиться.
#    Записать новое имя в .env, не убедившись в этом, — ровно тот способ
#    положить сайт, с которого начались все хлопоты.
#
# DB_PASSWORD не трогаем: пароль пережил переименование (scram-sha-256).

$envPath = Join-Path (Split-Path $PSScriptRoot -Parent) '.env'

if (-not (Test-Path $envPath)) {
    Write-Warning "Файл .env не найден ($envPath) — поправьте DB_DATABASE и DB_USERNAME вручную."
    return
}

# Пароль читаем из самого .env, чтобы нигде его не показывать
$пароль = (Select-String -Path $envPath -Pattern '^DB_PASSWORD="?([^"]*)"?$').Matches.Groups[1].Value

$env:PGPASSWORD = $пароль
& $psql -U $NewName -h 127.0.0.1 -d $NewName -tAc 'select 1' 2>&1 | Out-Null
$подключение = ($LASTEXITCODE -eq 0)
$env:PGPASSWORD = $null

if (-not $подключение) {
    Write-Warning 'Под новым именем подключиться не удалось — .env НЕ тронут.'
    Write-Host   'Приложение продолжит работать со старым именем. Разберитесь и запустите скрипт снова.'
    return
}

Write-Host ''
Write-Host "Подключение под ролью $NewName проверено — правлю .env" -ForegroundColor Cyan

# Копия рядом: возврат на случай, если что-то не так
Copy-Item $envPath "$envPath.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"

$строки = Get-Content $envPath
$строки = $строки `
    -replace '^DB_DATABASE=.*', "DB_DATABASE=`"$NewName`"" `
    -replace '^DB_USERNAME=.*', "DB_USERNAME=`"$NewName`""

[System.IO.File]::WriteAllLines($envPath, $строки, (New-Object System.Text.UTF8Encoding($false)))

Write-Host ''
Write-Host 'Готово полностью:' -ForegroundColor Green
Write-Host "  база и роль    -> $NewName"
Write-Host '  .env           -> обновлён (пароль не менялся)'
Write-Host '  pg_hba.conf    -> возвращён, вход только по паролю'
Write-Host ''
Write-Host 'Осталось: php artisan config:clear   и запустить сервер.'
