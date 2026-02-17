# Prime Bank Video Calling System - Project Task List

**Project Name:** Prime Bank Video Calling System  
**Client Company:** Prime Bank PLC  
**Development Company:** Unisoft System LTD  
**Prepared By:** MD Ashraful Momen  
**Document Version:** 2.0  
**Last Updated:** February 16, 2026

---

## 📋 TASK LIST SUMMARY

| Task List | Date | Status |
|-----------|------|--------|
| Task List 1: Backend Setup & Authentication | February 14, 2026 | ✅ Complete |
| Task List 2: UI/UX Improvements & Testing | February 16, 2026 | 🔄 In Progress |

---

# 📅 TASK LIST 1: Backend Setup & Authentication

**Date:** February 14, 2026  
**Status:** ✅ COMPLETED  
**Developer:** MD Ashraful Momen (Unisoft System LTD)

## 1.1 Project Initialization

| Task | Status | Assigned To | Completion Date |
|------|--------|------------|----------------|
| Laravel 12 project setup | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Docker configuration | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| MySQL database setup | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Redis cache configuration | ✅ Complete | MD Ashraful Momen | 2026-01-20 |

## 1.2 Authentication System

| Task | Status | Assigned To | Completion Date |
|------|--------|------------|----------------|
| Laravel Fortify installation | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Laravel Jetstream installation | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| User model with roles | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Login/Register functionality | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Password reset | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Email verification | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Session management | ✅ Complete | MD Ashraful Momen | 2026-01-20 |

## 1.3 Role-Based Access Control (RBAC)

| Task | Status | Assigned To | Completion Date |
|------|--------|------------|----------------|
| Spatie Permission installation | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Role migrations | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Role seeder (4 roles) | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Permission setup | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Middleware configuration | ✅ Complete | MD Ashraful Momen | 2026-01-20 |
| Role-based redirection | ✅ Complete | MD Ashraful Momen | 2026-01-20 |

## 1.4 Video Calling Backend

| Task | Status | Assigned To | Completion Date |
|------|--------|------------|----------------|
| Agent model | ✅ Complete | MD Ashraful Momen | 2026-02-14 |
| CallQueue model | ✅ Complete | MD Ashraful Momen | 2026-02-14 |
| CallSession model | ✅ Complete | MD Ashraful Momen | 2026-02-14 |
| CallFeedback model | ✅ Complete | MD Ashraful Momen | 2026-02-14 |
| CallMetric model | ✅ Complete | MD Ashraful Momen | 2026-02-14 |
| AgoraService | ✅ Complete | MD Ashraful Momen | 2026-02-14 |
| VideoCallController | ✅ Complete | MD Ashraful Momen | 2026-02-14 |
| Video routes | ✅ Complete | MD Ashraful Momen | 2026-02-14 |

## 1.5 User Accounts Created

| Email | Name | Role | Status |
|-------|------|------|--------|
| ashrafulinstasure@gmail.com | Receptionist | receptionist | ✅ Active |
| ashrafulunisoft@gmail.com | Staff | staff | ✅ Active |
| amshuvo64@gmail.com | Admin | admin | ✅ Active |
| kali1212hit@gmail.com | Visitor | visitor | ✅ Active |

---

# 📅 TASK LIST 2: UI/UX Improvements & Testing

**Date:** February 16, 2026  
**Status:** 🔄 IN PROGRESS  
**Developer:** MD Ashraful Momen (Unisoft System LTD)

## 2.1 Video Call Testing

| Task | Priority | Status | Notes |
|------|----------|--------|-------|
| Test customer request call | High | 🔄 Pending | Error: "Failed to request call" |
| Test agent availability | High | 🔄 Pending | Agent status check needed |
| Test queue system | High | 🔄 Pending | Queue position tracking |
| Test call connection | High | 🔄 Pending | Agora token generation |
| Test call end | Medium | 🔄 Pending | Session cleanup |
| Test feedback submission | Medium | 🔄 Pending | Rating system |

## 2.2 User Interface Fixes

| Task | Priority | Status | Description |
|------|----------|--------|-------------|
| Dashboard layout consistency | High | 🔄 Pending | Fix responsive design |
| Sidebar menu updates | High | 🔄 Pending | Add video call links |
| Navigation bar | High | 🔄 Pending | Fix active state highlighting |
| Customer video call page UI | High | 🔄 Pending | Full interface design |
| Agent dashboard UI | High | 🔄 Pending | Status controls layout |
| Admin analytics UI | Medium | 🔄 Pending | Charts and metrics display |
| Feedback form styling | Medium | 🔄 Pending | Rating stars design |
| Mobile responsiveness | Medium | 🔄 Pending | All pages mobile-friendly |

## 2.3 Layout & Navigation

| Task | Priority | Status | Description |
|------|----------|--------|-------------|
| Layout template for admin | High | 🔄 Pending | Consistent admin layout |
| Layout template for customer | High | 🔄 Pending | Consistent customer layout |
| Sidebar for receptionist | High | 🔄 Pending | Receptionist-specific menu |
| Sidebar for visitor | High | 🔄 Pending | Visitor-specific menu |
| Header with user info | Medium | 🔄 Pending | Profile dropdown |
| Breadcrumb navigation | Low | 🔄 Pending | Better page hierarchy |

