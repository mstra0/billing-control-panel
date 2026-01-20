# System Architecture: Control Panel Layer

*Last Updated: January 17, 2026*

## What This Application Is

This application is a **memory and operations layer** built on top of:

1. **An existing application + database** (the main system)
2. **An existing CRON server** that runs the 1st-of-month billing job

### The Problem: The Cron Script is "Dumb"

The cron billing script:
- Takes a CSV input
- Runs billing calculations for a date range (originally last month only, v2 supports any range)
- Outputs a CSV + EBDIC file
- **Has NO memory** - doesn't remember anything between runs
- **Has NO GUI** - no way to see what's happening
- **Has NO on-demand execution** - runs on schedule only
- **Has NO change capability** - can't adjust pricing, rules, etc.
- **Has NOTHING** - it's purely a stateless transformation: CSV in → CSV + EBDIC out

### The Solution: This Control Panel

We are the **brain** that wraps around the dumb script.

```
┌─────────────────────────────────────────────────────────────────┐
│                     CONTROL PANEL (this app)                     │
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐       │
│  │   MEMORY     │    │  OPERATIONS  │    │   DISPLAY    │       │
│  │              │    │              │    │              │       │
│  │ - History    │    │ - Generate   │    │ - Dashboard  │       │
│  │ - Pricing    │    │ - Ingest     │    │ - Reports    │       │
│  │ - Settings   │    │ - Calculate  │    │ - Alerts     │       │
│  │ - Audits     │    │ - Override   │    │ - History    │       │
│  └──────────────┘    └──────────────┘    └──────────────┘       │
│                              │                                   │
│                              ▼                                   │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    CSV GENERATION ✅                       │  │
│  │         (tier_pricing.csv, displayname_to_type.csv)        │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              │                                   │
└──────────────────────────────┼───────────────────────────────────┘
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                      CRON BILLING SCRIPT                         │
│                        (dumb, stateless)                         │
│                                                                  │
│                    CSV IN → [PROCESS] → CSV + EBDIC OUT          │
└─────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                     CONTROL PANEL (this app)                     │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    CSV INGESTION ✅                        │  │
│  │              (billing report output CSV)                   │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              │                                   │
│                              ▼                                   │
│                     Update our database                          │
│                     Calculate profits                            │
│                     Generate dashboards                          │
│                     LMS commission reports                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## Implementation Status

| Capability | Purpose | Status |
|------------|---------|--------|
| **GENERATION** | Create input CSVs from our config | ✅ DONE |
| **INGESTION** | Import output CSVs into our database | ✅ DONE |
| **Memory** | Store pricing, history, audits | ✅ DONE |
| **Operations** | Manage escalators, rules, overrides | ✅ DONE |
| **Display** | Dashboards, reports, alerts | ✅ DONE |

---

## Generation System (Completed)

### tier_pricing.csv Generator

**Location:** `?action=generation`

**What it does:**
1. Queries all active customers (or selected subset)
2. For each customer, gets effective pricing with full inheritance:
   - Check customer-level tiers first
   - Fall back to group-level tiers
   - Fall back to system default tiers
3. Calculates adjusted prices by applying escalators:
   - Percentage escalation per year
   - Fixed dollar adjustments
   - Delay handling (1st-of-month normalization)
4. Resolves billing flags (by_hit, zero_null, bav_by_trans) with inheritance:
   - Customer override → Group override → System default → Built-in defaults
5. Maps EFX codes from transaction_types table
6. Outputs CSV in exact format cron expects

**Output format:**
```csv
cust_id,discount_group,start_date,end_date,EFX_code,type,start_trans,end_trans,adj_price,base_price,by_hit,zero_null,bav_by_trans
```

**Features:**
- Preview mode (first 100 rows)
- Download as CSV file
- Save to pending directory
- As-of date selector (for escalator calculations)
- Include/exclude inactive customers option

### Transaction Types Management

**Location:** `?action=generation_types`

**What it does:**
- Stores mappings from displayname_to_type.csv
- Maps EFX codes to transaction types and services
- Used during generation to populate type and EFX_code columns

**Import format:**
```csv
type,display_name,EFX_code,EFX_displayname
```

### Billing Flags Configuration

**Location:** `?action=billing_flags`

**What it does:**
- Configure by_hit, zero_null, bav_by_trans at any level
- Three-level inheritance: System Default → Group → Customer
- Flags control how transactions are counted in billing

**Flags:**
| Flag | Default | Description |
|------|---------|-------------|
| by_hit | 1 | Count transactions per hit |
| zero_null | 0 | Treat null counts as zero |
| bav_by_trans | 0 | Calculate BAV per transaction |

---

## Ingestion System (Completed)

### CSV Parser

**Location:** `?action=ingestion`

**What it does:**
1. Parses billing report CSV files
2. Auto-detects daily vs monthly from filename pattern
3. Stores in billing_reports and billing_report_lines tables
4. Prevents duplicate imports (idempotent)

**Input format:**
```csv
y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id
```

**Filename patterns:**
- Daily: `DataX_YYYY_MM_D_*.csv` (e.g., DataX_2025_01_15_report.csv)
- Monthly: `DataX_YYYY_MM_YYYY_MM_*.csv` (e.g., DataX_2025_01_2025_01_final.csv)

### Bulk Import

**Location:** `?action=ingestion_bulk`

**What it does:**
- Multi-file upload for historical seeding
- Directory scan of archive folder
- Skips already-imported files
- Progress tracking and error reporting

### Report Viewer

**Location:** `?action=ingestion_view&id=X`

**What it does:**
- View imported report metadata
- Browse all line items
- See totals and customer breakdown
- Delete reports if needed

---

## Database Schema (Key Tables)

### Generation Tables

```sql
-- Transaction type mappings (from displayname_to_type.csv)
CREATE TABLE transaction_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,              -- grouping category
    display_name TEXT NOT NULL,      -- internal system display name
    efx_code TEXT NOT NULL,          -- EFX system code
    efx_displayname TEXT,            -- EBDIC-constrained name
    service_id INTEGER,              -- link to service if applicable
    UNIQUE(efx_code, display_name)
);

