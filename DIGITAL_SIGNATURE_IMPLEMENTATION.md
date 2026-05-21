# Digital Signature Authentication System - Implementation Summary

## Overview
You now have a complete digital signature authentication system for Purchase Requests (PR) and Purchase Orders (PO). Users upload their signatures once to their profile, and the system automatically applies them to documents during creation and approval steps.

---

## Features Implemented

### 1. **User Profile & Signature Management**
- **Route:** `/profile` (Click on your name in the top-right navbar)
- **Location:** `resources/views/users/profile.blade.php`
- **Features:**
  - View profile information (name, username, email, role)
  - Upload/update digital signature (PNG or JPG, max 5MB)
  - See current signature preview
  - Help section with recommendations

### 2. **Purchase Request (PR) Approval Flow**

The PR now follows a 4-signature approval process:

1. **Created By (Requestor)** ✓
   - Automatically signs when creating the PR
   - Signature fetched from the requesting user's profile
   - Displayed in PR view

2. **Approved By (Admin)** 
   - Route: `POST /purchase-requests/{id}/approve`
   - Admin must approve the submitted PR
   - Requires admin to have a signature on file
   - If no signature: redirected to profile page first

3. **Acknowledged By (Purchasing Staff)**
   - Route: `POST /purchase-requests/{id}/acknowledge`
   - Purchasing department acknowledges receipt
   - Requires signature on file
   - Button appears only after approval

4. **Purchasing Received (Final Signature)**
   - Route: `POST /purchase-requests/{id}/received`
   - Final step - marks PR as completed
   - Requires signature on file
   - Transitions PR status to "completed"

**Status Flow:** `draft` → `submitted` → `approved` → `completed`

### 3. **Purchase Order (PO) Approval Flow**

The PO now follows a similar 4-signature process:

1. **Created By (Purchasing Staff)** ✓
   - Automatically signs when creating the PO
   - Signature fetched from creator's profile

2. **Approved By (Admin)**
   - Route: `POST /purchase-orders/{id}/approve`
   - Admin approves the draft PO
   - Cannot self-approve
   - Requires signature on file

3. **Acknowledged By (Purchasing Staff)**
   - Route: `POST /purchase-orders/{id}/acknowledge`
   - Purchasing acknowledges the approved PO
   - Button visible only after approval
   - Requires signature on file

4. **Received By (Warehouse)**
   - Route: `POST /purchase-orders/{id}/receive`
   - When receiving goods
   - Warehouse staff signature auto-applied
   - Only if they have signature on file

**Status Flow:** `draft` → `approved` → `acknowledged` → `partial/received`

---

## Database Changes

### New User Field
- `signature_path` (nullable string) - Path to user's signature image in storage

### New PR Fields
- `acknowledged_by` (nullable foreign key) - User who acknowledged the PR
- `acknowledged_at` (nullable timestamp) - When PR was acknowledged
- `purchasing_received_by` (nullable foreign key) - Final receiver of PR
- `purchasing_received_at` (nullable timestamp) - When PR was received

### New PO Fields
- `approved_by` (nullable foreign key) - Admin who approved the PO
- `approved_at` (nullable timestamp) - When PO was approved
- `acknowledged_by` (nullable foreign key) - Purchasing staff who acknowledged
- `acknowledged_at` (nullable timestamp) - When PO was acknowledged
- `received_by` (nullable foreign key) - Who received the goods
- `received_at` (nullable timestamp) - When goods were received

---

## Migration Files Created

1. `2026_02_18_061718_add_signature_path_to_users_table.php`
2. `2026_02_18_061842_add_pr_approval_fields_to_purchase_requests_table.php`
3. `2026_02_18_061915_add_po_approval_fields_to_purchase_orders_table.php`

---

## Files Modified/Created

### Controllers
- `app/Http/Controllers/UserController.php` (NEW)
  - `profile()` - Show user profile & signature
  - `updateSignature()` - Handle signature upload
  - `getSignature()` - Retrieve signature image

- `app/Http/Controllers/PurchaseRequestController.php` (UPDATED)
  - `acknowledge()` - Acknowledge PR
  - `receivedByPurchasing()` - Mark PR as received

- `app/Http/Controllers/PurchaseOrderController.php` (UPDATED)
  - `approve()` - Admin approval for PO
  - `acknowledge()` - Purchasing acknowledgement for PO
  - Updated `receive()` to capture warehouse signature

### Views
- `resources/views/users/profile.blade.php` (NEW)
  - User profile with signature upload form

- `resources/views/purchase_requests/show.blade.php` (UPDATED)
  - Added signature approval section showing all 4 signatories
  - Added acknowledge & received buttons

