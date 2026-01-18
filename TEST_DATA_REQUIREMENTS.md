# Test Data Requirements

This document outlines ALL data needed to populate the control panel with realistic test data for end-to-end testing.

---

## 1. SERVICES (Billable Service Types)

**What is it?** The products/services you bill customers for. Each service has tiered volume-based pricing.

**I need from you:**
- List of service names
- How many services exist? (5? 10? 20?)

**Example format:**
```
Service ID | Service Name
-----------|------------------
1          | Credit Check Basic
2          | Credit Check Premium
3          | Identity Verification
4          | Address Validation
5          | Phone Lookup
```

**Questions:**
- What are your actual service names?
- Are some services more common than others?

---

## 2. DISCOUNT GROUPS (Pricing Templates)

**What is it?** Optional groupings that provide pricing templates. Customers in a group inherit the group's pricing unless they have their own override.

**I need from you:**
- List of group names
- What makes a group? (Partner tier? Industry? Contract type?)

**Example format:**
```
Group ID | Group Name
---------|------------------
1        | Enterprise Partners
2        | Resellers
3        | Financial Services
4        | Healthcare
5        | Standard
```

**Questions:**
- How many discount groups exist?
- What percentage of customers belong to a group vs. no group?
- Do groups have descriptive names or codes?

---

## 3. LMS (Loan Management Systems)

**What is it?** Revenue tracking grouping. Each customer MUST be assigned to an LMS. Commission is paid to LMS based on profit.

**I need from you:**
- List of LMS names/IDs
- Commission rate overrides (if any differ from default)

**Example format:**
```
LMS ID | LMS Name              | Commission Rate
-------|----------------------|----------------
1      | First National LMS   | NULL (use default)
2      | Pacific Lending      | 12.5%
3      | Midwest Finance      | NULL (use default)
4      | Direct Partners      | 15.0%
```

**Questions:**
- How many LMS entities exist?
- What's the default commission rate? (e.g., 10%)
- Do most LMS use default or have overrides?

---

## 4. CUSTOMERS

**What is it?** The entities being billed. Each customer can optionally belong to a discount group and MUST be assigned to an LMS.

**I need from you:**
- List of customer names
- Their discount group assignment (or none)
- Their LMS assignment
- Status (active/paused/decommissioned)
- Contract start date

**Example format:**
```
ID  | Name                  | Discount Group    | LMS              | Status   | Contract Start
----|----------------------|-------------------|------------------|----------|---------------
101 | Acme Corp            | Enterprise        | First National   | active   | 2023-01-15
102 | Beta Industries      | NULL              | Pacific Lending  | active   | 2022-06-01
103 | Gamma LLC            | Standard          | Midwest Finance  | paused   | 2024-03-01
104 | Delta Services       | Financial Svcs    | Direct Partners  | active   | 2021-11-01
105 | Old Client Inc       | NULL              | First National   | decommissioned | 2019-01-01
```

**Questions:**
- How many customers total? (10? 50? 100?)
- What percentage are active vs paused vs decommissioned?
- What percentage belong to a discount group?
- What's the date range of contract starts? (oldest to newest)
- Are customer IDs sequential integers or some other format?

---

## 5. PRICING TIERS (System Defaults)

**What is it?** Base pricing for each service. Every service MUST have default tiers defined. This is the fallback when no group/customer override exists.

**I need from you:**
- For EACH service: the tier structure (volume ranges and prices)

**Example format (per service):**
```
Service: Credit Check Basic
Volume Start | Volume End | Price Per Inquiry
-------------|------------|------------------
0            | 1000       | $0.50
1001         | 5000       | $0.45
5001         | 10000      | $0.40
10001        | NULL       | $0.35
```

**Questions:**
- How many tiers per service typically? (3? 5? varies?)
- What's the typical price range? ($0.01 - $10.00?)
- Do all services have the same tier structure or different?
- Are volume thresholds round numbers (1000, 5000, 10000)?

---

## 6. PRICING TIERS (Group Overrides)

**What is it?** Groups can override specific tiers for specific services. Not all groups override all services - only where they differ from defaults.

**I need from you:**
- Which groups have overrides for which services
- The specific tier/price changes

**Example format:**
```
Group: Enterprise Partners
Service: Credit Check Basic
Volume Start | Volume End | Price Per Inquiry
-------------|------------|------------------
0            | 1000       | $0.40  (20% discount from default $0.50)
1001         | 5000       | $0.36
5001         | NULL       | $0.30

Group: Enterprise Partners  
Service: Identity Verification
(uses system defaults - no override)

Group: Resellers
Service: Credit Check Basic
Volume Start | Volume End | Price Per Inquiry
-------------|------------|------------------
0            | 5000       | $0.42  (different tier structure!)
5001         | NULL       | $0.35
```

