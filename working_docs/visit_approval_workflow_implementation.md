# Visit Approval Workflow Implementation

## Overview
This document describes the implementation of a live notification system for visit approval with RFID generation and 5-minute timeout functionality.

## Features Implemented

### 1. Live Notification System
- When a visit is submitted by any user with permission, a live notification is sent to the host
- Dashboard displays real-time pending visit requests
- Auto-refresh every 10 seconds for live updates
- Badge count on dashboard showing pending requests

### 2. 5-Minute Approval Timeout
- Each visit request has a 5-minute (300 seconds) approval window
- Real-time countdown timer displayed on the approval dashboard
- Automatic expiration after 5 minutes if not approved/rejected
- Visual warning when less than 2 minutes remain (pulsing timer)

### 3. Visit Approval Workflow
- **Approve**: Requires entering an RFID tag which is stored in the database
- **Reject**: Requires entering a rejection reason which is recorded
- Both actions trigger email and SMS notifications to the visitor

### 4. RFID Generation
- RFID tag is generated at the time of visit approval
- RFID is linked to the specific visit in the database
- Tracks who generated the RFID and when
- Displayed on the live dashboard for approved visits

### 5. Live Dashboard
- Shows all approved visits with their RFID tags
- Displays visitor details, host, purpose, and schedule time
- Shows who generated the RFID and when
- Auto-refreshes every 10 seconds

## Database Changes

### Migration: 2026_01_23_120000_add_visit_id_to_rfids_table.php
Added to the `rfids` table:
- `visit_id` (foreign key to visits table)
- `generated_by` (foreign key to users table)

### Model Updates

#### Rfid Model
- Added `visit_id` and `generated_by` to fillable attributes
- Added relationship: `visit()` - belongs to Visit
- Added relationship: `generatedBy()` - belongs to User

#### Visit Model
- Added relationship: `rfid()` - has one Rfid

## Routes Added

### Visit Approval Routes (Protected with `can:edit visitors` permission)
- `GET /visit-approval` - Visit approval dashboard
- `GET /api/visit-approval/pending` - Get pending visits with timeout info
- `GET /api/visit-approval/live-visits` - Get live visits with RFID
- `GET /api/visit-approval/{id}` - Get visit details
- `POST /api/visit-approval/{id}/approve` - Approve visit and generate RFID
- `POST /api/visit-approval/{id}/reject` - Reject visit with reason

## New Controller: VisitApprovalController

### Methods:

1. **pendingVisits()**
   - Returns all pending visits with time remaining
   - Calculates timeout for each visit
   - Includes visitor, type, and host relationships

2. **liveVisitsWithRfid()**
   - Returns approved and completed visits with RFID info
   - Includes RFID tag, generation time, and generator

3. **approveVisit(Request $request, $id)**
   - Validates RFID tag uniqueness
   - Checks 5-minute timeout
   - Updates visit status to 'approved'
   - Generates RFID record
   - Sends approval notifications (email + SMS)

4. **rejectVisit(Request $request, $id)**
   - Validates rejection reason
   - Updates visit status to 'rejected'
   - Stores rejection reason
   - Sends rejection notifications (email + SMS)

5. **getVisitDetails($id)**
   - Returns complete visit information
   - Includes time remaining for pending visits
   - Shows rejection reason if rejected

## Views Created

### 1. Visit Approval Dashboard (`visit-approval.blade.php`)
Features:
- Real-time stats (pending, approved, RFIDs, live visits)
- Pending visits table with countdown timers
- Live visits table with RFID display
- Auto-refresh every 10 seconds
- Approve/Reject buttons with modals
- View details functionality

### 2. Dashboard Updates (`dashboard.blade.php`)
Added:
- "Visit Approval" button with pending count badge
- Real-time pending visits counter (refreshes every 30s)
- Pulsing animation when pending requests exist

## Access and Permissions

