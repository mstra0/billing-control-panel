# Pricing Requirements

## Overview

Control panel for customer contract pricing. This system **aids humans** in getting pricing right - it's not fully automated. The workflow:

1. System generates CSV with calculated/proposed pricing
2. Human reviews
3. Human submits CSV back (with overrides if needed)
4. System ingests

All pricing is per-customer, per-contract. Nothing is rigidly standard - contracts can vary completely.

---

## Inheritance Model

```
SYSTEM DEFAULTS (base)
    └── DISCOUNT GROUP (template, optional, 0 or 1 per customer)
            └── CUSTOMER (inherits, can override at any granularity)
```

### Resolution Logic (Deep Merge)

To determine Customer X's price for Service Y, Tier Z:

1. Start with **System Defaults** (required for all services)
2. If Customer X belongs to a **Discount Group** → overlay group's settings
3. If Customer X has **direct overrides** → overlay customer's settings
4. Result = merged configuration

**Granularity:** Overrides can be as specific as a single tier within a service.

**Safety Net:** Human review step catches merge errors before they affect billing.

---

## Core Concepts

### 1. Customer
- Has unique ID
- Optionally belongs to **one** Discount Group (0 or 1, not multiple)
- Can have fully custom pricing (no group) with system defaults as base
- Has customer-specific **Business Rules**
- Has **Status**: `active`, `paused`, `decommissioned`
  - Only `active` customers are billed
  - `paused` and `decommissioned` excluded from billing
  - Customers are never deleted, only status-changed

**Status Definitions:**
| Status | Billable | Can Return | Notes |
|--------|----------|------------|-------|
| `active` | Yes | n/a | Normal operating state |
| `paused` | No | Yes | Temporary hold, can resume |
| `decommissioned` | No | No* | Effectively permanent. If they return, they go through ingestion as new customer (new ID) |

### 2. Discount Group
- Acts as a **template** for member customers
- Defines default pricing for services/tiers
- All members inherit; individuals can override
- Think: contractual grouping, parent entities, partner tiers

### 3. Service
- A billable service item
- Has **tiered volume-based pricing**
- **Every service MUST have system defaults defined**

### 4. Volume-Based Pricing (Tiers)
Each service has tiered pricing:

| Volume Start | Volume End | Price Per Inquiry |
|--------------|------------|-------------------|
| 0            | 1000       | $0.50             |
| 1001         | 5000       | $0.40             |
| 5001         | NULL       | $0.30             |

- Tiers: `volume_start`, `volume_end`, `price_per_inquiry`
- Defined at: System Default → Discount Group → Customer (each layer can override)
- **Granular override**: Can override individual tiers, not just whole service

### 5. Business Rules (Customer-Level)
- Each customer has integration business rules
- Rules can be **masked** (blacklist) or **included** (whitelist)
- Masking excludes transactions from volume counts
- Pulled from remote DB (read-only) with "Refresh" button
- Local SQLite stores current mask configuration

### 6. Monthly Minimum
- If customer doesn't reach volume threshold in a month
- Bill shows: transaction-level detail + **1 "gap" line item**
- Gap = difference between calculated total and minimum
- Customer sees exactly what they used + what they paid to meet minimum

**Example:**
```
Service A - 150 transactions @ $0.50 = $75.00
Service B -  50 transactions @ $0.30 = $15.00
                          Subtotal = $90.00
         Monthly Minimum Gap (+1)  = $410.00   ← diff to reach $500 minimum
                             TOTAL = $500.00
```

### 7. Escalators (Annual Price Increases)

Contract-based price escalation over time:

**Parameters:**
- **Contract Start Date**: When counting begins
- **Escalator Percentage**: Annual increase (e.g., 5%)
- **Escalator Schedule**: Which year gets what %

**Example:**
```
Year 1: 0% (base pricing)
Year 2: 5% increase
Year 3: 10% increase
```

**Behavior:**
- Escalator applies on contract anniversary
- **Must land on 1st of month** - if anniversary is mid-month, rolls to next 1st
- Contract term length doesn't matter - escalator % is what matters
- A 1-year contract can have 5% escalator

**Common Operations:**
- **"Delay 1 Month" button** - postpone current escalator instance by 1 month
  - Only affects THIS instance, not future escalators
  - Calendar continues normally after delay

**Adjustments (Per-Customer Overrides):**
- **Percentage adjustment**: Override the standard % (e.g., 3% instead of 5%)
- **Fixed adjustment**: +/- dollar amount on top of percentage
- Both can be combined

**Example with adjustments:**
```
Standard Year 2: 5%
Customer X override: 3% + $50 fixed adjustment
```

