# KOMS (Karate Organization Management System) - Audit & Gap Analysis

## Executive Summary
This document provides a comprehensive audit comparing the current **koms-android** application against the full production **KOMS Specification**.

The goal is to transform **koms-android** from its current student-only prototype into a complete, enterprise-grade, role-based Android application that seamlessly connects to the KOMS REST API backend.

---

## 1. Role-Based Access Control Audit

| Role | Specification Requirement | Current State in koms-android | Status |
| :--- | :--- | :--- | :--- |
| **Grand Master / Super Admin** | View all dojos, approve/reject dojo registrations, manage Masters, organization stats, revenue reports, system audit logs, global announcements, tournament targeting | None | ❌ Missing |
| **Master / Dojo Admin** | Manage assigned dojo, approve/reject student join requests, create/lock attendance sessions, manage fee structures, record payments, select tournament participants, add achievements, belt grading | None | ❌ Missing |
| **Senior / Sub-Admin** | Take attendance for assigned dojo, monitor students, view session history | None | ❌ Missing |
| **Student / User** | Register, login, browse/join dojos, track joining requests, view attendance history, view fee records & payments, view belt progression, register for tournaments, view achievements & announcements | Implemented (Login, Dashboard, Dojos, Attendance, Fees, Tournaments) | ⚠️ Partial (Missing Profile, Joining Request, Notifications, Achievements) |

---

## 2. Data Models Audit (`com.koms.app.data.model`)

### Currently Implemented
- `User`: `userId`, `name`, `email`, `role`, `token`
- `Dojo`: `id`, `name`, `location`, `masterName`, `trainingDays`, `trainingTimings`
- `AttendanceHistory`: `sessionDate`, `startTime`, `status`, `remarks`
- `FeeRecord`: `id`, `billingMonth`, `amountDue`, `dueDate`, `status`, `feeName`
- `Tournament`: `id`, `name`, `eventDate`, `venue`, `registrationDeadline`
- `Announcement`: `id`, `title`, `content`, `publishDate`

### Missing / Required Data Models
- **`DojoJoinRequest`**: `requestId`, `studentId`, `dojoId`, `status` (`Pending`, `Approved`, `Rejected`), `requestDate`
- **`AttendanceSession`**: `sessionId`, `dojoId`, `date`, `startTime`, `endTime`, `instructor`, `isLocked`
- **`Payment`**: `paymentId`, `feeRecordId`, `amount`, `paymentMethod`, `paymentDate`, `referenceNo`
- **`Achievement`**: `achievementId`, `studentId`, `title`, `event`, `position`, `date`
- **`GradingRecord`**: `gradingId`, `studentId`, `previousBelt`, `newBelt`, `gradingDate`, `remarks`
- **`Notification`**: `notificationId`, `userId`, `title`, `message`, `type`, `isRead`, `createdDate`
- **`AuditLog`**: `logId`, `userId`, `action`, `module`, `timestamp`, `description`

---

## 3. API Architecture Audit (`ApiService.kt`)

### Existing Endpoints
- `POST auth/login.php`
- `GET dojos/list.php`
- `GET attendance/history.php`
- `GET fees/records.php`
- `GET tournaments/list.php`
- `GET announcements/list.php`

### Missing Endpoints
- **Authentication**: `POST auth/register.php`, `POST auth/forgot_password.php`
- **Dojo Management**: `POST dojos/request.php`, `POST dojos/approve.php`, `POST dojos/reject.php`
- **Student Requests**: `GET dojos/requests.php`, `POST dojos/requests/approve.php`, `POST dojos/requests/reject.php`
- **Attendance**: `POST attendance/create_session.php`, `POST attendance/mark.php`, `POST attendance/lock.php`
- **Fees & Payments**: `POST fees/create_structure.php`, `POST fees/record_payment.php`
- **Tournaments**: `POST tournaments/create.php`, `POST tournaments/register.php`, `POST tournaments/select_participants.php`
- **Achievements & Belt Grading**: `POST achievements/add.php`, `GET achievements/list.php`, `POST grading/add.php`, `GET grading/history.php`
- **Notifications**: `GET notifications/list.php`, `POST notifications/read.php`
- **Reports & Audit Logs**: `GET reports/revenue.php`, `GET audit/logs.php`

---

## 4. Screen Structure & Feature Roadmap

```
koms-android/
├── Authentication & Public
│   ├── LoginActivity (Done)
│   ├── RegisterActivity [NEW]
│   └── ForgotPasswordActivity [NEW]
│
├── Student Module
│   ├── StudentDashboardActivity (Done)
│   ├── DojosActivity (Done)
│   ├── DojoDetailsActivity [NEW] (With "Request to Join" workflow)
│   ├── AttendanceActivity (Done)
│   ├── FeesActivity (Done)
│   ├── GradingActivity (Done)
│   ├── AchievementsActivity [NEW]
│   ├── NotificationsActivity [NEW]
│   └── ProfileActivity [NEW]
│
├── Master / Dojo Admin Module [NEW]
│   ├── MasterDashboardActivity
│   ├── ManageStudentsActivity
│   ├── StudentRequestsActivity (Approve/Reject requests)
│   ├── MarkAttendanceActivity (Create & Lock sessions)
│   ├── FeeManagementActivity (Record payments)
│   ├── AddAchievementActivity
│   └── GradingManagementActivity (Promote belt)
│
└── Grand Master / Super Admin Module [NEW]
    ├── AdminDashboardActivity
    ├── ApproveDojosActivity
    ├── ManageMastersActivity
    ├── RevenueReportActivity
    └── AuditLogsActivity
```

---

## 5. Next Steps
To satisfy the full specification, we will implement these modules systematically:
1. **Student Module Expansion**: Add `DojoDetailsActivity` with the **"Request to Join"** workflow, `AchievementsActivity`, `NotificationsActivity`, and `ProfileActivity`.
2. **Master Dashboard & Workflows**: Build `MasterDashboardActivity`, `StudentRequestsActivity` (Approve/Reject), `MarkAttendanceActivity`, and `GradingManagementActivity`.
3. **Grand Master / Super Admin Module**: Build `AdminDashboardActivity`, `ApproveDojosActivity`, and `AuditLogsActivity`.
