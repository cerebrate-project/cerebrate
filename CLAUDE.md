# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Project Is

Cerebrate is a CakePHP 4.x open-source platform for managing contact information, encryption keys, and organizational structure for security communities (CSIRTs, FIRST.org, etc.). It supports distributed synchronization between multiple Cerebrate instances and integrates with external tools like MISP.

## Commands

```bash
# Dependencies
composer install

# Tests
composer test                                        # Full suite (starts/stops WireMock on port 8080)
./vendor/bin/phpunit --testsuite=api                 # API tests only
./vendor/bin/phpunit --testsuite=controller          # Controller tests only
./vendor/bin/phpunit --testsuite=app                 # App/unit tests only
./vendor/bin/phpunit tests/TestCase/path/to/Test.php # Single test file

# Code style
composer cs-check   # Check PSR-12 / CakePHP coding standard
composer cs-fix     # Auto-fix violations
composer stan       # PHPStan static analysis

# Database migrations
./bin/cake migrations migrate          # Run all pending migrations
./bin/cake migrations migrate -p Tags  # Run Tags plugin migrations

# Useful CLI commands
./bin/cake user --help
./bin/cake meta_template --help
./bin/cake importer --help
./bin/cake updater --help
```

## Architecture

### MVC Structure
- **Controllers** (`src/Controller/`) extend `AppController`, which loads: `RestResponse`, `CRUD`, `ACL`, `Navigation`, `ParamHandler`, `Notification`, and `FloodProtection` components.
- **Tables** (`src/Model/Table/`) extend `AppTable`.
- **Entities** (`src/Model/Entity/`) extend `AppModel`, which provides `rearrangeForAPI()`, `rearrangeMetaFields()`, and related helpers for consistent API serialization.
- **Templates** live in `templates/` and follow CakePHP conventions.

### Key Components (src/Controller/Component/)
- **CRUDComponent** – The core of almost every action. Handles index (with filtering/pagination), add, edit, delete, and tag operations. Controllers delegate heavily to this rather than implementing actions directly.
- **RestResponseComponent** – Standardizes all API responses; handles JSON/CSV output and OpenAPI validation.
- **ACLComponent** – Role-based access control checks.
- **ParamHandlerComponent** – Parses and normalizes request parameters for filtering.
- **FloodProtectionComponent** – Rate-limiting via the `FloodProtections` table.

### Key Behaviors (src/Model/Behavior/)
- **MetaFieldsBehavior** – Dynamic custom fields attached to organizations/individuals, driven by `MetaTemplates`.
- **AuditLogBehavior** – Automatic change tracking written to `audit_logs`.
- **UUIDBehavior** – Auto-generates UUIDs on save.
- **SyncToolBehavior** – Distributed sync between Cerebrate instances via Inbox/Outbox pattern.
- **AuthKeycloakBehavior** – Keycloak/OIDC authentication support.

### Meta-Field System
Organizations and individuals support dynamic attributes defined by `MetaTemplates`. The `meta_fields` table stores values; types are handled by classes under `src/Lib/default/meta_field_types/` (Text, IPv4, IPv6, etc.).

### Synchronization
Distributed sync uses an Inbox/Outbox pattern. Incoming messages land in `inbox`, are processed by handlers in `src/Model/Lib/`, and outbound messages queue in `outbox`. The `SyncTool` behavior and related connectors manage instance-to-instance communication.

### Local Tool Integration
External tools (e.g., MISP) connect via connector classes in `src/Lib/default/local_tool_connectors/`. The `LocalTools` table stores connection config; connectors implement a defined interface for actions/queries.

### REST API
All endpoints support JSON (and some CSV). The `RestResponseComponent` wraps responses; the OpenAPI spec lives at `webroot/docs/openapi.yaml` and is validated in the `api` test suite via WireMock.

### Authentication
Multiple methods are supported: session login, API keys (`auth_keys` table), and OAuth/OIDC via ADmad/SocialAuth. `AppController::beforeFilter()` resolves the active auth method.

### Routing
Uses CakePHP `DashedRoute` convention. Routes are defined in `config/routes.php`. Plugin routes (e.g., Tags) are loaded via plugin bootstrapping.

### Configuration
- `config/app_local.php` – Local overrides (not in git); copy from `app_local.example.php`.
- `config/config.json` – Runtime JSON overrides read by the app.
- `config/cerebrate.php` – Cerebrate-specific settings (security, feature flags).

### Plugins
- **Tags** (`plugins/Tags/`) – Local plugin providing tagging for any model.
- Standard CakePHP plugins: Authentication, Authorization, Migrations (Phinx), DebugKit (dev).

### Frontend
Bootstrap 4 + vanilla JS. Mermaid.js for diagrams, vis-network for graph visualization. Frontend assets live in `webroot/`. No build step is required; JS/CSS are included directly.

## Testing Notes

- Tests require a MySQL/MariaDB test database configured in `config/app_local.php` (or env vars).
- The `api` and `e2e` test suites use WireMock (`composer test` handles start/stop automatically); running individual test files that depend on WireMock requires starting it manually.
- Fixtures are in `tests/Fixture/`; see `tests/README.md` for setup details.
