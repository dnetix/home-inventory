<application-guidelines>
# HomeInventory APP

HomeInventory is a responsive (mobile-first + desktop) web app for managing everything a person owns: what you have, where it lives, what it's worth/sized, who borrowed it, and when it needs maintenance.

## Current status (2026-07-17)

**The app is fully built and working.** All four planned phases are complete and committed (see `git log` — one commit per phase area): schema/domain/auth, items+places+categories+tags+search, lending+upkeep+dashboard, settings+account+MinIO photos. **114 tests passing** (`php artisan test`). The user is now testing the app; upcoming work will be modifications and new features, not greenfield building.

Not built yet (deliberate): home-sharing/invite UI (schema supports it), reminder *delivery* (the settings toggle only persists), registration/password-reset (login only, seeded user).

## Design source of truth

The UI comes from Claude Design project `d03c2bb7-decf-440c-89ca-90c9ceae834a` (readable via the claude_design MCP / DesignSync; auth with /design-login). `CONTEXT.md` there is the master spec; `proto-*.jsx` files are the mobile screens, `desktop-*.jsx` the ≥1024px layouts, `proto-theme.css` the design tokens. **The design is visual reference only — its React code and REST API contract are NOT binding.** The Tailwind theme in `resources/css/app.css` is the tokens translated to CSS variables + `@theme inline`.

## Architecture decisions (do not re-litigate)