-- Billing flags with inheritance
CREATE TABLE service_billing_flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    level TEXT NOT NULL CHECK(level IN ('default', 'group', 'customer')),
    level_id INTEGER,  -- NULL for default, group_id for group, customer_id for customer
    service_id INTEGER NOT NULL,
    efx_code TEXT NOT NULL,
    by_hit INTEGER DEFAULT 1,
    zero_null INTEGER DEFAULT 0,
    bav_by_trans INTEGER DEFAULT 0,
    effective_date TEXT NOT NULL DEFAULT (date('now'))
);
```

### Ingestion Tables

```sql
-- Imported billing reports
CREATE TABLE billing_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_type TEXT NOT NULL,       -- 'daily' or 'monthly'
    report_date TEXT NOT NULL,       -- YYYY-MM-DD
    report_year INTEGER NOT NULL,
    report_month INTEGER NOT NULL,
    file_path TEXT NOT NULL,         -- original filename
    record_count INTEGER DEFAULT 0,
    imported_at TEXT DEFAULT (datetime('now'))
);

-- Individual billing lines
CREATE TABLE billing_report_lines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER NOT NULL,
    year INTEGER,
    month INTEGER,
    customer_id INTEGER,
    customer_name TEXT,
    hit_code TEXT,
    tran_displayname TEXT,
    actual_unit_cost REAL,
    count INTEGER,
    revenue REAL,
    efx_code TEXT,
    billing_id TEXT,
    FOREIGN KEY (report_id) REFERENCES billing_reports(id)
);
```

---

## Data Flow: Complete Picture

```
                    HISTORICAL ARCHIVE
                    (2025-01-01 onwards)
                           │
          ┌────────────────┴────────────────┐
          │                                 │
          ▼                                 ▼
   Old INPUT CSVs                    Old OUTPUT CSVs
   (tier_pricing)                    (billing reports)
          │                                 │
          │                                 │
          ▼                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                     CONTROL PANEL                                │
