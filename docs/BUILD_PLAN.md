# Build Plan

## Overview

Single PHP 5.6 file (`control_panel.php`) + SQLite database file (`control_panel.db`) in the shared directory. Builds on existing CSV/file management code already written.

---

## Phase 1: SQLite Foundation

**Goal:** Database schema and basic CRUD operations

### 1.1 Schema Creation
Create tables on first run if not exists:

```sql
-- Core entities (synced from remote)
customers
discount_groups
services

-- Pricing (append-only)
pricing_tiers
customer_settings

-- Escalators (append-only)
customer_escalators
escalator_delays

-- Business rules
business_rule_masks

-- Audit/meta
sync_log
```

### 1.2 DB Wrapper Functions
- `sqlite_connect()` - get/create DB connection
- `sqlite_query()` - SELECT wrapper
- `sqlite_execute()` - INSERT/UPDATE wrapper
- `get_current_*()` - helpers for latest effective record

### 1.3 Remote DB Sync
- `sync_from_remote()` - pull customers, services, business rules
- Only reads from remote, writes to local SQLite
- "Refresh" button triggers sync

**Deliverable:** Working SQLite with schema, can sync from remote (stubbed initially)

---

## Phase 2: System Defaults

**Goal:** Base pricing that all customers inherit from

### 2.1 UI
- List all services
- For each service: editable tier table (volume_start, volume_end, price)
- Add/remove tier rows
- Save button (append-only insert)

### 2.2 Functions
- `get_system_default_tiers($service_id)`
- `save_system_default_tier($service_id, $tiers)`

**Deliverable:** Can view/edit system default pricing for all services

---

## Phase 3: Discount Groups

**Goal:** Group-level templates that override system defaults

### 3.1 UI
- List groups
- Select group → show pricing table
- Inherited values shown (from system defaults), grayed out
- Override = click to edit, becomes "owned" by group
- See members list

### 3.2 Functions
- `get_group_tiers($group_id, $service_id)` - merged with defaults
- `save_group_tier_override($group_id, $service_id, $tier)`
- `get_group_members($group_id)`

**Deliverable:** Can view/edit group templates, see inheritance

---

## Phase 4: Customer Pricing

**Goal:** Customer-specific overrides, full inheritance chain

### 4.1 UI
- Customer selector (with status filter: active/paused/decommissioned)
- Show effective pricing (merged: default → group → customer)
- Visual indicator: "inherited" vs "overridden"
- Click to override any tier
- Customer settings: monthly minimum, annualized toggle, look period

### 4.2 Functions
- `get_customer_effective_tiers($customer_id, $service_id)` - full merge
- `save_customer_tier_override($customer_id, $service_id, $tier)`
- `get_customer_settings($customer_id)`
- `save_customer_settings($customer_id, $settings)`

**Deliverable:** Full inheritance working, can override at any level

---

## Phase 5: Escalators

**Goal:** Annual price escalation management

### 5.1 UI
- Per-customer escalator schedule table
- Year | Percentage | Fixed Adjustment | Effective Date (calculated)
- "Delay 1 Month" button per escalator instance
- Show normalized dates (1st of month)

### 5.2 Functions
- `get_customer_escalators($customer_id)`
- `save_escalator($customer_id, $year, $percentage, $fixed_adjustment)`
- `delay_escalator($customer_id, $year)` - adds 1 month delay
- `calculate_escalator_date($contract_start, $year, $delays)` - with 1st normalization

**Deliverable:** Escalator management with delay functionality

---

## Phase 6: Business Rules

**Goal:** Per-customer rule masking

### 6.1 UI
- Customer selector
- List of business rules (from remote sync)
- Toggle: masked/unmasked per rule
- "Refresh from DB" button

### 6.2 Functions
- `get_customer_business_rules($customer_id)`
- `set_rule_mask($customer_id, $rule_name, $is_masked)`
- `sync_business_rules_from_remote($customer_id)`

**Deliverable:** Can mask/unmask business rules per customer

---

## Phase 7: Dashboard / Alerts

**Goal:** "Coming Up" view for proactive management

### 7.1 UI
- Upcoming escalators (next 30/60/90 days)
- Contract anniversaries
- Look period resets (annualized customers)
- Countdown display

### 7.2 Functions
- `get_upcoming_escalators($days_ahead)`
- `get_upcoming_anniversaries($days_ahead)`
- `get_upcoming_look_resets($days_ahead)`

**Deliverable:** Dashboard showing upcoming events

---

## Phase 8: History / Audit

**Goal:** View historical changes

### 8.1 UI
- Entity selector (customer, group, default)
- Timeline of changes
- Compare versions side-by-side

### 8.2 Functions
- `get_pricing_history($level, $level_id, $service_id)`
- `get_settings_history($customer_id)`

**Deliverable:** Full audit trail visibility

