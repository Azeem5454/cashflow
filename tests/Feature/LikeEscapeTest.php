<?php

namespace Tests\Feature;

use App\Support\LikeSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Production ran PHP 8.3, where PDO's parser reads the `\'` inside
 * `ESCAPE '\'` as an escaped quote, decides the string literal is still open,
 * and miscounts every placeholder after it — SQLSTATE[HY093]. Search returned
 * 500s on the live API while passing locally on PHP 8.4+, which parses it
 * correctly.
 *
 * These tests fail on any PHP version, because they assert on the SQL text
 * rather than on whether the query happens to run.
 */
class LikeEscapeTest extends TestCase
{
    use RefreshDatabase;

    /** Every LIKE in the app must avoid a backslash escape character. */
    public function test_no_query_uses_a_backslash_like_escape(): void
    {
        $offenders = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            // The helper documents the broken form in prose; that's the point.
            if ($file->getFilename() === 'LikeSearch.php') {
                continue;
            }

            $body = (string) file_get_contents($file->getPathname());

            // Matches ESCAPE '\' as it appears in source (escaped or not).
            if (preg_match("/ESCAPE\s+'\\\\+'/", $body)) {
                $offenders[] = str_replace(base_path() . '/', '', $file->getPathname());
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These files use a backslash LIKE escape, which breaks on PHP 8.3 PDO:\n" . implode("\n", $offenders)
        );
    }

    public function test_the_escape_character_needs_no_sql_escaping(): void
    {
        $this->assertSame('!', LikeSearch::ESCAPE);
        $this->assertStringNotContainsString('\\', LikeSearch::CLAUSE);
    }

    public function test_it_neutralises_wildcards_in_the_users_term(): void
    {
        // A term of "100%" must not match everything.
        $this->assertSame('%100!%%', LikeSearch::contains('100%'));
        $this->assertSame('%a!_b%', LikeSearch::contains('a_b'));
        // The escape character itself is escaped.
        $this->assertSame('%a!!b%', LikeSearch::contains('a!b'));
        $this->assertSame('%café%', LikeSearch::contains('CAFÉ'));
    }

    public function test_search_sql_carries_matching_placeholder_and_binding_counts(): void
    {
        $user = \App\Models\User::factory()->create();
        $business = \App\Models\Business::create([
            'owner_id' => $user->id, 'name' => 'Acme', 'currency' => 'USD',
        ]);
        $business->members()->attach($user->id, ['role' => 'owner']);
        $book = $business->books()->create(['name' => 'Sep', 'opening_balance' => '0.00']);
        $book->entries()->create([
            'type' => 'out', 'amount' => '120.00', 'description' => 'Fuel', 'date' => now()->toDateString(),
        ]);

        $query = \App\Services\EntrySearch::query(
            $user,
            \App\Services\EntrySearch::normalise(['q' => 'fuel', 'from' => now()->subMonth()->toDateString()])
        );

        $this->assertNotNull($query);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertSame(
            substr_count($sql, '?'),
            count($bindings),
            "Placeholder count must equal binding count.\nSQL: {$sql}"
        );
        $this->assertStringNotContainsString("ESCAPE '\\'", $sql);
    }
}