│                                                                  │
│  ┌─────────────────────┐         ┌─────────────────────┐        │
│  │  GENERATION ✅      │         │  INGESTION ✅       │        │
│  │                     │         │                     │        │
│  │ Generate new        │         │ Parse billing CSVs  │        │
│  │ tier_pricing.csv    │         │ Store in database   │        │
│  │ with inheritance    │         │ Auto-detect type    │        │
│  │ and escalators      │         │ Bulk import support │        │
│  └─────────────────────┘         └─────────────────────┘        │
│            │                               │                     │
│            ▼                               ▼                     │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    SQLite DATABASE                       │    │
│  │                                                          │    │
│  │  - Pricing configuration (defaults, groups, customers)   │    │
│  │  - Transaction types (EFX code mappings)                 │    │
│  │  - Billing flags (by_hit, zero_null, bav_by_trans)       │    │
│  │  - Imported billing data (reports + lines)               │    │
│  │  - LMS assignments & commissions                         │    │
│  │  - Escalators with delays                                │    │
│  │  - Business rule masks                                   │    │
│  └─────────────────────────────────────────────────────────┘    │
│            │                               │                     │
│            ▼                               ▼                     │
│  ┌─────────────────────┐         ┌─────────────────────┐        │
│  │   PREVIEW/DOWNLOAD  │         │   REPORTS/DASHBOARD │        │
│  │                     │         │                     │        │
│  │ Preview rows        │         │ Revenue by LMS      │        │
│  │ Download CSV        │         │ Profit trends       │        │
│  │ Save to pending     │         │ Customer analytics  │        │
│  └─────────────────────┘         └─────────────────────┘        │
│            │                                                     │
└────────────┼─────────────────────────────────────────────────────┘
             │
             ▼
      CRON BILLING SCRIPT
             │
             ▼
      OUTPUT CSV + EBDIC
             │
             ▼
      (loops back to ingestion)
