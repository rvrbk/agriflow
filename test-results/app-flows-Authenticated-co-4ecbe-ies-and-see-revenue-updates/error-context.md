# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: app-flows.spec.ts >> Authenticated core navigation >> user can register harvests, sell in both currencies, and see revenue updates
- Location: tests\e2e\app-flows.spec.ts:198:3

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
            - paragraph [ref=e37]: UGX 273,000 UGX
            - paragraph [ref=e38]: 6 sales
          - generic [ref=e39]:
            - generic [ref=e40]: "View in:"
            - combobox [ref=e41]:
              - option "UGX" [selected]
              - option "USD"
            - link "View Details" [ref=e42] [cursor=pointer]:
              - /url: /sales-history
        - generic [ref=e43]:
          - paragraph [ref=e44]:
            - generic [ref=e45]: "USD:"
            - generic [ref=e46]: $50
          - paragraph [ref=e47]:
            - generic [ref=e48]: "UGX:"
            - generic [ref=e49]: UGX 88,000
      - generic [ref=e50]:
        - generic [ref=e51]: Search overview
        - textbox "Search overview" [ref=e52]:
          - /placeholder: Search by warehouse, product, quantity, or unit...
      - generic [ref=e53]:
        - heading "Warehouses" [level=2] [ref=e54]
        - generic [ref=e55]:
          - article [ref=e56]:
            - generic [ref=e57]:
              - heading "Aw5" [level=2] [ref=e58]
              - generic [ref=e59]: "Products: 1"
            - paragraph [ref=e60]: "Total Quantity: 19.00"
            - table [ref=e62]:
              - rowgroup [ref=e63]:
                - row "Product Quantity Unit" [ref=e64]:
                  - columnheader "Product" [ref=e65]
                  - columnheader "Quantity" [ref=e66]
                  - columnheader "Unit" [ref=e67]
              - rowgroup [ref=e68]:
                - row "Eggs 19.00 pcs" [ref=e69]:
                  - cell "Eggs" [ref=e70]
                  - cell "19.00" [ref=e71]
                  - cell "pcs" [ref=e72]
          - article [ref=e73]:
            - generic [ref=e74]:
              - heading "PW Sync Base Warehouse 1777388489295" [level=2] [ref=e75]
              - generic [ref=e76]: "Products: 1"
            - paragraph [ref=e77]: "Total Quantity: 40.00"
            - table [ref=e79]:
              - rowgroup [ref=e80]:
                - row "Product Quantity Unit" [ref=e81]:
                  - columnheader "Product" [ref=e82]
                  - columnheader "Quantity" [ref=e83]
                  - columnheader "Unit" [ref=e84]
              - rowgroup [ref=e85]:
                - row "PW Sync Base Product 1777388489295 40.00 kg" [ref=e86]:
                  - cell "PW Sync Base Product 1777388489295" [ref=e87]
                  - cell "40.00" [ref=e88]
                  - cell "kg" [ref=e89]
          - article [ref=e90]:
            - generic [ref=e91]:
              - heading "PW Warehouse 1777380632352" [level=2] [ref=e92]
              - generic [ref=e93]: "Products: 1"
            - paragraph [ref=e94]: "Total Quantity: 50.00"
            - table [ref=e96]:
              - rowgroup [ref=e97]:
                - row "Product Quantity Unit" [ref=e98]:
                  - columnheader "Product" [ref=e99]
                  - columnheader "Quantity" [ref=e100]
                  - columnheader "Unit" [ref=e101]
              - rowgroup [ref=e102]:
                - row "PW Harvest 1777380632352 50.00 kg" [ref=e103]:
                  - cell "PW Harvest 1777380632352" [ref=e104]
                  - cell "50.00" [ref=e105]
                  - cell "kg" [ref=e106]
          - article [ref=e107]:
            - generic [ref=e108]:
              - heading "PW Warehouse 1777380654220" [level=2] [ref=e109]
              - generic [ref=e110]: "Products: 1"
            - paragraph [ref=e111]: "Total Quantity: 50.00"
            - table [ref=e113]:
              - rowgroup [ref=e114]:
                - row "Product Quantity Unit" [ref=e115]:
                  - columnheader "Product" [ref=e116]
                  - columnheader "Quantity" [ref=e117]
                  - columnheader "Unit" [ref=e118]
              - rowgroup [ref=e119]:
                - row "PW Harvest 1777380654220 50.00 kg" [ref=e120]:
                  - cell "PW Harvest 1777380654220" [ref=e121]
                  - cell "50.00" [ref=e122]
                  - cell "kg" [ref=e123]
          - article [ref=e124]:
            - generic [ref=e125]:
              - heading "PW Warehouse 1777380667588" [level=2] [ref=e126]
              - generic [ref=e127]: "Products: 1"
            - paragraph [ref=e128]: "Total Quantity: 50.00"
            - table [ref=e130]:
              - rowgroup [ref=e131]:
                - row "Product Quantity Unit" [ref=e132]:
                  - columnheader "Product" [ref=e133]
                  - columnheader "Quantity" [ref=e134]
                  - columnheader "Unit" [ref=e135]
              - rowgroup [ref=e136]:
                - row "PW Harvest 1777380667588 50.00 kg" [ref=e137]:
                  - cell "PW Harvest 1777380667588" [ref=e138]
                  - cell "50.00" [ref=e139]
                  - cell "kg" [ref=e140]
          - article [ref=e141]:
            - generic [ref=e142]:
              - heading "PW Warehouse 1777380705905" [level=2] [ref=e143]
              - generic [ref=e144]: "Products: 1"
            - paragraph [ref=e145]: "Total Quantity: 50.00"
            - table [ref=e147]:
              - rowgroup [ref=e148]:
                - row "Product Quantity Unit" [ref=e149]:
                  - columnheader "Product" [ref=e150]
                  - columnheader "Quantity" [ref=e151]
                  - columnheader "Unit" [ref=e152]
              - rowgroup [ref=e153]:
                - row "PW Harvest 1777380705905 50.00 kg" [ref=e154]:
                  - cell "PW Harvest 1777380705905" [ref=e155]
                  - cell "50.00" [ref=e156]
                  - cell "kg" [ref=e157]
          - article [ref=e158]:
            - generic [ref=e159]:
              - heading "PW Warehouse 1777390310520" [level=2] [ref=e160]
              - generic [ref=e161]: "Products: 1"
            - paragraph [ref=e162]: "Total Quantity: 50.00"
            - table [ref=e164]:
              - rowgroup [ref=e165]:
                - row "Product Quantity Unit" [ref=e166]:
                  - columnheader "Product" [ref=e167]
                  - columnheader "Quantity" [ref=e168]
                  - columnheader "Unit" [ref=e169]
              - rowgroup [ref=e170]:
                - row "PW Harvest 1777390310520 50.00 kg" [ref=e171]:
                  - cell "PW Harvest 1777390310520" [ref=e172]
                  - cell "50.00" [ref=e173]
                  - cell "kg" [ref=e174]