---

## Phase 9: CSV Integration

**Goal:** Connect to existing CSV workflow

### 9.1 Export
- Generate CSV from current effective pricing
- Include all resolved values (after inheritance)
- For human review

### 9.2 Import
- Upload reviewed/modified CSV
- Parse and apply as overrides
- Validate before commit

### 9.3 Functions
- `export_customer_pricing_csv($customer_id)`
- `export_all_pricing_csv()`
- `import_pricing_csv($file)` - validate + apply

**Deliverable:** Full round-trip CSV workflow integrated

---

## Phase 10: Monthly Minimum & Gap Calculation

**Goal:** Minimum charge logic with gap line item

### 10.1 Functions
- `calculate_monthly_bill($customer_id, $month)`
- `calculate_gap($subtotal, $minimum)` - returns gap amount or 0
- `generate_bill_with_gap($customer_id, $month)` - full bill structure

### 10.2 UI
- Preview bill calculation
- Show gap line item when applicable

**Deliverable:** Monthly minimum logic complete

---

## UI Structure (Tabs)

Final tab layout in single page:

| Tab | Phase |
|-----|-------|
| Dashboard | Phase 7 |
| Customers | Phase 4 |
| Groups | Phase 3 |
| Defaults | Phase 2 |
| Escalators | Phase 5 |
| Business Rules | Phase 6 |
| Reports (CSV) | Phase 9 + existing |
| History | Phase 8 |

---

## Dependencies

```
Phase 1 (SQLite) 
    ↓
Phase 2 (Defaults) → Phase 3 (Groups) → Phase 4 (Customers)
    ↓                                        ↓
Phase 5 (Escalators) ←───────────────────────┘
    ↓
Phase 6 (Business Rules)
    ↓
Phase 7 (Dashboard) ← needs escalator dates
    ↓
Phase 8 (History)
    ↓
Phase 9 (CSV) ← needs all pricing logic
    ↓
Phase 10 (Minimum/Gap)
```

---

## Estimated Complexity

| Phase | Complexity | Notes |
|-------|------------|-------|
| 1 | Medium | Schema design critical |
| 2 | Low | Simple CRUD |
| 3 | Medium | Inheritance display |
| 4 | High | Full merge logic |
| 5 | Medium | Date calculations |
| 6 | Low | Simple toggle |
| 7 | Medium | Query aggregation |
| 8 | Low | Read-only views |
| 9 | Medium | CSV parsing/validation |
| 10 | Low | Calculation logic |

---

## Implementation Status

| Phase | Status | Notes |
|-------|--------|-------|
| 1 - SQLite Foundation | COMPLETE | Schema, migrations, CRUD helpers |
| 2 - System Defaults | COMPLETE | View/edit default tiers with expand/collapse |
| 3 - Discount Groups | COMPLETE | Group templates, inheritance from defaults |
| 4 - Customer Pricing | COMPLETE | Full 3-level inheritance, settings |
| 5 - Escalators | COMPLETE | Annual increases with delay feature |
| 6 - Business Rules | COMPLETE | Per-customer rule masking |
| 7 - Dashboard/Alerts | COMPLETE | Upcoming escalators, masked rules, paused customers |
| 8 - History/Audit | COMPLETE | Pricing, settings, escalator, rule history |
| 9 - CSV Integration | COMPLETE | Export pricing, settings, escalators |
| 10 - Monthly Minimum | COMPLETE | Gap calculation with explanation UI |

### Additional Features Implemented

| Feature | Status | Notes |
|---------|--------|-------|
| LMS (Loan Management System) | COMPLETE | Orthogonal grouping for revenue tracking |
| Commission Rates | COMPLETE | Default + per-LMS override inheritance |
| COGS per Service | COMPLETE | Cost of goods sold tracking |
| LMS Revenue Report | COMPLETE | Revenue, COGS, profit, commission per LMS |
| Customer-to-LMS Assignment | COMPLETE | Required field in customer settings |
| Database Migrations | COMPLETE | Auto-migrate schema for existing DBs |
| Expandable Tier Views | COMPLETE | Expand/collapse all, expanded by default |

---

## Current Architecture

```
control_panel.php (~6200 lines)
├── Configuration & Constants
├── Remote DB Wrapper (stub for production)
├── SQLite Database Layer
│   ├── Connection & Schema
│   ├── Migrations
│   └── CRUD Helpers
├── Helper Functions
│   ├── Pricing (defaults, groups, customers)
│   ├── Settings & Escalators
│   ├── Business Rules
│   ├── LMS & Commission
│   └── History & Export
├── Action Functions (route handlers)
├── Render Functions (HTML templates)
└── Mock Data & Bootstrap
```

---

## Pending Architectural Decisions

*Awaiting user input on direction*

---

*Last updated: January 2026*
