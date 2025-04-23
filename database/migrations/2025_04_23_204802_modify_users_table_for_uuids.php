<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // We need to check for all tables that reference the users table
        // We already know of 'sessions' and 'subscriptions'

        // Store relationships to rebuild after changing the users table
        $relationships = $this->findForeignKeyRelationships('users');

        // Drop all foreign keys to the users table
        foreach ($relationships as $table => $columns) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column => $constraint) {
                    $table->dropForeign($constraint);
                }
            });
        }

        // Create a temporary table to hold user data
        Schema::create('users_temp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('youtube_token')->nullable();
            $table->text('reddit_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Copy data from users to the temp table with generated UUIDs
        $users = DB::table('users')->get();

        // Create a mapping from old IDs to new UUIDs to update related tables later
        $idMapping = [];

        foreach ($users as $user) {
            $uuid = \Illuminate\Support\Str::uuid()->toString();
            $idMapping[$user->id] = $uuid;

            DB::table('users_temp')->insert([
                'id' => $uuid,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'youtube_token' => $user->youtube_token,
                'reddit_token' => $user->reddit_token,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        // Now update the user_id values in related tables before dropping the users table
        foreach ($relationships as $table => $columns) {
            foreach ($columns as $column => $constraint) {
                $this->updateForeignKeyValues($table, $column, $idMapping);
            }
        }

        // Now it's safe to drop the original users table
        Schema::dropIfExists('users');

        // Recreate users table with UUID
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('youtube_token')->nullable();
            $table->text('reddit_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Copy data from temp to new users table
        $tempUsers = DB::table('users_temp')->get();
        foreach ($tempUsers as $user) {
            DB::table('users')->insert([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'youtube_token' => $user->youtube_token,
                'reddit_token' => $user->reddit_token,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        // Update column types for all tables that had a user_id column
        foreach ($relationships as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $columnName => $constraint) {
                    // Change column type to UUID
                    $table->uuid($columnName)->nullable()->change();

                    // Add foreign key back
                    $table->foreign($columnName)
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                }
            });
        }

        // Drop the temporary table
        Schema::dropIfExists('users_temp');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is complex to reverse
        // We'll need to recreate the auto-incrementing ID table

        // First, identify all relationships
        $relationships = $this->findForeignKeyRelationships('users');

        // Drop all foreign keys to the users table
        foreach ($relationships as $table => $columns) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column => $constraint) {
                    $table->dropForeign($constraint);
                }
            });
        }

        // Create a temporary table with auto-incrementing IDs
        Schema::create('users_temp', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('youtube_token')->nullable();
            $table->text('reddit_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Copy data from UUID users to temp table
        // We can't preserve the exact same ID values
        $users = DB::table('users')->get();
        $idMapping = [];

        foreach ($users as $user) {
            $newId = DB::table('users_temp')->insertGetId([
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'youtube_token' => $user->youtube_token,
                'reddit_token' => $user->reddit_token,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);

            $idMapping[$user->id] = $newId;
        }

        // Update related tables with new ID values
        foreach ($relationships as $table => $columns) {
            foreach ($columns as $column => $constraint) {
                $this->updateForeignKeyValues($table, $column, $idMapping);
            }
        }

        // Drop the UUID users table
        Schema::dropIfExists('users');

        // Recreate users table with integer IDs
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('youtube_token')->nullable();
            $table->text('reddit_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Copy data from temp to new users table
        $tempUsers = DB::table('users_temp')->get();
        foreach ($tempUsers as $user) {
            DB::table('users')->insert([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'youtube_token' => $user->youtube_token,
                'reddit_token' => $user->reddit_token,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        // Update column types in all related tables
        foreach ($relationships as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $columnName => $constraint) {
                    // Change column type back to unsignedBigInteger
                    $table->unsignedBigInteger($columnName)->nullable()->change();

                    // Add foreign key back
                    $table->foreign($columnName)
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                }
            });
        }

        // Drop the temporary table
        Schema::dropIfExists('users_temp');
    }

    /**
     * Find all tables with foreign keys to the specified table.
     *
     * @param  string  $table  The table to find relationships for
     * @return array A map of table names to columns and constraint names
     */
    private function findForeignKeyRelationships(string $table): array
    {
        $relationships = [];

        // Get the current database name
        $databaseName = DB::connection()->getDatabaseName();

        // Query information_schema to find all foreign keys pointing to our table
        $foreignKeys = DB::select('
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME
            FROM 
                information_schema.KEY_COLUMN_USAGE
            WHERE 
                REFERENCED_TABLE_SCHEMA = ? AND
                REFERENCED_TABLE_NAME = ?
        ', [$databaseName, $table]);

        foreach ($foreignKeys as $fk) {
            if (! isset($relationships[$fk->TABLE_NAME])) {
                $relationships[$fk->TABLE_NAME] = [];
            }

            $relationships[$fk->TABLE_NAME][$fk->COLUMN_NAME] = $fk->CONSTRAINT_NAME;
        }

        return $relationships;
    }

    /**
     * Update foreign key values in a table based on a mapping.
     *
     * @param  string  $table  The table to update
     * @param  string  $column  The column to update
     * @param  array  $mapping  A mapping of old values to new values
     */
    private function updateForeignKeyValues(string $table, string $column, array $mapping): void
    {
        foreach ($mapping as $oldId => $newId) {
            DB::table($table)
                ->where($column, $oldId)
                ->update([$column => $newId]);
        }
    }
};
