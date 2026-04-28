import { expect, test } from '@playwright/test';

async function login(page: Parameters<Parameters<typeof test>[1]>[0]['page'], email: string, password: string) {
  await page.goto('/login');

  await page.getByRole('textbox').first().fill(email);
  await page.getByLabel(/password|nenosiri|ebyokusanyusa/i).fill(password);
  await page.getByRole('button', { name: /sign in|ingia/i }).click();

  await expect(page).not.toHaveURL(/\/login/);
}

function sumRevenueByCurrency(sales: Array<{ currency?: string; total_value?: number | string | null }>) {
  return sales.reduce<Record<string, number>>((totals, sale) => {
    const currency = sale.currency ?? 'USD';
    const amount = Number(sale.total_value ?? 0);

    if (!Number.isFinite(amount)) {
      return totals;
    }

    totals[currency] = (totals[currency] ?? 0) + amount;
    return totals;
  }, {});
}

function formatCurrency(amount: number, currency: string) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount);
}

async function callApi(page: Parameters<Parameters<typeof test>[1]>[0]['page'], path: string, method = 'GET', body?: unknown) {
  return page.evaluate(async ({ path, method, body }) => {
    const csrfCookie = document.cookie
      .split('; ')
      .find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    const xsrfToken = csrfCookie ? decodeURIComponent(csrfCookie.split('=').slice(1).join('=')) : null;

    const response = await fetch(path, {
      method,
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      },
      body: body === undefined ? undefined : JSON.stringify(body),
    });

    const raw = await response.text();
    let data: unknown = null;

    try {
      data = raw ? JSON.parse(raw) : null;
    } catch {
      data = raw;
    }

    return {
      status: response.status,
      data,
    };
  }, { path, method, body });
}

async function cleanupDelete(page: Parameters<Parameters<typeof test>[1]>[0]['page'], path: string) {
  const response = await callApi(page, path, 'DELETE');

  // Ignore resources already absent, but keep other failures visible in test output.
  if (![200, 404].includes(response.status)) {
    throw new Error(`Cleanup failed for ${path} with status ${response.status}`);
  }
}

test.describe('Guest flows', () => {
  test('login and forgot-password pages are accessible', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: /login|ingia/i })).toBeVisible();

    const forgotLink = page.getByRole('link', { name: /forgot|werabidde|umesahau/i });
    await expect(forgotLink).toBeVisible();
    await forgotLink.click();

    await expect(page).toHaveURL(/\/forgot-password/);

    await page.getByRole('textbox').first().fill(process.env.PLAYWRIGHT_FORGOT_EMAIL ?? 'joannah@gmail.com');
    await page.getByRole('button', { name: /send|tuma|kutuma|inatuma/i }).click();

    await expect(page.locator('text=/reset link|kiungo|link/i')).toBeVisible();
  });

  test('set-password page with token and email query renders', async ({ page }) => {
    await page.goto('/set-password?token=fake-token&email=e2e@example.test');
    await expect(page.getByRole('heading', { name: /set password|weka nenosiri|saza ebyokusanyusa/i })).toBeVisible();
  });
});

