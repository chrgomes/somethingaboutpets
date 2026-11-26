# Something About Pets

Website for Something About Pets: pet sitting, dog walking, daily visits and pet taxi in Auckland.
Plain PHP 8 + MySQL, built to run on GoDaddy cPanel (Linux) hosting. No build step.

## What's in it

**Public site**
- Home page (`index.php`) with services, about, how it works, FAQ, reviews and contact form
- Animated SVG cat and dog logo, paw-print trail, floating hearts on reviews, yarn-ball "back to top"
  (`assets/css/pets-anim.css`, `assets/js/pets-anim.js`). All animation turns off for visitors who prefer reduced motion.
- Terms, Payment & Subscription Policy and Privacy Policy (`terms.php`, `payments.php`, `privacy.php`), written for
  New Zealand (Consumer Guarantees Act, Privacy Act 2020). Cancellation notice and charges come from the config file.
  **Have the owner read them through before launch.**

**Clients** (`register.php`, `login.php`, `account/`)
- Create an account, reset a forgotten password
- **Pet profiles** for as many pets as they like: photos, breed, age, weight, sex, desexed, vaccinations with expiry dates
  and certificates, behaviour with dogs/cats/children, anxiety triggers, walking preferences, feeding, health and medication,
  vet details, emergency contact, signed emergency vet authorisation (with spending limit), microchip and council
  registration, and ID documents
- **Pay online** once a booking is confirmed: card (Stripe, including Apple Pay and Google Pay) or PayPal
- Book online (`book.php`):
  - Walks, visits and pet taxi use **time slots** inside the working hours
  - In-house sitting uses a **date range** (priced per night)
- See bookings and cancel online up to 24 hours before the start

**Admin** (`admin/`)
- **Today:** today's bookings with call and directions buttons, tomorrow, requests to approve, this month vs last month
  (bookings, earnings, new clients), popular services, busiest days, and alerts
- **Calendar:** month, week and day views, stays shown as bars, blocked days, quick approve/decline. Can also feed
  bookings into Google Calendar or Apple Calendar
- **Payments:** paid/unpaid on every booking, mark cash or bank-transfer payments as paid, one-click refunds
- **Pet profiles** for every client's pets, with warnings for expired vaccinations or missing emergency authorisation
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

### Online payments (Stripe + PayPal)
Payments are off until you add keys. Start with **test keys** and make a test booking, then switch to live keys.
1. **Stripe:** in the Stripe Dashboard, open Developers → API keys and copy the secret key (`sk_test_...`) into
   `payments.stripe.secret_key`. Then open Developers → Webhooks → Add endpoint with
   `https://yourdomain.com/webhooks/stripe.php` and the events `checkout.session.completed`,
   `checkout.session.async_payment_succeeded` and `charge.refunded`. Copy its signing secret (`whsec_...`) into
   `payments.stripe.webhook_secret`. The account's country and currency should be New Zealand / NZD.
2. **PayPal:** at developer.paypal.com, open Apps & Credentials, create an app and copy its Client ID and Secret into
   `payments.paypal`. Keep `mode` as `sandbox` for testing, then use the Live app's keys with `mode => 'live'`.
3. Set `payments.enabled` to `true`.
Card details are only entered on Stripe's or PayPal's own pages. The site never sees or stores them.

### Pet photos and documents
Set `uploads.path` to a folder **outside** `public_html` (e.g. `/home/CPANELUSER/sap-uploads`). Photos are resized to
1600px and their hidden data (such as GPS location) is removed. Files are only shown to the pet's owner and the admin.
If photo uploads fail, raise `upload_max_filesize` and `post_max_size` to `16M` in cPanel → **MultiPHP INI Editor**.

### Updating the site later
After uploading a new version, visit `install.php?key=YOUR_INSTALL_KEY` again (then delete it). It adds any new
database fields without touching existing data.

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
account/      client area (bookings, pets, payments)    admin/   owner area (today, calendar, bookings, payments)
api/slots.php availability for the booking page        file.php   serves pet photos/documents to owner + admin only
webhooks/     Stripe webhook                            storage/   default uploads folder (blocked from the web)
includes/     shared PHP: bootstrap, db, auth, booking rules, mail, layout (blocked from the web)
config/       config.sample.php (blocked from the web; real config.php is git-ignored)
setup/        database schema (blocked from the web)
assets/       css, js, images
```
