<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $this->convertModelIdToBigInteger(
            table: 'model_has_permissions',
            index: 'model_has_permissions_model_id_model_type_index',
            primaryKeyName: $this->primaryKeyName('model_has_permissions'),
            primaryColumns: ['permission_id', 'model_id', 'model_type'],
        );

        $this->convertModelIdToBigInteger(
            table: 'model_has_roles',
            index: 'model_has_roles_model_id_model_type_index',
            primaryKeyName: $this->primaryKeyName('model_has_roles'),
            primaryColumns: ['role_id', 'model_id', 'model_type'],
        );
    }

    public function down(): void
    {
        $this->convertModelIdToUuid(
            table: 'model_has_permissions',
            index: 'model_has_permissions_model_id_model_type_index',
            primaryKeyName: $this->primaryKeyName('model_has_permissions'),
            primaryColumns: ['permission_id', 'model_id', 'model_type'],
        );

        $this->convertModelIdToUuid(
            table: 'model_has_roles',
            index: 'model_has_roles_model_id_model_type_index',
            primaryKeyName: $this->primaryKeyName('model_has_roles'),
            primaryColumns: ['role_id', 'model_id', 'model_type'],
        );
    }

    private function convertModelIdToBigInteger(string $table, string $index, ?string $primaryKeyName, array $primaryColumns): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'model_id')) {
            return;
        }

        $type = $this->columnType($table, 'model_id');

        if (is_null($type) || in_array($type, ['bigint', 'integer'], true)) {
            return;
        }

        DB::table($table)->delete();

        $this->dropPrimaryKey($table, $primaryKeyName);

        $this->dropIndex($index);

        $this->dropColumnIfExists($table, 'model_id');

        Schema::table($table, function (Blueprint $table) {
            $table->unsignedBigInteger('model_id');
        });

        $this->createModelTypeIndex($table, $index);

        $this->recreatePrimaryKey($table, $primaryKeyName, $primaryColumns);
    }

    private function convertModelIdToUuid(string $table, string $index, ?string $primaryKeyName, array $primaryColumns): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'model_id')) {
            return;
        }

        $type = $this->columnType($table, 'model_id');

        if (is_null($type) || in_array($type, ['uuid', 'text'], true)) {
            return;
        }

        DB::table($table)->delete();

        $this->dropPrimaryKey($table, $primaryKeyName);

        $this->dropIndex($index);

        $this->dropColumnIfExists($table, 'model_id');

        Schema::table($table, function (Blueprint $table) {
            $table->uuid('model_id');
        });

        $this->createModelTypeIndex($table, $index);

        $this->recreatePrimaryKey($table, $primaryKeyName, $primaryColumns);
    }

    private function columnType(string $table, string $column): ?string
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $result = DB::selectOne(
                <<<'SQL'
                    SELECT data_type
                    FROM information_schema.columns
                    WHERE table_schema = current_schema()
                        AND table_name = ?
                        AND column_name = ?
                SQL,
                [$table, $column],
            );

            return $result->data_type ?? null;
        }

        if ($driver === 'sqlite') {
            $result = collect(DB::select("PRAGMA table_info({$table})"))
                ->firstWhere('name', $column);

            return isset($result->type) ? strtolower($result->type) : null;
        }

        return null;
    }

    private function primaryKeyName(string $table): ?string
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return null;
        }

        $result = DB::selectOne(
            <<<'SQL'
                SELECT tc.constraint_name
                FROM information_schema.table_constraints AS tc
                WHERE tc.table_schema = current_schema()
                    AND tc.table_name = ?
                    AND tc.constraint_type = 'PRIMARY KEY'
            SQL,
            [$table],
        );

        return $result->constraint_name ?? null;
    }

    private function dropPrimaryKey(string $table, ?string $primaryKeyName): void
    {
        if (! $primaryKeyName) {
            return;
        }

        DB::statement(
            sprintf(
                'ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s',
                $table,
                $primaryKeyName,
            ),
        );
    }

    private function recreatePrimaryKey(string $table, ?string $primaryKeyName, array $columns): void
    {
        if (! $primaryKeyName || empty($columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($columns, $primaryKeyName) {
            $table->primary($columns, $primaryKeyName);
        });
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }

            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });

            return;
        }

        DB::statement(
            sprintf(
                'ALTER TABLE %s DROP COLUMN IF EXISTS %s',
                $table,
                $column,
            ),
        );
    }

    private function dropIndex(string $index): void
    {
        DB::statement(
            sprintf(
                'DROP INDEX IF EXISTS %s',
                $index,
            ),
        );
    }

    private function createModelTypeIndex(string $table, string $index): void
    {
        DB::statement(
            sprintf(
                'CREATE INDEX IF NOT EXISTS %s ON %s (model_id, model_type)',
                $index,
                $table,
            ),
        );
    }
};
