# MIKHMON Project - Quick Reference & Architecture Diagrams

## System Architecture Overview

```
┌──────────────────────────────────────────────────────────────┐
│                         END USER                             │
│                    (Hotspot Customer)                        │
└──────────────────────┬─────────────────────────────────────┘
                       │
                       │ WiFi Connection
                       ↓
┌──────────────────────────────────────────────────────────────┐
│                                                              │
│                   MikroTik RouterOS                          │
│            (Hotspot Server @ 192.168.x.x:8728)             │
│                                                              │
│  ├─ /ip/hotspot/user/       (Manage users)                │
│  ├─ /ip/hotspot/profile/    (User profiles)               │
│  ├─ /ip/hotspot/active/     (Connected users)             │
│  ├─ /system/script/         (Scheduled scripts)           │
│  ├─ /ip/dhcp-server/lease/  (DHCP leases)                │
│  ├─ /interface/ether-like/  (Interface stats)            │
│  └─ Other RouterOS features                               │
│                                                              │
└─┬────────────────────────────────────────────────────────┬─┘
  │ RouterOS API (Socket: Port 8728)                       │ 
  │                                                          │
  ↓                                                          ↑
┌──────────────────────────────────────────────────────────────┐
│                                                              │
│         MIKHMON WEB INTERFACE (This Project)                │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Admin.php (Entry Point & Authentication)         │    │
│  │                                                    │    │
│  │  1. Display Login Form                            │    │
│  │  2. Verify Credentials (config.php)              │    │
│  │  3. Create Session                                │    │
│  │  4. Connect to RouterOS via API                  │    │
│  └────────────────────────────────────────────────────┘    │
│                        ↓                                    │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Main Dashboard (index.php?session=name)          │    │
│  │                                                    │    │
│  │  Navigation Menu (include/menu.php)               │    │
│  │  ├─ Dashboard                                      │    │
│  │  ├─ Hotspot Users                                 │    │
│  │  ├─ User Profiles                                 │    │
│  │  ├─ Quick Print / Vouchers                        │    │
│  │  ├─ DHCP Leases                                   │    │
│  │  ├─ Traffic Monitor                               │    │
│  │  ├─ Reports                                        │    │
│  │  ├─ Settings                                       │    │
│  │  └─ System Status                                 │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Functional Modules                               │    │
│  │                                                    │    │
│  │  hotspot/          → User management              │    │
│  │  voucher/          → Voucher generation           │    │
│  │  traffic/          → Monitoring & stats           │    │
│  │  report/           → Historical reports           │    │
│  │  settings/         → Configuration                │    │
│  │  process/          → API operations               │    │
│  │  lib/              → RouterOS API class           │    │
│  └────────────────────────────────────────────────────┘    │
│                        ↓                                    │
│  ┌────────────────────────────────────────────────────┐    │
│  │  RouterOS API Class (routeros_api.class.php)     │    │
│  │                                                    │    │
│  │  $API->connect($ip, $user, $pass)                │    │
│  │  $API->comm("/path/to/command", $params)        │    │
│  │  $API->disconnect()                              │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## Current Data Source & Configuration

```
┌─────────────────────────────────────────┐
│     include/config.php                  │
│  (HARDCODED CONFIGURATION)              │
├─────────────────────────────────────────┤
│                                         │
│  $data['mikhmon'] = [                  │
│    '1' => 'mikhmon<|<password'         │
│    '2' => 'mikhmon>|>encoded_pass'    │
│  ];                                     │
│                                         │
│  $data['router-session-1'] = [         │
│    '1' => 'ip!192.168.1.1'            │
│    '2' => 'user@|@admin'              │
│    '3' => 'pass#|#password'           │
│    '4' => 'hotspot%hotspot-name'      │
│    '5' => 'dns^8.8.8.8'               │
│    '6' => 'currency&USD'              │
│    '7' => 'reload*30'                 │
│    '8' => 'iface(ether1'              │
│    ... more config fields ...         │
│  ];                                     │
│                                         │
└─────────────────────────────────────────┘
          ↓
┌─────────────────────────────────────────┐
│     include/readcfg.php                 │
│  (PARSE CONFIGURATION)                  │
├─────────────────────────────────────────┤
│  Splits by delimiters:                 │
│  <|<, >|>, @|@, #|#, %, ^, &, *, (, ),│
│  =, +, @!@                             │
└─────────────────────────────────────────┘
          ↓