### 8. Annualized Tiers (Optional, Supersedes Regular)

For customers with annualized contracts:

**Parameters:**
- **Start Date**: Contract start
- **Look Period**: Window for counting (e.g., 3 or 6 months)
- **Normalization Factor**: 12 / look_period (3mo → 4x, 6mo → 2x)

**Behavior:**
- Billing on 1st of month
- Mid-month starts → counting begins next 1st
- Tier locked for duration of look period
- Recalculate each look period

**Calculation:**
```
annualized_volume = (transactions_in_look_period) × (12 / look_period_months)
tier = find_tier(annualized_volume)
```

System recalculates dynamically on every run.

---

## Billing Rules

### Date Normalization
- **All billing events must land on 1st of month**
- If a date (contract start, escalator anniversary, etc.) is not the 1st:
  - Roll forward to next month's 1st
- This applies to: billing cycles, escalator triggers, annualized look periods

---

## Dashboard / Alerts

### "Coming Up" Tab
System needs an **alert dashboard** showing upcoming events:

- **Escalators coming up** - "Customer X escalator in 2 months!"
- **Contract renewals**
- **Look period resets** (for annualized customers)
- **Countdown timers** to important dates

This helps humans prepare for changes before they happen.

---

## Data Persistence

### Append-Only History
- All changes are **append-only**
- Historical records preserved, never deleted
- UI shows **current** (latest effective), hides historical
- Can query history for auditing

### Effective Dating
- Records have `effective_date`
- Current = MAX(effective_date) WHERE effective_date <= NOW

---

## Data Model (Draft)

```
-- Base entity tables (synced from remote DB)
customers
  - id
  - name
  - discount_group_id (nullable)
  - status ENUM('active', 'paused', 'decommissioned')
  - contract_start_date

discount_groups
  - id  
  - name

services
  - id
  - name

-- Pricing tiers (append-only, hierarchical)
pricing_tiers
  - id
  - effective_date
  - level ENUM('default', 'group', 'customer')
  - level_id (customer_id or discount_group_id or NULL for default)
  - service_id
  - volume_start
  - volume_end (nullable = unlimited)
  - price_per_inquiry

-- Customer settings (append-only)
customer_settings
  - id
  - customer_id
  - effective_date
  - monthly_minimum (nullable)
  - uses_annualized (boolean)
  - annualized_start_date (nullable)
  - look_period_months (nullable)

-- Escalator configuration (per customer)
customer_escalators
  - id
  - customer_id
  - effective_date
  - escalator_start_date (when year 1 begins)
  - year_number
  - escalator_percentage (e.g., 5.00 for 5%)
  - delayed_months (0 = on schedule, 1+ = delayed)

-- Business rule masking (customer-level)
business_rule_masks
  - id
  - customer_id
  - rule_name
  - is_masked (boolean)
  - effective_date
```

---

## UI Sections (Planned)

### Tab 1: Dashboard / Coming Up
- Alerts for upcoming escalators
- Contract events countdown
- Things requiring attention

### Tab 2: Customer Pricing Builder
- Select customer (or "new from defaults")
- See inherited values (from group/defaults) in read-only style
- Override fields become editable
- Granular tier editing
- Customer status management

### Tab 3: Discount Group Templates  
- Manage group-level defaults
- See which customers inherit

### Tab 4: System Defaults
- Base pricing for all services
- Starting point for all customers

### Tab 5: Business Rules
- Per-customer blacklist/whitelist
- "Refresh from DB" button
- Toggle masks

### Tab 6: Escalators
- View/edit escalator schedules per customer
- **"Delay 1 Month" button**
- See calculated effective dates (with 1st-of-month normalization)

### Tab 7: History/Audit
- View historical pricing
- Compare versions

---

## Resolved Questions

| Question | Answer |
|----------|--------|
| Base without group? | Yes - System Defaults serve as base, group is optional |
| Override granularity? | Granular - can override single tier |
| Business rule scope? | Customer-level |
| Time dimension? | Append-only with effective dates |
| Merge pattern? | Deep merge with human review safety net |
| Every service needs default? | Yes - required |
| Multiple groups per customer? | No - 0 or 1 group only |
| Monthly minimum logic? | Shows as gap line item on bill (transparency) |

---

---

## LMS (Loan Management System) - Revenue Tracking

### Overview
LMS is a **separate grouping orthogonal to Discount Groups**. Used for tracking revenue, cost, and commission payouts.

```
Discount Groups → Pricing templates (what customer PAYS)
LMS            → Revenue tracking (what LMS partner EARNS)
```

