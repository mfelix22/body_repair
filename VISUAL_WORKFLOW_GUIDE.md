# 📊 Digital Signature System - Visual Workflow Guide

## 1️⃣ User Profile Page Layout

```
┌─────────────────────────────────────────────────────────────┐
│                     USER PROFILE                             │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  LEFT COLUMN (Left 2/3)                                      │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ PROFILE INFORMATION                                  │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ Name:     John Doe                                   │   │
│  │ Username: john.doe                                   │   │
│  │ Email:    john@company.com                           │   │
│  │ Role:     [Staff]                                    │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ DIGITAL SIGNATURE                                    │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ Current Signature:                                   │   │
│  │ ┌────────────────────────┐                           │   │
│  │ │   [Signature Image]    │  ← Max 100px height      │   │
│  │ └────────────────────────┘                           │   │
│  │                                                       │   │
│  │ Upload New Signature:                                │   │
│  │ [Choose File] [PNG/JPG, Max 5MB]                     │   │
│  │ [Upload Signature Button]                            │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
│  RIGHT COLUMN (Right 1/3)                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ HELP                                                 │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ • Use transparent PNG                                │   │
│  │ • Recommended: 200×100px                             │   │
│  │ • Max file: 5MB                                      │   │
│  │ • Updated automatically when used                    │   │
│  │ • Visible on all PRs and POs                         │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 2️⃣ Purchase Request (PR) Show Page - Signature Section

```
┌─────────────────────────────────────────────────────────────┐
│ SIGNATURE APPROVALS                                          │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│ Step              │ User           │ Signature             │
│ ─────────────────┼────────────────┼──────────────────────│
│ Created By        │ John Doe       │ [Image]              │
│                   │                │                      │
│ ─────────────────┼────────────────┼──────────────────────│
│ Approved By       │ Sarah Johnson  │ [Image]              │
│                   │ 2026-02-18     │ ✓ 10:00 AM           │
│                   │ 10:00 AM       │                      │
│ ─────────────────┼────────────────┼──────────────────────│
│ Acknowledged By   │ Mike Chen      │ [Image]              │
│                   │ 2026-02-18     │ ✓ 10:30 AM           │
│                   │ 10:30 AM       │                      │
│ ─────────────────┼────────────────┼──────────────────────│
│ Purchasing Rcvd   │ Mike Chen      │ [Image]              │
│                   │ 2026-02-18     │ ✓ 11:00 AM           │
│                   │ 11:00 AM       │                      │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 3️⃣ Purchase Request Workflow - Status & Actions

```
STEP 1: DRAFT → SUBMITTED
┌────────────────────────────────────────┐
│ Status: [draft]                        │
│ Actions (Staff):                       │
│   [Edit] [Submit]                      │
└────────────────────────────────────────┘
         ↓ (Staff clicks Submit)
         
STEP 2: SUBMITTED → APPROVED
┌────────────────────────────────────────┐
│ Status: [submitted]                    │
│ Actions (Admin):                       │
│   [✓ Approve] [✗ Reject]               │
│   ↳ Checks: Admin has signature?      │
│     YES → Auto-signs PR               │
│     NO  → "Upload signature first"    │
└────────────────────────────────────────┘
         ↓ (Admin clicks Approve)
         ↓ (Admin's signature auto-applied)
         
STEP 3: APPROVED → ACKNOWLEDGED
┌────────────────────────────────────────┐
│ Status: [approved]                     │
│ Actions (Purchasing):                  │
│   [👍 Acknowledge] [Create PO]         │
│   ↳ Checks: Purchasing has sig?       │
│     YES → Auto-signs PR               │
│     NO  → "Upload signature first"    │
└────────────────────────────────────────┘
         ↓ (Purchasing clicks Acknowledge)
         ↓ (Purchasing's signature auto-applied)
         
STEP 4: APPROVED → COMPLETED
┌────────────────────────────────────────┐
│ Status: [approved] (still)             │
│ Actions (Purchasing):                  │
│   [📋 Mark as Received]                │
│   ↳ Checks: Purchasing has sig?       │
│     YES → Auto-signs PR               │
│     NO  → "Upload signature first"    │
└────────────────────────────────────────┘
         ↓ (Purchasing clicks Mark as Received)
         ↓ (Purchasing's signature auto-applied)
         ↓
FINAL: COMPLETED ✅
┌────────────────────────────────────────┐
│ Status: [completed] ✅                 │
│ All 4 signatures visible               │
│ No more actions needed                 │
└────────────────────────────────────────┘
```

