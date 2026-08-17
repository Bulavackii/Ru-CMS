-- ═══════════════════════════════════════════════════════════════════════
--  Nexum Core: создать роль и базу с нуля
--
--  Нужен, если старой базы (rucms_dev) уже нет — тогда переименовывать
--  нечего, поднимаем чистую. Содержимое ставится потом мастером установки
--  либо сидерами.
--
--  Запускать ПОД СУПЕРПОЛЬЗОВАТЕЛЕМ postgres:
--
--    "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -h 127.0.0.1 -d postgres -f scripts/create-database.sql
--
--  ⚠️ Пароль в строке ниже ЗАМЕНИТЕ на тот, что стоит в DB_PASSWORD в .env.
--     Они обязаны совпадать, иначе приложение не подключится.
--
--  ⚠️ Кодировка и правила сортировки заданы явно и СВЕРЕНЫ с текущей базой
--     (UTF8 + Russian_Russia.1251). По умолчанию Windows-сборка PostgreSQL
--     берёт системную кодировку, и русские тексты получают другой порядок
--     сортировки — это всплывает не сразу, а на первом же списке материалов,
--     отсортированном по названию.
-- ═══════════════════════════════════════════════════════════════════════

\set ON_ERROR_STOP on

-- ── Роль ───────────────────────────────────────────────────────────────
CREATE ROLE nc WITH LOGIN CREATEDB PASSWORD 'ЗАМЕНИТЕ_НА_DB_PASSWORD_ИЗ_ENV';

-- ── База ───────────────────────────────────────────────────────────────
CREATE DATABASE nc
    WITH OWNER = nc
         ENCODING = 'UTF8'
         LC_COLLATE = 'Russian_Russia.1251'
         LC_CTYPE   = 'Russian_Russia.1251'
         TEMPLATE   = template0;

GRANT ALL PRIVILEGES ON DATABASE nc TO nc;

-- ── Права на схему public ──────────────────────────────────────────────
-- С PostgreSQL 15 обычная роль больше НЕ может создавать таблицы в public
-- по умолчанию. Без этой строки `php artisan migrate` падает с «permission
-- denied for schema public» — самая частая заминка на свежей установке.
\connect nc
GRANT ALL ON SCHEMA public TO nc;
ALTER SCHEMA public OWNER TO nc;

\echo ''
\echo 'Роль и база созданы. Дальше:'
\echo '   php artisan migrate --force'
\echo '   и пройти мастер установки на /install (или прогнать сидеры)'
\echo ''
