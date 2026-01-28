<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Resources\TextResource;

/**
 * Database Schema Resource for VMS
 * 
 * This resource provides the database schema information that the AI chatbot
 * can reference when answering questions about the visitor management system.
 */
class VmsDatabaseSchemaResource extends TextResource
{
    protected string $name = 'vms_database_schema';
    protected string $description = 'Visitor Management System database schema with tables, columns, and relationships';

    public function __construct()
    {
        $schema = self::getSchemaContent();
        parent::__construct(
            name: 'vms_database_schema',
            description: 'Complete database schema for the Visitor Management System including all tables, columns, data types, and relationships.',
            content: $schema,
            mimeType: 'text/html'
        );
    }

    /**
     * Get the database schema content
     */
    private static function getSchemaContent(): string
    {
        return <<<SCHEMA
# Visitor Management System (VMS) Database Schema

## Database: pragati_ai_db_2 (local) or vmsucbl_db_1 (external)

---

## Tables

### 1. visitors
Stores visitor information who register in the system.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(255) | Visitor's full name |
| phone | varchar(20) | Phone number |
| email | varchar(255) | Email address |
| address | text | Physical address |
| is_blocked | boolean | Whether visitor is blocked (default: false) |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

**Relationships:**
- Has many `visits` (visits.visitor_id → visitors.id)

---

### 2. visits
Stores visit/appointment records.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| visitor_id | bigint | Foreign key to visitors table |
| host_id | bigint | Foreign key to users table (host/staff) |
| visit_type_id | bigint | Foreign key to visit_types table |
| purpose | text | Reason/purpose of visit |
| status | enum | Status: pending, approved, rejected, completed, checked_in, checked_out |
| scheduled_at | timestamp | Scheduled date/time for visit |
| checked_in_at | timestamp | Actual check-in time |
| checked_out_at | timestamp | Actual check-out time |
| created_at | timestamp | Record creation time |

**Status Values:**
- `pending` - Awaiting approval
- `approved` - Approved by host
- `rejected` - Rejected by host
- `completed` - Visit completed successfully
- `checked_in` - Visitor has checked in
- `checked_out` - Visitor has checked out

**Relationships:**
- Belongs to `Visitor` (visits.visitor_id → visitors.id)
- Belongs to `User` as host (visits.host_id → users.id)
- Belongs to `VisitType` (visits.visit_type_id → visit_types.id)
- Has many `VisitLogs`

---

### 3. users
Stores system users (hosts/staff/admins).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(255) | User's full name |
| email | varchar(255) | Email address (unique) |

**Note:** This table stores hosts (people being visited), staff, and administrators.

**Relationships:**
- Has many `visits` (visits.host_id → users.id)

---

### 4. visit_types
Stores different types of visits.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(100) | Type name (e.g., "Meeting", "Interview", "Delivery") |
| description | text | Description of the visit type |

**Relationships:**
- Has many `visits` (visits.visit_type_id → visit_types.id)

---

### 5. visit_logs
Audit log for all visit-related actions.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| visit_id | bigint | Foreign key to visits table |
| action | varchar(100) | Action performed (e.g., "created", "checked_in", "checked_out") |
| description | text | Detailed description of the action |
| created_at | timestamp | When the action occurred |

**Relationships:**
- Belongs to `Visit` (visit_logs.visit_id → visits.id)

---

## Common Query Patterns

### Count Visitors
```sql
SELECT COUNT(*) FROM visitors
```

### Count by Status
```sql
SELECT status, COUNT(*) FROM visits GROUP BY status
```

### Today's Visits
```sql
SELECT * FROM visits WHERE DATE(created_at) = CURDATE()
```

### Pending Visits
```sql
SELECT * FROM visits WHERE status = 'pending'
```

### Visitor with Visits
```sql
SELECT v.*, vi.* FROM visitors v 
LEFT JOIN visits vi ON v.id = vi.visitor_id
```

---

## Example Questions the Chatbot Can Answer

1. **Count queries:** "How many visitors today?", "How many pending visits?"
2. **List queries:** "Show me today's visits", "List all blocked visitors"
3. **Status queries:** "Show me approved visits", "What visits are pending?"
4. **Time-based:** "Show visits from this week", "Today's check-ins"
5. **Statistics:** "Give me the dashboard stats", "Total visitors this month"
SCHEMA;
    }
}
