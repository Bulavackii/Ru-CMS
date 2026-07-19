# RU CMS — project notes for Claude

Laravel 12 HMVC modular CMS ("RuShop CMS" / "RU CMS"). Modules live in `modules/<Name>/` (Controllers/Models/Providers/Migrations/Views/Routes), loaded by `app/Providers/ModuleServiceProvider.php`. GitHub: `Bulavackii/Ru-CMS` (public), remote `origin` already configured, pushes go straight to `master` (no PR flow set up).

## Where things stand (as of 2026-07-19)

- **Test suite: 151/151 passing, 0 errors, 0 failures** (`php vendor/bin/phpunit --no-coverage`). Got there from a starting point of "couldn't even boot" → 142 errors → 30 errors/48 failures → 0/0, across two work sessions. See commits `61648cf` and `dcb8329` for the detailed reasoning — both have long, specific commit messages explaining root causes, worth reading with `git show <sha>` before re-deriving the same things.
- Dependencies (composer + npm) were updated to latest **within existing major-version constraints** in the earlier session. These were **deliberately NOT bumped** (breaking changes, needs dedicated migration effort, not started): Laravel 12→13, Tailwind 3→4, Vite 6→8, PHPUnit 11→13, laravel/tinker 2→3, spatie/laravel-sitemap 7→8.
- CDN-independence pass done: local webfonts (`@fontsource`, latin+cyrillic), local swagger-ui, Yandex Maps consent-gated (click-to-load), Yandex Metrika config-gated, security headers (CSP/HSTS/etc) added in `app/Http/Middleware/ContentSecurityPolicy.php` and `bootstrap/app.php`.

## Sandbox environment gotcha (read this before debugging weird failures)

This dev sandbox has **no working `pdo_mysql` driver**. `php artisan <anything>` fails immediately with "could not find driver" because `ModuleServiceProvider::boot()` touches the DB (`Schema::hasTable('modules')`) unconditionally at boot, and the real `.env` points at MySQL. Workarounds used throughout:
- For one-off artisan commands: prefix with `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory:` env vars.
- For the test suite: just use `php vendor/bin/phpunit --no-coverage` directly — `phpunit.xml` already overrides `DB_CONNECTION`/`DB_DATABASE`/`CACHE_STORE`/`SESSION_DRIVER`/`CAPTCHA_ENABLED` etc. Don't use `php artisan test` (boots via the real `.env` first).
- If you edit a `.blade.php` file, run `rm -rf storage/framework/views/*.php` before the next `phpunit` run — stale compiled views can mask fixes (bit us once with a Blade compile bug).

`storage/install.lock` exists on this sandbox (git-ignored, machine-local) and is **load-bearing for the test suite** — its absence makes `RedirectIfNotInstalled` middleware redirect every request to `/install`, breaking nearly everything. Don't delete it. If it's ever missing, recreate with `printf 'Installed' > storage/install.lock`.

## Architecture landmines found this session (fixed, but good to know)

- **`require_once` in route files is wrong in this codebase.** `routes/web.php` used to `require_once` module route files (Categories/Slideshow/Delivery/Payments/etc). Since PHPUnit runs all tests in one PHP process, the first app boot "used up" the `require_once` and every later app boot silently lost those routes. Same bug would hit Octane/RoadRunner in production. Fixed → always use plain `require` for anything that needs to re-run per app-instance boot.
- **`bootstrap/app.php`'s provider registration was double-counting.** It manually `require`s `bootstrap/providers.php` into `$providers`, then called `->withProviders($providers)` — which by default *also* re-merges that same file. Every provider's `boot()` ran twice (including `EventServiceProvider`'s `Event::listen()` calls). Now calls `->withProviders($providers, false)` and explicitly `->withEvents(discover: false)` (Laravel 11's implicit event auto-discovery was independently re-discovering the same listeners a second way).
- **`ModuleServiceProvider`'s `isInstalled()` gate was too broad.** It used to gate *all* module route/view/migration loading behind `isInstalled()` (which itself needs the `modules` DB table, which migrations create — chicken/egg). But the base layout (`resources/views/layouts/frontend.blade.php`) and globally-shared Blade components (`<x-frontend-notifications />` etc, registered unconditionally in `ThemeServiceProvider`) reference `Menu::`/`Notifications::`/`Categories::` view namespaces on *every* page regardless of install status. Fixed: `loadLegacyModules()` (routes+views+migrations for the legacy module list) now runs unconditionally at the top of `boot()`, not just after install. If you add a new legacy module that needs to work pre-install, add it to the `$legacyModules` array in that method.
- **Captcha module never merged its own config.** `CaptchaServiceProvider::register()` didn't call `mergeConfigFrom()`, so `config('captcha.enabled')` always silently fell back to the `config()` helper's own default (`true`) — the `.env` var `CAPTCHA_ENABLED` had zero effect anywhere. Fixed.
- **`bootstrap/cache/services.php` is regenerable — if something looks doubled, check it first.** `composer dump-autoload -o` runs `php artisan package:discover` as a post-hook, which needs a working DB connection env (see sandbox gotcha above) or it silently fails to regenerate and leaves stale/partial cache. Regenerate with: `rm bootstrap/cache/services.php bootstrap/cache/packages.php && APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan package:discover`.

## Known remaining work (not started, no one has asked for it yet)

- The 4 major-version dependency bumps listed above (Laravel 13, Tailwind 4, Vite 8, PHPUnit 13, tinker 3, spatie/laravel-sitemap 8) — each needs its own migration pass, don't attempt casually.
- `resources/views/frontend/pages/footer.blade.php` (a *different* file from `layouts/partials/footer.blade.php`, the one actually used by the main layout) uses `<x-country-switcher />` and looks like it might be an orphaned/unused duplicate — never verified whether it's dead code or used by some other layout. Didn't touch it, wasn't in scope.
- `routes/auth.php` is Laravel Breeze's stock scaffold file and is **not** loaded from anywhere (`routes/web.php` doesn't `require` it). The app has its own custom login/register controllers instead. It's currently harmless dead code, but if anyone goes looking for "why doesn't X Breeze route work," this is why — the file exists but is never wired in. The few routes from it that *are* actually needed (`verification.*`, `password.confirm`, `password.update`, `profile.*`) were individually re-added to `routes/web.php` directly (cherry-picked, not by requiring the whole file, since that file's `login`/`register`/`password.*` routes would collide with the custom controllers).

## Testing conventions in this repo

- Always `use RefreshDatabase;` in any Feature test that touches the DB or renders a page (LocalizationMiddleware queries `countries` on every request) — several tests were missing this and it silently no-op'd instead of failing loudly.
- Don't call `$this->seed()` in Unit tests that assert exact row counts via model scopes — `PaymentDeliverySeeder` inserts ~20 real rows and will blow up any `assertCount(1, Model::someScope()->get())`.
- Truthy checks (`if ($model->some_nullable_int)`) are a recurring bug pattern in this codebase for fields where `0` is a legitimate value (delivery days, etc) — use `!== null` instead when you see this pattern.
