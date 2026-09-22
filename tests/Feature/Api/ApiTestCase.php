<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Business;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Never hit the real Anthropic (or any external) API from tests.
        Http::preventStrayRequests();
        config(['services.anthropic.key' => 'test-key']);
    }

    protected function makeUser(bool $pro = false, array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->plan = $pro ? 'pro' : 'free';
        $user->save();

        return $user;
    }

    protected function makeBusiness(User $owner, array $attrs = []): Business
    {
        $business = Business::create(array_merge([
            'owner_id' => $owner->id,
            'name'     => 'Biz ' . uniqid(),
            'currency' => 'USD',
        ], $attrs));
        $business->members()->attach($owner->id, ['role' => 'owner']);

        return $business;
    }

    protected function addMember(Business $business, User $user, string $role): void
    {
        $business->members()->attach($user->id, ['role' => $role]);
    }

    protected function makeBook(Business $business, array $attrs = []): Book
    {
        return $business->books()->create(array_merge([
            'name'            => 'Book ' . uniqid(),
            'opening_balance' => 0,
        ], $attrs));
    }

    protected function makeEntry(Book $book, string $type, string $amount, string $date, array $attrs = []): Entry
    {
        return $book->entries()->create(array_merge([
            'type'        => $type,
            'amount'      => $amount,
            'description' => ucfirst($type) . ' ' . $amount,
            'date'        => $date,
        ], $attrs));
    }

    protected function actingAsUser(User $user): User
    {
        Sanctum::actingAs($user);

        return $user;
    }
}