### LMS Structure

**Relationship:** Many customers → One LMS (like discount groups)

**Every customer MUST have an LMS assignment.**

**Data Source:**
- LMS list (id, name) synced from main database
- Commission rates stored locally
- Supports "force refresh" from main DB

### Commission Inheritance

```
SYSTEM DEFAULT COMMISSION (e.g., 10%)
    └── LMS OVERRIDE (optional, e.g., 12% for specific LMS)
```

- Default commission rate at system level
- Each LMS can override with its own rate
- If no override, inherits default

### Revenue/Cost Calculation

Per customer, per business rule, per period:

| Field | Source | Description |
|-------|--------|-------------|
| **COST** | Pricing tiers | Price per inquiry (what customer pays) |
| **COGS** | Local config | Cost of goods (what WE pay) |
| **COUNT** | Reports | Transaction count from billing reports |

**Calculations:**
```
REVENUE   = COST × COUNT     (bill to customer)
OUR_COST  = COGS × COUNT     (what we pay)
PROFIT    = REVENUE - OUR_COST
PAYOUT    = PROFIT × COMMISSION_RATE  (what LMS partner earns)
```

### LMS Dashboard Report

Show per LMS:
- Total revenue (sum of customer bills)
- Total COGS
- Total profit
- Commission payout amount
- List of customers in that LMS

---

## Billing System Integration

### Billing Cycle

**Automated Run:** 1st of month at 12:05 AM
- Human operators prep system before this run
- Produces: REAL RUN REPORT (CSV) + EBDIC FILE

**Report Types Stored:**
| Type | Frequency | Purpose |
|------|-----------|---------|
| **Daily** | Manual runs | Monitoring, tracking, trending |
| **Monthly** | 1st of month auto | Official billing record |

**Key:** All runs (daily/monthly) use same input format, produce same output format.

**Filename Patterns:**
```
Daily:   DataX_2025_01_1_humanreadable.csv     (single day)
Monthly: DataX_2025_01_2025_01_humanreadable.csv (full month range)
```
- Daily: `DataX_YYYY_MM_D_*.csv` - year, month, day
- Monthly: `DataX_YYYY_MM_YYYY_MM_*.csv` - start year/month, end year/month

### File Formats

#### OUTPUT: Billing Report CSV
Generated by billing system, consumed by control panel.

```csv
y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id
```

| Field | Description |
|-------|-------------|
| `y` | Year |
| `m` | Month |
| `cust_id` | Customer ID |
| `cust_name` | Customer name |
| `hit_code` | Hit/transaction code |
| `tran_displayname` | Transaction display name |
| `actual_unit_cost` | Final calculated price (after all pricing logic) |
| `count` | Transaction count |
| `revenue` | Calculated revenue (cost × count) |
| `EFX_code` | EFX system code |
| `billing_id` | Billing record ID |

#### OUTPUT: EBDIC File
Binary format, same data as CSV but in EBDIC encoding for mainframe systems.

---

#### INPUT: displayname_to_type.csv
Maps display names to transaction types.

```csv
type,display_name,EFX_code,EFX_displayname
```

| Field | Description |
|-------|-------------|
| `type` | Grouping category |
| `display_name` | Internal system display name |
| `EFX_code` | EFX system code (matches output report) |
| `EFX_displayname` | EBDIC-constrained name (length/charset limited) |

**Note:** `display_name` values are event-like things for counting: hits, errors, nulls, countable events.

---

#### INPUT: tier_pricing.csv
Pricing configuration fed to billing system.

```csv
cust_id,discount_group,start_date,end_date,EFX_code,type,start_trans,end_trans,adj_price,base_price,by_hit,zero_null,bav_by_trans
```

| Field | Description |
|-------|-------------|
| `cust_id` | Customer ID |
| `discount_group` | Group name (string, not ID) |
| `start_date` | Effective start date |
| `end_date` | Effective end date |
| `EFX_code` | EFX system code (from displayname_to_type.csv) |
| `type` | Transaction type (from displayname_to_type.csv) |
| `start_trans` | Tier volume start |
| `end_trans` | Tier volume end |
| `adj_price` | **Adjusted price** (final after escalators/overrides) |
| `base_price` | Base price (tier-1 default, before adjustments) |
| `by_hit` | Boolean (0/1) - counting control flag |
| `zero_null` | Boolean (0/1) - counting control flag |
| `bav_by_trans` | Boolean (0/1) - counting control flag |

**Price Calculation:**
- `base_price` = Tier 1 default price (before any adjustments)
- `adj_price` = Final price after inheritance + escalators + overrides

