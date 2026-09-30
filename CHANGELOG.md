# Changelog

All notable changes to this package are recorded here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/christianjbrown/key-value-store-php/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/christianjbrown/key-value-store-php/releases/tag/v1.0.0
