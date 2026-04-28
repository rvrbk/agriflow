# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: app-flows.spec.ts >> Authenticated core navigation >> user can login and open main modules
- Location: tests\e2e\app-flows.spec.ts:168:3

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: getByText(/total revenue|obungibwa bwonna|jumla ya mapato/i)
Expected: visible
Error: strict mode violation: getByText(/total revenue|obungibwa bwonna|jumla ya mapato/i) resolved to 2 elements:
    1) <p class="text-sm text-[#4e5f4f]">Overview of total revenue and warehouse inventory.</p> aka getByText('Overview of total revenue and')
    2) <p class="text-sm font-medium text-[#4e5f4f] mt-1">Total Revenue</p> aka getByText('Total Revenue', { exact: true })

Call log:
  - Expect "toBeVisible" with timeout 10000ms
  - waiting for getByText(/total revenue|obungibwa bwonna|jumla ya mapato/i)

```

# Page snapshot

```yaml
- generic [ref=e3]:
  - banner [ref=e4]:
    - generic [ref=e5]:
      - generic [ref=e6]: AF
      - generic [ref=e7]:
        - paragraph [ref=e8]: AgriFlow
        - paragraph [ref=e9]: Operations Console
    - generic [ref=e10]:
      - generic [ref=e11]: Language
      - combobox "Language" [ref=e12]:
        - option "English" [selected]
        - option "Luganda"
        - option "Swahili"
      - button "Toggle menu" [ref=e13] [cursor=pointer]
      - button "Logout" [ref=e17]:
        - img [ref=e18]
        - generic [ref=e20]: Logout
  - navigation "Primary menu":
    - generic:
      - generic:
        - paragraph: Main
        - link "Dashboard":
          - /url: /
        - link "Harvests":
          - /url: /harvests
        - link "Sales":
          - /url: /sales
        - link "Sales History":
          - /url: /sales-history
      - generic:
        - paragraph: Management
        - link "Products":
          - /url: /products
        - link "Inventory":
          - /url: /inventory
        - link "Corporation":
          - /url: /corporations
        - link "Warehouses":
          - /url: /warehouses
        - link "Users":
          - /url: /users
  - main [ref=e21]:
    - generic [ref=e22]:
      - generic [ref=e23]:
        - heading "Dashboard" [level=1] [ref=e24]
        - paragraph [ref=e25]: Overview of total revenue and warehouse inventory.
      - generic [ref=e26]:
        - link "Sell Inventory" [ref=e27] [cursor=pointer]:
          - /url: /sales
          - img [ref=e28]
          - text: Sell Inventory
        - link "View Sales History" [ref=e30] [cursor=pointer]:
          - /url: /sales-history
          - img [ref=e31]
          - text: View Sales History
      - generic [ref=e33]:
        - generic [ref=e34]:
          - generic [ref=e35]:
            - paragraph [ref=e36]: Total Revenue
            - paragraph [ref=e37]: UGX 68,000 UGX
            - paragraph [ref=e38]: 4 sales
          - generic [ref=e39]:
            - generic [ref=e40]: "View in:"
            - combobox [ref=e41]:
              - option "UGX" [selected]
              - option "USD"
            - link "View Details" [ref=e42] [cursor=pointer]:
              - /url: /sales-history
        - paragraph [ref=e44]:
          - generic [ref=e45]: "UGX:"
          - generic [ref=e46]: UGX 68,000
      - generic [ref=e47]:
        - generic [ref=e48]: Search overview
        - textbox "Search overview" [ref=e49]:
          - /placeholder: Search by warehouse, product, quantity, or unit...
      - generic [ref=e50]:
        - heading "Warehouses" [level=2] [ref=e51]
        - generic [ref=e52]:
          - article [ref=e53]:
            - generic [ref=e54]:
              - heading "Aw5" [level=2] [ref=e55]
              - generic [ref=e56]: "Products: 1"
            - paragraph [ref=e57]: "Total Quantity: 19.00"
            - table [ref=e59]:
              - rowgroup [ref=e60]:
                - row "Product Quantity Unit" [ref=e61]:
                  - columnheader "Product" [ref=e62]
                  - columnheader "Quantity" [ref=e63]
                  - columnheader "Unit" [ref=e64]
              - rowgroup [ref=e65]:
                - row "Eggs 19.00 pcs" [ref=e66]:
                  - cell "Eggs" [ref=e67]
                  - cell "19.00" [ref=e68]
                  - cell "pcs" [ref=e69]
          - article [ref=e70]:
            - generic [ref=e71]:
              - heading "PW Sync Base Warehouse 1777388489295" [level=2] [ref=e72]
              - generic [ref=e73]: "Products: 1"
            - paragraph [ref=e74]: "Total Quantity: 40.00"
            - table [ref=e76]:
              - rowgroup [ref=e77]:
                - row "Product Quantity Unit" [ref=e78]:
                  - columnheader "Product" [ref=e79]
                  - columnheader "Quantity" [ref=e80]
                  - columnheader "Unit" [ref=e81]
              - rowgroup [ref=e82]:
                - row "PW Sync Base Product 1777388489295 40.00 kg" [ref=e83]:
                  - cell "PW Sync Base Product 1777388489295" [ref=e84]
                  - cell "40.00" [ref=e85]
                  - cell "kg" [ref=e86]
          - article [ref=e87]:
            - generic [ref=e88]:
              - heading "PW Warehouse 1777380632352" [level=2] [ref=e89]
              - generic [ref=e90]: "Products: 1"
            - paragraph [ref=e91]: "Total Quantity: 50.00"
            - table [ref=e93]:
              - rowgroup [ref=e94]:
                - row "Product Quantity Unit" [ref=e95]:
                  - columnheader "Product" [ref=e96]
                  - columnheader "Quantity" [ref=e97]
                  - columnheader "Unit" [ref=e98]
              - rowgroup [ref=e99]:
                - row "PW Harvest 1777380632352 50.00 kg" [ref=e100]:
                  - cell "PW Harvest 1777380632352" [ref=e101]
                  - cell "50.00" [ref=e102]
                  - cell "kg" [ref=e103]
          - article [ref=e104]:
            - generic [ref=e105]:
              - heading "PW Warehouse 1777380654220" [level=2] [ref=e106]
              - generic [ref=e107]: "Products: 1"
            - paragraph [ref=e108]: "Total Quantity: 50.00"
            - table [ref=e110]:
              - rowgroup [ref=e111]:
                - row "Product Quantity Unit" [ref=e112]:
                  - columnheader "Product" [ref=e113]
                  - columnheader "Quantity" [ref=e114]
                  - columnheader "Unit" [ref=e115]
              - rowgroup [ref=e116]:
                - row "PW Harvest 1777380654220 50.00 kg" [ref=e117]:
                  - cell "PW Harvest 1777380654220" [ref=e118]
                  - cell "50.00" [ref=e119]
                  - cell "kg" [ref=e120]
          - article [ref=e121]:
            - generic [ref=e122]:
              - heading "PW Warehouse 1777380667588" [level=2] [ref=e123]
              - generic [ref=e124]: "Products: 1"
            - paragraph [ref=e125]: "Total Quantity: 50.00"
            - table [ref=e127]:
              - rowgroup [ref=e128]:
                - row "Product Quantity Unit" [ref=e129]:
                  - columnheader "Product" [ref=e130]
                  - columnheader "Quantity" [ref=e131]
                  - columnheader "Unit" [ref=e132]
              - rowgroup [ref=e133]:
                - row "PW Harvest 1777380667588 50.00 kg" [ref=e134]:
                  - cell "PW Harvest 1777380667588" [ref=e135]
                  - cell "50.00" [ref=e136]
                  - cell "kg" [ref=e137]
          - article [ref=e138]:
            - generic [ref=e139]:
              - heading "PW Warehouse 1777380705905" [level=2] [ref=e140]
              - generic [ref=e141]: "Products: 1"
            - paragraph [ref=e142]: "Total Quantity: 50.00"
            - table [ref=e144]:
              - rowgroup [ref=e145]:
                - row "Product Quantity Unit" [ref=e146]:
                  - columnheader "Product" [ref=e147]
                  - columnheader "Quantity" [ref=e148]
                  - columnheader "Unit" [ref=e149]
              - rowgroup [ref=e150]:
                - row "PW Harvest 1777380705905 50.00 kg" [ref=e151]:
                  - cell "PW Harvest 1777380705905" [ref=e152]
                  - cell "50.00" [ref=e153]
                  - cell "kg" [ref=e154]
