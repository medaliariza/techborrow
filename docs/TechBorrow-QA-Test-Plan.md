# TechBorrow Software Test Plan and QA Suite

**Version:** 1.0
**Project:** TechBorrow - Technical Equipment Borrowing and Inventory System
**Date prepared:** 2026-11-20
**Execution status:** Sample suite prepared; all cases are **Not Run**. This document does not claim that tests have passed.

## 1. Test plan

### Objective

Validate the core user journeys and role boundaries for account access, equipment discovery, borrowing requests, review, release, returns, announcements, maintenance, and reports.

### Scope

- Functional testing: browser forms, validation, account status, inventory and borrowing state transitions.
- Integration testing: PHP handlers, session and role checks, MySQL records, notifications, and inventory quantities.
- User acceptance testing: representative student, instructor/teacher, and administrator tasks.
- Android/PWA smoke checks: installer launch, network-backed sign-in, and small-screen use. Device-specific execution is not assumed.

### Test environment and data

- PHP 8.2+, Apache, MySQL/MariaDB, and an isolated TechBorrow test database.
- Import `database/techborrow_db.sql`. Optional accounts are in `docs/demo-accounts.sql`.
- Use seeded or disposable equipment with known quantities. Use future dates for new requests and a valid current/past date for return tests.
- Student photo tests require a consented, synthetic/test JPEG, PNG, or WebP under 5 MB.
- Do not run state-changing tests on production data. Record the browser/device, database fixture IDs, tester, and date with each execution.

### Entry and exit criteria

- Entry: application and database load; tester can access the local site; sample accounts and fixture equipment exist.
- Exit: all 30 cases have an actual result and evidence or a blocker; defects are logged with severity and reproduction steps; critical workflows are rerun after fixes.
- Expected results below are test oracles. Set each case to Pass, Fail, or Blocked only after execution.

## 2. Sample test cases

### TC-01 - Sign in with active account
- Type: Functional
- Priority: High
- Preconditions: Active instructor test account exists.
- Steps: Open sign-in; enter the active email and correct password; submit.
- Expected: User is signed in and routed to the role-appropriate dashboard.
- Status: Not Run

### TC-02 - Reject invalid sign-in credentials
- Type: Functional
- Priority: High
- Preconditions: Sign-in page is available.
- Steps: Submit a known email with an incorrect password.
- Expected: Sign-in is denied and a clear error is shown; no authenticated session is created.
- Status: Not Run

### TC-03 - Block inactive account sign-in
- Type: Integration
- Priority: High
- Preconditions: An account has `account_status=inactive`.
- Steps: Submit that account's correct email and password.
- Expected: Sign-in is denied with the inactive/invalid account message.
- Status: Not Run

### TC-04 - Register an instructor account
- Type: Functional
- Priority: Medium
- Preconditions: Use an email not already in the test database.
- Steps: Open registration; enter valid required values and matching password confirmation; submit.
- Expected: Account is created with instructor role and can sign in.
- Status: Not Run

### TC-05 - Browse and search equipment
- Type: Functional
- Priority: Medium
- Preconditions: At least two equipment records with distinguishable names/codes exist.
- Steps: Sign in as instructor; open equipment; search for one known name or code.
- Expected: Matching equipment is shown and unrelated records are excluded.
- Status: Not Run

### TC-06 - View equipment details
- Type: Functional
- Priority: Medium
- Preconditions: An equipment record exists.
- Steps: Open its details from the equipment list.
- Expected: Name, identifying information, condition, availability, and any image are displayed consistently with the record.
- Status: Not Run

### TC-07 - Submit a valid instructor request
- Type: Functional
- Priority: High
- Preconditions: Sign in as instructor; selected item has sufficient date-range availability.
- Steps: Enter purpose, room, future start/due times, equipment, and positive quantity; submit.
- Expected: Request and request items are saved as pending; confirmation appears in the request list.
- Status: Not Run

### TC-08 - Require fields in instructor request
- Type: Functional
- Priority: Medium
- Preconditions: Sign in as instructor and open request form.
- Steps: Submit with a required field or all equipment selections omitted.
- Expected: Request is not saved and form shows a validation message.
- Status: Not Run

