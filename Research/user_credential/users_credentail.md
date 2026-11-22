

আপনার প্রজেক্টের ডাটাবেজ ও কনফিগারেশন থেকে যাচাই করা সব ইউজারনেম এবং পাসওয়ার্ডের তালিকা নিচে দেওয়া হলো:

---

### ১. ওয়েব অ্যাপ্লিকেশন লগইন (Web App Login)
- **Login URL:** [http://localhost:8000/login](http://localhost:8000/login)

| Role | Name | Email (Username) | Password |
| :--- | :--- | :--- | :--- |
| **Admin** | Admin | `admin@test.com` | `password` |
| **Receptionist** | Receptionist | `receptionist@test.com` | `password` |
| **Staff** | Staff | `staff@test.com` | `password` |
| **Visitor** | Visitor | `visitor@test.com` | `password` |

---

### ২. ডাটাবেজ ও phpMyAdmin ক্রেডেনশিয়ালস
- **phpMyAdmin URL:** [http://localhost:8080](http://localhost:8080)

| Service | Host / Port | Username | Password | Database Name |
| :--- | :--- | :--- | :--- | :--- |
| **MySQL (Root)** | `127.0.0.1:3307` | `root` | `root_password` | `pragati_ai_db_2` |
| **MySQL (App User)** | `127.0.0.1:3307` | `pragati_ai_user` | `pragati_ai_password` | `pragati_ai_db_2` |

---

### ৩. রেডিস (Redis)
- **Host / Port:** `127.0.0.1:6380`
- **Password:** *None* (পাসওয়ার্ড ছাড়া)