# CLAUDE.md

Guidance for working in this repository. Match the existing conventions exactly — this codebase is
small, uniform, and highly opinionated, so new code should be indistinguishable from what's here.

## What this is

A thin, strongly-typed PHP 8.5+ library of interchangeable **key-value store** implementations. Every
store is a simple `get`/`set` of a single `?string` value (plus an optional `?int` TTL) behind one
tiny contract, `KeyValueStoreInterface`, so callers can swap the backing store without changing code.
It exists to hold small pieces of state — configuration flags, refresh tokens, cursors — and is
consumed by other libraries and a cloud function, so **the public API is stable within a major version** (class names,
the `ChristianBrown\KeyValueStore\` namespace, and every public method signature; a breaking change
needs a major release and a CHANGELOG entry marked breaking).

Four stores ship today:

- **`DatabaseKeyValueStore`** — persists to a database table via Doctrine ORM (any Doctrine DBAL
  platform — MySQL/MariaDB, PostgreSQL, SQLite, …), keyed by a string id.
- **`GoogleSecretKeyValueStore`** — reads/writes a Google Secret Manager secret (no TTL support).
- **`FirestoreKeyValueStore`** — reads/writes a single Google Firestore document (serverless,
  connectionless; TTL via an `expiresAt` field).
- **`MemoryKeyValueStore`** — a per-process in-memory value (mostly for tests/defaults).

## Commands

Binaries install into `bin/` (Composer `bin-dir`), not `vendor/bin/`. Both `bin/` and `vendor/` are
gitignored and Composer-installed, so run `composer install` first. The style tooling comes from the
`christianjbrown/code-quality-scripts` dev dependency (public on Packagist): `check-style` lints with
**PHP_CodeSniffer 4** using the `ChristianBrown` standard (slevomat sniffs plus PSR/PEAR/Squiz/Generic),
while **php-cs-fixer** (`@PhpCsFixer`/`@Symfony`) handles formatting.

| Task | Command |
| --- | --- |
| Run tests + coverage (opens HTML report) | `composer test` |
| Run tests, no coverage | `php -d memory_limit=-1 ./bin/phpunit --no-coverage` |
| Run one test | `php -d memory_limit=-1 ./bin/phpunit --filter DatabaseKeyValueStoreTest` |
| Static analysis (PHPStan level max) | `composer stan` |
| Check code style | `composer check-style` |
| Auto-fix code style | `composer fix-style` |
| Check / fix style on git diff only | `composer check-style-diff` / `composer fix-style-diff` |

Always run `composer fix-style` first (php-cs-fixer auto-fixes what it can), then `composer
check-style` to surface remaining violations that must be fixed by hand, then `composer stan`, then
`composer test` before finishing. If the `composer stan` wrapper runs out of memory, invoke PHPStan
directly: `./bin/phpstan analyse --no-progress --memory-limit=-1`. CI
(`.github/workflows/ci.yml`) runs the same gates — style → PHPStan → PHPUnit-with-coverage — on push/PR
to `main`, then enforces 100% coverage with `./bin/php-coverage-check` against the text coverage
report the PHPUnit step writes to `.phpunit.cache/coverage.txt`.

## Changelog

`CHANGELOG.md` follows Keep a Changelog. A pull request that changes `src/` must add a line under
`## [Unreleased]`; CI enforces it with `bin/php-changelog-check`. A release renames that section to the
version and the date, and its text becomes the GitHub release notes.

## Architecture

Everything lives flat under the `ChristianBrown\KeyValueStore\` namespace (`src/`), mirrored under
`ChristianBrown\KeyValueStore\Tests\` (`tests/`).

- **`KeyValueStoreInterface`** — the minimal shared contract every store implements:
  `getValue(): ?string` and `setValue(?string $value): self`. It carries **no TTL** — a store that
  cannot express a TTL (Google Secret Manager) implements only this, so it never has to throw on an
  unsupported operation (LSP).
- **`TtlAwareKeyValueStoreInterface extends KeyValueStoreInterface`** — the contract for stores that
  *do* support expiry: it adds `getTtl(): ?int` and widens `setValue(?string $value, ?int $ttl = null):
  self` (an optional parameter, so it remains substitutable for the base). `DatabaseKeyValueStore`,
  `FirestoreKeyValueStore` and `MemoryKeyValueStore` implement this; callers that need a TTL (e.g. the
  OAuth access-token cache) type-hint this interface rather than the base.
- **`DatabaseKeyValueStore` / `DatabaseKeyValueStoreInterface`** — constructed with an
  `EntityManagerInterface`, a `class-string` naming the Doctrine entity, and the row's string key. It
  resolves the entity's `EntityRepository` up front and does `findOneBy([FIELD_ID => $key])` on each
  read/write, inserting a fresh entity on first `setValue`. The entity class must implement
  `DatabaseKeyValueStoreEntityInterface`; the constructor guards this with `is_a(..., true)` and throws
  `InvalidArgumentException` otherwise.
- **`AbstractDatabaseKeyValueStoreEntity` / `DatabaseKeyValueStoreEntityInterface`** — the Doctrine
  `#[ORM\MappedSuperclass]` that stores extend to get the `id` / `ttl` / `value` columns and their
  accessors for free. See the deliberate deviation below.
- **`GoogleSecretKeyValueStore` / `GoogleSecretKeyValueStoreInterface`** — talks to Google Secret
  Manager through the library-owned **`SecretManagerClientInterface`** port, expressed in this package's
  own terms (`accessLatest(string $versionName): ?string` and `addVersion(string $secretName, ?string
  $value): void`), so the store never touches a Google class. The port throws
  `SecretManagerClientExceptionInterface` (implemented by `SecretManagerClientException`). That interface is **constructor-injected** so it is fully mockable — the
  `google/cloud-secret-manager` v2 client (`V1\Client\SecretManagerServiceClient`) is `final` and
  cannot be doubled, so the store never depends on it directly. **`GoogleSecretManagerClientAdapter`**
  (`final`, implements `SecretManagerClientInterface`) is the single production implementation: it
  wraps the real final v2 client, builds the v2 request objects and turns `ApiException` into
  `SecretManagerClientException`. There is no static `create()`:
  **`GoogleSecretKeyValueStoreFactory`** (`final`, behind `GoogleSecretKeyValueStoreFactoryInterface`) is
  the way to build the store from a secret path. It is constructed with a
  **`SecretManagerClientFactoryInterface`** and `create(string $secretPath)` news up the store with the
  client that factory returns. The production client factory is `DefaultSecretManagerClientFactory`
  (`final`), which builds the client from `GOOGLE_APPLICATION_CREDENTIALS`, wraps it in the adapter, and
  normalizes any startup failure into
  `RuntimeException(GoogleSecretKeyValueStoreInterface::CLIENT_START_FAILED)`. A consumer that wants a
  different client construction implements `SecretManagerClientFactoryInterface` itself.
  `getValue()` reads the `/versions/latest` version;
  `setValue()` adds a new secret version, both through the port. Secret Manager has no
  TTL, so this store implements only the base `KeyValueStoreInterface` (no `getTtl()`, no TTL-bearing
  `setValue()`) rather than throwing on an unsupported operation. The store normalizes
  `SecretManagerClientExceptionInterface` into `GoogleSecretKeyValueStoreException`. The adapter is covered by building a
  real v2 client over `google/gax`'s `Google\ApiCore\Testing\MockTransport` (plus a stubbed
  `CredentialsWrapper`) that returns a canned response — the one place the real final SDK client is
  exercised, alongside `DefaultSecretManagerClientFactory`.
