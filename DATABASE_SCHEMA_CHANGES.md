# Database Schema Changes - Digital Signature System

## Summary of Changes

Three new migrations were created and applied to your database. Here's what changed:

---

## 1️⃣ Users Table - Added Signature Field

**Migration File:** `2026_02_18_061718_add_signature_path_to_users_table.php`

### New Column Added:
```sql
ALTER TABLE users ADD COLUMN signature_path VARCHAR(255) NULL AFTER role;
```

### Schema:
| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| signature_path | VARCHAR(255) | YES | NULL | Path to signature image file |

### Example Data:
```
user_id = 1
name = "John Doe"
email = "john@company.com"
role = "staff"
signature_path = "signatures/64e5f3b8c9d2a.png"
```

---

## 2️⃣ Purchase Requests Table - Added Approval Fields

**Migration File:** `2026_02_18_061842_add_pr_approval_fields_to_purchase_requests_table.php`

### New Columns Added:
```sql
ALTER TABLE purchase_requests 
ADD COLUMN acknowledged_by BIGINT UNSIGNED NULL,
ADD COLUMN acknowledged_at TIMESTAMP NULL,
ADD COLUMN purchasing_received_by BIGINT UNSIGNED NULL,
ADD COLUMN purchasing_received_at TIMESTAMP NULL,
ADD FOREIGN KEY (acknowledged_by) REFERENCES users(id),
ADD FOREIGN KEY (purchasing_received_by) REFERENCES users(id);
```

### Schema:
| Column | Type | Nullable | Foreign Key | Notes |
|--------|------|----------|-------------|-------|
| acknowledged_by | BIGINT UNSIGNED | YES | users.id | Who acknowledged the PR |
| acknowledged_at | TIMESTAMP | YES | - | When PR was acknowledged |
| purchasing_received_by | BIGINT UNSIGNED | YES | users.id | Who received the PR |
| purchasing_received_at | TIMESTAMP | YES | - | When PR was received |

### Existing Columns (Still There):
| Column | Type | Notes |
|--------|------|-------|
| approved_by | BIGINT UNSIGNED | Admin who approved |
| approved_at | TIMESTAMP | When approved |

### Complete PR Approval Fields:
```
1. requested_by (staff who created) - EXISTING
2. approved_by (admin who approved) - EXISTING
3. acknowledged_by (purchasing who acknowledged) - NEW
4. purchasing_received_by (purchasing who received) - NEW
```

### Example Data:
```
pr_id = 1
pr_number = "PR-2026-0001"
requested_by = 1 (John - staff)
approved_by = 2 (Sarah - admin)
approved_at = "2026-02-18 10:00:00"
acknowledged_by = 3 (Mike - purchasing)
acknowledged_at = "2026-02-18 10:30:00"
purchasing_received_by = 3 (Mike - purchasing)
purchasing_received_at = "2026-02-18 11:00:00"
```

---

## 3️⃣ Purchase Orders Table - Added Approval Fields

**Migration File:** `2026_02_18_061915_add_po_approval_fields_to_purchase_orders_table.php`

### New Columns Added:
```sql
ALTER TABLE purchase_orders 
ADD COLUMN approved_by BIGINT UNSIGNED NULL,
ADD COLUMN approved_at TIMESTAMP NULL,
ADD COLUMN acknowledged_by BIGINT UNSIGNED NULL,
ADD COLUMN acknowledged_at TIMESTAMP NULL,
ADD COLUMN received_by BIGINT UNSIGNED NULL,
ADD COLUMN received_at TIMESTAMP NULL,
ADD FOREIGN KEY (approved_by) REFERENCES users(id),
ADD FOREIGN KEY (acknowledged_by) REFERENCES users(id),
ADD FOREIGN KEY (received_by) REFERENCES users(id);
```

### Schema:
| Column | Type | Nullable | Foreign Key | Notes |
|--------|------|----------|-------------|-------|
| approved_by | BIGINT UNSIGNED | YES | users.id | Admin who approved PO |
| approved_at | TIMESTAMP | YES | - | When PO was approved |
| acknowledged_by | BIGINT UNSIGNED | YES | users.id | Purchasing who acknowledged |
| acknowledged_at | TIMESTAMP | YES | - | When acknowledged |
| received_by | BIGINT UNSIGNED | YES | users.id | Warehouse who received |
| received_at | TIMESTAMP | YES | - | When goods received |

### Existing Columns (Still There):
| Column | Type | Notes |
|--------|------|-------|
| created_by | BIGINT UNSIGNED | Purchasing who created |

### Complete PO Approval Fields:
```
1. created_by (purchasing who created) - EXISTING
2. approved_by (admin who approved) - NEW
3. acknowledged_by (purchasing who acknowledged) - NEW
4. received_by (warehouse who received) - NEW
```

### Example Data:
```
po_id = 1
po_number = "PO-2026-0001"
created_by = 3 (Mike - purchasing)
approved_by = 2 (Sarah - admin)
approved_at = "2026-02-18 14:00:00"
acknowledged_by = 3 (Mike - purchasing)
acknowledged_at = "2026-02-18 14:30:00"
received_by = 4 (Tom - warehouse)
received_at = "2026-02-18 16:00:00"
```

---

## 📊 Complete Flow Visualization

