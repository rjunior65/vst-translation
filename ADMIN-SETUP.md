# VST Admin Panel — Setup (HostGator)

This adds a **private admin panel** (one administrator, email + password) that
manages the **Team members** and feeds them to the public website through a
database. The public site keeps working even before you do any of this — it
simply falls back to the built-in list until the database is connected.

> Requires a HostGator plan with **PHP + MySQL** (all standard shared plans have it).

---

## 1. Upload the files
Upload the whole project into `public_html` as usual (File Manager or FTP).
New folders included: `admin/`, `api/`, `includes/`, `sql/`, `tools/`, `uploads/`.

## 2. Create the database (cPanel → **MySQL® Databases**)
1. **Create a database**, e.g. `vst` (final name looks like `cpaneluser_vst`).
2. **Create a user** + password, e.g. `vstadmin` (`cpaneluser_vstadmin`).
3. **Add the user to the database** with **ALL PRIVILEGES**.
4. Write down the 3 values: database name, user, password.

## 3. Import the tables (cPanel → **phpMyAdmin**)
1. Select your database on the left.
2. Tab **Import** → choose `sql/schema.sql` → **Go**.
3. Import again with `sql/seed.sql` → this loads the current 12 team members.

## 4. Enter your database details
Edit **`includes/config.php`** and fill:
```php
define('DB_NAME', 'cpaneluser_vst');
define('DB_USER', 'cpaneluser_vstadmin');
define('DB_PASS', 'your-database-password');
```
(`DB_HOST` stays `localhost` on HostGator.)

## 5. Make the uploads folders writable
In File Manager, set permissions of **`uploads/`**, **`uploads/team/`** and
**`uploads/tourism/`** to **755** (or 775 if 755 doesn't allow uploads).

## 6. Create your admin login (one time)
1. Open `https://YOURDOMAIN/tools/create-admin.php` in the browser.
2. Enter your **name, email and password** (min 8 chars) → **Create admin**.
3. **Delete the file `tools/create-admin.php`** afterwards (important).

## 7. Turn on SSL / HTTPS
1. cPanel → **SSL/TLS Status** → run **AutoSSL** (free).
2. In the root **`.htaccess`**, uncomment the 3 "Force HTTPS" lines.

## 8. Done — use it
- Admin panel: `https://YOURDOMAIN/admin/`
- Sign in with your email + password.
- **Team** — add / edit / delete members, upload photos.
- **Tourism** — add / edit / delete destinations, and manage the photo gallery.
- **Contacts** — edit address, phones, WhatsApp, email, hours and social links.
- Changes appear on the public site immediately.

---

## How it fails safe
If the database is ever unreachable, the public website automatically falls back
to the **built-in content** in `js/script.js` and `index.html` (team, tourism and
contacts) — visitors never see an error. On HostGator with the database connected,
the live data from the admin panel is used instead.

## Security included
- Passwords stored hashed (bcrypt), never in plain text.
- All database access uses prepared statements (no SQL injection).
- CSRF tokens on every form; login lockout after repeated failures.
- `includes/` and `sql/` blocked from the web; uploads folder can't run code.
- Turn on HTTPS (step 7) so logins travel encrypted.

## Backups
Periodically export the database: phpMyAdmin → your DB → **Export** → **Go**.
Keep a copy of the `uploads/` folder too.
