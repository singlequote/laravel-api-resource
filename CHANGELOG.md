# Changelog

All notable changes to `Laravel Api Resource` will be documented in this file

## [2.4.0] - 2026-08-03

Backwards-compatible release: adds grouped `where` conditions for null-safe
filters. Existing `where`/`orWhere` usage is unchanged.

### Added
- **Grouped `where` conditions via the `$or` / `$and` sentinel keys.** A group is
  attached to the surrounding query with the current boolean and its
  sub-conditions are combined with the group's own boolean; groups may be nested.
  This enables null-safe filters such as
  `where[$or][0][col][neq]=true & where[$or][1][col]=null` →
  `AND (col != ? OR col IS NULL)`. Works on plain columns and JSON paths
  (`custom_fields->key`).

### Fixed
- `ScopeWhere` now passes the surrounding boolean to `whereNull()`/`whereNotNull()`,
  so a `'column': 'null'` condition inside an `$or` group (or an `orWhere`) is
  combined with `OR` instead of always `AND`.

## [2.3.0] - 2026-07-29

Backwards-compatible release: runtime performance improvements, correctness fixes
for relation ordering and policies, and one new opt-in config option. **No code or
config changes are required to upgrade** — the new option defaults to the existing
behavior.

### Performance
- `ApiModel` now memoizes `fillable()`/`relations()` in a process-static cache on
  top of the existing cache store, removing repeated cache-store round-trips within
  a request (and, under Octane, across requests in the same worker).
- `ApiRequestService::defaults()` is now memoized per model + configured `api.limit`,
  so the validation rule set is no longer rebuilt on every request.
- `ScopeSearch` resolves the fillable list once per request instead of once per
  search field.

### Added
- New `search.lower` config option (default `true`). On databases whose columns
  already use a case-insensitive collation (e.g. MySQL `*_ci`), set it to `false`
  to drop the per-row `LOWER()` wrapping in search — results stay identical, with
  less work on large scans. Keep it `true` on case-sensitive collations (MySQL
  `*_bin`) and on PostgreSQL, where `LIKE` is case-sensitive.

### Fixed
- **Ordering by a relation column** (`orderBy=relation.column`):
  - To-one relations now use a `LEFT JOIN` instead of an `INNER JOIN`, so parent
    rows without a related record are no longer silently dropped.
  - To-many relations (`hasMany`/`morphMany`) are now ordered via a correlated
    subquery, preventing duplicate parent rows and incorrect pagination totals
    caused by join fan-out.
  - Ordering by a `morphTo` column is now safely ignored instead of producing an
    invalid/ambiguous query.
- `ApiPolicyService::defaults()` no longer throws on public/unauthenticated
  endpoints when there is no authenticated user; it returns an all-`false` ability
  map instead.
- `whereJsonContains` / `whereJsonDoesntContain` now support the list/array form
  (`whereJsonContains[0][column]=...`), which was previously silently ignored.
- `whereIn` / `whereNotIn` now qualify the column with the table name, avoiding
  "ambiguous column" errors when the query also contains a join.