- **`GoogleSecretKeyValueStoreException` / `GoogleSecretKeyValueStoreExceptionInterface`** — the one
  library-specific exception (a `RuntimeException`), so callers can `catch` the interface.
- **`FirestoreKeyValueStore` / `FirestoreKeyValueStoreInterface`** — wraps Google's `FirestoreClient`.
  It is serverless and connectionless — no VPC connector (unlike Redis) or Cloud SQL connection
  (unlike the database store). The **constructor-injected** collaborator is a
  **`FirestoreDocumentAdapterInterface`** (two methods: `getFields(): ?array`, null when the document
  does not exist, and `setFields(array)`), so the store never touches Google's classes.
  **`FirestoreDocumentAdapter`** (`final`) is the production implementation wrapping one
  `Google\Cloud\Firestore\DocumentReference`, the same pattern as `GoogleSecretManagerClientAdapter`.
  There is no static `create()`: **`FirestoreKeyValueStoreFactory`** (`final`, behind
  `FirestoreKeyValueStoreFactoryInterface`) builds the store from `(FirestoreClient $client, string
  $collection, string $documentId)`. It is constructed with a **`FirestoreDocumentAdapterFactoryInterface`**
  (production implementation **`DefaultFirestoreDocumentAdapterFactory`**, `final`, which does
  `new FirestoreDocumentAdapter($client->collection($collection)->document($documentId))`).
  The document holds two fields (`FIELD_VALUE`, `FIELD_EXPIRES_AT` on the interface): the string value
  and an integer `expiresAt` unix timestamp. `setValue()` writes both via `FirestoreDocumentAdapterInterface::setFields()`,
  storing `expiresAt` as now + `$ttl` (or `null`), where now comes from the constructor-injected PSR-20
  `ClockInterface` (the factory takes the clock too and passes it on). `getValue()` reads the fields:
  `null` when the document does not exist or `expiresAt` has passed, else the value; `getTtl()` returns
  `expiresAt` minus the clock's now (or `null`). Expiry guards are split into sequential single-condition `if`s for
  path coverage. **`google/cloud-firestore` is a `require-dev` + `suggest`, not a hard `require`** — it
  pulls in `ext-grpc`, which no other store needs, so it stays optional; consumers who use this store
  install it (and `ext-grpc`) themselves. Because `ext-grpc` isn't present locally or in CI, the
  `composer install` in `.github/workflows/ci.yml` passes `--ignore-platform-req=ext-grpc` (the tests
  mock the whole Firestore chain, so grpc is never loaded at runtime).
