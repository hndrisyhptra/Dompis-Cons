# Database schema baseline

`mysql-schema.sql` is the audited MariaDB baseline captured on 2026-09-09.
It is intended for rebuilding an empty database after the migration ledger has
been reconciled. Migration execution remains disabled while
`MIGRATIONS_FROZEN=true`.

The baseline contains all 54 tables and only these reference datasets:

- roles
- customers
- project stages
- issue categories
- permit categories

It contains no users, projects, LOPs, BOQ items, evidences, prices, file paths,
or other transactional data.

Two reviewed migrations intentionally remain pending:

- `2026_08_31_120000_add_performance_indexes_for_pm_dashboard`
- `2026_09_07_090000_drop_role_enum_from_users_table`

Do not enable migrations until those two migrations and the live migration
ledger have been handled explicitly.
