# Something About Pets

Website for Something About Pets: pet sitting, dog walking, daily visits and pet taxi in Auckland.
Plain PHP 8 + MySQL, built to run on GoDaddy cPanel (Linux) hosting. No build step.

## What's in it

**Public site**
- Home page (`index.php`) with services, about, how it works, FAQ, reviews and contact form
- Animated SVG cat and dog logo, paw-print trail, floating hearts on reviews, yarn-ball "back to top"
  (`assets/css/pets-anim.css`, `assets/js/pets-anim.js`). All animation turns off for visitors who prefer reduced motion.
- Privacy policy and terms (`privacy.php`, `terms.php`). **These are drafts. Review them before launch.**

**Clients** (`register.php`, `login.php`, `account/`)
- Create an account, reset a forgotten password
- Add pets with care notes
- Book online (`book.php`):
  - Walks, visits and pet taxi use **time slots** inside the working hours
  - In-house sitting uses a **date range** (priced per night)
- See bookings and cancel online up to 24 hours before the start

**Admin** (`admin/`)
- Overview: requests waiting for approval and the next 7 days
- Approve, decline, complete or cancel bookings. The client is emailed automatically.
- Weekly working hours and blocked days (holidays)
- Services and prices
- Clients with their pets and booking history
- Contact form messages

### How availability works
There is one sitter. A pending or confirmed booking makes that time unavailable to everyone else.
A 15-minute travel buffer is kept between bookings. An in-house sitting stay blocks those whole days.
Clients can book from 12 hours ahead up to 90 days out. All of these numbers are in the config file.

## Deploying to GoDaddy (cPanel)

1. **Create the database.** In cPanel, open **MySQL Databases** and:
   - create a database (e.g. `cpuser_pets`)
   - create a user with a strong password
   - add the user to the database with **ALL PRIVILEGES**
2. **Create a "from" email address**, e.g. `no-reply@yourdomain.com`, in cPanel under **Email Accounts**.
3. **Upload the site** into `public_html`, using either:
   - **File Manager**: upload a zip of this repo and extract it, or
   - **Git Version Control** in cPanel: clone this repo and deploy it to `public_html`
   Don't upload `.git`. If you deploy with Git, the `.htaccess` file blocks it anyway.
4. **Create the config file.** Copy `config/config.sample.php` to **`sap-config.php` in your home folder**,
   one level *above* `public_html` (e.g. `/home/cpuser/sap-config.php`). Fill in:
   - `app.url`: `https://yourdomain.com`
   - `app.install_key`: a long random string
   - `db`: the database name, user and password from step 1
   - `mail.from_email`: the address from step 2
   - `business.email`: where booking notifications should go
   - `business.social`: Facebook and Instagram links (empty links are hidden)
5. **Run the installer.** Visit `https://yourdomain.com/install.php?key=YOUR_INSTALL_KEY`. It creates the tables and the
   4 default services, then asks for the owner's admin login.
6. **Delete `install.php`** from the server.
7. Log in, open **Admin → Services & prices** to add prices, and **Admin → Availability** to set hours.
8. **Turn on SSL.** In cPanel, **SSL/TLS Status** sets up a free certificate. Once it's active, uncomment the
   HTTPS redirect lines in `.htaccess`.

### More reliable email (optional)
By default emails go out through PHP `mail()`, which works on GoDaddy cPanel. Some mail can land in spam that way.
For better delivery, use SMTP:
1. Install PHPMailer. Either run `composer require phpmailer/phpmailer` in cPanel **Terminal** inside `public_html`,
   or run it on your computer and upload the `vendor/` folder.
2. Set `mail.smtp.enabled` to `true` and fill in the SMTP settings in `sap-config.php`.

## Local development

```bash
# Create a config that uses SQLite (no MySQL needed)
cp config/config.sample.php config/config.php
# In config/config.php set:
#   'db' => ['dsn' => 'sqlite:' . __DIR__ . '/../dev.sqlite', 'user' => '', 'pass' => ''],
#   'app' => [... 'url' => 'http://localhost:8080', 'install_key' => 'local-dev-key-123456', 'debug' => true]
php -S localhost:8080
# Then open http://localhost:8080/install.php?key=local-dev-key-123456
```
When you run it locally, emails are written to the PHP server log instead of being sent.

## Files

```
index.php, book.php, login.php, register.php, ...   public pages
account/      client area          admin/   owner area
api/slots.php availability for the booking page
includes/     shared PHP: bootstrap, db, auth, booking rules, mail, layout (blocked from the web)
config/       config.sample.php (blocked from the web; real config.php is git-ignored)
setup/        database schema (blocked from the web)
assets/       css, js, images
```