- **`MemoryKeyValueStore` / `MemoryKeyValueStoreInterface`** — in-process holder that takes a PSR-20
  `ClockInterface` and honours TTLs the way the Firestore store does (an absolute expiry computed from
  the clock; `getValue()` is `null` once passed, `getTtl()` is the remaining seconds). Production wiring
  passes `Symfony\Component\Clock\NativeClock`; tests pass `MockClock`. Never call `time()` or build a
  `DateTimeImmutable` in `src/`.

## Conventions (follow all of these)

- `declare(strict_types=1);` on every file, immediately after `<?php`.
- **Every concrete class is `final` and implements a matching `...Interface`** in the same namespace
  (`MemoryKeyValueStore`/`MemoryKeyValueStoreInterface`,
  `GoogleSecretKeyValueStore`/`GoogleSecretKeyValueStoreInterface`).
- **Constants live on the interface, not the class**: the Doctrine lookup field
  (`DatabaseKeyValueStoreInterface::FIELD_ID`), the secret version suffix
  (`GoogleSecretKeyValueStoreInterface::VERSION_LATEST`), and **every** exception message template
  (`*_SPRINTF`, `*_FAILED`, …). Message text never appears as a literal in a class
  body — reference the interface constant (via `self::` from the implementing class).
- **No constructor property promotion** — declare typed `private` properties and assign them in the
  constructor body. Class members (properties then methods) are ordered **alphabetically**.
- Import functions with `use function sprintf;` etc. (after class imports, blank line between) and call
  them unqualified. Note php-cs-fixer's `mb_str_functions` rule rewrites string calls to their `mb_*`
  equivalents (e.g. `trim` → `mb_trim`) — let it, don't fight it.
- Full type declarations on all params/returns; express generics/array shapes via `@param`/`@return`/
  `@var` docblocks (e.g. `DatabaseKeyValueStore::$repository` is
  `@var EntityRepository<DatabaseKeyValueStoreEntityInterface>`). Public methods that can throw carry
  `@throws` docblocks naming the concrete exception interface.
- **Do not add a lone constructor `@param` for one argument.** The Symfony/PEAR `FunctionComment` sniff
  maps a single `@param` to the *first* parameter positionally, so a `@param` for only the second
  argument fails style. Prefer expressing the constraint another way — `DatabaseKeyValueStore`'s
  `$entityClassName` is left as a plain `string` and narrowed to
  `class-string<DatabaseKeyValueStoreEntityInterface>` by the `is_a($x, ..., true)` guard, which
  PHPStan understands with no docblock.
- Dependencies are constructor-injected and typed against interfaces (`EntityManagerInterface`, `SecretManagerClientInterface`,
  `FirestoreDocumentAdapterInterface`). Google SDK classes are only referenced from the adapters and
  factories that wrap them. No public static functions, and no `new` of a collaborator outside a factory.