test.describe('Authenticated core navigation', () => {
  test('user can login and open main modules', async ({ page }) => {
    const email = process.env.PLAYWRIGHT_LOGIN_EMAIL;
    const password = process.env.PLAYWRIGHT_LOGIN_PASSWORD;

    test.skip(!email || !password, 'Set PLAYWRIGHT_LOGIN_EMAIL and PLAYWRIGHT_LOGIN_PASSWORD for authenticated E2E flows.');

    await login(page, email!, password!);

    await page.goto('/');
    await expect(page.getByText(/total revenue|obungibwa bwonna|jumla ya mapato/i)).toBeVisible();

    await page.goto('/products');
    await expect(page.getByRole('heading', { name: /products|ebintu|bidhaa/i })).toBeVisible();

    await page.goto('/warehouses');
    await expect(page.getByRole('heading', { name: /warehouses|sitowa|maghala/i })).toBeVisible();

    await page.goto('/harvests');
    await expect(page.getByRole('heading', { name: /harvests|amakungula/i })).toBeVisible();

    await page.goto('/inventory');
    await expect(page.getByRole('heading', { name: /inventory|sitooko/i })).toBeVisible();

    await page.goto('/sales');
    await expect(page.getByRole('heading', { name: /sell inventory|mauzo|okugyako/i })).toBeVisible();

    await page.goto('/sales-history');
    await expect(page.getByRole('heading', { name: /sales history|historia ya mauzo|ebifaananyo bya okugyako/i })).toBeVisible();
  });

  test('user can register harvests, sell in both currencies, and see revenue updates', async ({ page }) => {
    const email = process.env.PLAYWRIGHT_LOGIN_EMAIL;
    const password = process.env.PLAYWRIGHT_LOGIN_PASSWORD;

    test.skip(!email || !password, 'Set PLAYWRIGHT_LOGIN_EMAIL and PLAYWRIGHT_LOGIN_PASSWORD for authenticated E2E flows.');

    await login(page, email!, password!);

    const runId = Date.now().toString();
    const productName = `PW Harvest ${runId}`;
    const warehouseName = `PW Warehouse ${runId}`;
    const buyerUsd = `Buyer USD ${runId}`;
    const buyerUgx = `Buyer UGX ${runId}`;

    let createdProductUuid: string | null = null;
    let createdWarehouseUuid: string | null = null;
    let createdBatchUuid: string | null = null;

    try {
      const beforeSalesResponse = await callApi(page, '/api/sales');
      expect(beforeSalesResponse.status).toBe(200);
      const beforeSales = (Array.isArray(beforeSalesResponse.data) ? beforeSalesResponse.data : []) as Array<{ currency?: string; total_value?: number | string | null }>;
      const baselineTotals = sumRevenueByCurrency(beforeSales);

      const createProductResponse = await callApi(page, '/api/product', 'POST', [{
        name: productName,
        unit: 'kg',
        properties: [],
      }]);
      expect(createProductResponse.status).toBe(201);

      const productsResponse = await callApi(page, '/api/product');
      expect(productsResponse.status).toBe(200);
      const products = (Array.isArray(productsResponse.data) ? productsResponse.data : []) as Array<{ uuid: string; name: string }>;
      const createdProduct = products.find((product) => product.name === productName);
      expect(createdProduct).toBeTruthy();
      createdProductUuid = createdProduct!.uuid;

      const createWarehouseResponse = await callApi(page, '/api/warehouse', 'POST', [{
        name: warehouseName,
        city: 'Kampala',
        country: 'UG',
      }]);
      expect(createWarehouseResponse.status).toBe(201);

      const warehousesResponse = await callApi(page, '/api/warehouse');
      expect(warehousesResponse.status).toBe(200);
      const warehouses = (Array.isArray(warehousesResponse.data) ? warehousesResponse.data : []) as Array<{ uuid: string; name: string }>;
      const createdWarehouse = warehouses.find((warehouse) => warehouse.name === warehouseName);
      expect(createdWarehouse).toBeTruthy();
      createdWarehouseUuid = createdWarehouse!.uuid;

      const createHarvestResponse = await callApi(page, '/api/harvest', 'POST', [{
        product_uuid: createdProduct!.uuid,
        warehouse_uuid: createdWarehouse!.uuid,
        quantity: 80,
        location: 'E2E-A1',
        harvested_on: new Date().toISOString(),
        quality: 'high',
        replace_quantity: true,
      }]);
      expect(createHarvestResponse.status).toBe(201);

      const harvestsResponse = await callApi(page, '/api/harvest');
      expect(harvestsResponse.status).toBe(200);
      const harvests = (Array.isArray(harvestsResponse.data) ? harvestsResponse.data : []) as Array<{ batch_uuid: string; product_uuid: string; warehouse_uuid: string }>;
      const harvest = harvests.find(
        (row) => row.product_uuid === createdProduct!.uuid && row.warehouse_uuid === createdWarehouse!.uuid,
      );
      expect(harvest).toBeTruthy();
      createdBatchUuid = harvest!.batch_uuid;

      const sellUsdResponse = await callApi(page, '/api/inventory/sell', 'POST', {
        batch_uuid: harvest!.batch_uuid,
        amount: 10,
        price: 5,
        currency: 'USD',
        buyer_name: buyerUsd,
      });
      expect(sellUsdResponse.status).toBe(200);
      const sellUsdData = (sellUsdResponse.data ?? {}) as { sale_uuid?: string; currency?: string; total_value?: number | string | null };
      expect(sellUsdData.currency).toBe('USD');
      expect(Number(sellUsdData.total_value ?? 0)).toBe(50);
      expect(sellUsdData.sale_uuid).toBeTruthy();

      const sellUgxResponse = await callApi(page, '/api/inventory/sell', 'POST', {
        batch_uuid: harvest!.batch_uuid,
        amount: 20,
        price: 1000,
        currency: 'UGX',
        buyer_name: buyerUgx,
      });
      expect(sellUgxResponse.status).toBe(200);
      const sellUgxData = (sellUgxResponse.data ?? {}) as { sale_uuid?: string; currency?: string; total_value?: number | string | null };
      expect(sellUgxData.currency).toBe('UGX');
      expect(Number(sellUgxData.total_value ?? 0)).toBe(20000);
      expect(sellUgxData.sale_uuid).toBeTruthy();

      const afterSalesResponse = await callApi(page, '/api/sales');
      expect(afterSalesResponse.status).toBe(200);
      const afterSales = (Array.isArray(afterSalesResponse.data) ? afterSalesResponse.data : []) as Array<{ uuid?: string; currency?: string; total_value?: number | string | null }>;

      const usdSale = afterSales.find((sale) => sale.uuid === sellUsdData.sale_uuid);
      const ugxSale = afterSales.find((sale) => sale.uuid === sellUgxData.sale_uuid);
      expect(usdSale?.currency).toBe('USD');
      expect(Number(usdSale?.total_value ?? 0)).toBe(50);
      expect(ugxSale?.currency).toBe('UGX');
      expect(Number(ugxSale?.total_value ?? 0)).toBe(20000);

      const afterTotals = sumRevenueByCurrency(afterSales);
      const expectedUsdTotal = (baselineTotals.USD ?? 0) + 50;
      const expectedUgxTotal = (baselineTotals.UGX ?? 0) + 20000;

      expect(afterTotals.USD ?? 0).toBe(expectedUsdTotal);
      expect(afterTotals.UGX ?? 0).toBe(expectedUgxTotal);

      await page.goto('/');
      await expect(page.getByText(/total revenue|obungibwa bwonna|jumla ya mapato/i)).toBeVisible();

      const usdRevenueLine = page.locator('p').filter({ hasText: /^USD:/ }).first();
      const ugxRevenueLine = page.locator('p').filter({ hasText: /^UGX:/ }).first();

      await expect(usdRevenueLine).toContainText(formatCurrency(expectedUsdTotal, 'USD'));
      await expect(ugxRevenueLine).toContainText(formatCurrency(expectedUgxTotal, 'UGX'));
    } finally {
      if (createdBatchUuid) {
        await cleanupDelete(page, `/api/harvest/${createdBatchUuid}`);
      }

      if (createdWarehouseUuid) {
        await cleanupDelete(page, `/api/warehouse/${createdWarehouseUuid}`);
      }

      if (createdProductUuid) {
        await cleanupDelete(page, `/api/product/${createdProductUuid}`);
      }
    }
  });
});
