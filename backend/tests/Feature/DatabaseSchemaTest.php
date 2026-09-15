<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Schema guards.
 *
 * The test suite runs on in-memory SQLite because it is fast, but the application
 * runs on MySQL. SQLite is far more forgiving: it has no 64-character identifier
 * limit, so an over-long generated index name passes in tests and then fails on
 * MySQL (which is exactly what happened to `subscription_plan_features`).
 *
 * These tests close that gap by asserting the conventions directly, and they also
 * enforce the project's `id` + `uuid` rule rather than trusting review.
 */
class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * MySQL (and MariaDB) limit identifiers to 64 characters. SQLite does not.
     */
    private const MAX_IDENTIFIER_LENGTH = 64;

    /**
     * Laravel's own infrastructure tables, which deliberately do not follow the
     * AUTOSECURE conventions. See docs/phase-1/DATABASE.md.
     *
     * @var array<int, string>
     */
    private const FRAMEWORK_TABLES = [
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
    ];

    public function test_no_index_name_exceeds_the_mysql_identifier_limit(): void
    {
        $offenders = [];

        foreach ($this->tables() as $table) {
            foreach (Schema::getIndexes($table) as $index) {
                $name = (string) ($index['name'] ?? '');

                if (strlen($name) > self::MAX_IDENTIFIER_LENGTH) {
                    $offenders[] = "{$table}: {$name} (".strlen($name).' chars)';
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Index names must be <=64 characters for MySQL. Give these an explicit short name: '
                .implode(', ', $offenders),
        );
    }

    public function test_no_foreign_key_name_exceeds_the_mysql_identifier_limit(): void
    {
        $offenders = [];

        foreach ($this->tables() as $table) {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                $name = (string) ($foreignKey['name'] ?? '');

                // SQLite does not always report constraint names.
                if ($name !== '' && strlen($name) > self::MAX_IDENTIFIER_LENGTH) {
                    $offenders[] = "{$table}: {$name} (".strlen($name).' chars)';
                }
            }
        }

        $this->assertSame([], $offenders, 'Foreign key names must be <=64 characters for MySQL.');
    }

    /**
     * The project rule: every AUTOSECURE table carries an internal `id` and a
     * public `uuid`, because every route resolves records by uuid.
     */
    public function test_every_application_table_has_an_id_and_a_uuid(): void
    {
        $offenders = [];

        foreach ($this->tables() as $table) {
            if (in_array($table, self::FRAMEWORK_TABLES, true)) {
                continue;
            }

            $columns = array_map(
                fn ($column) => $column['name'],
                Schema::getColumns($table),
            );

            foreach (['id', 'uuid'] as $required) {
                if (! in_array($required, $columns, true)) {
                    $offenders[] = "{$table} is missing `{$required}`";
                }
            }
        }

        $this->assertSame([], $offenders, implode('; ', $offenders));
    }

    public function test_uuid_columns_are_unique_so_they_can_be_used_as_route_keys(): void
    {
        $offenders = [];

        foreach ($this->tables() as $table) {
            if (in_array($table, self::FRAMEWORK_TABLES, true)) {
                continue;
            }

            $hasUuidIndex = false;

            foreach (Schema::getIndexes($table) as $index) {
                $columns = $index['columns'] ?? [];

                if ($columns === ['uuid'] && ($index['unique'] ?? false)) {
                    $hasUuidIndex = true;
                    break;
                }
            }

            if (! $hasUuidIndex) {
                $offenders[] = $table;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'These tables need a unique index on `uuid`: '.implode(', ', $offenders),
        );
    }

    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        return array_map(
            fn ($table) => is_array($table) ? $table['name'] : $table,
            Schema::getTables(),
        );
    }
}
