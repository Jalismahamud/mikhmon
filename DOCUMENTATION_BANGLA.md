# মিখমন (MIKHMON) - সম্পূর্ণ ডকুমেন্টেশন

**প্রজেক্ট নাম**: Mikhmon (Golden WiFi)  
**ভার্সন**: 3.20 (06-30-2021)  
**লাইসেন্স**: GNU General Public License v2  
**নির্মাতা**: Laksamadi Guko

---

## ১. প্রজেক্ট পরিচয়

### উদ্দেশ্য
মিখমন একটি ওয়েব-ভিত্তিক ম্যানেজমেন্ট সিস্টেম যা **মাইক্রোটিক রাউটারওএস** ডিভাইসগুলি নিয়ন্ত্রণ করতে ব্যবহৃত হয়। এটি হটস্পট ব্যবহারকারী, DHCP, ট্রাফিক মনিটরিং এবং ভাউচার ম্যানেজমেন্ট সুবিধা প্রদান করে।

### মূল বৈশিষ্ট্য
- 🔐 মাল্টি-রাউটার সাপোর্ট
- 👥 ব্যবহারকারী ম্যানেজমেন্ট সিস্টেম
- 📱 ভাউচার জেনারেশন এবং প্রিন্টিং
- 📊 ট্রাফিক মনিটরিং এবং রিপোর্টিং
- 🌍 মাল্টি-ল্যাঙ্গুয়েজ সাপোর্ট
- 🎨 একাধিক থিম সমর্থন
- 📅 শিডিউলার এবং স্ক্রিপ্ট ম্যানেজমেন্ট

---

## ২. প্রজেক্ট আর্কিটেকচার

### সামগ্রিক কাঠামো

```
মিখমন ওয়েব ইন্টারফেস
        ↓
   রাউটারওএস API ক্লাস (routeros_api.class.php)
        ↓
   মাইক্রোটিক রাউটার (API সংযোগ)
        ↓
   হটস্পট, DHCP, ট্রাফিক, ইউজার ডেটা
```

### ফোল্ডার স্ট্রাকচার এবং ফাংশন

