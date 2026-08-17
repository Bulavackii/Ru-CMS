-- ═══════════════════════════════════════════════════════════════════════
--  Nexum Core: привести базу и роль PostgreSQL в соответствие с .env
--
--  В .env сейчас:  DB_USERNAME=nc   DB_DATABASE=nc
--  Старые имена:   rucms_dev / rucms
--
--  Запускать ПОД СУПЕРПОЛЬЗОВАТЕЛЕМ postgres, подключившись к базе postgres:
--
--    "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -h 127.0.0.1 -d postgres -f scripts/rename-database.sql
--
--  psql спросит пароль postgres сам — он нигде не сохраняется.
--
--  ⚠️ ПЕРЕД ЗАПУСКОМ остановите `php artisan serve` и закройте pgAdmin:
--     переименовать базу с активными подключениями PostgreSQL не даст.
--
--  ⚠️ Пароль роли при переименовании НЕ теряется: в pg_hba.conf стоит
--     scram-sha-256. Он обнулялся бы только при старом md5.
--
--  ⚠️ Данные при переименовании базы НЕ теряются — меняется только имя.
--     Таблицы, схема public и содержимое остаются на месте.
-- ═══════════════════════════════════════════════════════════════════════

\set ON_ERROR_STOP on

\echo ''
\echo '════ ЧТО ЕСТЬ СЕЙЧАС ════'

SELECT datname AS "база" FROM pg_database
WHERE datname IN ('rucms_dev', 'rucms', 'nc', 'nexum_core') ORDER BY 1;

SELECT rolname AS "роль", rolcreatedb AS "может создавать базы"
FROM pg_roles WHERE rolname IN ('rucms_dev', 'rucms', 'nc', 'nexum_core') ORDER BY 1;

-- ── 1. Роль ────────────────────────────────────────────────────────────
-- Переименование сохраняет права и владение объектами: роль опознаётся по
-- внутреннему номеру, а не по имени.
\echo ''
\echo '════ РОЛЬ ════'

ALTER ROLE rucms_dev RENAME TO nc;

-- Права, без которых Laravel не прогонит миграции в тестах
ALTER ROLE nc CREATEDB;

-- ── 2. База ────────────────────────────────────────────────────────────
\echo ''
\echo '════ БАЗА ════'

-- Отключаем чужие соединения, иначе переименование не пройдёт
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE datname = 'rucms_dev' AND pid <> pg_backend_pid();

ALTER DATABASE rucms_dev RENAME TO nc;
ALTER DATABASE nc OWNER TO nc;
GRANT ALL PRIVILEGES ON DATABASE nc TO nc;

-- ── 3. Что стало ───────────────────────────────────────────────────────
\echo ''
\echo '════ ЧТО СТАЛО ════'

SELECT d.datname AS "база", pg_get_userbyid(d.datdba) AS "владелец"
FROM pg_database d WHERE d.datname = 'nc';

\echo ''
\echo 'Готово. Пароль роли остался прежним — тот, что в DB_PASSWORD.'
\echo 'Если он в .env изменился, задайте новый:  ALTER ROLE nc PASSWORD ''…'';'
\echo ''