```

# Test source

```ts
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
  278 |       const sellUsdData = (sellUsdResponse.data ?? {}) as { sale_uuid?: string; currency?: string; total_value?: number | string | null };
  279 |       expect(sellUsdData.currency).toBe('USD');
  280 |       expect(Number(sellUsdData.total_value ?? 0)).toBe(50);
  281 |       expect(sellUsdData.sale_uuid).toBeTruthy();
  282 | 
  283 |       const sellUgxResponse = await callApi(page, '/api/inventory/sell', 'POST', {
  284 |         batch_uuid: harvest!.batch_uuid,
  285 |         amount: 20,
  286 |         price: 1000,
  287 |         currency: 'UGX',
  288 |         buyer_name: buyerUgx,
  289 |       });
  290 |       expect(sellUgxResponse.status).toBe(200);
  291 |       const sellUgxData = (sellUgxResponse.data ?? {}) as { sale_uuid?: string; currency?: string; total_value?: number | string | null };
  292 |       expect(sellUgxData.currency).toBe('UGX');
  293 |       expect(Number(sellUgxData.total_value ?? 0)).toBe(20000);
  294 |       expect(sellUgxData.sale_uuid).toBeTruthy();
  295 | 
  296 |       const afterSalesResponse = await callApi(page, '/api/sales');
  297 |       expect(afterSalesResponse.status).toBe(200);
  298 |       const afterSales = (Array.isArray(afterSalesResponse.data) ? afterSalesResponse.data : []) as Array<{ uuid?: string; currency?: string; total_value?: number | string | null }>;
  299 | 
  300 |       const usdSale = afterSales.find((sale) => sale.uuid === sellUsdData.sale_uuid);
  301 |       const ugxSale = afterSales.find((sale) => sale.uuid === sellUgxData.sale_uuid);
  302 |       expect(usdSale?.currency).toBe('USD');
  303 |       expect(Number(usdSale?.total_value ?? 0)).toBe(50);
  304 |       expect(ugxSale?.currency).toBe('UGX');
  305 |       expect(Number(ugxSale?.total_value ?? 0)).toBe(20000);
  306 | 
  307 |       const afterTotals = sumRevenueByCurrency(afterSales);
  308 |       const expectedUsdTotal = (baselineTotals.USD ?? 0) + 50;
  309 |       const expectedUgxTotal = (baselineTotals.UGX ?? 0) + 20000;
  310 | 
  311 |       expect(afterTotals.USD ?? 0).toBe(expectedUsdTotal);
  312 |       expect(afterTotals.UGX ?? 0).toBe(expectedUgxTotal);
  313 | 
  314 |       await page.goto('/');
> 315 |       await expect(page.getByText(/total revenue|obungibwa bwonna|jumla ya mapato/i)).toBeVisible();
      |                                                                                       ^ Error: expect(locator).toBeVisible() failed
  316 | 
  317 |       const usdRevenueLine = page.locator('p').filter({ hasText: /^USD:/ }).first();
  318 |       const ugxRevenueLine = page.locator('p').filter({ hasText: /^UGX:/ }).first();
  319 | 
  320 |       await expect(usdRevenueLine).toContainText(formatCurrency(expectedUsdTotal, 'USD'));
  321 |       await expect(ugxRevenueLine).toContainText(formatCurrency(expectedUgxTotal, 'UGX'));
  322 |     } finally {
  323 |       if (createdBatchUuid) {
  324 |         await cleanupDelete(page, `/api/harvest/${createdBatchUuid}`);
  325 |       }
  326 | 
  327 |       if (createdWarehouseUuid) {
  328 |         await cleanupDelete(page, `/api/warehouse/${createdWarehouseUuid}`);
  329 |       }
  330 | 
  331 |       if (createdProductUuid) {
  332 |         await cleanupDelete(page, `/api/product/${createdProductUuid}`);
  333 |       }
  334 |     }
  335 |   });
  336 | });
  337 | 
```