- **A method that does not use `$this` must be `static`** (called via `self::`) — a stateless helper is
  static. Enforced for private methods by the shared `RequireStaticPrivateMethodRule` PHPStan rule (via
  `code-quality-scripts`' `config/phpstan.neon`); interface/override methods stay instance.

### Deliberate deviation: the abstract mapped-superclass

`AbstractDatabaseKeyValueStoreEntity` is the one class that is **`abstract`, not `final`** — it is a
Doctrine `#[ORM\MappedSuperclass]`, whose entire purpose is to be extended by a consumer's concrete
`#[ORM\Entity]`. This is an intentional, isolated exception to the "every concrete class is final"
rule (the same kind of carve-out as `cloud-run-function-lib`'s `AbstractJsonResponse`). Keep it abstract.
Its public accessors are `final` (enforced by the `final_public_method_for_abstract_class` fixer rule),
so tests exercise it through a concrete fixture subclass (`Tests\TestDatabaseKeyValueStoreEntity`)
rather than a partial mock — you cannot mock a `final` method. Do not introduce any other abstract base
class.

## Testing

The `phpunit.xml` config is strict (`requireCoverageMetadata`, `beStrictAboutCoverageMetadata`,
`failOnRisky`, `failOnWarning`, `restrictNotices`/`restrictWarnings`, path coverage).

- **Keep line, branch, method, class, AND path coverage at 100%.** Every guard — each "entity
  exists / does not exist" branch, each `ApiException` → `GoogleSecretKeyValueStoreException`
  translation, the TTL-unsupported throws — must be exercised. Run `composer test` and check the report
  (text summary to stdout + HTML at `.phpunit.cache/code-coverage-html/index.html`) before finishing;
  the suite currently sits at 100% on all five metrics — keep it there. There are no loops in `src/`,
  so path coverage is currently just the branch combinations; if you add list processing, prefer array
  functions (`array_map`/`array_filter`) over `foreach`, which spawns unreachable back-edge paths.
- **Every test class needs a `#[CoversClass(...)]` attribute** (may list more than one) or the run
  fails. Use PHPUnit 12 **attributes, not annotations**: `#[CoversClass]`, `#[DataProvider]`,
  `#[TestWith]`.
- Tests mirror `src/` under `tests/`, one `final class XTest extends TestCase` per class, methods named
  `test<Method><Scenario>`. `tests/TestDatabaseKeyValueStoreEntity.php` is a shared **fixture**, not a
  test (no `Test.php` suffix, so PHPUnit does not collect it).
- **Double every collaborator, and pick the right kind of double** (PHPUnit 12 emits a notice for a
  `createMock()` that is never given an expectation, so don't reach for a mock by default):
  - **`self::createStub(SomeInterface::class)`** for a *pure return-value double* — one you only feed
    canned answers (`->method(...)->willReturn(...)`/`->willThrowException(...)`) or pass through
    unconfigured. Do **not** call `->with()` on a stub. The read paths stub `EntityManagerInterface`,
    `EntityRepository`, and the Google SDK classes this way.
  - **`self::createMock(SomeInterface::class)` with `->expects(self::once())`** for a *verified
    collaborator* — one whose call you assert on via `->with(...)`. `DatabaseKeyValueStoreTest` mocks
    the `EntityManager` to prove `persist()`/`flush()` are called; `GoogleSecretKeyValueStoreTest`
    mocks the client to prove `addSecretVersion()` gets the right path + payload.
  - Both factories are **static** — call them `self::createStub(...)`/`self::createMock(...)`.
- Assert statically (`self::assertSame`) and reference the **same interface constants** production code
  uses for expected exception messages (`sprintf(GoogleSecretKeyValueStoreInterface::…_SPRINTF, …)`),
  so no strings are hardcoded in tests.
- `DefaultSecretManagerClientFactory` is covered by real client construction against
  `tests/test-credentials.json` (success) and a missing file (failure); it is the one path that
  touches the real Google SDK, and it stays green because the SDK only validates credentials lazily.
  The factories that build stores are tested with a stubbed or mocked collaborator factory.

## Adding a feature

1. Add the store/class + its matching `...Interface` in `src/`, with any constants (field names,
   message templates) on the interface. Concrete classes are `final`.
2. Constructor-inject every collaborator (typed against an interface, or the external SDK class) so it
   stays mockable: do not `new` a dependency inside a method except in a factory class (behind its own interface, with a
   non-static `create()`).
3. Add a matching `#[CoversClass]` test under `tests/`, doubling all collaborators per the rules above.
4. Run `composer fix-style`, then `composer check-style`, then `composer stan`, then `composer test`
   and **confirm the coverage report is 100%** on classes, lines, paths, methods, and branches.
5. Do not change an existing public method signature, class name, or namespace without a major
   release: external consumers depend on them.