```

---

## Key Functions

### Generation Functions

| Function | Purpose |
|----------|---------|
| `generate_tier_pricing_csv($options)` | Main generator - creates full CSV content |
| `get_effective_customer_tiers($cust_id, $svc_id)` | Resolve pricing with inheritance |
| `calculate_escalated_price($base, $cust_id, $date)` | Apply escalators to get adj_price |
| `get_effective_billing_flags($svc_id, $efx, $cust, $grp)` | Resolve flags with inheritance |
| `get_all_transaction_types()` | Get EFX code mappings |
| `import_transaction_types_csv($content)` | Bulk import transaction types |
| `save_billing_flags($level, $id, $svc, $efx, ...)` | Save flag configuration |

### Ingestion Functions

| Function | Purpose |
|----------|---------|
| `parse_billing_filename($filename)` | Extract date/type from filename pattern |
| `parse_billing_csv($content)` | Parse CSV content to structured array |
| `import_billing_report($filename, $content)` | Full import with duplicate detection |
| `get_billing_reports($type, $limit)` | List imported reports |
| `get_billing_report_lines($report_id)` | Get lines for a report |
| `delete_billing_report($report_id)` | Remove report and lines |

---

## Summary

We are the **brain** that gives the dumb cron script:
- **Memory** (it forgets everything; we remember everything)
- **Flexibility** (it's rigid; we can adjust anything)
- **Visibility** (it's a black box; we show everything)
- **Control** (it runs blindly; we prepare and validate)

**The loop is now closed:**
1. Configure pricing, escalators, flags in the control panel
2. Generate tier_pricing.csv with all business logic applied
3. Cron script consumes CSV, produces billing report
4. Ingest billing report back into control panel
5. View results, calculate commissions, track trends
6. Repeat next month

---

## Report Types & Frequency

### Daily Reports (Automatic)
- Generated automatically by the cron
- Filename pattern: `DataX_YYYY_MM_D_*.csv`
- **Purpose:** Fuel for the month-to-date dashboard
- **No manual intervention** - these flow automatically
- Ingested to track running totals

### Monthly Reports (1st of Month)
- Generated on the 1st of each month by cron
- Filename pattern: `DataX_YYYY_MM_YYYY_MM_*.csv`
- **Purpose:** Official billing for the previous month
- **Triggers "month complete"** when ingested
- This is what the billing calendar tracks

### Test Reports
- No special concept - reports are read-only, safe to run anytime
- Just generate a report for any date range to preview

---

## Billing Calendar & Checklist System (Phase 13)

### Concept

The system KNOWS everything about upcoming billing events:
- Customer contract start dates → when escalators kick in
- Escalator schedules (year 1, year 2, year 3...)
- Escalator delays applied
- Annualized reset dates
- New customers added since last run
- New products/services added

**The system generates the checklist that humans used to create manually.**

### Calendar View (Year)

```
┌─────────────────────────────────────────────────────────────────┐
│  BILLING CALENDAR 2026                                          │
│                                                                 │
│  [◀ 2025]                                            [2027 ▶]   │
│                                                                 │
│     JAN     FEB     MAR     APR     MAY     JUN                │
│   ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐             │
│   │ ✓   │ │ ✓   │ │ 5   │ │ 2   │ │ ●   │ │ 3   │             │
│   │done │ │done │ │ ⚠️2  │ │     │ │     │ │ 🔺   │             │
│   └─────┘ └─────┘ └─────┘ └─────┘ └─────┘ └─────┘             │
│                                                                 │
│  ● = nothing special   🔺 = escalators   ⚠️ = needs attention   │
│  ✓ = month complete (monthly report ingested)                   │
│                                                                 │
│            [ 📋 What's Next? → March 2026 Checklist ]           │
└─────────────────────────────────────────────────────────────────┘
```

- Click any month → drill down to that month's checklist
- "What's Next?" button → jumps to first incomplete month
- Month is "done" when the monthly report (1st of month) is ingested

### Month Checklist (Dynamic)

For any given month, the checklist answers:

**1. WHAT'S NEW?** (Additions to the universe)
- New customers added since last month - are they configured? have LMS?
- New services/products - are they in transaction_types? have pricing?

**2. WHAT'S CHANGING?** (Scheduled events)
- Escalators kicking in this month
- Escalator delays becoming active
- Annualized volume resets

**3. WHAT'S EXCLUDED?** (Intentional omissions)
- Paused customers - are they supposed to be paused?
- Masked business rules - still intentional?

**4. WHAT'S DIFFERENT?** (Changes since last run)
- Config changes since last generation
- Price differences from last output

**5. FINAL OUTPUT**
- Preview CSV
- Row count sanity check
- Customer count check
- Generate & download

### The Scary Misses This Prevents

| Miss | How We Catch It |
|------|-----------------|
| Forgot escalator | Calendar shows escalator events per month |
| Wrong price | Preview CSV for human eyeballs |
| Paused customer billed | Checklist shows excluded customers |
| **New customer not billed** | "What's New" section highlights additions |
| **New product not billed** | "What's New" shows unconfigured transaction types |

---

## Month-to-Date Dashboard (Phase 13)

### Concept

Daily reports fuel a running view of the current month's billing activity.

**Purpose:**
- See billing accumulating throughout the month
- Catch anomalies early (sudden drops, spikes)
- No waiting until month-end to see problems

### Data Flow

```
Daily Reports (automatic) 
      │
      ▼
  Ingestion (automatic or batch)
      │
      ▼
  Month-to-Date Dashboard
      │
      ├── Revenue running total
      ├── Transaction counts by service
      ├── Customer activity
      └── Comparison to previous month (same point in time)
```

---

*Document updated: January 18, 2026 - Added billing calendar and MTD concepts*
