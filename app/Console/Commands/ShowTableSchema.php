<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShowTableSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:show {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the schema of a given table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tableName = $this->argument('table');

        if (! Schema::hasTable($tableName)) {
            $this->error("Table {$tableName} does not exist");

            return Command::FAILURE;
        }

        $columns = Schema::getColumnListing($tableName);

        $this->info("Columns for table: {$tableName}");
        $this->newLine();

        foreach ($columns as $column) {
            $type = DB::getSchemaBuilder()->getColumnType($tableName, $column);
            $this->line("- {$column} ({$type})");
        }

        return Command::SUCCESS;
    }
}