**Boolean Flags (by_hit, zero_null, bav_by_trans):**
- Control how transactions are counted
- Have system defaults
- Can be overridden at ANY level (group, customer)
- Follow same inheritance pattern as pricing

**Date Range Logic:**
- Past date range → Historical record (no longer active, kept for audit)
- Future end date → Currently active
- End date 100 years in future → "Indefinitely" / no expiration

**Circular Dependency Note:**
- tier_pricing.csv requires EFX_code and type
- These come from displayname_to_type.csv
- displayname_to_type.csv must be provided/seeded initially

---

### Data Flow

```
                    ┌─────────────────────────────────────────┐
                    │           CONTROL PANEL                 │
                    │  (this system - pricing management)     │
                    └─────────────────┬───────────────────────┘
                                      │
                    Generates INPUT files:
                    • displayname_to_type.csv
                    • tier_pricing.csv
                                      │
                                      ▼
                    ┌─────────────────────────────────────────┐
                    │          BILLING SYSTEM                 │
                    │   (runs 1st of month @ 12:05 AM)        │
                    └─────────────────┬───────────────────────┘
                                      │
                    Produces OUTPUT files:
                    • Billing Report CSV
                    • EBDIC File
                                      │
                                      ▼
                    ┌─────────────────────────────────────────┐
                    │           CONTROL PANEL                 │
                    │  (imports reports, calculates profit)   │
                    └─────────────────────────────────────────┘
                                      │
                    Stores & calculates:
                    • COUNT per customer/rule
                    • REVENUE = COST × COUNT
                    • PROFIT = REVENUE - COGS
                    • LMS commission payouts
```

---

## Data Model Additions

```sql
-- LMS entities (synced from main DB)
lms
  - id
  - name
  - commission_rate (nullable - inherits default if NULL)
  - last_synced

-- System-wide default commission
system_settings
  - key ('default_commission_rate')
  - value

-- Customer to LMS assignment
customers (add column)
  - lms_id (required, FK to lms)

-- COGS configuration (per service? per business rule?)
cogs_config
  - id
  - service_id (or rule reference)
  - cogs_rate
  - effective_date

-- Billing report imports (monthly/daily)
billing_reports
  - id
  - report_type ENUM('daily', 'monthly')
  - report_date
  - imported_at
  - file_path (original file reference)

-- Billing report line items
billing_report_lines
  - id
  - report_id (FK to billing_reports)
  - year
  - month
  - customer_id
  - hit_code
  - tran_displayname
  - actual_unit_cost
  - count
  - revenue
  - efx_code
  - billing_id
```

---

## Resolved Questions (Additional)

| Question | Answer |
|----------|--------|
| COGS granularity | Per service |
| COGS data source | Synced from main database (like other entities) |
| Archive format | Same input/output CSVs, organized in folder hierarchy |
| Report retention | 7 years |

---

---

## Implementation Status (January 17, 2026)

### Core Features - COMPLETE
- Three-level pricing inheritance (Default → Group → Customer)
- Volume-based tiered pricing with granular overrides
- Customer status management (active/paused/decommissioned)
- Monthly minimum with gap line item calculation
- Annualized tiers with look-back periods
- Escalators with delay feature
- Business rule masking
- Full audit history
- CSV export

### LMS Feature - COMPLETE
- LMS entities synced from main DB
- Commission rate inheritance (Default → LMS override)
- Customer-to-LMS assignment (required)
- COGS per service
- Revenue report by LMS (revenue, COGS, profit, commission)

### Ingestion System - COMPLETE (New!)
- Billing report CSV parsing
- Auto-detection of daily vs monthly from filename pattern
- Single file upload interface
- Bulk import for historical seeding
- Directory scan for archive import
- Duplicate detection (idempotent imports)
- Report viewer with line item details
- Delete capability

### Generation System - COMPLETE (New!)
- tier_pricing.csv generator with full format
- Pricing inheritance resolution (Default → Group → Customer)
- Escalator calculations (adj_price with percentage/fixed adjustments)
- Delay support (1st-of-month normalization)
- Transaction types management (from displayname_to_type.csv)
- Billing flags configuration (by_hit, zero_null, bav_by_trans)
- Three-level flag inheritance
- Preview mode (first 100 rows)
- Download CSV / Save to pending directory
- Recent files list

### Pending
- Billing cycle workflow (pre-run checklist, tracking)
- Diff/compare between generated CSVs
- Reconciliation (input vs output comparison)
- Production remote DB integration

*Last updated: January 17, 2026 - CHECKPOINT: Ingestion + Generation Complete*