- `resources/views/purchase_orders/show.blade.php` (UPDATED)
  - Added signature approval section showing all 4 signatories
  - Added approval & acknowledge buttons
  - Status updated for new statuses

- `resources/views/layouts/admin.blade.php` (UPDATED)
  - Made user name a link to profile page

### Models
- `app/Models/User.php` (UPDATED)
  - Added `signature_path` to fillable

- `app/Models/PurchaseRequest.php` (UPDATED)
  - Added relationships: `acknowledger()`, `purchasingReceiver()`
  - Added fillable fields for new columns

- `app/Models/PurchaseOrder.php` (UPDATED)
  - Added relationships: `approver()`, `acknowledger()`, `receiver()`
  - Added fillable fields for new columns

### Routes
- `routes/web.php` (UPDATED)
  - `GET /profile` → `users.profile`
  - `POST /profile/signature` → `users.updateSignature`
  - `GET /users/{user}/signature` → `users.signature`
  - `POST /purchase-requests/{id}/acknowledge` → `purchase_requests.acknowledge`
  - `POST /purchase-requests/{id}/received` → `purchase_requests.received`
  - `POST /purchase-orders/{id}/approve` → `purchase_orders.approve`
  - `POST /purchase-orders/{id}/acknowledge` → `purchase_orders.acknowledge`

---

## How to Use

### Step 1: Upload Your Signature
1. Click on your **name** in the top-right navbar
2. Go to **"Digital Signature"** section
3. Click **"Upload Signature Image"**
4. Select a PNG or JPG file (recommended: transparent PNG, 200x100px)
5. Click **"Upload Signature"**

### Step 2: Create a Purchase Request
1. Create a new PR as usual
2. Your signature is automatically signed on the PR as "Created By"

### Step 3: Admin Approves PR
1. Admin views the PR (Submit state)
2. Clicks **"Approve"** button
3. System checks if admin has signature on file
4. If yes: PR approved and signed by admin
5. If no: Redirected to profile to upload signature first
6. PR moves to "approved" status

### Step 4: Purchasing Staff Acknowledges
1. Purchasing views the approved PR
2. Clicks **"Acknowledge"** button
3. PR gets their signature and timestamp
4. New button appears: **"Mark as Received"**

### Step 5: Mark as Received (Final Step)
1. Same purchasing staff clicks **"Mark as Received"**
2. PR is signed by receiving user
3. PR status changes to "completed"
4. All 4 signatures are now visible on the PR

### Similar Flow for Purchase Orders
- Same 4-step process
- Warehouse staff signs during goods receipt
- PO shows approval timeline

---

## Security & Validation

✅ **Signature Validation**
- Users must have signature on file before approving/acknowledging documents
- If missing: users are redirected to profile to upload first

✅ **Self-Approval Prevention**
- Users cannot approve their own PRs/POs
- Error message shown if attempted

✅ **Role-Based Access**
- Admin: Approve documents
- Purchasing: Acknowledge & receive PRs
- Warehouse: Receive POs
- Staff: Create PRs

✅ **File Upload Validation**
- Only PNG/JPG accepted
- Max 5MB file size
- Stored securely in `storage/app/public/signatures/`
- Accessible via symlink at `public/storage/signatures/`

---

## Display in Views

All PR and PO show pages now display a **"Signature Approvals"** table showing:
- **Step name** (Created By, Approved By, Acknowledged By, Received By)
- **User name & timestamp**
- **Signature image** (or "Pending" / "-" if not done)

Signatures are displayed as small images (max 80px × 40px) in the approval table.

---

## Example Workflow

```
Day 1:
- Staff creates PR (auto-signed by staff)
- PR status: draft

Staff submits PR:
- PR status: submitted

Admin approves (has signature):
- PR signed by admin
- PR status: approved
- Purchasing can now acknowledge

Purchasing acknowledges (has signature):
- PR signed by purchasing
- PR status: approved (still)
- Mark as Received button appears

Purchasing marks as received (has signature):
- PR signed by purchasing receiver
- PR status: completed
```

---

## Testing Tips

1. **Create test users** with different roles
2. **Have each user upload a signature** via their profile
3. **Create a test PR** as staff
4. **Submit** it
5. **Approve** as admin
6. **Acknowledge & Receive** as purchasing
7. **View the PR** to see all 4 signatures with timestamps

---

## Next Steps (Optional Enhancements)

- Add PDF export with signature images
- Email notifications when awaiting action
- Signature drag-to-sign widget (instead of file upload)
- Signature history/audit trail
- Bulk approval/acknowledge actions
- Dashboard showing pending approvals by user

---

## Support

If any errors occur:
1. Ensure migrations ran successfully: `php artisan migrate:status`
2. Check storage link: `public/storage` should exist
3. Verify `storage/app/public/signatures/` directory exists
4. Check user role assignments in database