**Questions:**
- What percentage of groups have overrides? (all? some?)
- Do overrides typically affect all tiers or just some?
- Are group prices always lower than defaults? (discounts)
- Do any groups have DIFFERENT tier volume thresholds than defaults?

---

## 7. PRICING TIERS (Customer Overrides)

**What is it?** Individual customers can have their own pricing, overriding both group and default.

**I need from you:**
- Which customers have custom pricing
- For which services
- The specific tier/price changes

**Example format:**
```
Customer: Acme Corp (belongs to Enterprise Partners group)
Service: Credit Check Basic
Volume Start | Volume End | Price Per Inquiry
-------------|------------|------------------
0            | 2000       | $0.38  (custom negotiated deal)
2001         | NULL       | $0.28

Customer: Beta Industries (no group)
Service: Credit Check Basic
(uses system defaults - no override)
```

**Questions:**
- What percentage of customers have custom overrides?
- Do customers with groups still get customer-level overrides?
- Are customer overrides for specific services only or all services?

---

## 8. CUSTOMER SETTINGS

**What is it?** Per-customer configuration: monthly minimums, annualized tier calculations.

**I need from you:**
- Which customers have monthly minimums (and what amount)
- Which customers use annualized tiers
- Annualized start dates and look periods

**Example format:**
```
Customer          | Monthly Min | Uses Annualized | Annualized Start | Look Period
------------------|-------------|-----------------|------------------|------------
Acme Corp         | $500.00     | No              | NULL             | NULL
Beta Industries   | NULL        | Yes             | 2024-01-01       | 3 months
Gamma LLC         | $1000.00    | Yes             | 2024-06-01       | 6 months
Delta Services    | NULL        | No              | NULL             | NULL
```

**Questions:**
- What percentage of customers have monthly minimums?
- What's the typical minimum range? ($100 - $10,000?)
- What percentage use annualized tiers?
- What are common look periods? (3 months? 6 months? 12 months?)

---

## 9. ESCALATORS (Annual Price Increases)

**What is it?** Per-customer price escalation schedules. Prices increase on contract anniversary.

**I need from you:**
- Which customers have escalators
- Their escalator schedule (year-by-year percentages)
- Any fixed adjustments
- Any delays applied

**Example format:**
```
Customer: Acme Corp
Contract Start: 2023-01-15 (escalators start on 1st of following month)
Year | Percentage | Fixed Adjustment
-----|------------|------------------
1    | 0%         | $0
2    | 5%         | $0
3    | 5%         | $0
4    | 3%         | $0
5    | 3%         | $0

Customer: Delta Services
Contract Start: 2021-11-01
Year | Percentage | Fixed Adjustment
-----|------------|------------------
1    | 0%         | $0
2    | 4%         | +$25.00
3    | 4%         | +$25.00
Delays: Year 2 delayed by 2 months
```

**Questions:**
- What percentage of customers have escalators?
- What's the typical escalator percentage? (3%? 5%? 10%?)
- How many years out do escalators typically go? (3? 5? 10?)
- Are fixed adjustments common?
- How often are delays used?

---

## 10. BUSINESS RULES (Integration Rules)

**What is it?** Customer-specific rules that can be masked (excluded from billing counts).

**I need from you:**
- Example rule names
- Which customers have which rules
- Which rules are typically masked

**Example format:**
```
Customer: Acme Corp
Rule Name                    | Description                    | Masked?
-----------------------------|--------------------------------|--------
RULE_CREDIT_RETRY           | Credit check retry attempts    | No
RULE_BATCH_PROCESSING       | Batch processing mode          | Yes
RULE_DUPLICATE_CHECK        | Duplicate detection            | No

Customer: Beta Industries
Rule Name                    | Description                    | Masked?
-----------------------------|--------------------------------|--------
RULE_CREDIT_RETRY           | Credit check retry attempts    | Yes
RULE_HIGH_VOLUME            | High volume processing         | No
```

**Questions:**
- What are typical rule names?
- How many rules per customer? (3? 10? 50?)
- What percentage of rules are typically masked?
- Are rules consistent across customers or unique per customer?

---

## 11. TRANSACTION TYPES (EFX Code Mapping)

**What is it?** Maps display names to EFX codes for billing system integration.

**I need from you:**
- List of transaction types
- EFX codes
- Which service they belong to