┌─────────────────────────────────────────┐
│     $_SESSION Variables                 │
│  (SESSION STORAGE)                      │
├─────────────────────────────────────────┤
│  $_SESSION['iphost']      - Router IP   │
│  $_SESSION['userhost']    - API User    │
│  $_SESSION['passwdhost']  - API Pass    │
│  $_SESSION['hotspotname'] - Hotspot    │
│  $_SESSION['currency']    - Currency    │
│  ... etc                                │
└─────────────────────────────────────────┘
          ↓
┌─────────────────────────────────────────┐
│     RouterOS Live Database              │
│  (SOURCE OF TRUTH)                      │
├─────────────────────────────────────────┤
│  Hotspot users, profiles, active conns  │
│  DHCP leases                           │
│  Traffic statistics                    │
│  Interface data                        │
└─────────────────────────────────────────┘
```

---

## Post-MySQL Architecture (Proposed)

```
┌─────────────────────────────────────────┐
│     include/config.php                  │
│  (MySQL Connection Credentials)         │
├─────────────────────────────────────────┤
│  DB_HOST, DB_USER, DB_PASS, DB_NAME   │
└────────────────────┬────────────────────┘
                     ↓
┌─────────────────────────────────────────┐
│     lib/Database.class.php              │
│  (PDO Abstraction Layer)                │
├─────────────────────────────────────────┤
│  $db = new Database();                 │
│  $users = $db->select(...);            │
│  $db->execute(...);                    │
└────────────────────┬────────────────────┘
                     ↓
         ┌───────────┴────────────┐
         ↓                        ↓
┌─────────────────────┐   ┌──────────────────┐
│  MySQL Database     │   │ RouterOS Device  │
│  (Local Storage)    │   │ (Live Data)      │
├─────────────────────┤   ├──────────────────┤
│ admin_users         │   │ Hotspot Users    │
│ routers             │   │ Profiles         │
│ vouchers            │   │ Traffic          │
│ user_logs           │   │ Interfaces       │
│ transactions        │   │ Leases           │
│ system_logs         │   │ Bindings         │
└─────────────────────┘   └──────────────────┘
         ↑                        ↑
         └────────┬───────────────┘
                  │
        Application Logic Flow
        (Services & Controllers)
```

---

## User Management Workflow

```
┌────────────────────────────────────────────────┐
│  HOTSPOT USER LIFECYCLE                        │
└────────────────────────────────────────────────┘

1. CREATE NEW USER:
   ┌──────────────┐
   │ hotspot/     │
   │ adduser.php  │  → User enters: name, profile, price
   └──────┬───────┘
          │
          ↓
   ┌──────────────┐
   │ HTML Form    │  → POST request
   └──────┬───────┘
          │
          ↓
   ┌──────────────┐
   │ process/     │
   │ process.php  │  → Validate input
   └──────┬───────┘
          │
          ↓
   ┌──────────────────────┐
   │ RouterOS API Call:   │
   │ /ip/hotspot/user/add │  → Add to RouterOS
   └──────┬───────────────┘
          │
          ↓
   ┌──────────────────────┐
   │ system/script/add    │  → Create expiry script
   │ system/scheduler/add │  → Create scheduler
   └──────┬───────────────┘
          │
          ↓
   ✓ User Created!


2. DISABLE/ENABLE USER:
   removehotspotuser.php  →  /ip/hotspot/user/remove
   disablehotspotuser.php →  /ip/hotspot/user/set (disabled=yes)
   enablehotspotuser.php  →  /ip/hotspot/user/set (disabled=no)
   resethotspotuser.php   →  Update password


3. REMOVE USER:
   ┌──────────────────────┐
   │ User List View       │  → User clicks "Delete"
   └──────┬───────────────┘
          │
          ↓
   ┌──────────────────────┐
   │ removehotspotuser.php│
   └──────┬───────────────┘
          │
          ├─ Get associated script ID
          ├─ Get associated scheduler ID
          ├─ Delete script
          ├─ Delete scheduler
          └─ Delete user
          │
          ↓
   ✓ User Completely Removed!
