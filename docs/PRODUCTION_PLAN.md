# Production Deployment Plan

*Created: January 19, 2026*

---

## Executive Summary

This document outlines the strategy for deploying the Control Panel from its current **stub/mock state** to **production integration** with the main application database.

### Current State
- SQLite database with **fake/seeded data** (customers, services, LMS, etc.)
- `MOCK_MODE = true` - all sync functions return mock data
- `remote_db_query()` is a stub returning empty arrays
- Standalone PHP application

### Target State
- SQLite database stores **only our calculations and reporting data**
    WRONG! it pulls from main db and stores double data, a copy of the real database iformation we need.
    - we should have a sync check: is our data we need, synced with prod db? a few easy count checks or last updated checks should be easy enough. then if update, find deltas, and inform app user.
  - 
- Master data (customers, services, LMS, business rules) **synced from main DB**
- Embedded within or alongside the main application frontend
- Real CSV ingestion from shared drive
- Real CSV generation consumed by cron billing script

---

## Architecture: What Lives Where

### MAIN APPLICATION DATABASE (Their System)
*Source of truth for master data - we READ from here*

| Entity | We Need | Sync Strategy |
|--------|---------|---------------|
| Customers | id, name, status, discount_group_id, contract_start_date | Sync button + periodic |
| Discount Groups | id, name | Sync with customers |
| Services | id, name, type | Sync button |
| LMS / Connectors | id, name, status, commission_rate? | Sync button |
| Business Rules | rule definitions per customer | Sync button or manual |
| Transaction Types | EFX codes, display names | CSV import (current) or sync |

### OUR SQLITE DATABASE (This System)
*Our calculations, overrides, and reporting - we OWN this*

| Entity | Purpose | Source |
|--------|---------|--------|
| **pricing_tiers** | Volume-based pricing at all levels | Manual entry in UI |
| **customer_settings** | Monthly minimum, annualized, look period | Manual entry in UI |
| **customer_escalators** | Annual price increases | Manual entry in UI |
| **escalator_delays** | Delay applications | Manual entry in UI |
| **business_rule_masks** | Customer-specific rule overrides | Manual entry in UI |
| **service_billing_flags** | by_hit, zero_null, bav_by_trans | Manual entry in UI |
| **service_cogs** | Cost of goods sold per service | Sync or manual |      SYNC!!
| **billing_reports** | Ingested billing report metadata | CSV ingestion |
| **billing_report_lines** | Ingested billing line items | CSV ingestion |
| **generated_reports** | Archive of generated CSVs | Auto on generation |
| **sync_log** | Audit trail of syncs | Automatic |

### SHARED DRIVE (File System)
*CSV exchange point between systems*

```
/shared/
├── archive/                    # Historical billing reports (input from cron)
│   ├── DataX_2025_12_01_humanreadable.csv
│   ├── DataX_2025_12_2025_12_humanreadable.csv
│   └── DataX_2025_12_2025_12_ebcdic.csv
├── pending/                    # Generated CSVs waiting for cron pickup
│   └── tier_pricing_20260118_102852.csv
├── reports/                    # Our archived generated reports
│   ├── tier_pricing/
│   ├── displayname_to_type/
│   └── custom/
└── temp/                       # Temporary files for comparison
```

---

## Phase 14: Production Readiness - Detailed Plan

### 14.1 Database Integration Layer

#### Step 1: Implement `remote_db_query()`

**File:** `db.php`

```php
function remote_db_query($sql, $params = [])
{
    // Option A: Direct PDO connection
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . REMOTE_DB_HOST . ';dbname=' . REMOTE_DB_NAME,
            REMOTE_DB_USER,
            REMOTE_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Option B: Use existing application's DB class
function remote_db_query($sql, $params = [])
{
    // Hook into parent application's database layer
    global $app_db;  // or however the main app exposes it
    return $app_db->query($sql, $params)->fetchAll();
}

// Option C: REST API call to main application
function remote_db_query($sql, $params = [])
{
    // If we can't get direct DB access, call an API endpoint
    $response = file_get_contents(MAIN_APP_API . '/query?' . http_build_query([
        'sql' => $sql,
        'params' => json_encode($params)
    ]));
    return json_decode($response, true);
}
```

