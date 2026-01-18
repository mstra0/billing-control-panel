# Project Status & Roadmap

*Last Updated: January 17, 2026*

---

## Executive Summary

**What we're building:** A memory and operations layer for a stateless billing cron script.

**Where we are:** CHECKPOINT REACHED - Ingestion AND Generation systems are complete! The full loop is closed.

**What's done:** Configuration, Ingestion, Generation - we can now feed the cron AND consume its output.

---

## Current State: What's Done

### Core Infrastructure ✅
| Component | Status | Notes |
|-----------|--------|-------|
| SQLite database | DONE | Schema, migrations, CRUD helpers |
| PHP 5.6 single-file app | DONE | ~8500 lines, runs in container |
| Mock data system | DONE | For development/testing |
| Navigation & UI framework | DONE | Tabbed interface, flash messages |

### Pricing Management ✅
| Feature | Status | Notes |
|---------|--------|-------|
| System Defaults | DONE | Base pricing for all services |
| Discount Groups | DONE | Group templates, inheritance |
| Customer Pricing | DONE | Full 3-level inheritance |
| Tier Management | DONE | Volume-based, expandable views |
| Monthly Minimum | DONE | Gap calculation with explanation |
| Annualized Tiers | DONE | Look-back period support |

### Operations ✅
| Feature | Status | Notes |
|---------|--------|-------|
| Escalators | DONE | Annual increases with delay |
| Business Rules | DONE | Per-customer masking |
| Customer Status | DONE | Active/paused/decommissioned |

### LMS & Revenue ✅
| Feature | Status | Notes |
|---------|--------|-------|
| LMS Entities | DONE | Synced from main DB (mocked) |
| Commission Rates | DONE | Default + per-LMS override |
| Customer-to-LMS | DONE | Required assignment |
| COGS per Service | DONE | Cost tracking |
| LMS Revenue Report | DONE | Revenue, COGS, profit, commission |

### History & Export ✅
| Feature | Status | Notes |
|---------|--------|-------|
| Audit History | DONE | Pricing, settings, escalators, rules |
| CSV Export | DONE | Pricing, settings, escalators |
| Dashboard Alerts | DONE | Upcoming events, warnings |

### Ingestion System ✅ (NEW - Phase 11)
| Feature | Status | Notes |
|---------|--------|-------|
| CSV Parser | DONE | Parses billing report output format |
| Filename Parser | DONE | Auto-detects daily vs monthly from filename pattern |
| Single File Upload | DONE | Upload and import individual reports |
| Bulk Import | DONE | Multi-file upload + directory scan |
| Historical Seed | DONE | Import all files from archive directory |
| Duplicate Detection | DONE | Prevents re-importing same file |
| Report Viewer | DONE | View imported reports with line details |
| Delete Capability | DONE | Remove imported reports |

### Generation System ✅ (NEW - Phase 12)
| Feature | Status | Notes |
|---------|--------|-------|
| tier_pricing.csv Generator | DONE | Full CSV generation with all fields |
| Pricing Inheritance | DONE | Default → Group → Customer resolution |
| Escalator Calculation | DONE | adj_price with percentage/fixed adjustments |
| Delay Support | DONE | Escalator delays factored into calculations |
| Billing Flags | DONE | by_hit, zero_null, bav_by_trans with inheritance |
| Transaction Types | DONE | Import from CSV, manual entry, service linking |
| Preview Mode | DONE | Preview first 100 rows before download |
| Download CSV | DONE | Direct browser download |
| Save to Pending | DONE | Save generated file to pending directory |
| Recent Files List | DONE | Shows last 10 generated files |

### Billing Flags Configuration ✅ (NEW)
| Feature | Status | Notes |
|---------|--------|-------|
| System Default Flags | DONE | Base flags for all services |
| Group Override Flags | DONE | Override at discount group level |
| Customer Override Flags | DONE | Override at individual customer level |
| Flag Inheritance | DONE | Customer → Group → Default resolution |
| Level Selector UI | DONE | Easy switching between levels |

---

## The Loop is Closed!

```
┌─────────────────────────────────────────────────────────────────┐
│                     CONTROL PANEL (this app)                     │
│                                                                  │
│  CONFIGURATION          GENERATION              INGESTION        │
│  ─────────────          ──────────              ─────────        │
│  ✅ Pricing             ✅ tier_pricing.csv     ✅ CSV Parser    │
│  ✅ Escalators          ✅ Transaction Types    ✅ Bulk Import   │
│  ✅ Business Rules      ✅ Billing Flags        ✅ Single Upload │
│  ✅ LMS                 ✅ Preview/Download     ✅ Report View   │
│                                │                      ▲          │
└────────────────────────────────┼──────────────────────┼──────────┘
                                 │                      │
                                 ▼                      │
                    ┌────────────────────────┐          │
                    │   CRON BILLING SCRIPT  │          │
                    │      (runs monthly)    │ ─────────┘
                    │   CSV IN → CSV OUT     │
                    └────────────────────────┘
```

---

## Remaining Work: Integration & Polish

### Phase 13: BILLING CALENDAR & DASHBOARDS (In Progress)

**Goal:** Calendar-driven billing workflow + month-to-date visibility

#### 13.1 - Billing Calendar (Year View) ✅
- [x] Calendar showing all 12 months in grid layout
- [x] Visual indicators: events count, warnings, escalators
- [x] "Done" state when monthly report is ingested
- [x] Click month → drill down to checklist
- [x] "What's Next?" button → jump to first incomplete month
- [x] Year navigation (prev/next)
- [x] Color-coded status: Complete (green), Current (blue), Past/Incomplete (red), Upcoming (gray)

