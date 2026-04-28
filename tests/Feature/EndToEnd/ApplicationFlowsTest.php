<?php

namespace Tests\Feature\EndToEnd;

use App\Models\Corporation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_complete_core_tenant_flow(): void
    {
        [$tenant, $user] = $this->createTenantAndUser('alpha');

        $tenant->makeCurrent();

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/user')
            ->assertUnauthorized();

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/translations/en')
            ->assertOk()
            ->assertJsonPath('login.title', 'Login');

        Sanctum::actingAs($user);

        $productName = 'E2E Product '.Str::upper(Str::random(6));

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->postJson('/api/product', [[
                'name' => $productName,
                'unit' => 'kg',
                'properties' => [],
            ]])
            ->assertCreated();

        $productsResponse = $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/product')
            ->assertOk()
            ->assertJsonFragment(['name' => $productName]);

        $productUuid = (string) collect($productsResponse->json())
            ->firstWhere('name', $productName)['uuid'];

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->postJson('/api/warehouse', [[
                'name' => 'E2E Warehouse '.Str::upper(Str::random(4)),
                'city' => 'Kampala',
                'country' => 'UG',
            ]])
            ->assertCreated();

        $warehousesResponse = $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/warehouse')
            ->assertOk();

        $warehouseUuid = (string) collect($warehousesResponse->json())->first()['uuid'];

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->postJson('/api/harvest', [[
                'product_uuid' => $productUuid,
                'warehouse_uuid' => $warehouseUuid,
                'quantity' => 120,
                'location' => 'A1',
                'harvested_on' => now()->toISOString(),
                'quality' => 'high',
                'replace_quantity' => true,
            ]])
            ->assertCreated();

        $harvestsResponse = $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/harvest')
            ->assertOk();

        $batchUuid = (string) collect($harvestsResponse->json())->first()['batch_uuid'];

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/inventory')
            ->assertOk()
            ->assertJsonFragment([
                'batch_uuid' => $batchUuid,
                'product_uuid' => $productUuid,
            ]);

        $sellResponse = $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->postJson('/api/inventory/sell', [
                'batch_uuid' => $batchUuid,
                'amount' => 10,
                'price' => 2500,
                'currency' => 'UGX',
                'buyer_name' => 'Buyer One',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Inventory sold.');

        $saleUuid = (string) $sellResponse->json('sale_uuid');

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/sales')
            ->assertOk()
            ->assertJsonFragment([
                'uuid' => $saleUuid,
                'currency' => 'UGX',
            ]);

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/sales/'.$saleUuid)
            ->assertOk()
            ->assertJsonPath('uuid', $saleUuid);

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/harvest/public/'.$batchUuid)
            ->assertOk()
            ->assertJsonPath('batch_uuid', $batchUuid);

        $this->withServerVariables(['HTTP_HOST' => $tenant->domain])
            ->getJson('/api/currencies')
            ->assertOk();
    }

    public function test_tenants_are_isolated_across_products_warehouses_and_sales(): void
    {
        [$tenantA, $userA] = $this->createTenantAndUser('tenant-a');
        [$tenantB, $userB] = $this->createTenantAndUser('tenant-b');

        $tenantA->makeCurrent();
        Sanctum::actingAs($userA);

        $productName = 'Tenant A Product '.Str::upper(Str::random(5));

        $this->withServerVariables(['HTTP_HOST' => $tenantA->domain])
            ->postJson('/api/product', [[
                'name' => $productName,
                'unit' => 'pcs',
                'properties' => [],
            ]])
            ->assertCreated();

        $productAUuid = (string) collect(
            $this->withServerVariables(['HTTP_HOST' => $tenantA->domain])
                ->getJson('/api/product')
                ->assertOk()
                ->json()
        )->firstWhere('name', $productName)['uuid'];

        $this->withServerVariables(['HTTP_HOST' => $tenantA->domain])
            ->postJson('/api/warehouse', [[
                'name' => 'Tenant A Warehouse '.Str::upper(Str::random(4)),
            ]])
            ->assertCreated();

        $warehouseAUuid = (string) collect(
            $this->withServerVariables(['HTTP_HOST' => $tenantA->domain])
                ->getJson('/api/warehouse')
                ->assertOk()
                ->json()
        )->first()['uuid'];

        $this->withServerVariables(['HTTP_HOST' => $tenantA->domain])
            ->postJson('/api/harvest', [[
                'product_uuid' => $productAUuid,
                'warehouse_uuid' => $warehouseAUuid,
                'quantity' => 25,
                'location' => 'A2',
                'harvested_on' => now()->toISOString(),
                'quality' => 'medium',
                'replace_quantity' => true,
            ]])
            ->assertCreated();

        $batchAUuid = (string) collect(
            $this->withServerVariables(['HTTP_HOST' => $tenantA->domain])
                ->getJson('/api/harvest')
                ->assertOk()
                ->json()
        )->first()['batch_uuid'];

        $this->withServerVariables(['HTTP_HOST' => $tenantA->domain])
            ->postJson('/api/inventory/sell', [
                'batch_uuid' => $batchAUuid,
                'amount' => 5,
                'price' => 12,
                'currency' => 'USD',
            ])
            ->assertOk();

        $tenantB->makeCurrent();
        Sanctum::actingAs($userB);

        $this->withServerVariables(['HTTP_HOST' => $tenantB->domain])
            ->getJson('/api/product')
            ->assertOk()
            ->assertJsonMissing(['name' => $productName]);

        $this->withServerVariables(['HTTP_HOST' => $tenantB->domain])
            ->getJson('/api/warehouse')
            ->assertOk()
            ->assertJsonMissing(['uuid' => $warehouseAUuid]);

        $this->withServerVariables(['HTTP_HOST' => $tenantB->domain])
            ->getJson('/api/sales')
            ->assertOk()
            ->assertJsonCount(0);
    }

    /**
     * @return array{0: Corporation, 1: User}
     */
    private function createTenantAndUser(string $slug): array
    {
        $tenant = Corporation::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Tenant '.Str::title(str_replace('-', ' ', $slug)),
            'domain' => $slug.'.agriflow.test',
            'country' => 'UG',
        ]);

        $user = User::withoutGlobalScopes()->create([
            'name' => 'User '.Str::title(str_replace('-', ' ', $slug)),
            'email' => $slug.'@example.test',
            'password' => bcrypt('password'),
            'corporation_id' => $tenant->id,
        ]);

        return [$tenant, $user];
    }
}