#### Step 2: Add Remote DB Configuration

**File:** `control_panel.php`

```php
// Production database connection
define("REMOTE_DB_HOST", "localhost");
define("REMOTE_DB_NAME", "main_application");
define("REMOTE_DB_USER", "billing_readonly");
define("REMOTE_DB_PASS", "secure_password");

// Or if embedding in larger app, these come from parent config
```

#### Step 3: Create Sync Functions for All Entities

**Current Status:**
| Function | Status | Production SQL Needed |
|----------|--------|----------------------|
| `sync_lms_from_remote()` | Exists (mock) | `SELECT id, name, status FROM connectors` |
| `sync_cogs_from_remote()` | Exists (mock) | `SELECT service_id, cogs_rate FROM service_cogs` |
| `sync_customers_from_remote()` | NOT EXISTS | Need to create |
| `sync_services_from_remote()` | NOT EXISTS | Need to create |
| `sync_discount_groups_from_remote()` | NOT EXISTS | Need to create |
| `sync_business_rules_from_remote()` | NOT EXISTS | Need to create |

**To Implement:**

```php
function sync_customers_from_remote()
{
    if (MOCK_MODE) {
        // Current mock data
        return sync_customers_mock();
    }
    
    // Production: Query main database
    // QUESTION: What is the actual table/column structure?
    $remote_customers = remote_db_query("
        SELECT 
            c.id,
            c.name,
            c.status,
            c.discount_group_id,
            c.contract_start_date,
            c.lms_id
        FROM customers c
        WHERE c.status != 'deleted'
        ORDER BY c.name
    ");
    
    $count = 0;
    foreach ($remote_customers as $cust) {
        // Upsert into our SQLite
        sqlite_execute("
            INSERT OR REPLACE INTO customers 
            (id, name, status, discount_group_id, contract_start_date, lms_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $cust['id'],
            $cust['name'],
            $cust['status'],
            $cust['discount_group_id'],
            $cust['contract_start_date'],
            $cust['lms_id']
        ]);
        $count++;
    }
    
    // Log the sync
    sqlite_execute("
        INSERT INTO sync_log (entity_type, record_count, status)
        VALUES ('customers', ?, 'success')
    ", [$count]);
    
    return $count;
}
```

---

### 14.2 Frontend Integration Options

#### Option A: Standalone with Shared Auth
- Control Panel remains separate PHP application
- Share session/authentication with main app
- Link to Control Panel from main app navigation
- **Pros:** Minimal changes to either system
- **Cons:** Separate deployment, potential auth complexity

#### Option B: Embed as Module
- Include Control Panel PHP files into main application
- Render within main app's layout/navigation
- Share database connection, session, helpers
- **Pros:** Seamless integration, single deployment
- **Cons:** Need to adapt code to main app's patterns

#### Option C: iFrame Embed
- Main app embeds Control Panel in an iframe
- Pass auth token via URL or postMessage
- **Pros:** Complete isolation, easy to deploy
- **Cons:** Less integrated UX, potential security concerns

**Recommended:** Option B (Embed as Module) for best UX, falling back to Option A if main app architecture doesn't allow embedding.

---

### 14.3 Migration & Initial Data Load

#### Step 1: Prepare Production SQLite Database
```bash
# Create fresh database with schema
php -r "require 'db.php'; sqlite_init_schema(); sqlite_run_migrations();"
```

#### Step 2: Initial Sync from Main Database
```
1. Sync Discount Groups (must come before customers)
2. Sync Customers
3. Sync Services
4. Sync LMS/Connectors
5. Sync COGS
6. Sync Business Rules (if applicable)
```