```

---

## Voucher Generation Workflow

```
┌────────────────────────────────────────────────┐
│  VOUCHER GENERATION PROCESS                    │
└────────────────────────────────────────────────┘

Step 1: Access Voucher Module
   voucher/index.php  →  Select template (default, thermal, small)

Step 2: Configure Parameters
   User enters:
   ├─ Number of vouchers
   ├─ Profile name
   ├─ Base price
   └─ Currency

Step 3: Generate
   voucher/generateuser.php
          ↓
   For each voucher:
   ├─ Generate unique username (random)
   ├─ Generate unique password
   ├─ Add to RouterOS (/ip/hotspot/user/add)
   ├─ Store in temporary table (voucher/temp.php)
   └─ Create QR Code (using qrious.min.js)

Step 4: Preview
   voucher/vpreview.php
          ↓
   Display formatted voucher layout
   ├─ Voucher code
   ├─ Username
   ├─ Password
   ├─ QR Code
   ├─ Price
   └─ Expiry date

Step 5: Print
   voucher/print.php  →  Browser print dialog
          ↓
   Select paper size (default, thermal, small)
   Print to printer


VOUCHER DATA STRUCTURE (Currently temporary):
┌──────────────────────────┐
│  voucher/temp.php        │
├──────────────────────────┤
│ Temporary storage while  │
│ generating/printing      │
│                          │
│ After print completed:   │
│ - Data discarded OR      │
│ - Saved to RouterOS only │
└──────────────────────────┘
```

---

## Module Interaction Map

```
                    ┌─────────────────┐
                    │   admin.php     │
                    │   (Entry Point) │
                    └────────┬────────┘
                             │
                    ┌────────┴────────┐
                    ↓                 ↓
            ┌─────────────┐   ┌──────────────┐
            │ Login Page  │   │ Dashboard    │
            │ include/    │   │ index.php    │
            │ login.php   │   └──────┬───────┘
            └─────────────┘          │
                                 ┌───┴──────────────┬──────────┬──────┬──────────┐
                                 ↓                  ↓          ↓      ↓          ↓
                         ┌──────────────┐  ┌────────────┐ ┌────────┐ ┌──────┐ ┌─────┐
                         │   hotspot/   │  │  voucher/  │ │traffic/│ │report│ │syst-│
                         │              │  │            │ │        │ │      │ │em/  │
                         ├─ users.php   │  ├─ index.php │ ├─ index │ ├─ ... │ └─────┘
                         ├─ adduser.php │  ├─ generate  │ ├─ traffic
                         ├─ profiles.py │  ├─ print.php │ ├─ monitor
                         ├─ active.php  │  └─ temp.php  │ └───────┘
                         ├─ ...         │
                         └──────────────┘
                                 │
                    All modules use:
                    ├─ include/readcfg.php
                    ├─ lib/routeros_api.class.php
                    ├─ include/menu.php
                    ├─ include/lang/[lang].php
                    ├─ css/mikhmon-ui.[theme].min.css
                    └─ js/mikhmon.js