---

## 4️⃣ Purchase Order Workflow - Status & Actions

```
STEP 1: DRAFT → APPROVED
┌────────────────────────────────────────┐
│ Status: [draft]                        │
│ Actions (Admin):                       │
│   [✓ Approve]                          │
│   ↳ Checks: Admin has signature?      │
│     YES → Auto-signs PO               │
│     NO  → "Upload signature first"    │
└────────────────────────────────────────┘
         ↓ (Admin clicks Approve)
         ↓ (Admin's signature auto-applied)
         
STEP 2: APPROVED → ACKNOWLEDGED
┌────────────────────────────────────────┐
│ Status: [approved]                     │
│ Actions (Purchasing):                  │
│   [👍 Acknowledge] [Receive Goods]     │
│   ↳ Checks: Purchasing has sig?       │
│     YES → Auto-signs PO               │
│     NO  → "Upload signature first"    │
└────────────────────────────────────────┘
         ↓ (Purchasing clicks Acknowledge)
         ↓ (Purchasing's signature auto-applied)
         
STEP 3: KNOWN → PARTIAL/RECEIVED
┌────────────────────────────────────────┐
│ Status: [acknowledged]                 │
│ Actions (Warehouse):                   │
│   [📦 Receive Goods]                   │
│   ↳ Receives goods from supplier      │
│   ↳ Checks: Warehouse has signature?  │
│     YES → Auto-signs PO               │
│     NO  → Still recorded              │
└────────────────────────────────────────┘
         ↓ (Warehouse Click Receive & records qty)
         ↓ (Warehouse's signature auto-applied)
         ↓
FINAL: RECEIVED ✅ (or PARTIAL if qty < ordered)
┌────────────────────────────────────────┐
│ Status: [received/partial] ✅          │
│ All 4 signatures visible               │
│ Goods recorded in inventory            │
└────────────────────────────────────────┘
```

---

## 5️⃣ User Action Decision Tree

```
                    START
                      ↓
        "I need to APPROVE"
                      ↓
        ┌─────────────┴─────────────┐
        ↓                           ↓
    Uploaded?               Your own doc?
     ↙   ↖                    ↙   ↖
    YES   NO                YES   NO
     ↓     ↓                ↓     ↓
   OK  UPLOAD!            ✗    ✓
        (Redirect         NOT   OK
         to profile)   ALLOWED (Click button)
                                  ↓
                          Signature
                          auto-applied
                          ✅


                    START
                      ↓
        "I need to ACKNOWLEDGE"
                      ↓
        ┌─────────────┴─────────────┐
        ↓                           ↓
    Doc approved?          Uploaded signature?
     ↙   ↖                    ↙   ↖
    YES   NO                YES   NO
     ↓     ↓                ↓     ↓
    ✓     ✗               OK  UPLOAD!
    OK  WAIT                     (Redirect
  (Click      (More            to profile)
   button)   steps needed)
     ↓
  Signature
  auto-applied
  ✅
```

---

## 6️⃣ Signature Display in Documents

### PR Show Page - Top Buttons Area:
```
┌─────────────────────────────────────────────┐
│ PR-2026-0001                                │
│                                             │
│ Status: [approved]                          │
│ [👍 Acknowledge]  [✓ Mark as Received]     │
│                                             │
└─────────────────────────────────────────────┘
```

### PR Show Page - Signature Table:
```
┌────────────────────────────────────────────────────────────┐
│ SIGNATURE APPROVALS                                        │
├────────────────────────────────────────────────────────────┤
│                                                             │
│ Created By                                                  │
│ John Doe                    [Signature Image 80×40px]     │
│                                                             │
│ Approved By                                                 │
│ Sarah Johnson               [Signature Image 80×40px]     │
│ Feb 18, 2026 10:00 AM                                     │
│                                                             │
│ Acknowledged By                                             │
│ Mike Chen                   [Signature Image 80×40px]     │
│ Feb 18, 2026 10:30 AM                                     │
│                                                             │
│ Purchasing Received                                         │
│ Mike Chen                   [Signature Image 80×40px]     │
│ Feb 18, 2026 11:00 AM                                     │
│                                                             │
└────────────────────────────────────────────────────────────┘
```

---

## 7️⃣ Error Handling Flow

