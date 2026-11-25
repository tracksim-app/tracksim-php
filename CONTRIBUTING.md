# Contributing

Thanks for helping improve the TrackSim PHP client.

## Reporting bugs and requesting features

Open an issue with what you did, what you expected and what happened, plus your PHP, Laravel
(if used) and package versions. Never include your API key, webhook secret or map links.

Questions about the TrackSim service itself (not this library) belong in the TrackSim
Discord or support channels.

## Development setup

```bash
git clone https://github.com/tracksim-app/tracksim-php.git
cd tracksim-php
composer install
```

## Checks

Run all of these before opening a pull request; CI runs the same:

```bash
composer test
composer analyse
vendor/bin/pint --test
```

Tests mock the HTTP client, so they never call the real API and need no API key.

## Pull requests

- Keep each pull request to one change, with tests for new behaviour.
- Follow the [API docs](https://docs.tracksim.app) for endpoint paths and response fields.
- Add an entry under an `Unreleased` heading in `CHANGELOG.md`.
- Public methods need docblocks, with array shapes for responses.

## Releasing (maintainers)

1. Update `TrackSim::VERSION` in `src/TrackSim.php` and move the changelog entries under the new version.
2. Merge to `main`, then create a GitHub release with the tag `vX.Y.Z`.
3. Packagist picks up the new tag automatically.
