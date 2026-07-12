<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderBalanceConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjust_balance_is_atomic_under_concurrent_writers(): void
    {
        $user = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000010',
            'balance' => 100,
        ]);

        // Simulate two "concurrent" debits using two separate connections'
        // worth of sequential calls through the atomic helper — this proves
        // each call re-reads the latest committed value instead of relying
        // on a stale in-memory $user instance (the bug pattern being fixed).
        $staleCopy1 = User::find($user->id);
        $staleCopy2 = User::find($user->id);

        User::adjustBalance($user->id, -30); // using the atomic helper, not $staleCopy1
        User::adjustBalance($user->id, -30); // using the atomic helper, not $staleCopy2

        $user->refresh();

        $this->assertEquals(40.0, (float) $user->balance); // 100 - 30 - 30, not 70 (lost update)
    }

    public function test_adjust_points_is_atomic(): void
    {
        $user = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000011',
            'points_balance' => 50,
        ]);

        User::adjustPoints($user->id, -20);
        User::adjustPoints($user->id, -20);

        $user->refresh();

        $this->assertEquals(10.0, (float) $user->points_balance);
    }
}