## 2.4 Pending Issues

| Issue | Priority | Status | Error Message |
|--------|----------|--------|---------------|
| Video call request fails | High | 🔄 Pending | "Failed to request call. Please try again." |
| Agora token generation | High | 🔄 Pending | "no Route matched with those values" |
| Layout compatibility | Medium | 🔄 Pending | `$slot` undefined error |
| Session management | Medium | 🔄 Pending | Session not persisting |

---

## 📊 PROJECT PROGRESS

### Completed Tasks: 45

| Category | Total | Completed | Pending |
|----------|-------|-----------|---------|
| Backend Setup | 20 | 20 | 0 |
| Authentication | 10 | 10 | 0 |
| RBAC | 8 | 8 | 0 |
| Video Call Backend | 10 | 10 | 0 |
| Video Call Testing | 6 | 0 | 6 |
| UI/UX Improvements | 15 | 0 | 15 |
| **Total** | **69** | **48** | **21** |

### Completion Rate: 70%

```
Backend Setup      ████████████████████ 100%
Authentication     ████████████████████ 100%
RBAC               ████████████████████ 100%
Video Call Backend ████████████████████ 100%
Video Call Testing ░░░░░░░░░░░░░░░░░░░░   0%
UI/UX Improvements ░░░░░░░░░░░░░░░░░░░░   0%
```

---

## 🎯 UPCOMING TASKS

### Immediate (Today)

1. **Fix Agora Token Generation**
   - Error: "no Route matched with those values"
   - Check Agora API configuration
   - Fix baseUrl in AgoraService.php

2. **Fix Video Call Request**
   - Error: "Failed to request call"
   - Check agent availability logic
   - Verify queue system integration

3. **Fix Layout Issues**
   - Resolve `$slot` undefined error
   - Update customer.blade.php layout

### This Week

1. Complete all video call testing
2. Fix dashboard UI inconsistencies
3. Update sidebar menus for all roles
4. Make all pages mobile-responsive
5. Complete admin analytics dashboard UI

---

## 📝 DAILY PROGRESS LOG

### February 14, 2026 (MD Ashraful Momen - Unisoft System LTD)

| Time | Activity | Status |
|------|----------|--------|
| Morning | Backend setup and authentication | ✅ Complete |
| Afternoon | Video call models and controllers | ✅ Complete |
| Evening | Database tables and migrations | ✅ Complete |

### February 15, 2026 (MD Ashraful Momen - Unisoft System LTD)

| Time | Activity | Status |
|------|----------|--------|
| Morning | Initial testing | ⚠️ Issues found |
| Afternoon | Bug investigation | 🔄 In Progress |
| Evening | Documentation | ✅ Complete |

### February 16, 2026 (MD Ashraful Momen - Unisoft System LTD)

| Time | Activity | Status |
|------|----------|--------|
| Morning | Task list documentation | ✅ Complete |
| Afternoon | UI/UX planning | 🔄 In Progress |
| Evening | To be continued... | ⏳ Pending |

---

## 📞 SUPPORT INFORMATION

### Development Company

**Unisoft System LTD**  
Contact: MD Ashraful Momen  
Role: Software Engineer

### Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | amshuvo64@gmail.com | password |
| Receptionist | ashrafulinstasure@gmail.com | password |
| Staff | ashrafulunisoft@gmail.com | password |
| Visitor | kali1212hit@gmail.com | password |

### URLs

| Environment | URL |
|-------------|-----|
| Application | http://127.0.0.1:8000 |
| phpMyAdmin | http://127.0.0.1:8080 |

---

## 🔧 TECHNICAL NOTES

### Technologies Used

| Component | Technology |
|-----------|------------|
| Backend | Laravel 12.x |
| Database | MySQL 8.0 |
| Cache | Redis |
| Frontend | Blade + Bootstrap 5 |
| Video SDK | Agora.io |
| Auth | Laravel Fortify + Jetstream |
| RBAC | Spatie Permission |
| Deployment | Docker |

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /video/request-call | Request video call |
| GET | /video/queue-status | Check queue position |
| POST | /video/end-call | End call session |
| POST | /video/feedback | Submit rating |
| GET | /video/admin/stats | Get analytics |

---

## 📌 ACTION ITEMS

### For Today (February 16, 2026)

- [ ] Fix Agora token generation error
- [ ] Fix video call request functionality
- [ ] Fix layout compatibility issues
- [ ] Test complete video call flow
- [ ] Update sidebar menus for all roles

### For Tomorrow (February 17, 2026)

- [ ] Complete UI improvements
- [ ] Mobile responsiveness testing
- [ ] Admin dashboard analytics UI
- [ ] Final testing and bug fixes

---

**Document Prepared By:**  
MD Ashraful Momen  
Software Engineer  
**Unisoft System LTD**

**Date:** February 16, 2026

---

*This document is confidential and intended for internal use of Unisoft System LTD and Prime Bank PLC.*