#### Step 3: Manual Configuration Entry
These items are NOT in the main database - they're our value-add:
- Pricing tiers (system defaults, group overrides, customer overrides)
- Customer escalator schedules
- Monthly minimums
- Annualized tier settings
- Look-back periods
- Billing flag overrides

**Question:** Is there any existing spreadsheet/documentation with this data that could be imported via CSV?

#### Step 4: Bulk Ingest Historical Billing Reports
```php
// From admin panel or script:
// 1. Point to archive directory with all historical DataX_*.csv files
// 2. Run bulk import
// 3. Verify counts match expectations
```

---

### 14.4 Sync Strategy & UI

#### Sync Dashboard (New Admin Tab)

```
┌─────────────────────────────────────────────────────────────────┐
│  DATA SYNC STATUS                                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Entity            Last Sync           Count    Status    Action│
│  ───────────────────────────────────────────────────────────────│
│  Customers         2026-01-19 10:30    127      OK       [Sync] │
│  Discount Groups   2026-01-19 10:30     12      OK       [Sync] │
│  Services          2026-01-19 10:30     45      OK       [Sync] │
│  LMS/Connectors    2026-01-19 10:30      8      OK       [Sync] │
│  COGS              2026-01-19 10:30     45      OK       [Sync] │
│  Business Rules    Never                --      --       [Sync] │
│                                                                 │
│                                        [Sync All Master Data]   │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│  SYNC LOG                                                       │
│  ───────────────────────────────────────────────────────────────│
│  2026-01-19 10:30:45  customers     127 records  success        │
│  2026-01-19 10:30:44  services       45 records  success        │
│  2026-01-19 10:25:00  lms            8 records   success        │
│  2026-01-18 15:00:00  customers     125 records  success        │
└─────────────────────────────────────────────────────────────────┘
```

#### Sync Frequency Options
| Option | When | How |
|--------|------|-----|
| Manual | Click button | User triggers from Admin panel |
| On-demand | Before generation | Prompt: "Sync master data before generating?" |
| Scheduled | Nightly | Cron job calls sync endpoint |
| Real-time | On change | Webhook from main app (if supported) |

**Recommended:** Manual + On-demand prompt before generation

---

### 14.5 Configuration & Environment

#### Environment Variables / Config

```php
// control_panel.php or config.php

// Environment: 'development', 'staging', 'production'
define("ENVIRONMENT", "production");

// Mock mode: false in production
define("MOCK_MODE", ENVIRONMENT !== "production");

// Paths
define("SHARED_BASE_PATH", "/mnt/shared_drive");  // Production path
define("SQLITE_DB_PATH", __DIR__ . "/data/control_panel.db");

// Remote database (production only)
if (!MOCK_MODE) {
    define("REMOTE_DB_HOST", getenv("REMOTE_DB_HOST") ?: "db.internal");
    define("REMOTE_DB_NAME", getenv("REMOTE_DB_NAME") ?: "main_app");
    define("REMOTE_DB_USER", getenv("REMOTE_DB_USER") ?: "billing");
    define("REMOTE_DB_PASS", getenv("REMOTE_DB_PASS"));
}
```

---

## Questions to Answer Before Production

### Database Schema Questions

1. **Customers table in main DB:**
   - What columns exist? (id, name, status, discount_group_id, contract_start_date?)
   - Is there an lms_id or connector_id column?
   - What status values exist? (active, paused, decommissioned, deleted?)

2. **Discount Groups table:**
   - Table name?
   - Column structure?

3. **Services/Products table:**
   - Table name?
   - How are services identified? (id, code, name?)

4. **LMS/Connectors:**
   - Is it one table or two?
   - What's the second source mentioned in comments?
   - Where do commission rates come from?

5. **Business Rules:**
   - Are these stored in the main DB?
   - What's the schema?
   - Or are they purely in our system?

### File System Questions

6. **Shared drive path:**
   - What is the actual production path?
   - Mount point or network path?

7. **Archive directory structure:**
   - Are daily/monthly/EBCDIC in separate folders or same folder?
   - Any naming convention differences from current patterns?

