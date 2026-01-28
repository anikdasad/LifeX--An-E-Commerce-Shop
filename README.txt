LifeX - PHP/MySQL E‑Commerce (Apple‑style landing)

✅ Features (from spec)
- Apple-like landing section: fade-up, parallax, text reveal, scale-in cards
- Product catalog with categories + search
- Category filter (AJAX) + Apple-style "Load More" (AJAX)
- Auth: register/login/logout (password_hash / password_verify)
- Cart: add/remove/update + subtotal
- Multi-step checkout: address + shipping + payment method
- Payments (demo/test): Cash on Delivery, bKash, Nagad, Bank
- Orders: history for users, status management for Admin/Employee
- Admin dashboard: sales summary, product CRUD, category CRUD, users, orders
- Invoices (HTML printable)
- Security: prepared statements + basic CSRF token on forms

------------------------------------------------------------
1) XAMPP Setup
------------------------------------------------------------
1. Start Apache + MySQL from XAMPP Control Panel
2. Copy this folder "LifeX" into:
   C:\xampp\htdocs\LifeX
3. Create database:
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create DB: lifex
   - Import: database/lifex.sql

4. Configure DB if needed:
   config/db.php  (default user: root, password: "")

5. Create first Admin:
   - Open: http://localhost/LifeX/setup/create-admin.php
   - It will create:
     Email: admin@lifex.test
     Password: Admin@123
   - Then DELETE the /setup folder for security.

------------------------------------------------------------
2) URLs
------------------------------------------------------------
Home:          http://localhost/LifeX/
Cart:          http://localhost/LifeX/cart.php
Login:         http://localhost/LifeX/app/auth/login.php
Register:      http://localhost/LifeX/app/auth/register.php
My Orders:     http://localhost/LifeX/orders.php
Admin:         http://localhost/LifeX/admin/dashboard.php

------------------------------------------------------------
3) Folder Structure (important)
------------------------------------------------------------
admin/      -> dashboard + management pages
api/        -> AJAX endpoints (load more / filter)
app/        -> auth, cart actions, checkout, payments, helpers
assets/     -> CSS/JS
config/     -> DB config
database/   -> SQL schema + seed sample products
setup/      -> one-time admin creator (delete after use)

------------------------------------------------------------
Ownership footer:
Coded by DreamWas (Anik Kumar Das) • Address: Asia
