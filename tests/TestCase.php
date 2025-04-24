<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * The number of queries executed during the test.
     *
     * @var int
     */
    protected $queryCount = 0;

    /**
     * Setup query counter.
     */
    protected function setupQueryCounter(): void
    {
        $this->queryCount = 0;
        DB::listen(function (QueryExecuted $query) {
            $this->queryCount++;
        });
    }

    /**
     * Assert that the number of database queries executed is less than or equal to a given amount.
     *
     * @param int $max
     * @return void
     */
    protected function assertQueryCountLessThanOrEqual(int $max): void
    {
        $this->assertLessThanOrEqual(
            $max,
            $this->queryCount,
            "Expected query count to be less than or equal to {$max}, but got {$this->queryCount}."
        );
    }

    /**
     * Assert that the execution of the given callback results in the expected number of database queries.
     *
     * @param int $expectedCount
     * @param callable $callback
     * @return void
     */
    protected function assertQueryCount(int $expectedCount, callable $callback): void
    {
        // Reset the query count
        $this->queryCount = 0;

        // Set up a query counter
        $queryListener = function () {
            $this->queryCount++;
        };

        DB::listen($queryListener);

        // Execute the callback
        $callback();

        // Remove the listener
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        // Assert the query count
        $this->assertEquals(
            $expectedCount,
            $this->queryCount,
            "Expected {$expectedCount} queries, but got {$this->queryCount}."
        );
    }

    /**
     * Assert that the execution of the given callback results in fewer queries than another callback.
     *
     * @param callable $inefficientCallback
     * @param callable $efficientCallback
     * @param string $message
     * @return void
     */
    protected function assertQueryCountLess(callable $inefficientCallback, callable $efficientCallback, string $message = ''): void
    {
        // Count queries for the first callback
        $this->queryCount = 0;
        DB::listen(function () {
            $this->queryCount++;
        });
        $inefficientCallback();
        $inefficientCount = $this->queryCount;
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        // Count queries for the second callback
        $this->queryCount = 0;
        DB::listen(function () {
            $this->queryCount++;
        });
        $efficientCallback();
        $efficientCount = $this->queryCount;
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        // Make the assertion
        $this->assertLessThan(
            $inefficientCount,
            $efficientCount,
            $message ?: "Expected fewer than {$inefficientCount} queries, but got {$efficientCount}."
        );
    }

    /**
     * Assert that an index is used in the query.
     *
     * @param string $table
     * @param string $column
     * @param mixed $value
     * @return void
     */
    protected function assertIndexUsed(string $table, string $column, $value): void
    {
        $explain = DB::select("EXPLAIN SELECT * FROM {$table} WHERE {$column} = ?", [$value]);
        
        // Check if 'possible_keys' column contains a value, which indicates an index might be used
        $this->assertNotNull(
            $explain[0]->possible_keys ?? null,
            "No index found for {$table}.{$column}"
        );
    }
}