#### 13.2 - Month Checklist (Dynamic) ✅
- [x] **WHAT'S NEW?** - New customers since last month
- [x] **WHAT'S CHANGING?** - Escalators (with delay info), annualized resets
- [x] **WHAT'S EXCLUDED?** - Paused customers listed
- [x] **WHAT'S DIFFERENT?** - Config changes since last month
- [x] **WARNINGS** - Customers without LMS, other issues
- [x] **MTD SUMMARY** - If daily reports exist, shows revenue/transactions
- [x] **FINAL OUTPUT** - Link to generation page

#### 13.3 - Month-to-Date Dashboard ✅
- [x] Standalone MTD dashboard page (`?action=mtd_dashboard`)
- [x] Revenue accumulating through the month by day (bar chart + cumulative)
- [x] Transaction counts by service (breakdown table)
- [x] Customer activity tracking (top 15 customers by revenue)
- [x] Comparison to same point in previous month (% change indicators)
- [x] Projected month-end based on daily average
- [ ] Anomaly detection (sudden drops/spikes) - future enhancement

#### 13.4 - Diff / Compare
- [ ] Compare generated CSV to previous month
- [ ] Highlight changes (new customers, price changes, etc.)
- [ ] Archive generated files automatically with versioning

---

### Phase 14: PRODUCTION READINESS

**Goal:** Connect to real systems

#### 14.1 - Remote Database Integration
- [ ] Replace mock data with real DB queries
- [ ] Connection configuration
- [ ] Error handling for DB issues

#### 14.2 - File System Integration
- [ ] Read from actual shared drive paths
- [ ] Watch directories for new reports
- [ ] Archive management

#### 14.3 - Security & Access
- [ ] Authentication (if needed)
- [ ] Audit logging
- [ ] Backup strategy

---

## Visual Progress

```
COMPLETED ════════════════════════════════════════════════════════════════╗
                                                                          ║
Phases 1-10          Phase 11              Phase 12          Phase 13-14  ║
CONFIGURATION        INGESTION             GENERATION        INTEGRATION  ║
    ✅                  ✅                    ✅                 ⬜        ║
                                                                          ║
├─ Pricing Config    ├─ CSV Parser         ├─ CSV Generator    ├─ Workflow║
├─ Inheritance       ├─ Filename Parser    ├─ Inheritance      ├─ Compare ║
├─ Escalators        ├─ Bulk Import        ├─ Escalators       ├─ Reconcile
├─ Business Rules    ├─ Historical Seed    ├─ Billing Flags    └─ Production
├─ LMS/Commission    ├─ Report Viewer      ├─ Transaction Types
├─ History/Audit     └─ Delete Reports     ├─ Preview/Download
└─ Export                                  └─ Save to Pending

"Set everything up"  "Learn from results"  "Feed the beast"   "Polish & Deploy"
══════════════════════════════════════════════════════════════════════════╝
```

---

## Navigation Structure

| Tab | Actions | Status |
|-----|---------|--------|
| Dashboard | Overview, alerts, stats | ✅ |
| Defaults | System default pricing | ✅ |
| Groups | Discount group templates | ✅ |
| Customers | Customer pricing, settings | ✅ |
| Escalators | Annual increases, delays | ✅ |
| Rules | Business rule masking | ✅ |
| LMS | Revenue tracking, commissions | ✅ |
| **Ingestion** | Import billing reports | ✅ NEW |
| **Generation** | Generate tier_pricing.csv | ✅ NEW |
| Reports | View generated reports | ✅ |
| Export | Download CSVs | ✅ |
| History | Audit trail | ✅ |

---

## Files Reference

| File | Purpose | Lines |
|------|---------|-------|
| `control_panel.php` | Main application | ~8500 |
| `phpliteadmin.php` | SQLite database explorer | External |
| `PRICING_REQUIREMENTS.md` | Detailed requirements & data models | ~400 |
| `BUILD_PLAN.md` | Original 10-phase build plan | ~300 |
| `SYSTEM_ARCHITECTURE.md` | Architecture overview | ~250 |
| `PROJECT_STATUS.md` | This file - status & roadmap | ~300 |

---

## Key File Formats

### INPUT: tier_pricing.csv (Generated)
```csv
cust_id,discount_group,start_date,end_date,EFX_code,type,start_trans,end_trans,adj_price,base_price,by_hit,zero_null,bav_by_trans
```

### OUTPUT: Billing Report (Ingested)
```csv
y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id
```

### Filename Patterns
- Daily: `DataX_YYYY_MM_D_*.csv`
- Monthly: `DataX_YYYY_MM_YYYY_MM_*.csv`

---

## Checkpoint Summary - January 18, 2026

**Milestone:** INGESTION + GENERATION + TESTS COMPLETE

**What works:**
1. Import historical and new billing reports (daily/monthly)
2. Generate tier_pricing.csv with full inheritance + escalators
3. Configure billing flags at any level (default/group/customer)
4. Manage transaction type mappings
5. Preview before download, save to pending directory
6. **377 automated tests passing** across 8 test files

**Test Coverage:**
- Escalator calculations (28 tests)
- Pricing inheritance (52 tests)
- CSV parsing (79 tests)
- CRUD operations (47 tests)
- Query functions (59 tests)
- Utility functions (66 tests)
- Dashboard functions (16 tests)
- History functions (16 tests)

**Report Types Clarified:**
- **Daily reports:** Automatic, fuel MTD dashboard, no manual intervention
- **Monthly reports:** 1st of month, triggers "month complete" when ingested
- **Test reports:** No concept - read-only operation, safe to run anytime

**Ready for:** Phase 13 (Billing Calendar & Dashboards)

---

*Checkpoint: January 18, 2026*