| ফোল্ডার | উদ্দেশ্য | প্রধান ফাইল |
|---------|---------|-----------|
| **admin.php** | লগইন এবং সেশন ম্যানেজমেন্ট | অ্যাডমিন এন্ট্রি পয়েন্ট |
| **include/** | মূল কার্যকারিতা | config.php, login.php, menu.php |
| **dashboard/** | ড্যাশবোর্ড ভিউ | home.php, aload.php |
| **hotspot/** | হটস্পট ম্যানেজমেন্ট | users.php, userprofile.php, adduser.php |
| **dhcp/** | DHCP লিজ ম্যানেজমেন্ট | dhcpleases.php |
| **traffic/** | ট্রাফিক মনিটরিং | traffic.php, trafficmonitor.php |
| **voucher/** | ভাউচার তৈরি এবং প্রিন্ট | default.php, print.php |
| **report/** | রিপোর্ট এবং লগ | userlog.php, selling.php, livereport.php |
| **settings/** | সেটিংস এবং কনফিগ | settings.php, settheme.php, setlang.php |
| **process/** | API অপারেশন | removehotspotuser.php, adduser.php ইত্যাদি |
| **lib/** | লাইব্রেরি | routeros_api.class.php (API ক্লাস) |
| **js/, css/** | ফ্রন্টএন্ড অ্যাসেট | JQuery, Bootstrap, কাস্টম JS |
| **lang/** | ভাষা ফাইল | en.php, es.php, id.php, tl.php |

---

## ৩. কর্মপ্রবাহ (ওয়ার্কিং প্রসেস)

### A. অ্যাপ্লিকেশন শুরু হওয়ার প্রক্রিয়া

```
1. ব্যবহারকারী admin.php অ্যাক্সেস করে
   ↓
2. লগইন ফর্ম প্রদর্শিত হয় (include/login.php)
   ↓
3. ব্যবহারকারী ইউজারনেম + পাসওয়ার্ড এন্টার করে
   ↓
4. config.php থেকে হার্ডকোডেড ক্রেডেনশিয়াল যাচাই করা হয়
   ↓
5. সফল হলে: সেশন সেট করা হয় $_SESSION["mikhmon"] = true
   ↓
6. মাইক্রোটিক রাউটারে সংযোগ করা হয় (routeros_api.class.php)
   ↓
7. মেইন ড্যাশবোর্ড লোড হয় (index.php?session=সেশন_নাম)
```

### B. ব্যবহারকারী ম্যানেজমেন্ট প্রক্রিয়া

#### নতুন ব্যবহারকারী যোগ করা:
```
hotspot/adduser.php
   ↓
ফর্ম সাবমিট হয় (নাম, প্রোফাইল, পাসওয়ার্ড, মূল্য)
   ↓
process فایل প্রসেস করে
   ↓
RouterOS API কল: /ip/hotspot/user/add
   ↓
স্ক্রিপ্ট এবং শিডিউলার তৈরি হয় (সময়োপযোগী মেয়াদের জন্য)
   ↓
ব্যবহারকারী আইডি ডাটাবেস (RouterOS) এ সংরক্ষিত হয়
```

#### ব্যবহারকারী অপসারণ করা:
```
users.php থেকে ব্যবহারকারী নির্বাচন করুন
   ↓
process/removehotspotuser.php ট্রিগার হয়
   ↓
- স্ক্রিপ্ট অপসারণ
- শিডিউলার অপসারণ  
- ব্যবহারকারী অপসারণ
   ↓
সম্পূর্ণ অপসারণ সম্পন্ন
```

### C. ভাউচার জেনারেশন প্রক্রিয়া

```
voucher অনুভাগে যান
   ↓
ভাউচার টেমপ্লেট নির্বাচন করুন (default.php, thermal.php ইত্যাদি)
   ↓
সংখ্যা, প্রোফাইল, মূল্য নির্ধারণ করুন
   ↓
generateuser.php এক্সিকিউট হয়
   ↓
প্রতিটি ভাউচারের জন্য:
   - অনন্য ইউজারনেম এবং পাসওয়ার্ড তৈরি
   - RouterOS এ যোগ করা হয়
   - QR কোড তৈরি (qrious.min.js ব্যবহার করে)
   - প্রিন্টের জন্য ফর্ম্যাট করা হয়
```

### D. ট্রাফিক মনিটরিং প্রক্রিয়া

```
traffic অনুভাগে যান
   ↓
traffic/traffic.php লোড হয়
   ↓
RouterOS API: /interface/ether-like/print (ইন্টারফেস ডেটা)
            /interface/wireless/print (ওয়াইফাই ডেটা)
   ↓
রিয়েল-টাইম ট্রাফিক ডেটা প্রদর্শিত হয়
   ↓
traffic/trafficmonitor.php: উন্নত মনিটরিং (চার্ট এবং গ্রাফ)
```

### E. হোস্ট এবং IP বাইন্ডিং ম্যানেজমেন্ট

```
hosts অনুভাগ:
   MAC Address ↔ IP Address ম্যাপিং
   RouterOS: /ip/hotspot/host/

IP Binding অনুভাগ:
   IP Address ↔ MAC Address নিয়ন্ত্রণ
   RouterOS: /ip/hotspot/ip-binding/
```

---

## ৪. ডেটা ফ্লো এবং সংযোগ

### বর্তমান ডেটা সোর্স

```
┌─────────────────────────┐
│  include/config.php     │  হার্ডকোডেড কনফিগ
├─────────────────────────┤
│  রাউটার IP, ইউজার, পাস │
│  হটস্পট নাম, ইন্টারফেস │
│  মুদ্রা, সেটিংস         │
└──────────────┬──────────┘
               ↓
       ┌───────────────────┐
       │ include/readcfg.php│ কনফিগ পার্স করে
       └────────┬──────────┘
                ↓
      RouterOS API সংযোগ
                ↓
     মাইক্রোটিক রাউটার ডিভাইস
```

### MySQL ইন্টিগ্রেশনের পরে (প্রস্তাবিত)

```
┌─────────────────────────┐
│  include/config.php     │  MySQL ক্রেডেনশিয়াল
├─────────────────────────┤
│  DB নাম, হোস্ট, ব্যবহারকারী │
└──────────────┬──────────┘
               ↓
       ┌───────────────────┐
       │ include/db.php    │ MySQL সংযোগ ক্লাস
       └────────┬──────────┘
                ↓
        PDO/মাইসকলিনি ড্রাইভার
                ↓
        MySQL ডাটাবেস
```

---

## ৫. প্রধান ফাংশন এবং ক্লাস

### RouterOS API ক্লাস (`lib/routeros_api.class.php`)

**মূল মেথড:**
- `connect($ip, $login, $password)` - রাউটারে সংযোগ
- `comm($command, $params)` - RouterOS API কমান্ড পাঠান
- `disconnect()` - সংযোগ বন্ধ করুন
- `isConnected()` - সংযোগ স্ট্যাটাস চেক করুন

**উদাহরণ ব্যবহার:**
```php
// রাউটারে সংযোগ
$API = new RouterosAPI();
$API->connect('192.168.1.1', 'admin', 'password');

// সব ব্যবহারকারী পান
$users = $API->comm("/ip/hotspot/user/print");

// নতুন ব্যবহারকারী যোগ করুন
$API->comm("/ip/hotspot/user/add", array(
    "name" => "user123",
    "password" => "pass123",
    "profile" => "default"
));
```

---

## ৬. কনফিগারেশন ফাইল (`include/config.php`)

### বর্তমান স্ট্রাকচার

```php
$data['mikhmon'] = array(
    '1' => 'mikhmon<|<goldenwifi',     // admin:password
    '2' => 'mikhmon>|>aWNlbA=='        // encoded password
);

$data['router-session'] = array(
    '1' => 'ip!192.168.1.1',           // Router IP
    '2' => 'user@|@admin',             // Router username
    '3' => 'pass#|#password',          // Router password
    '4' => 'hotspot%Hotspot1',         // Hotspot name
    '5' => 'dns^1.1.1.1',              // DNS
    '6' => 'currency&USD',             // Currency
    '7' => 'reload*30',                // Auto reload seconds
    '8' => 'iface(ether1',             // Interface
    '9' => 'infolp)login-page',        // Info login page
    '10' => 'idle=600',                // Idle timeout
    '11' => 'disable@!@yes'            // Live report disable
);
```

### ডিলিমিটার সিস্টেম
- `<|<` এবং `>|>` - ইউজার/পাসওয়ার্ড ডিলিমিটার
- `@|@`, `#|#`, `%`, `^`, `&`, `*`, `(`, `)`, `=` - কনফিগ ফিল্ড ডিলিমিটার

---

## ৭. ভাষা এবং লোকালাইজেশন

### সমর্থিত ভাষা
- **en.php** - English (ইংরেজি)
- **es.php** - Español (স্প্যানিশ)
- **id.php** - Bahasa Indonesia (ইন্দোনেশিয়ান)
- **tl.php** - Tagalog (তাগালগ)

### ভাষা পরিবর্তন প্রক্রিয়া

```
settings/setlang.php
   ↓
selected_language সেশনে সংরক্ষিত হয়
   ↓
lang/ ফোল্ডার থেকে ভাষা ফাইল লোড হয়
   ↓
$_lang_variable সমূহ UI তে ব্যবহৃত হয়
```

---

## ৮. থিম সিস্টেম

### উপলব্ধ থিম
- **blue** - নীল থিম (mikhmon-ui.blue.min.css)
- **dark** - কালো থিম (mikhmon-ui.dark.min.css)
- **green** - সবুজ থিম (mikhmon-ui.green.min.css)
- **light** - হালকা থিম (mikhmon-ui.light.min.css)
- **pink** - গোলাপি থিম (mikhmon-ui.pink.min.css)

### থিম পরিবর্তন প্রক্রিয়া

```
settings/settheme.php
   ↓
theme_name সেশনে সংরক্ষিত হয়
   ↓
headhtml.php এ থিম CSS লোড হয়:
   <link href="css/mikhmon-ui.{theme}.min.css">
```

---

## ৯. নিরাপত্তা বিবেচনা

### বর্তমান নিরাপত্তা ব্যবস্থা

⚠️ **সীমাবদ্ধতা:**
- হার্ডকোডেড ক্রেডেনশিয়াল config.php এ
- কোন ইনপুট ভ্যালিডেশন নেই
- কোন এনক্রিপশন নেই (RouterOS API ছাড়া)
- সিঙ্গেল অ্যাডমিন অ্যাকাউন্ট

✅ **মজবুত দিক:**
- RouterOS API SSL সংযোগ সাপোর্ট করে
- সেশন-ভিত্তিক প্রমাণীকরণ
- error_reporting(0) ত্রুটি লুকায়

### সুপারিশকৃত উন্নতি
- MySQL বাস্তবায়ন
- প্যাসওয়ার্ড এনক্রিপশন (bcrypt/password_hash)
- Input validation/sanitization
- CSRF প্রোটেকশন
- মাল্টি-ইউজার অ্যাডমিন সিস্টেম
- অডিট লগিং

---

## ১০. MySQL ইন্টিগ্রেশন পরিকল্পনা

### প্রয়োজনীয় টেবিল স্ট্রাকচার

#### 1. Admin Users Table
```sql
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    email VARCHAR(150),
    role ENUM('admin', 'operator', 'viewer'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP
);
```

#### 2. Router Profiles Table
```sql
CREATE TABLE routers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    ip_address VARCHAR(15),
    username VARCHAR(100),
    password_encrypted VARCHAR(255),
    hotspot_name VARCHAR(100),
    dns_server VARCHAR(100),
    currency VARCHAR(10),
    interface_name VARCHAR(50),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 3. User Logs Table
```sql
CREATE TABLE user_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    router_id INT,
    username VARCHAR(100),
    action VARCHAR(50),
    details TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (router_id) REFERENCES routers(id)
);
```

#### 4. Transaction History Table
```sql
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    router_id INT,
    voucher_code VARCHAR(50),
    profile_name VARCHAR(100),
    price DECIMAL(10, 2),
    currency VARCHAR(10),
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (router_id) REFERENCES routers(id)
);
```

---

## ১১. দ্রুত সমস্যা সমাধান

### সাধারণ সমস্যা

| সমস্যা | কারণ | সমাধান |
|--------|------|--------|
| রাউটারে সংযোগ ব্যর্থ | ভুল IP/পোর্ট | config.php চেক করুন |
| ভাউচার প্রিন্ট হচ্ছে না | qrious.min.js লোড না হওয়া | js/ ফোল্ডার চেক করুন |
| ভাষা পরিবর্তন কাজ করছে না | সেশন ইস্যু | সেশন সাফ হয়েছে কি চেক করুন |
| ট্রাফিক দেখাচ্ছে না | ইন্টারফেস নাম ভুল | settings এ interface চেক করুন |
| ব্যবহারকারী যোগ হচ্ছে না | প্রোফাইল নেই | প্রথমে প্রোফাইল তৈরি করুন |

---

## ১२. ফাইল রেফারেন্স চার্ট

### অগ্রাধিকার ফাইল
| প্রাধিকার | ফাইল | ক্রিয়া |
|----------|------|--------|
| 🔴 Critical | admin.php | প্রবেশ বিন্দু |
| 🔴 Critical | include/config.php | কনফিগারেশন |
| 🔴 Critical | lib/routeros_api.class.php | RouterOS সংযোগ |
| 🟠 Important | include/login.php | লগইন পৃষ্ঠা |
| 🟠 Important | include/readcfg.php | কনফিগ পার্সিং |
| 🟡 Useful | include/menu.php | নেভিগেশন |
| 🟡 Useful | hotspot/users.php | ইউজার ম্যানেজমেন্ট |

---

## ১३. বিকাশকারীদের জন্য

### নতুন বৈশিষ্ট্য সংযোজন করার ধাপ

```
1. include/menu.php তে নতুন মেনু আইটেম যোগ করুন

2. নতুন ফোল্ডার/ফাইল তৈরি করুন:
   module/index.php
   module/action.php

3. RouterOS API কল লিখুন (প্রয়োজনে):
   $API->comm("/path/to/command", array(...))

4. lang/*.php এ অনুবাদ যোগ করুন

5. css/mikhmon-ui.*.css এ স্টাইলিং যোগ করুন

6. js/mikhmon.js এ ক্লায়েন্ট-সাইড লজিক যোগ করুন
```

---

## ১४. পরবর্তী পদক্ষেপ

এই ডেভেলপমেন্ট রোডম্যাপ অনুসরণ করা হবে:

1. ✅ প্রজেক্ট বিশ্লেষণ সম্পন্ন
2. 🔄 MySQL ডাটাবেস স্ট্রাকচার তৈরি (পরবর্তী)
3. 🔄 DB সংযোগ ক্লাস লেখা (পরবর্তী)
4. 🔄 লিগেসি ফাংশন মোড়ানো (পরবর্তী)
5. 🔄 নিরাপত্তা উন্নতি (পরবর্তী)
6. 🔄 টেস্টিং এবং সমন্বয় (পরবর্তী)

---

## সংস্করণ তথ্য

- **ভার্সন**: 3.20
- **প্রকাশ তারিখ**: 06-30-2021
- **স্ট্যাটাস**: সক্রিয় উন্নয়ন
- **পরবর্তী আপডেট**: MySQL ইন্টিগ্রেশন সহ

---

**ডকুমেন্টেশন প্রস্তুতি**: এপ্রিল ২০২৬  
**ভাষা**: বাংলা (Bengali)  
**লক্ষ্য দর্শক**: বাঙ্গালি ডেভেলপার এবং অ্যাডমিনিস্ট্রেটর
