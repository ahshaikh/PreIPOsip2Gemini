<?php

namespace Tests\Feature;

use App\Models\CompanyDisclosure;
use App\Models\DisclosureVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

//  * PHASE 1 REMEDIATION - DisclosureVersion Immutability Tests
//  *
//  * Tests that DisclosureVersionObserver properly enforces immutability:
//  * 1. Blocks all update attempts
//  * 2. Blocks all delete attempts
//  * 3. Blocks force delete attempts
//  * 4. Logs security violations
//  * 5. Auto-locks on creation

class DisclosureVersionImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function version_is_auto_locked_on_creation()
    {
        $version = DisclosureVersion::factory()->create([
            'is_locked' => false, // Try to create unlocked
        ]);

        // Observer should force it to locked
        $this->assertTrue($version->fresh()->is_locked);
        $this->assertNotNull($version->fresh()->locked_at);
    }

    public function cannot_update_version_disclosure_data()
    {
        $version = DisclosureVersion::factory()->create([
            'disclosure_data' => ['original' => 'data'],
        ]);

        // Attempt to update disclosure data
        $result = $version->update([
            'disclosure_data' => ['modified' => 'data'],
        ]);

        // Update should be blocked (returns false)
        $this->assertFalse($result);

        // Data should remain unchanged
        $this->assertEquals(['original' => 'data'], $version->fresh()->disclosure_data);
    }

    public function cannot_update_version_hash()
    {
        $originalHash = hash('sha256', 'original');

        $version = DisclosureVersion::factory()->create([
            'version_hash' => $originalHash,
        ]);

        $result = $version->update([
            'version_hash' => hash('sha256', 'tampered'),
        ]);

        $this->assertFalse($result);
        $this->assertEquals($originalHash, $version->fresh()->version_hash);
    }

    public function cannot_update_version_number()
    {
        $version = DisclosureVersion::factory()->create([
            'version_number' => 1,
        ]);

        $result = $version->update([
            'version_number' => 999,
        ]);

        $this->assertFalse($result);
        $this->assertEquals(1, $version->fresh()->version_number);
    }

    public function cannot_update_approved_at_timestamp()
    {
        $originalTime = now()->subDays(10);

        $version = DisclosureVersion::factory()->create([
            'approved_at' => $originalTime,
        ]);

        $result = $version->update([
            'approved_at' => now(),
        ]);

        $this->assertFalse($result);
        $this->assertEquals($originalTime->timestamp, $version->fresh()->approved_at->timestamp);
    }

    public function cannot_delete_version()
    {
        $version = DisclosureVersion::factory()->create();

        $result = $version->delete();

        $this->assertFalse($result);
        $this->assertDatabaseHas('disclosure_versions', ['id' => $version->id]);
    }

    public function cannot_force_delete_version()
    {
        $version = DisclosureVersion::factory()->create();

        $result = $version->forceDelete();

        $this->assertFalse($result);
        $this->assertDatabaseHas('disclosure_versions', ['id' => $version->id]);
    }

    public function update_attempt_logs_critical_security_violation()
    {
        Log::spy();

        $version = DisclosureVersion::factory()->create([
            'disclosure_data' => ['test' => 'data'],
        ]);

        // Attempt update
        $version->update(['disclosure_data' => ['tampered' => 'data']]);

        // Assert critical log was created
        Log::shouldHaveReceived('critical')
            ->once()
            ->with(
                'IMMUTABILITY VIOLATION: Attempted to modify locked disclosure version',
                \Mockery::on(function ($context) use ($version) {
                    return isset($context['version_id']) &&
                           $context['version_id'] === $version->id &&
                           isset($context['attempted_changes']) &&
                           in_array('disclosure_data', $context['attempted_changes']);
                })
            );
    }

    public function delete_attempt_logs_critical_security_violation()
    {
        Log::spy();

        $version = DisclosureVersion::factory()->create();

        // Attempt delete
        $version->delete();

        // Assert critical log was created
        Log::shouldHaveReceived('critical')
            ->once()
            ->with(
                'IMMUTABILITY VIOLATION: Attempted to delete disclosure version',
                \Mockery::on(function ($context) use ($version) {
                    return isset($context['version_id']) &&
                           $context['version_id'] === $version->id;
                })
            );
    }

    public function force_delete_attempt_logs_emergency_violation()
    {
        Log::spy();

        $version = DisclosureVersion::factory()->create();

        // Attempt force delete
        $version->forceDelete();

        // Assert emergency log was created
        Log::shouldHaveReceived('emergency')
            ->once()
            ->with(
                'CRITICAL: Force delete attempted on disclosure version',
                \Mockery::on(function ($context) use ($version) {
                    return isset($context['version_id']) &&
                           $context['version_id'] === $version->id;
                })
            );
    }

    public function version_hash_integrity_can_be_verified()
    {
        $data = ['test' => 'data', 'items' => [1, 2, 3]];
        $correctHash = hash('sha256', json_encode($data));

        $version = DisclosureVersion::factory()->create([
            'disclosure_data' => $data,
            'version_hash' => $correctHash,
        ]);

        $this->assertTrue($version->verifyIntegrity());
    }

    public function version_hash_integrity_fails_on_data_mismatch()
    {
        $version = DisclosureVersion::factory()->create([
            'disclosure_data' => ['original' => 'data'],
            'version_hash' => hash('sha256', json_encode(['tampered' => 'data'])),
        ]);

        $this->assertFalse($version->verifyIntegrity());
    }

    public function investor_visibility_can_be_marked()
    {
        $version = DisclosureVersion::factory()->create([
            'was_investor_visible' => false,
            'first_investor_view_at' => null,
        ]);

        $version->markAsInvestorVisible();

        $refreshed = $version->fresh();
        $this->assertTrue($refreshed->was_investor_visible);
        $this->assertNotNull($refreshed->first_investor_view_at);
    }

    public function investor_view_count_can_be_incremented()
    {
        $version = DisclosureVersion::factory()->create([
            'investor_view_count' => 0,
        ]);

        $version->incrementViewCount();
        $version->incrementViewCount();
        $version->incrementViewCount();

        $this->assertEquals(3, $version->fresh()->investor_view_count);
    }

    public function incrementing_view_count_marks_as_investor_visible()
    {
        $version = DisclosureVersion::factory()->create([
            'was_investor_visible' => false,
            'investor_view_count' => 0,
        ]);

        $version->incrementViewCount();

        $refreshed = $version->fresh();
        $this->assertTrue($refreshed->was_investor_visible);
        $this->assertNotNull($refreshed->first_investor_view_at);
        $this->assertEquals(1, $refreshed->investor_view_count);
    }

    public function transactions_can_be_linked_to_version()
    {
        $version = DisclosureVersion::factory()->create([
            'linked_transactions' => null,
        ]);

        $version->linkTransaction(12345, ['amount' => 50000]);
        $version->linkTransaction(12346, ['amount' => 75000]);

        $refreshed = $version->fresh();
        $this->assertCount(2, $refreshed->linked_transactions);
        $this->assertEquals(12345, $refreshed->linked_transactions[0]['transaction_id']);
        $this->assertEquals(12346, $refreshed->linked_transactions[1]['transaction_id']);
    }

    public function has_linked_transactions_check_works()
    {
        $versionWithTransactions = DisclosureVersion::factory()->withTransactions()->create();
        $versionWithoutTransactions = DisclosureVersion::factory()->create();

        $this->assertTrue($versionWithTransactions->hasLinkedTransactions());
        $this->assertFalse($versionWithoutTransactions->hasLinkedTransactions());
    }

    public function linked_transaction_count_works()
    {
        $version = DisclosureVersion::factory()->create([
            'linked_transactions' => null,
        ]);

        $this->assertEquals(0, $version->getLinkedTransactionCount());

        $version->linkTransaction(1);
        $version->linkTransaction(2);
        $version->linkTransaction(3);

        $this->assertEquals(3, $version->fresh()->getLinkedTransactionCount());
    }

    public function mass_update_is_also_blocked()
    {
        $version1 = DisclosureVersion::factory()->create(['version_number' => 1]);
        $version2 = DisclosureVersion::factory()->create(['version_number' => 2]);

        // Attempt mass update
        DisclosureVersion::whereIn('id', [$version1->id, $version2->id])
            ->update(['version_number' => 999]);

        // Both should remain unchanged
        $this->assertEquals(1, $version1->fresh()->version_number);
        $this->assertEquals(2, $version2->fresh()->version_number);
    }

    public function version_created_from_disclosure_is_immutable()
    {
        $disclosure = CompanyDisclosure::factory()->create([
            'disclosure_data' => ['business' => 'model'],
        ]);

        $admin = User::factory()->create();

        $version = DisclosureVersion::createFromDisclosure($disclosure, $admin->id, 'Test approval');

        // Verify it's locked
        $this->assertTrue($version->is_locked);
        $this->assertNotNull($version->locked_at);

        // Verify it can't be modified
        $result = $version->update(['disclosure_data' => ['tampered' => 'data']]);
        $this->assertFalse($result);
    }

    public function version_factory_creates_locked_versions()
    {
        $version = DisclosureVersion::factory()->create();

        $this->assertTrue($version->is_locked);
        $this->assertNotNull($version->locked_at);
        $this->assertNotNull($version->approved_at);
    }
}