### TC-09 - Reject reversed request dates
- Type: Functional
- Priority: High
- Preconditions: Sign in as instructor.
- Steps: Set due time earlier than or equal to start time and submit an otherwise valid request.
- Expected: Request is not saved and user is told start must precede due.
- Status: Not Run

### TC-10 - Enforce requested quantity availability
- Type: Integration
- Priority: High
- Preconditions: Fixture equipment has a known available quantity for a selected period.
- Steps: Request one more than that quantity.
- Expected: Request is rejected; no pending request or inventory decrement is committed.
- Status: Not Run

### TC-11 - Submit public student request with valid photo
- Type: Functional
- Priority: High
- Preconditions: Public request page is available; fixture equipment available; consented synthetic image under 5 MB.
- Steps: Complete student details, dates, equipment/quantity, select photo, consent, and submit.
- Expected: Student record (when new), pending request, item, and private photo file are recorded; confirmation is shown.
- Status: Not Run

### TC-12 - Require student photo consent
- Type: Functional
- Priority: High
- Preconditions: Public request page is available.
- Steps: Complete all valid fields and attach a test photo but leave consent unchecked.
- Expected: Submission is rejected with a consent validation message and no request is saved.
- Status: Not Run

### TC-13 - Reject unsupported or oversized student photo
- Type: Functional
- Priority: High
- Preconditions: Public request form and test fixture files are available.
- Steps: Submit otherwise valid data with a non-image file or image larger than 5 MB.
- Expected: Upload is rejected and no request is saved.
- Status: Not Run

### TC-14 - Prevent duplicate student identity
- Type: Integration
- Priority: High
- Preconditions: A student account is already registered with a student number/email pair.
- Steps: Submit a public request reusing only one of those identifiers with conflicting details.
- Expected: Request is rejected with a conflict message; no duplicate account/request is committed.
- Status: Not Run

### TC-15 - Create and list a category
- Type: Functional
- Priority: Medium
- Preconditions: Sign in as admin.
- Steps: Add a uniquely named category; return to categories list.
- Expected: Category is stored and displayed once.
- Status: Not Run

### TC-16 - Create equipment with image
- Type: Integration
- Priority: High
- Preconditions: Sign in as admin; category exists; valid small image fixture available.
- Steps: Create equipment with unique code, name, category, quantity, condition, and image.
- Expected: Record and image are saved; quantity and status appear correctly in inventory.
- Status: Not Run

### TC-17 - Update and delete equipment
- Type: Functional
- Priority: Medium
- Preconditions: A disposable equipment fixture exists and is not referenced by active borrowing.
- Steps: Edit a description/condition; save; then delete the disposable fixture.
- Expected: Edit persists and deletion is reflected; unrelated inventory remains unchanged.
- Status: Not Run

### TC-18 - Create and update maintenance record
- Type: Integration
- Priority: High
- Preconditions: Sign in as admin; equipment fixture exists.
- Steps: Report an issue; assign it and change status to in progress; save each change.
- Expected: Maintenance record and selected status/assignee persist and are visible.
- Status: Not Run

### TC-19 - Publish and edit announcement
- Type: Functional
- Priority: Medium
- Preconditions: Sign in as admin.
- Steps: Create an announcement; verify it is visible; edit its text and save.
- Expected: Announcement is displayed with updated text and no duplicate is created.
- Status: Not Run

### TC-20 - Review a pending request as approved
- Type: Integration
- Priority: High
- Preconditions: Sign in as admin; pending request and sufficient inventory exist.
- Steps: Approve the request in the review page.
- Expected: Status becomes approved, quantities are approved, review/audit record is saved, and borrower is notified.
- Status: Not Run

### TC-21 - Review a pending request as unavailable
- Type: Integration
- Priority: High
- Preconditions: Sign in as admin; a separate pending request exists.
- Steps: Reject/unavailable the request and enter a reason.
- Expected: Status becomes unavailable; review and notification include the decision/reason.
- Status: Not Run

### TC-22 - Prevent a second review decision
- Type: Integration
- Priority: High
- Preconditions: A request has already been approved or marked unavailable.
- Steps: Attempt to submit another review decision for the same request.
- Expected: Second decision is rejected; original decision and inventory remain unchanged.
- Status: Not Run

