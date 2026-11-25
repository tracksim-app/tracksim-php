# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-11-25

### Added

- `TrackSim` client for PHP 8.2+, built on Guzzle 7.
- Company details (`me`), drivers (`add`, `remove`, `get`, `manage`), job routes (`route`)
  and Route Planner maps (`create`, `edit`, `list`, `get`, `delete`).
- Low-level `request()` helper for endpoints that aren't wrapped yet.
- Exceptions for each status: `AuthenticationException` (401), `PermissionDeniedException` (403),
  `NotFoundException` (404), `RateLimitException` (429) and `NetworkException`.
- `Webhook::verifySignature` and `Webhook::constructEvent` for the `tracksim-signature` header.
- Laravel 12 and 13 support: auto-discovered service provider, `TrackSim` facade, publishable
  config, `WebhookController` and the `WebhookReceived` event.

[1.0.0]: https://github.com/tracksim-app/tracksim-php/releases/tag/v1.0.0