```
User clicks "Approve"
        ↓
  Check: Has signature?
    ↙   ↖
   NO    YES
    ↓     ↓
 ERROR   OK
    ↓     ↓
 ALERT  Check: Self-approval?
 "Upload ↙   ↖
 signature"  YES  NO
 REDIRECT    ↓     ↓
   to     ERROR   OK
profile   ↓       ↓
         ALERT  Check:
         "Can't  Status
         self-   OK?
         approve" ↙   ↖
                 YES   NO
                  ↓     ↓
                 OK   ERROR
                  ↓     ↓
                SIGN ALERT
                 ✅  "Wrong
                    status"
```

---

## 8️⃣ Signature Image Storage

```
User's Computer          Server                  Web Access
     ↓                     ↓                         ↓
┌──────────┐         ┌──────────────┐         ┌──────────────┐
│signature │ upload  │ storage/app/ │    via  │   public/    │
│.png file │────────→│  public/     │────────→│  storage/    │
└──────────┘  POST   │  signatures/ │ symlink │  signatures/ │
             /profile │  abc123.png  │         │  abc123.png  │
                      └──────────────┘         └──────────────┘
                            ↓
                       Stored securely
                       (not web root)
                            ↓
                       <img src="/storage
                       /signatures/abc123.png">
```

---

## 9️⃣ Complete Timeline Example

```
Timeline: John's PR Journey

2026-02-18, 2:00 PM
├─ John (Staff) creates PR-2026-0001
├─ John's signature auto-added as "Created By"
├─ Status: draft
└─ PR shows: 1 signature (John)

2026-02-18, 3:00 PM
├─ John clicks "Submit"
├─ No signature added
└─ Status: submitted

2026-02-18, 10:00 AM (Next day)
├─ Sarah (Admin) views PR
├─ Sarah clicks "Approve"
├─ Sarah's signature auto-added as "Approved By"
├─ Status: approved
└─ PR shows: 2 signatures (John, Sarah)

2026-02-18, 10:30 AM
├─ Mike (Purchasing) views PR
├─ Mike clicks "Acknowledge"
├─ Mike's signature auto-added as "Acknowledged By"
├─ Status: approved (unchanged)
└─ PR shows: 3 signatures (John, Sarah, Mike)

2026-02-18, 11:00 AM
├─ Mike clicks "Mark as Received"
├─ Mike's signature auto-added as "Purchasing Received"
├─ Status: completed ✅
└─ PR shows: 4 signatures (John, Sarah, Mike, Mike)

FINAL STATE:
┌─────────────────────────────────────────┐
│ PR-2026-0001                            │
│ Status: COMPLETED ✅                   │
│                                         │
│ Created By:          John (2:00 PM)     │
│ Approved By:         Sarah (10:00 AM)   │
│ Acknowledged By:     Mike (10:30 AM)    │
│ Purchasing Received: Mike (11:00 AM)    │
│                                         │
│ All signatures visible with images      │
│ Complete audit trail maintained         │
└─────────────────────────────────────────┘
```

---

## 🔟 Button Visibility Matrix

```
Document Status: DRAFT
┌─────────────────────┬────────┬───────────┬──────────┐
│ User Role           │ Edit   │ Submit    │ Approve  │
├─────────────────────┼────────┼───────────┼──────────┤
│ Staff (creator)     │ ✓      │ ✓         │ ✗        │
│ Staff (other)       │ ✗      │ ✗         │ ✗        │
│ Admin               │ ✗      │ ✗         │ ✗        │
│ Purchasing          │ ✗      │ ✗         │ ✗        │
│ Warehouse           │ ✗      │ ✗         │ ✗        │
└─────────────────────┴────────┴───────────┴──────────┘

Document Status: SUBMITTED
┌─────────────────────┬─────────┬──────────┐
│ User Role           │ Approve │ Reject   │
├─────────────────────┼─────────┼──────────┤
│ Staff (creator)     │ ✗       │ ✗        │
│ Staff (other)       │ ✗       │ ✗        │
│ Admin (not creator) │ ✓       │ ✓        │
│ Purchasing          │ ✗       │ ✗        │
│ Warehouse           │ ✗       │ ✗        │
└─────────────────────┴─────────┴──────────┘

Document Status: APPROVED
┌──────────────────────┬───────────────┬──────────┐
│ User Role            │ Acknowledge   │ Create PO│
├──────────────────────┼───────────────┼──────────┤
│ Any Staff            │ ✗             │ ✗        │
│ Admin                │ ✗             │ ✗        │
│ Purchasing (any)     │ ✓             │ ✓        │
│ Warehouse            │ ✗             │ ✗        │
└──────────────────────┴───────────────┴──────────┘
```

---

This visual guide shows the complete flow of the digital signature system from the user perspective.

