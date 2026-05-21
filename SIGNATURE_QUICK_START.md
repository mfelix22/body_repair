# Digital Signature System - Quick Start Guide

## 🎯 What You Now Have

### **Online Digital Signatures** for:
- ✅ Purchase Requests (PR) - 4 signatures
- ✅ Purchase Orders (PO) - 4 signatures

Instead of manually writing signatures, users upload them once and documents are automatically signed during approvals.

---

## 📋 The 4-Signature Process

### **For Purchase Requests:**

```
┌─────────────────────────────────────────────────────┐
│ 1. CREATED BY (Auto-Signed)                          │
│    ✓ Staff creates PR                               │
│    ✓ Their signature auto-applied                   │
│    Status: draft                                     │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 2. SUBMITTED BY STAFF                                │
│    Status: submitted                                 │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 3. APPROVED BY (Admin Signs)                        │
│    ✓ Admin clicks "Approve"                         │
│    ✓ Admin's signature auto-applied                 │
│    Status: approved                                 │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 4. ACKNOWLEDGED BY (Purchasing Signs)               │
│    ✓ Purchasing clicks "Acknowledge"                │
│    ✓ Their signature auto-applied                   │
│    Status: approved (same)                          │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 5. PURCHASING RECEIVED (Final Sign)                 │
│    ✓ Purchasing clicks "Mark as Received"           │
│    ✓ Their signature auto-applied                   │
│    Status: completed ✅                             │
└─────────────────────────────────────────────────────┘
```

### **For Purchase Orders:**

```
┌─────────────────────────────────────────────────────┐
│ 1. CREATED BY (Auto-Signed)                          │
│    ✓ Purchasing creates PO                          │
│    ✓ Their signature auto-applied                   │
│    Status: draft                                    │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 2. APPROVED BY (Admin Signs)                        │
│    ✓ Admin clicks "Approve"                         │
│    ✓ Admin's signature auto-applied                 │
│    Status: approved                                 │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 3. ACKNOWLEDGED BY (Purchasing Signs)               │
│    ✓ Purchasing clicks "Acknowledge"                │
│    ✓ Their signature auto-applied                   │
│    Status: acknowledged                             │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 4. RECEIVED BY (Warehouse Signs)                    │
│    ✓ Warehouse receives goods                       │
│    ✓ Their signature auto-applied                   │
│    Status: received ✅                              │
└─────────────────────────────────────────────────────┘
```

---

## 🚀 Getting Started - First Time Setup

### **Step 1: Upload Your Signature**
1. Click on **your name** in the top-right corner
2. Scroll to **"Digital Signature"** section
3. Click **"Select Image"** and choose PNG or JPG file
4. Recommended specs:
   - Format: PNG (transparent background recommended)
   - Size: 200 x 100 pixels
   - Max file: 5 MB
5. Click **"Upload Signature"**
6. See your signature preview
7. ✅ Done! You're ready to approve documents

### **Step 2: Create a Document**
- Create a new PR or PO as normal
- Your signature is **automatically applied** as "Created By"

### **Step 3: Approve/Acknowledge**
- When someone needs to approve: they click the blue button
- System checks: "Does this user have a signature?"
  - **YES** → Auto-sign the document
  - **NO** → Redirect to profile to upload first
- After approval: Next step button appears

### **Step 4: View All Signatures**
- Open any PR or PO
- See the **"Signature Approvals"** section
- Shows all 4 signatures with timestamps
- Shows "Pending" if step not done yet

---

## 👥 Who Does What

| Role | Can Do |
|------|--------|
| **Staff** | Create PR, see their signature auto-applied when creating |
| **Admin** | Approve submitted PRs (their signature auto-applied) |
| **Purchasing** | Acknowledge PRs, receive PRs, create POs, acknowledge POs |
| **Warehouse** | Receive goods on POs (their signature auto-applied) |

---

## 🔐 Important Rules

### ✋ **Cannot Self-Approve**
- You cannot approve your own PR/PO
- Error message: "You cannot approve your own Purchase Request"

### 📝 **Must Have Signature First**
- Before you approve/acknowledge: you must upload signature
- If missing: System redirects you to profile page
- Error message: "Please upload your signature before approving"

### 🔍 **Signature Validation**
- Only PNG and JPG files accepted
- Max 5MB per file
- Stored securely on server
- Accessible only to authenticated users

---

## 📊 Example: Complete PR Workflow

### **Day 1 - 2:00 PM**
```
John (Staff) creates PR-2026-0001
Status: draft
Signatures: Created By = John ✓
```

### **Day 1 - 3:00 PM**
```
John submits PR-2026-0001
Status: submitted
Waiting for: Admin approval
```

### **Day 2 - 10:00 AM**
```
Sarah (Admin) approves PR-2026-0001
Status: approved
Signatures: 
  - Created By = John ✓
  - Approved By = Sarah ✓
Waiting for: Purchasing acknowledgement
```

### **Day 2 - 10:30 AM**
```
Mike (Purchasing) acknowledges PR-2026-0001
Status: approved (waiting for final receipt)
Signatures: 
  - Created By = John ✓
  - Approved By = Sarah ✓
  - Acknowledged By = Mike ✓
Waiting for: Purchasing received confirmation
```

### **Day 2 - 11:00 AM**
```
Mike marks PR-2026-0001 as received
Status: completed ✅
Signatures: 
  - Created By = John ✓ (2:00 PM Day 1)
  - Approved By = Sarah ✓ (10:00 AM Day 2)
  - Acknowledged By = Mike ✓ (10:30 AM Day 2)
  - Purchasing Received By = Mike ✓ (11:00 AM Day 2)
```

---

## ⚠️ Troubleshooting

| Problem | Solution |
|---------|----------|
| "No signature uploaded" | Click your name → Upload signature → Go back |
| Can't see approve button | Check PR status is "submitted" and you're admin |
| Signature image not showing | Contact admin, upload PNG instead of JPG |
| Can't upload file | File must be PNG/JPG and under 5MB |
| Can't approve own document | This is intentional - prevent self-approval |

---

## 📱 Browser Compatibility

Works in:
- ✅ Chrome / Chromium
- ✅ Firefox
- ✅ Safari
- ✅ Edge

Signature images stored in: `storage/app/public/signatures/`
Accessible via: `https://yoursite.com/storage/signatures/filename.png`

---

## 💡 Pro Tips

1. **Use PNG images** with transparent background for best appearance
2. **Keep signature size small** (200x100px) - loads faster
3. **Update signature** anytime from your profile
4. **Check "Signature Approvals" table** to track workflow
5. **Print documents** - signatures will show in printed view

---

## 🎓 Key Differences from Old System

| Old System | New System |
|-----------|-----------|
| Manual signature writing | Automatic digital signature |
| Signature added per approval | Signature uploaded once, reused always |
| Manual date/time entry | Automatic timestamp recorded |
| No signature history | All signatures timestamped |
| Paper-based signatures | Digital + audit trail |

---

## 📞 Questions?

Check the full documentation:
- File: `DIGITAL_SIGNATURE_IMPLEMENTATION.md`

Or contact your system administrator for help with:
- Signature upload issues
- Document approval workflows
- User role assignments