### TC-23 - Release approved equipment
- Type: Integration
- Priority: High
- Preconditions: Sign in as admin; approved request and sufficient equipment are available.
- Steps: Release the approved request.
- Expected: Borrow transaction/items are created; request becomes borrowed; available quantity decreases once; borrower is notified.
- Status: Not Run

### TC-24 - Block release of unapproved request
- Type: Functional
- Priority: High
- Preconditions: A pending or unavailable request exists.
- Steps: Attempt to release it.
- Expected: Release is refused; no transaction is created and inventory is unchanged.
- Status: Not Run

### TC-25 - Record good return
- Type: Integration
- Priority: High
- Preconditions: Sign in as admin; borrowed transaction with outstanding items exists.
- Steps: Record a valid non-future return time and condition `good`.
- Expected: Return record/items are saved, transaction/request become returned, availability is restored up to total, and borrower is notified.
- Status: Not Run

### TC-26 - Flag damaged return for maintenance
- Type: Integration
- Priority: High
- Preconditions: Separate borrowed fixture exists with known inventory counts.
- Steps: Record return condition `damaged`.
- Expected: Return is recorded and transaction closed; equipment is flagged maintenance and is not returned to available stock.
- Status: Not Run

### TC-27 - Reject future return time
- Type: Functional
- Priority: Medium
- Preconditions: A borrowed transaction exists.
- Steps: Submit return datetime later than current time.
- Expected: Validation rejects the date and no return or inventory change is saved.
- Status: Not Run

### TC-28 - Enforce role access to admin action
- Type: Integration
- Priority: High
- Preconditions: Sign in as instructor; know a disposable request ID.
- Steps: POST a review request to the admin review handler.
- Expected: Instructor is not authorized; request status and review tables remain unchanged.
- Status: Not Run

### TC-29 - Prevent suspended account access
- Type: Integration
- Priority: High
- Preconditions: Set a test account to suspended.
- Steps: Sign out and attempt sign-in with its correct credentials.
- Expected: Sign-in is denied; no authenticated session is granted.
- Status: Not Run

### TC-30 - Verify report and notification flow
- Type: UAT / Integration
- Priority: Medium
- Preconditions: Test database contains borrowing, return, and notification fixtures.
- Steps: Sign in as admin and open reports; sign in as borrower and review dashboard notifications.
- Expected: Report counts/data agree with fixtures; borrower sees relevant notifications and can mark one read.
- Status: Not Run

## 3. User acceptance scripts

### Student

1. Open the public student request page.
2. Select available equipment and enter student details, purpose, room, and a valid future borrowing period.
3. Attach a consented synthetic portrait, acknowledge consent, and submit.
4. Confirm the request number/status message and ask an admin to locate the pending request.
5. Accept when the required fields, validation, and confirmation are understandable.

### Instructor / teacher

1. Sign in with an instructor account and find an item using search.
2. Create a request for available equipment for a valid date/time.
3. Confirm the request appears as pending and that a deliberately invalid date/quantity is blocked.
4. After admin approval and release, confirm the borrowed item/status is visible.
5. Accept when the workflow communicates request versus approval versus actual release clearly.

### Administrator

1. Sign in and create a disposable category/equipment fixture.
2. Review one pending request, approve it, then release it.
3. Record a good return and confirm quantity restoration; use a separate fixture for a damaged return and verify maintenance status.
4. Review announcements, maintenance, users, and reports.
5. Accept when inventory and request states remain consistent and each action has clear feedback.

## 4. Initial bug tracking log

No execution findings are recorded yet because the sample cases have not been run. This is not a claim that the application has no defects. Add one row per observed issue and attach screenshots/logs without exposing real student data.

| Bug ID | Date / tester | Environment | Related case | Steps and actual result | Expected result | Severity | Status / retest |
|---|---|---|---|---|---|---|---|
| TBD | Fill after execution | Fill after execution | TC-xx | Not yet executed | See case oracle | TBD | Not Run |

Suggested severity: Critical (core flow unavailable/data loss), High (important workflow blocked), Medium (workaround exists), Low (cosmetic/minor).