### Purchase Request Approval Chain:
```
┌─────────────────────────────────────────────────────────────────┐
│ PURCHASE_REQUESTS TABLE - All Signatures                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Signature 1: CREATED BY (REQUESTED_BY)                        │
│  ├─ requested_by: BIGINT (user_id)                             │
│  ├─ Auto-set when staff creates PR                             │
│                                                                 │
│  Signature 2: APPROVED BY                                      │
│  ├─ approved_by: BIGINT (user_id)                              │
│  ├─ approved_at: TIMESTAMP                                     │
│  ├─ Set by Admin when approving                                │
│                                                                 │
│  Signature 3: ACKNOWLEDGED BY (NEW)                            │
│  ├─ acknowledged_by: BIGINT (user_id)                          │
│  ├─ acknowledged_at: TIMESTAMP                                 │
│  ├─ Set by Purchasing when acknowledging                       │
│                                                                 │
│  Signature 4: PURCHASING RECEIVED (NEW)                        │
│  ├─ purchasing_received_by: BIGINT (user_id)                   │
│  ├─ purchasing_received_at: TIMESTAMP                          │
│  ├─ Set by Purchasing when marking as received                 │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Purchase Order Approval Chain:
```
┌─────────────────────────────────────────────────────────────────┐
│ PURCHASE_ORDERS TABLE - All Signatures                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Signature 1: CREATED BY                                        │
│  ├─ created_by: BIGINT (user_id)                               │
│  ├─ Auto-set when purchasing creates PO                        │
│                                                                 │
│  Signature 2: APPROVED BY (NEW)                                │
│  ├─ approved_by: BIGINT (user_id)                              │
│  ├─ approved_at: TIMESTAMP                                     │
│  ├─ Set by Admin when approving                                │
│                                                                 │
│  Signature 3: ACKNOWLEDGED BY (NEW)                            │
│  ├─ acknowledged_by: BIGINT (user_id)                          │
│  ├─ acknowledged_at: TIMESTAMP                                 │
│  ├─ Set by Purchasing when acknowledging                       │
│                                                                 │
│  Signature 4: RECEIVED BY (NEW)                                │
│  ├─ received_by: BIGINT (user_id)                              │
│  ├─ received_at: TIMESTAMP                                     │
│  ├─ Set by Warehouse when receiving goods                      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔍 SQL Queries to Inspect

### View all users with their signatures:
```sql
SELECT id, name, email, role, signature_path 
FROM users 
WHERE signature_path IS NOT NULL;
```

### View all PRs with all approval signatures:
```sql
SELECT 
  pr.pr_number,
  pr.status,
  u1.name as 'Requested By',
  u2.name as 'Approved By',
  pr.approved_at,
  u3.name as 'Acknowledged By',
  pr.acknowledged_at,
  u4.name as 'Received By',
  pr.purchasing_received_at
FROM purchase_requests pr
LEFT JOIN users u1 ON pr.requested_by = u1.id
LEFT JOIN users u2 ON pr.approved_by = u2.id
LEFT JOIN users u3 ON pr.acknowledged_by = u3.id
LEFT JOIN users u4 ON pr.purchasing_received_by = u4.id;
```

### View all POs with all approval signatures:
```sql
SELECT 
  po.po_number,
  po.status,
  u1.name as 'Created By',
  u2.name as 'Approved By',
  po.approved_at,
  u3.name as 'Acknowledged By',
  po.acknowledged_at,
  u4.name as 'Received By',
  po.received_at
FROM purchase_orders po
LEFT JOIN users u1 ON po.created_by = u1.id
LEFT JOIN users u2 ON po.approved_by = u2.id
LEFT JOIN users u3 ON po.acknowledged_by = u3.id
LEFT JOIN users u4 ON po.received_by = u4.id;
```

---

## 📁 Storage Structure

### Signatures stored at:
```
storage/app/public/signatures/
├── 64e5f3b8c9d2a.png
├── 64e5f3b8c9d2b.png
├── 64e5f3b8c9d2c.png
└── ...
```

### Accessible via web:
```
https://yoursite.com/storage/signatures/64e5f3b8c9d2a.png
```

### File permissions:
- Stored securely (not accessible to non-authenticated users in code)
- Symlink: `public/storage` → `storage/app/public`

---

## 🔄 Backwards Compatibility

✅ **All changes are backwards compatible:**
- No existing columns removed
- All new columns are NULLABLE
- Existing PRs/POs continue to work
- Existing workflows unaffected
- Can add signatures retroactively

---

## 📈 Data Integrity

### Foreign Keys:
- All user IDs have foreign key constraints
- Cannot reference deleted users
- Referential integrity maintained

### Cascading:
- When user deleted: signature_path can be NULL
- When user deleted: approval fields become NULL (no cascade delete)

### Timestamps:
- Automatically set to current timestamp on approval
- Format: YYYY-MM-DD HH:MM:SS
- Timezone: Server default (usually UTC)

---

## 🗑️ Rollback (If Needed)

If you need to revert, use:
```bash
php artisan migrate:rollback --step=4
```

This will:
- Remove all 4 new migrations
- Restore original `users`, `purchase_requests`, `purchase_orders` tables
- Keep all data (migrations don't delete data)

---

## ✅ Verification Queries

### Verify migrations applied:
```bash
php artisan migrate:status
```

### Check users table signature column:
```sql
SHOW COLUMNS FROM users LIKE 'signature_path';
```

### Check PR approval columns:
```sql
SHOW COLUMNS FROM purchase_requests 
WHERE Field IN ('acknowledged_by', 'acknowledged_at', 'purchasing_received_by', 'purchasing_received_at');
```

### Check PO approval columns:
```sql
SHOW COLUMNS FROM purchase_orders 
WHERE Field IN ('approved_by', 'approved_at', 'acknowledged_by', 'acknowledged_at', 'received_by', 'received_at');
```