- **Stack:** Laravel 13 + PHP 8.4 + Octane/FrankenPHP · Livewire 4 **class components** (`app/Livewire`, views in `resources/views/livewire`) + Alpine · Tailwind 4 · MySQL 8.4 · MinIO for photos.
- **Multi-home tenancy:** every inventory table has `home_id`. Users ↔ homes via `home_user` pivot (role column, unused yet); `users.current_home_id` is the active home. `BelongsToHome` trait (app/Models/Concerns) applies a global scope + auto-fills `home_id`; it resolves via the **scoped** `CurrentHome` binding (Octane-safe) and **throws `MissingCurrentHomeException` when no home is resolvable** — console/jobs/seeders must use `CurrentHome::override()` or `Model::forHome($home)`. `HomeScopedPolicy` double-checks membership for all scoped models.
- **Units:** dimensions stored as **integer millimetres** (`width/height/depth` columns, exposed as a `Dimensions` VO via `DimensionsCast`); money as **integer cents** (`Money` VO/cast). Display units are per-user (`users.unit`: metric→cm, imperial→in) via `UnitFormatter` (scoped binding); forms convert input back to mm/cents on save. **Never store display units.**
- **Domain math** lives in `app/Support` as pure classes: `FitChecker` (rotation-tolerant bounding box + remaining volume → fit/tight/full/toobig/unknown), `PlaceTree`/`PlaceFill` (whole-tree fill computed in memory from two queries — never per-node queries), `SearchItems` (FULLTEXT boolean mode + LIKE fallback; matches name/note/tags/categories/places incl. place descendants; relevance-ranked).
- **Livewire conventions:** Form objects in `app/Livewire/Forms` for validation; multi-model mutations as action classes in `app/Actions` (e.g. `CompleteUpkeepTask` rolls recurring due dates forward from the completion date and logs the upkeeper); sheets/modals are server-driven (`@if` + `x-ui.sheet`); toasts via `$this->dispatch('toast', message: ...)` or `session()->flash('toast', ...)` for redirects.
- **Responsive:** one blade per screen; `lg:` breakpoint splits mobile (bottom tabbar + FAB, pushed detail pages) from desktop (76px icon rail, master–detail panes). Layout: `resources/views/components/layouts/app.blade.php` (also Livewire's `component_layout` in config/livewire.php).
- **Theme:** `[data-theme]` on `<html>`, set pre-paint from `localStorage['hi-theme']` (script in `layouts/head.blade.php`), re-applied on `livewire:navigated`, synced from Settings via the `theme-changed` event (resources/js/app.js).

## Dev environment (THIS MACHINE HAS NO LOCAL PHP/COMPOSER)

Everything PHP runs through Docker. App container: service `home-inventory`, name `inventory-app` (`docker compose up -d` first).

- Artisan/tests/tinker: `docker compose exec home-inventory php artisan …`
- Tests: `docker compose exec home-inventory php artisan test --compact [--filter=…]`
- Pint (run after PHP changes): `docker compose exec home-inventory ./vendor/bin/pint --dirty --format agent`
- Composer: `docker run --rm -v ${PWD}:/app composer:2 <cmd>`
- Frontend: host has node — `npm run build` (ALWAYS rebuild after adding Tailwind classes in views, before browser-checking)
- **After ANY change (PHP, Blade, or `npm run build`), run `docker compose exec home-inventory php artisan octane:reload`** — Octane runs 4 persistent workers (`--max-requests=500`). PHP is cached until reload; Blade/asset changes are too in practice (Laravel's Vite helper caches the manifest in a worker static, and the 9p attribute cache makes compiled-view mtime checks unreliable). If a view still renders stale after reload, `php artisan view:clear` then reload.
- Perf-test mode: `docker compose exec home-inventory composer perf` (runs `artisan optimize` + octane:reload — production-like caches); back to dev with `composer perf:off`. **While perf mode is on, NEVER run `php artisan test` directly** — cached config overrides phpunit.xml's test DB and `RefreshDatabase` would hit the real `home_inventory` data. Use `composer test` (prefixed with config:clear) or run `perf:off` first; check `bootstrap/cache/config.php` doesn't exist before testing.

Shared dev infra (separate compose at `D:\Works\www\developer_infraestructure\shared\compose.yml`, network `shared`):
- **MySQL 8.4** host `db`, root/root — databases `home_inventory` + `home_inventory_test` (phpunit.xml points tests at the test DB; tests run on real MySQL for FULLTEXT parity)
- **MinIO** bucket `home-inventory`, minioadmin/minioadmin, API `http://s3.test`, console `http://minio.test` — this repo's docker-compose maps `s3.test → host-gateway` so presigned URLs work from container AND browser; the Windows hosts file has `127.0.0.1 s3.test minio.test inventory.test` entries
- **Traefik** serves the app at `http://inventory.test`; DbGate at `http://db-admin.test`; **Redis** at host `redis` (cache + sessions)

Login: `dnetix@gmail.com` / `password` (seeded via `php artisan migrate:fresh --seed` — DemoSeeder recreates the design prototype's dataset with relative dates). The user has real usage data now — **ask before running migrate:fresh.**

## Known gotchas

- Octane workers persist (`--workers=4 --max-requests=500`): run `php artisan octane:reload` after ANY change (PHP, Blade, asset rebuilds) or the browser serves stale code/asset hashes. The code sits on a slow 9p Windows bind mount — that's why worker persistence + the OPcache tuning in the Dockerfile matter (see the perf-fix commits).
- Livewire 4's layout config key is `component_layout` (NOT v3's `layout`); its default `layouts::app` hint doesn't exist here.
- Tests still fake images with `UploadedFile::fake()->create('x.jpg', 128, 'image/jpeg')` (works regardless of GD; the container now HAS gd+exif for `PhotoShrinker`).
- `User` mirrors DB defaults in `$attributes` (unit/theme/notifications) — keep that in sync if columns are added; Livewire tests use in-memory models that never see DB defaults.
- Artisan-generated files must be Read before Write/Edit when using file tools.
- Desktop top bar (in `layouts/app.blade.php`): screens teleport their heading/actions into `#topbar-page` / `#topbar-actions` via `@teleport` (`<x-topbar-heading>` for the title). Each `@teleport` block carries only its FIRST root element (Alpine x-teleport) — wrap multiple actions in a single div.
- Item photos: `Item::booted()` deletes the S3 object on item delete; the Items\Form component deletes the old object on replace/remove. Bucket is private; display uses 30-min `temporaryUrl`s. Size is capped at 1600px twice: `window.shrinkPhoto` (resources/js/app.js) downscales in the browser before upload, and `PhotoShrinker` (app/Support) re-encodes anything oversized server-side (`photos:shrink` re-runs it over stored objects).

## Working agreements with the user

- Follow the phased/decision history in `git log` and the memory directory; don't re-ask settled decisions.
- No new composer/npm dependencies without asking.
- PHPUnit only (never Pest); factories with states; feature tests per module including a cross-home isolation case.
- Keep both layouts (mobile + desktop) in mind for every screen change; compare against the design project when fidelity matters.
</application-guidelines>


<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== octane/core rules ===

# Laravel Octane

This application uses Laravel Octane, a long-running PHP server. The application bootstraps once and handles many requests within the same process.

- Never store request-specific state in singletons or static properties, because it can leak across requests.
- Use `config('octane.server')` to detect the active driver (`swoole`, `roadrunner`, or `frankenphp`).
- Prefer scoped bindings (`$this->app->scoped()`) over singletons for per-request services.

When working on Octane-specific features (concurrency, shared tables, memory, driver configuration, testing), invoke `octane-development` for detailed rules.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file)
</laravel-boost-guidelines>