### Required Permissions
Users must have the `edit visitors` permission to:
- View the visit approval dashboard
- Approve visit requests
- Reject visit requests
- Generate RFID tags

### Access URL
- Dashboard: `/dashboard`
- Visit Approval: `/visit-approval`

## Workflow Steps

### 1. Visit Submission
When a user submits a visit:
1. Visit is created with `status = 'pending'`
2. Email and SMS notifications sent to visitor
3. Visit appears in pending visits list
4. 5-minute countdown timer starts

### 2. Host Approval
Host (user with `edit visitors` permission) can:
1. View pending visits on dashboard
2. See real-time countdown timer
3. Click "Approve" button
4. Enter unique RFID tag
5. System validates RFID uniqueness
6. Updates visit status to 'approved'
7. Generates RFID record
8. Sends approval notification with RFID to visitor

### 3. Host Rejection
Host can:
1. Click "Reject" button
2. Enter rejection reason
3. System updates visit status to 'rejected'
4. Stores rejection reason
5. Sends rejection notification to visitor

### 4. Automatic Expiration
If 5 minutes elapse:
1. Visit automatically marked as 'rejected'
2. Rejection reason: "Visit request timed out (exceeded 5 minutes)"
3. No further approval/rejection possible

## Live Dashboard Features

### Real-Time Updates
- Auto-refreshes every 10 seconds
- Shows live visit count
- Displays approved visits with RFID tags
- Shows who generated each RFID

### Displayed Information
- Visitor name and email
- Host name
- Visit purpose
- Schedule time
- RFID tag (formatted for readability)
- RFID generator name and time
- Visit status

## Notifications

### Approval Notification (Email + SMS)
Email includes:
- Visitor name
- Visit details (date, time, type, purpose)
- Host name
- RFID tag
- Approval status

SMS format:
```
Dear [Visitor Name], Your visit to UCB Bank has been APPROVED for [Date/Time]. 
Your RFID Tag: [RFID Tag]. Host: [Host Name]. Thank you!
```

### Rejection Notification (Email + SMS)
Email includes:
- Visitor name
- Visit details
- Rejection reason
- Host name

SMS format:
```
Dear [Visitor Name], Your visit to UCB Bank has been REJECTED. 
Reason: [Rejection Reason]. Thank you!
```

## Testing the Implementation

### Prerequisites
1. User with `edit visitors` permission
2. At least one visitor registered
3. Database migrated successfully

### Test Scenarios

#### Scenario 1: Submit and Approve Visit
1. Login as user with create permission
2. Submit a new visit request
3. Logout and login as user with edit permission
4. Navigate to `/dashboard` or `/visit-approval`
5. Verify pending visit appears with countdown timer
6. Click "Approve" button
7. Enter a unique RFID tag (e.g., "RFID-001")
8. Verify success message
9. Check that visit appears in "Live Visits" with RFID
10. Verify visitor receives email and SMS

#### Scenario 2: Submit and Reject Visit
1. Submit a new visit request
2. Navigate to visit approval dashboard
3. Click "Reject" button
4. Enter rejection reason
5. Verify success message
6. Visit should disappear from pending list
7. Verify visitor receives rejection notification

#### Scenario 3: Test 5-Minute Timeout
1. Submit a new visit request
2. Wait 5 minutes without action
3. Visit should automatically be marked as expired
4. Verify approval/rejection buttons are disabled
5. Check that rejection reason shows timeout

#### Scenario 4: RFID Uniqueness Validation
1. Try to approve two different visits with the same RFID tag
2. System should reject the second attempt
3. Error message: "The rfid tag has already been taken"

#### Scenario 5: Real-Time Updates
1. Open visit approval dashboard in multiple browser tabs
2. Submit a new visit request in one tab
3. Verify all tabs update automatically
4. Check countdown timer synchronization

## API Endpoints

### Get Pending Visits
```bash
GET /api/visit-approval/pending
Headers: X-Requested-With: XMLHttpRequest
```