```

# Test source

```ts
  77  |   return sales.reduce<Record<string, number>>((totals, sale) => {
  78  |     const currency = sale.currency ?? 'USD';
  79  |     const amount = Number(sale.total_value ?? 0);
  80  | 
  81  |     if (!Number.isFinite(amount)) {
  82  |       return totals;
  83  |     }
  84  | 
  85  |     totals[currency] = (totals[currency] ?? 0) + amount;
  86  |     return totals;
  87  |   }, {});
  88  | }
  89  | 
  90  | function formatCurrency(amount: number, currency: string) {
  91  |   return new Intl.NumberFormat('en-US', {
  92  |     style: 'currency',
  93  |     currency,
  94  |     minimumFractionDigits: 0,
  95  |     maximumFractionDigits: 2,
  96  |   }).format(amount);
  97  | }
  98  | 
  99  | async function callApi(page: Parameters<Parameters<typeof test>[1]>[0]['page'], path: string, method = 'GET', body?: unknown) {
  100 |   return page.evaluate(async ({ path, method, body }) => {
  101 |     const csrfCookie = document.cookie
  102 |       .split('; ')
  103 |       .find((cookie) => cookie.startsWith('XSRF-TOKEN='));
  104 | 
  105 |     const xsrfToken = csrfCookie ? decodeURIComponent(csrfCookie.split('=').slice(1).join('=')) : null;
  106 | 
  107 |     const response = await fetch(path, {
  108 |       method,
  109 |       credentials: 'include',
  110 |       headers: {
  111 |         Accept: 'application/json',
  112 |         'Content-Type': 'application/json',
  113 |         'X-Requested-With': 'XMLHttpRequest',
  114 |         ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
  115 |       },
  116 |       body: body === undefined ? undefined : JSON.stringify(body),
  117 |     });
  118 | 
  119 |     const raw = await response.text();
  120 |     let data: unknown = null;
  121 | 
  122 |     try {
  123 |       data = raw ? JSON.parse(raw) : null;
  124 |     } catch {
  125 |       data = raw;
  126 |     }
  127 | 
  128 |     return {
  129 |       status: response.status,
  130 |       data,
  131 |     };
  132 |   }, { path, method, body });
  133 | }
  134 | 
  135 | async function cleanupDelete(page: Parameters<Parameters<typeof test>[1]>[0]['page'], path: string) {
  136 |   const response = await callApi(page, path, 'DELETE');
  137 | 
  138 |   // Ignore resources already absent, but keep other failures visible in test output.
  139 |   if (![200, 404].includes(response.status)) {
  140 |     throw new Error(`Cleanup failed for ${path} with status ${response.status}`);
  141 |   }
  142 | }
  143 | 
  144 | test.describe('Guest flows', () => {
  145 |   test('login and forgot-password pages are accessible', async ({ page }) => {
  146 |     await page.goto('/login');
  147 |     await expect(page.getByRole('heading', { name: /login|ingia/i })).toBeVisible();
  148 | 
  149 |     const forgotLink = page.getByRole('link', { name: /forgot|werabidde|umesahau/i });
  150 |     await expect(forgotLink).toBeVisible();
  151 |     await forgotLink.click();
  152 | 
  153 |     await expect(page).toHaveURL(/\/forgot-password/);
  154 | 
  155 |     await page.getByRole('textbox').first().fill(process.env.PLAYWRIGHT_FORGOT_EMAIL ?? 'joannah@gmail.com');
  156 |     await page.getByRole('button', { name: /send|tuma|kutuma|inatuma/i }).click();
  157 | 
  158 |     await expect(page.locator('text=/reset link|kiungo|link/i')).toBeVisible();
  159 |   });
  160 | 
  161 |   test('set-password page with token and email query renders', async ({ page }) => {
  162 |     await page.goto('/set-password?token=fake-token&email=e2e@example.test');
  163 |     await expect(page.getByRole('heading', { name: /set password|weka nenosiri|saza ebyokusanyusa/i })).toBeVisible();
  164 |   });
  165 | });
  166 | 
  167 | test.describe('Authenticated core navigation', () => {
  168 |   test('user can login and open main modules', async ({ page }) => {
  169 |     const email = process.env.PLAYWRIGHT_LOGIN_EMAIL;
  170 |     const password = process.env.PLAYWRIGHT_LOGIN_PASSWORD;
  171 | 
  172 |     test.skip(!email || !password, 'Set PLAYWRIGHT_LOGIN_EMAIL and PLAYWRIGHT_LOGIN_PASSWORD for authenticated E2E flows.');
  173 | 
  174 |     await login(page, email!, password!);
  175 | 
  176 |     await page.goto('/');
> 177 |     await expect(page.getByText(/total revenue|obungibwa bwonna|jumla ya mapato/i)).toBeVisible();
      |                                                                                     ^ Error: expect(locator).toBeVisible() failed
  178 | 
  179 |     await page.goto('/products');
  180 |     await expect(page.getByRole('heading', { name: /products|ebintu|bidhaa/i })).toBeVisible();
  181 | 
  182 |     await page.goto('/warehouses');
  183 |     await expect(page.getByRole('heading', { name: /warehouses|sitowa|maghala/i })).toBeVisible();
  184 | 
  185 |     await page.goto('/harvests');
  186 |     await expect(page.getByRole('heading', { name: /harvests|amakungula/i })).toBeVisible();
  187 | 
  188 |     await page.goto('/inventory');
  189 |     await expect(page.getByRole('heading', { name: /inventory|sitooko/i })).toBeVisible();
  190 | 
  191 |     await page.goto('/sales');
  192 |     await expect(page.getByRole('heading', { name: /sell inventory|mauzo|okugyako/i })).toBeVisible();
  193 | 
  194 |     await page.goto('/sales-history');
  195 |     await expect(page.getByRole('heading', { name: /sales history|historia ya mauzo|ebifaananyo bya okugyako/i })).toBeVisible();
  196 |   });
  197 | 
  198 |   test('user can register harvests, sell in both currencies, and see revenue updates', async ({ page }) => {
  199 |     const email = process.env.PLAYWRIGHT_LOGIN_EMAIL;
  200 |     const password = process.env.PLAYWRIGHT_LOGIN_PASSWORD;
  201 | 
  202 |     test.skip(!email || !password, 'Set PLAYWRIGHT_LOGIN_EMAIL and PLAYWRIGHT_LOGIN_PASSWORD for authenticated E2E flows.');
  203 | 
  204 |     await login(page, email!, password!);
  205 | 
  206 |     const runId = Date.now().toString();
  207 |     const productName = `PW Harvest ${runId}`;
  208 |     const warehouseName = `PW Warehouse ${runId}`;
  209 |     const buyerUsd = `Buyer USD ${runId}`;
  210 |     const buyerUgx = `Buyer UGX ${runId}`;
  211 | 
  212 |     let createdProductUuid: string | null = null;
  213 |     let createdWarehouseUuid: string | null = null;
  214 |     let createdBatchUuid: string | null = null;
  215 | 
  216 |     try {
  217 |       const beforeSalesResponse = await callApi(page, '/api/sales');
  218 |       expect(beforeSalesResponse.status).toBe(200);
  219 |       const beforeSales = (Array.isArray(beforeSalesResponse.data) ? beforeSalesResponse.data : []) as Array<{ currency?: string; total_value?: number | string | null }>;
  220 |       const baselineTotals = sumRevenueByCurrency(beforeSales);
  221 | 
  222 |       const createProductResponse = await callApi(page, '/api/product', 'POST', [{
  223 |         name: productName,
  224 |         unit: 'kg',
  225 |         properties: [],
  226 |       }]);
  227 |       expect(createProductResponse.status).toBe(201);
  228 | 
  229 |       const productsResponse = await callApi(page, '/api/product');
  230 |       expect(productsResponse.status).toBe(200);
  231 |       const products = (Array.isArray(productsResponse.data) ? productsResponse.data : []) as Array<{ uuid: string; name: string }>;
  232 |       const createdProduct = products.find((product) => product.name === productName);
  233 |       expect(createdProduct).toBeTruthy();
  234 |       createdProductUuid = createdProduct!.uuid;
  235 | 
  236 |       const createWarehouseResponse = await callApi(page, '/api/warehouse', 'POST', [{
  237 |         name: warehouseName,
  238 |         city: 'Kampala',
  239 |         country: 'UG',
  240 |       }]);
  241 |       expect(createWarehouseResponse.status).toBe(201);
  242 | 
  243 |       const warehousesResponse = await callApi(page, '/api/warehouse');
  244 |       expect(warehousesResponse.status).toBe(200);
  245 |       const warehouses = (Array.isArray(warehousesResponse.data) ? warehousesResponse.data : []) as Array<{ uuid: string; name: string }>;
  246 |       const createdWarehouse = warehouses.find((warehouse) => warehouse.name === warehouseName);
  247 |       expect(createdWarehouse).toBeTruthy();
  248 |       createdWarehouseUuid = createdWarehouse!.uuid;
  249 | 
  250 |       const createHarvestResponse = await callApi(page, '/api/harvest', 'POST', [{
  251 |         product_uuid: createdProduct!.uuid,
  252 |         warehouse_uuid: createdWarehouse!.uuid,
  253 |         quantity: 80,
  254 |         location: 'E2E-A1',
  255 |         harvested_on: new Date().toISOString(),
  256 |         quality: 'high',
  257 |         replace_quantity: true,
  258 |       }]);
  259 |       expect(createHarvestResponse.status).toBe(201);
  260 | 
  261 |       const harvestsResponse = await callApi(page, '/api/harvest');
  262 |       expect(harvestsResponse.status).toBe(200);
  263 |       const harvests = (Array.isArray(harvestsResponse.data) ? harvestsResponse.data : []) as Array<{ batch_uuid: string; product_uuid: string; warehouse_uuid: string }>;
  264 |       const harvest = harvests.find(
  265 |         (row) => row.product_uuid === createdProduct!.uuid && row.warehouse_uuid === createdWarehouse!.uuid,
  266 |       );
  267 |       expect(harvest).toBeTruthy();
  268 |       createdBatchUuid = harvest!.batch_uuid;
  269 | 
  270 |       const sellUsdResponse = await callApi(page, '/api/inventory/sell', 'POST', {
  271 |         batch_uuid: harvest!.batch_uuid,
  272 |         amount: 10,
  273 |         price: 5,
  274 |         currency: 'USD',
  275 |         buyer_name: buyerUsd,
  276 |       });
  277 |       expect(sellUsdResponse.status).toBe(200);
```