# Changelog

All notable changes to this package are recorded here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.0.1] - 2026-10-01

### Changed

- The archive Composer installs no longer contains the tests, CI and editor configuration, `CLAUDE.md` or other development-only files, only the library itself, its README, CHANGELOG and LICENSE.

## [3.0.0] - 2026-10-01

### Added

- `SecretManagerClientException` and `SecretManagerClientExceptionInterface`, thrown by a
  `SecretManagerClientInterface` when the backing service fails.
- `psr/clock` is now a required dependency, and `symfony/clock` a dev dependency used by the tests.

### Changed

- **Breaking:** `FirestoreKeyValueStore` takes a PSR-20 `Psr\Clock\ClockInterface` as a second
  constructor argument and reads the time from it instead of calling `time()`.
- **Breaking:** `FirestoreKeyValueStoreFactory` takes a `ClockInterface` as a second constructor
  argument and passes it to every store it builds.
- **Breaking:** `MemoryKeyValueStore` takes a `ClockInterface` in its constructor and now honours the TTL
  given to `setValue()`: `getValue()` returns `null` once the TTL has passed, and `getTtl()` returns the
  remaining seconds (or `null` when no TTL was set). Previously the TTL was echoed back unchanged and
  never enforced.
- **Breaking:** `SecretManagerClientInterface` is now expressed in this package's terms:
  `accessLatest(string $versionName): ?string` and `addVersion(string $secretName, ?string $value): void`
  replace `accessSecretVersion()` and `addSecretVersion()`, which took and returned Google request and
  response objects. `GoogleSecretKeyValueStore` no longer builds or catches any Google class;
  `GoogleSecretManagerClientAdapter` now builds the Google requests and turns `ApiException` into
  `SecretManagerClientException`.

## [2.0.0] - 2026-09-30

### Changed

- **Breaking:** `FirestoreKeyValueStore` now takes a `FirestoreDocumentAdapterInterface` in its
  constructor instead of a `Google\Cloud\Firestore\DocumentReference`, so it no longer depends on a
  Google class. `FirestoreDocumentAdapter` wraps a `DocumentReference` for production use.
- **Breaking:** `FirestoreDocumentReferenceFactoryInterface` and `DefaultFirestoreDocumentReferenceFactory`
  are replaced by `FirestoreDocumentAdapterFactoryInterface` and `DefaultFirestoreDocumentAdapterFactory`,
  which return an adapter.

### Added

- `GoogleSecretKeyValueStoreFactory` and `FirestoreKeyValueStoreFactory`, each behind an interface, as
  the way to build those stores.

### Removed

- **Breaking:** the static `GoogleSecretKeyValueStore::create()` and `FirestoreKeyValueStore::create()`
  factories, and `create()` on `GoogleSecretKeyValueStoreInterface` and `FirestoreKeyValueStoreInterface`.
  Use the new factory classes. See "Upgrading to 2.0" in the README.

## [1.0.0] - 2026-09-28

First stable release.

### Added

- `KeyValueStoreInterface`, the shared contract for reading and writing a single `?string` value, so
  calling code does not depend on where the value lives.
- `TtlAwareKeyValueStoreInterface`, for stores that can expire a value. It adds `getTtl()` and an
  optional `$ttl` argument to `setValue()`. Stores that cannot express a TTL implement only the base
  interface.
- `DatabaseKeyValueStore`, which persists to a table through Doctrine ORM on any platform Doctrine DBAL
  supports, using an entity you define by extending `AbstractDatabaseKeyValueStoreEntity`.
- `GoogleSecretKeyValueStore`, backed by a Google Secret Manager secret.
- `FirestoreKeyValueStore`, backed by a single Google Firestore document, with TTL support through an
  `expiresAt` field.
- `MemoryKeyValueStore`, a per-process value for tests and defaults.

[Unreleased]: https://github.com/christianjbrown/key-value-store-php/compare/v3.0.1...HEAD
[3.0.1]: https://github.com/christianjbrown/key-value-store-php/compare/v3.0.0...v3.0.1
[3.0.0]: https://github.com/christianjbrown/key-value-store-php/compare/v2.0.0...v3.0.0
[2.0.0]: https://github.com/christianjbrown/key-value-store-php/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/christianjbrown/key-value-store-php/releases/tag/v1.0.0