8. **Who consumes our generated CSV?**
   - Does the cron script look in `pending/`?
   - Or do we need to copy to a specific location?

### Integration Questions

9. **Authentication:**
   - Does the main app use sessions? JWT? Other?
   - Can we share auth or need separate?

10. **Embedding:**
    - PHP framework of main app? (Laravel, Symfony, custom?)
    - Can we include our files directly?

### Process Questions

11. **Who enters pricing data initially?**
    - Is there existing documentation/spreadsheets?
    - Or start from scratch with defaults?

12. **Historical data:**
    - How far back do we need to ingest?
    - Are all historical CSVs in the archive?

---

## Implementation Checklist

### Phase 14.1 - Database Integration
- [ ] Document main DB schema (customers, services, groups, lms)
- [ ] Implement `remote_db_query()` with actual connection
- [ ] Create `sync_customers_from_remote()`
- [ ] Create `sync_services_from_remote()`
- [ ] Create `sync_discount_groups_from_remote()`
- [ ] Update `sync_lms_from_remote()` with real SQL
- [ ] Update `sync_cogs_from_remote()` with real SQL
- [ ] Create `sync_business_rules_from_remote()` (if applicable)
- [ ] Add sync logging to all functions
- [ ] Test each sync function individually

### Phase 14.2 - Admin Sync UI
- [ ] Create Sync Dashboard page
- [ ] Add "Sync All" button
- [ ] Add individual sync buttons per entity
- [ ] Show last sync timestamps
- [ ] Show record counts
- [ ] Display sync log history
- [ ] Add "Sync before generate" prompt

### Phase 14.3 - File System Integration
- [ ] Update `SHARED_BASE_PATH` for production
- [ ] Verify directory permissions
- [ ] Test CSV generation to production path
- [ ] Test CSV ingestion from production archive
- [ ] Set up directory watching (if desired)

### Phase 14.4 - Frontend Integration
- [ ] Decide on integration approach (A, B, or C)
- [ ] Implement auth sharing/checking
- [ ] Add navigation link in main app
- [ ] Style consistency with main app (if embedding)

### Phase 14.5 - Initial Data Load
- [ ] Run all sync functions
- [ ] Verify customer/service counts
- [ ] Enter initial pricing configuration (or import)
- [ ] Bulk import historical billing reports
- [ ] Verify ingestion totals

### Phase 14.6 - Testing & Validation
- [ ] Generate tier_pricing.csv with real data
- [ ] Compare output to previous/expected format
- [ ] Run through billing calendar checklist
- [ ] Verify LMS commission calculations
- [ ] Test full cycle: generate → cron → ingest

### Phase 14.7 - Go-Live
- [ ] Set `MOCK_MODE = false`
- [ ] Deploy to production server
- [ ] Final sync all master data
- [ ] Generate first production CSV
- [ ] Monitor first billing cycle

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Main DB schema doesn't match assumptions | Document actual schema first, adapt sync functions |
| Historical data missing/incomplete | Verify archive contents before migration |
| Auth integration fails | Fall back to standalone mode with separate login |
| Performance issues with large datasets | Add pagination, optimize queries, consider caching |
| Sync conflicts (data changed in both places) | Our data wins for our tables, main DB wins for master data |

---

## Rollback Plan

If production deployment fails:

1. **Set `MOCK_MODE = true`** - Immediately returns to mock data
2. **Keep SQLite backup** - Can restore previous state
3. **Main system unaffected** - We only READ from main DB
4. **CSV generation still works** - Can manually export if needed

---

## Success Criteria

Production deployment is successful when:

1. All master data syncs from main DB without errors
2. Historical billing reports are ingested (12+ months)
3. tier_pricing.csv generates with real customer data
4. Generated CSV is consumed successfully by cron billing script
5. Billing output is ingested and dashboards populate
6. Full billing cycle completes end-to-end

---

*Document Version: 1.0*
*Next Review: Before Phase 14 implementation begins*
