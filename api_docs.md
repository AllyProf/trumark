# TRUMARK CRM - Mobile API Documentation

This document provides all the necessary endpoints for the Flutter mobile application to interact with the TRUMARK CRM system.

## Authentication
All protected routes require a **Bearer Token** in the `Authorization` header.

**Base URL**: `http://your-server-domain/api`

---

### 1. Authentication Endpoints

#### Login
`POST /login`
- **Parameters**:
    - `email` (string, required)
    - `password` (string, required)
    - `device_name` (string, optional)
- **Response**:
```json
{
    "success": true,
    "token": "1|abc123token...",
    "user": { "id": 1, "name": "John Doe", "role": "super_admin", ... }
}
```

#### Logout
`POST /logout` (Auth Required)
- **Response**: `{ "success": true, "message": "Successfully logged out." }`

#### Get Profile
`GET /user` (Auth Required)
- **Response**: `{ "success": true, "user": { ... } }`

---

### 2. Dashboard Endpoints

#### Get Summary Stats
`GET /dashboard` (Auth Required)
- **Response**:
```json
{
    "success": true,
    "data": {
        "summary": {
            "total_customers": 150,
            "pending_follow_ups": 12,
            "pipeline_value": 4500000.0,
            "won_value": 1200000.0
        },
        "funnel": { "Inquiry": 10, "Quotation Sent": 5, ... },
        "recent_activity": [ ... ]
    }
}
```

---

### 3. Customer Management Endpoints

#### List Customers
`GET /customers` (Auth Required)
- **Query Parameters**:
    - `page` (int, optional)
    - `search` (string, optional) - search by name or phone
    - `stage` (string, optional) - filter by buying stage
- **Response**: Standard Laravel Paginated JSON.

#### Create Lead
`POST /customers` (Auth Required)
- **Parameters**: `name`, `phone`, `status`, `buying_stage`, `type`, `source`, `email`, `region`, `district`, `estimated_monthly_value`.

#### View Customer Details
`GET /customers/{id}` (Auth Required)
- **Response**: Full customer object including `sms_logs` and `follow_ups`.

#### Update Customer
`PATCH /customers/{id}` (Auth Required)
- **Parameters**: `status`, `buying_stage`, `next_follow_up_date`, `notes`, `estimated_monthly_value`.

---

### 4. Notification Endpoints

#### List Unread Notifications
`GET /notifications` (Auth Required)

#### Mark Notification as Read
`POST /notifications/{id}/read` (Auth Required)

#### Mark All as Read
`POST /notifications/read-all` (Auth Required)

---

### Notes for the Developer:
- **Errors**: Standard HTTP status codes are used (422 for validation, 401 for unauthorized, 403 for forbidden).
- **Images**: User avatars are returned as full URLs using the `asset()` helper.
- **Roles**: The system handles data filtering automatically based on the authenticated user's role (Super Admin sees all, Sales Officer sees only their leads).