Response:
```json
{
  "success": true,
  "pending_visits": [
    {
      "id": 1,
      "visitor_name": "John Doe",
      "visitor_email": "john@example.com",
      "visitor_phone": "+8801234567890",
      "purpose": "Business Meeting",
      "schedule_time": "January 25, 2026 - 2:30 PM",
      "visit_type": "Official",
      "host_name": "Host User",
      "created_at": "January 23, 2026 - 6:00 PM",
      "time_remaining": 180,
      "is_expired": false
    }
  ]
}
```

### Get Live Visits with RFID
```bash
GET /api/visit-approval/live-visits
Headers: X-Requested-With: XMLHttpRequest
```

Response:
```json
{
  "success": true,
  "live_visits": [
    {
      "id": 1,
      "visitor_name": "John Doe",
      "visitor_email": "john@example.com",
      "purpose": "Business Meeting",
      "schedule_time": "January 25, 2026 - 2:30 PM",
      "visit_type": "Official",
      "host_name": "Host User",
      "status": "approved",
      "rfid_tag": "RFID-001",
      "rfid_generated_at": "January 23, 2026 - 6:15 PM",
      "rfid_generated_by": "Admin User",
      "approved_at": "January 23, 2026 - 6:15 PM"
    }
  ]
}
```

### Approve Visit
```bash
POST /api/visit-approval/{id}/approve
Headers: X-Requested-With: XMLHttpRequest
Content-Type: multipart/form-data

Body:
- rfid_tag: "RFID-001"
- _token: [CSRF_TOKEN]
```

### Reject Visit
```bash
POST /api/visit-approval/{id}/reject
Headers: X-Requested-With: XMLHttpRequest
Content-Type: multipart/form-data

Body:
- rejected_reason: "Host not available"
- _token: [CSRF_TOKEN]
```

## Troubleshooting

### Issue: Visit approval not working
- Check user has `edit visitors` permission
- Verify routes are registered: `php artisan route:list | grep visit-approval`
- Clear caches: `php artisan route:clear && php artisan config:clear`

### Issue: RFID not saving
- Verify database migration ran successfully
- Check RFID tag uniqueness
- Review logs: `tail -f storage/logs/laravel.log`

### Issue: Notifications not sending
- Check email configuration in `.env`
- Verify SMS service credentials
- Test email: `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('test@example.com'));`
- Check job queue: `php artisan queue:work`

### Issue: Timer not updating
- Check JavaScript console for errors
- Verify auto-refresh interval
- Clear browser cache
- Check network tab for API calls

## Future Enhancements

Potential improvements:
1. WebSocket integration for true real-time updates
2. Audio notification for new pending visits
3. Bulk approval/rejection for multiple visits
4. RFID printing functionality
5. Visit check-in/check-out tracking
6. Historical RFID assignment tracking
7. Automatic RFID tag generation
8. Visit approval delegation

## Security Considerations

1. RFID tags must be unique
2. Only users with `edit visitors` permission can approve/reject
3. CSRF protection on all POST requests
4. Rejection reason required for accountability
5. Timeout prevents indefinite pending states
6. Audit trail of who generated each RFID

## Performance Notes

- Dashboard auto-refreshes every 10 seconds
- Pending count refreshes every 30 seconds on main dashboard
- Database queries optimized with eager loading
- Pagination not currently used (limit: 20 visits)

## Conclusion

The visit approval workflow system provides:
- ✅ Real-time notifications for new visit requests
- ✅ 5-minute approval window with countdown timer
- ✅ RFID generation on approval
- ✅ Rejection with reason tracking
- ✅ Live dashboard showing approved visits with RFID
- ✅ Email and SMS notifications
- ✅ Automatic timeout handling
- ✅ Full audit trail

The system is production-ready and handles all edge cases including timeouts, duplicate RFID tags, and unauthorized access attempts.