```

---

## Development Phase Checklist

### Phase 1: MySQL Integration ☐
- [ ] Create database and tables
- [ ] Create Database.class.php
- [ ] Create Auth.class.php with password_hash
- [ ] Migrate router configuration to routers table
- [ ] Create admin user management backend
- [ ] Test database connectivity

### Phase 2: API & Services ☐
- [ ] RESTful API endpoints for auth
- [ ] RESTful API endpoints for routers
- [ ] RESTful API endpoints for hotspot users
- [ ] API authentication & authorization
- [ ] API rate limiting

### Phase 3: Security ☐
- [ ] Implement input validation
- [ ] Add CSRF token protection
- [ ] Encrypt sensitive data
- [ ] Remove error_reporting(0)
- [ ] Add security headers
- [ ] Implement API rate limiting

### Phase 4: Logging & Monitoring ☐
- [ ] User activity logging
- [ ] API request logging
- [ ] Error/exception logging
- [ ] System event logging
- [ ] Database audit trail

### Phase 5: UI/UX Enhancement ☐
- [ ] Responsive design fixes
- [ ] Modern Bootstrap 5 framework
- [ ] Improved navigation
- [ ] Dashboard widgets
- [ ] Better data tables with sorting/filtering

### Phase 6: Testing & Documentation ☐
- [ ] Unit tests
- [ ] Integration tests
- [ ] User acceptance tests
- [ ] Performance optimization
- [ ] API documentation
- [ ] End-user documentation in Bangla

---

## Key Files Reference

### Critical Core Files
```
admin.php                          ← Entry point - Authentication
include/config.php                 ← Router credentials (TO BE MIGRATED)
include/readcfg.php                ← Config parsing
lib/routeros_api.class.php         ← RouterOS API communication
```

### User Management Files
```
hotspot/users.php                  ← List/manage users
hotspot/adduser.php                ← Add new user form
hotspot/userprofile.php            ← Manage user profiles
hotspot/userbyname.php             ← Search users
hotspot/userbyprofile.php          ← Filter by profile
process/removehotspotuser.php      ← Delete user operation
process/disablehotspotuser.php     ← Disable user operation
process/enablehotspotuser.php      ← Enable user operation
```

### Voucher Management Files
```
voucher/index.php                  ← Voucher generation interface
voucher/generateuser.php           ← Generate vouchers
voucher/default.php                ← Standard template
voucher/default-thermal.php        ← Thermal printer template
voucher/print.php                  ← Print handler
voucher/vpreview.php               ← Preview vouchers
```

### Support Files
```
include/headhtml.php               ← HTML head & resources
include/menu.php                   ← Navigation menu
include/lang.php                   ← Language loader
lang/en.php, es.php, id.php, tl.php  ← Language strings
include/theme.php                  ← Theme loader
settings/settheme.php              ← Theme switcher
settings/setlang.php               ← Language switcher
```

---

## Quick Command Reference

### RouterOS API Common Commands

```php
// Get all hotspot users
$API->comm("/ip/hotspot/user/print");

// Get users by profile
$API->comm("/ip/hotspot/user/print", array("?profile" => "customers"));

// Get active hotspot sessions
$API->comm("/ip/hotspot/active/print");

// Add new hotspot user
$API->comm("/ip/hotspot/user/add", array(
    "name" => "user123",
    "password" => "pass123",
    "profile" => "customers",
    "comment" => "test user"
));

// Remove user
$API->comm("/ip/hotspot/user/remove", array(".id" => "user-id"));

// Enable/Disable user
$API->comm("/ip/hotspot/user/set", array(
    ".id" => "user-id",
    "disabled" => "yes|no"
));

// Get DHCP leases
$API->comm("/ip/dhcp-server/lease/print");

// Get interface statistics
$API->comm("/interface/ether-like/print");

// Create scheduled task
$API->comm("/system/scheduler/add", array(
    "name" => "task-name",
    "on-time" => "jan/01/2025 23:00:00",
    "interval" => "0",
    "policy" => "ftp,reboot,read,write,policy,test,winbox,password,sniff,sensitive,api,romon,dude,tikapp"
));
```

---

## Environment Setup (Windows - Laragon)

```
Installation Path: C:\laragon\www\mikhmon\

Components:
- Apache 2.4+ (Running)
- PHP 7.4+ (With PDO/MySQLi support)
- MySQL 5.7 or MariaDB 10.3+

Access:
- Web: http://localhost/mikhmon/admin.php
- phpMyAdmin: http://localhost/phpmyadmin
```

---

## Document Files Generated

Three comprehensive documentation files have been created:

1. **DOCUMENTATION_BANGLA.md** (This Project's Main Bangla Guide)
   - Project overview in Bengali
   - Complete architecture explanation
   - Step-by-step workflows
   - Troubleshooting guide
   - file: DOCUMENTATION_BANGLA.md

2. **PROJECT_OPTIMIZATION_CONCEPT.md** (Modernization Plan)
   - Current state analysis
   - 6-phase development plan
   - MySQL database schema
   - Code examples (OOP, Services, API)
   - Security hardening strategies
   - Development timeline
   - file: PROJECT_OPTIMIZATION_CONCEPT.md

3. **This File: Quick Reference** (Architecture & Diagrams)
   - Visual system diagrams
   - Data flow illustrations
   - Module interaction maps
   - Development checklist
   - Key files reference
   - Quick command reference

---

**Generated**: April 8, 2026  
**For**: Mikhmon Project Development Team  
**Status**: Complete - Ready for Implementation