**Example format:**
```
Type       | Display Name          | EFX Code | EFX Display    | Service
-----------|----------------------|----------|----------------|------------------
credit     | Credit Hit           | CR001    | CREDIT_HIT     | Credit Check Basic
credit     | Credit Miss          | CR002    | CREDIT_MISS    | Credit Check Basic
credit     | Credit Error         | CR003    | CREDIT_ERR     | Credit Check Basic
identity   | ID Verified          | ID001    | ID_VERIFY      | Identity Verification
identity   | ID Not Found         | ID002    | ID_NOTFND      | Identity Verification
address    | Address Valid        | AD001    | ADDR_VALID     | Address Validation
address    | Address Invalid      | AD002    | ADDR_INVLD     | Address Validation
```

**Questions:**
- What are your actual EFX codes?
- How many transaction types per service?
- What's the type/category grouping logic?

---

## 12. SERVICE BILLING FLAGS

**What is it?** Boolean flags (by_hit, zero_null, bav_by_trans) that control how transactions are counted. Can be set at default/group/customer level.

**I need from you:**
- Default flag values per service/EFX code
- Any group-level overrides
- Any customer-level overrides

**Example format:**
```
Level    | Level ID | Service            | EFX Code | by_hit | zero_null | bav_by_trans
---------|----------|--------------------|---------:|--------|-----------|-------------
default  | NULL     | Credit Check Basic | CR001    | 1      | 0         | 0
default  | NULL     | Credit Check Basic | CR002    | 1      | 1         | 0
group    | 1        | Credit Check Basic | CR001    | 1      | 0         | 1
customer | 101      | Credit Check Basic | CR001    | 0      | 0         | 0
```

**Questions:**
- What do these flags actually mean for your business?
- What are typical default values?
- How often are they overridden?

---

## 13. SERVICE COGS (Cost of Goods Sold)

**What is it?** What YOU pay per transaction (your cost). Used for profit calculation.

**I need from you:**
- COGS rate per service

**Example format:**
```
Service              | COGS Rate
---------------------|----------
Credit Check Basic   | $0.10
Credit Check Premium | $0.25
Identity Verification| $0.15
Address Validation   | $0.05
Phone Lookup         | $0.08
```

**Questions:**
- What's your cost per service?
- Does COGS change over time?

---

## 14. BILLING REPORTS (Historical Data)

**What is it?** Imported billing data showing actual transactions per customer.

**I need from you:**
- Sample billing data covering several months
- Mix of daily and monthly reports

**Example format:**
```
Year | Month | Customer ID | EFX Code | Count | Revenue
-----|-------|-------------|----------|-------|--------
2024 | 10    | 101         | CR001    | 1500  | $675.00
2024 | 10    | 101         | CR002    | 200   | $90.00
2024 | 10    | 102         | CR001    | 500   | $250.00
2024 | 11    | 101         | CR001    | 1800  | $810.00
```

**Questions:**
- How many months of historical data do we need?
- What's a realistic transaction count range per customer?
- Do all customers use all services?

---

## 15. SYSTEM SETTINGS

**I need from you:**
- Default commission rate (for LMS)
- Any other system-wide settings

---

## SUMMARY: What I Need From You

| # | Entity | Key Questions |
|---|--------|---------------|
| 1 | Services | Names, count |
| 2 | Discount Groups | Names, count, what defines a group |
| 3 | LMS | Names, count, commission rates |
| 4 | Customers | Names, group assignments, LMS assignments, statuses, contract dates |
| 5 | Default Pricing | Tier structure per service |
| 6 | Group Pricing | Which groups override which services |
| 7 | Customer Pricing | Which customers have custom pricing |
| 8 | Customer Settings | Monthly minimums, annualized configs |
| 9 | Escalators | Which customers, what schedules |
| 10 | Business Rules | Rule names, masking |
| 11 | Transaction Types | EFX codes, service mapping |
| 12 | Billing Flags | Default values, overrides |
| 13 | COGS | Cost per service |
| 14 | Billing Reports | Sample transaction data |
| 15 | System Settings | Default commission rate |

---

## Option: Fictional vs. Real Data

**Option A: You provide real (anonymized) data**
- Most realistic testing
- I format it for import

**Option B: You describe the SHAPE and I generate fictional data**
- Tell me: "10 services, 5 groups, 50 customers, 3-tier pricing..."
- I create plausible fake names and numbers

**Option C: Hybrid**
- You provide real service names, EFX codes, tier structures
- I generate fictional customer names and transactions

---

## Response Template

Feel free to copy this and fill in:

```
SERVICES:
- [list your services]

DISCOUNT GROUPS:
- [list your groups]

LMS:
- [list your LMS entities]

CUSTOMERS:
- Approximately [X] customers
- [X]% active, [X]% paused, [X]% decommissioned
- [X]% belong to a group
- Contract dates range from [YYYY] to [YYYY]

PRICING STRUCTURE:
- [X] tiers per service
- Prices range from $[X] to $[X]
- [Describe tier thresholds]

[Continue for other sections...]
```
