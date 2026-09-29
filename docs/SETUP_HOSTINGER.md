# Setting Up Bedrock Lapidary on Hostinger

This site now has a real PHP/MySQL backend. Everything is written and ready,
but it needs your actual Hostinger account details plugged in before it
will work. Follow this in order.

## 1. Create the database

1. In hPanel, go to **Databases > MySQL Databases**.
2. Create a new database. Hostinger will give it a name like `u123456789_bedrock`.
3. Create a database user and password (or use the one auto-created with the database).
4. Note down: database name, username, password, and host (almost always `localhost` on Hostinger shared plans).

## 2. Run the schema and seed data

1. In hPanel, open **phpMyAdmin** for your new database.
2. Click the **SQL** tab.
3. Run these files from the `sql/` folder, in this order (open each, copy the full contents, paste, run):
   1. `schema.sql`
   2. `patches_combined.sql` (adds guides, variants and other tables and columns; safe to re-run)
   3. `seed_products.sql` (417 products with the final Bedrock descriptions)
   4. `seed_product_variants.sql` (variants; re-running `seed_products.sql` clears them, so run this after it)
   5. `content_updates_combined.sql` (re-applies the descriptions, fixes vendors, fills the vendor list)
   6. `seo/guides_seo_batch_01.sql` (buying guides)
4. Confirm it worked: the `products` table should have 417 rows and there should be no `reviews` table.
5. Updating a site that is already live: run only `sql/content_updates_combined.sql`.

## 3. Fill in `api/config.php`

Open `api/config.php` and replace every placeholder value:

- `DB_NAME`, `DB_USER`, `DB_PASS`, this is the info from step 1
- `SITE_URL`, your real domain once it's connected, e.g. `https://bedrocklapidary.com`

Leave the email settings (`SMTP_USER`, `SMTP_PASS`, `STORE_OWNER_EMAIL`) as
placeholders for now if you don't have the domain yet, see step 5.

## 4. Upload the files

Upload the entire site folder to your Hostinger `public_html` directory via
hPanel's File Manager or FTP. The folder structure should end up as:

```
public_html/
  index.html, product.html, cart.html, etc.
  assets/
  api/
  admin/
  sql/, docs/, tools/   (blocked from the web; safe to leave, or keep them out of public_html)
```

## 5. Set up real email sending (once you have the domain)

1. In hPanel, go to **Emails > Email Accounts** and create a mailbox on your
   domain, for example `orders@bedrocklapidary.com`.
2. Back in `api/config.php`, fill in:
   - `SMTP_USER` = the full mailbox address you just created
   - `SMTP_PASS` = that mailbox's password
   - `STORE_OWNER_EMAIL` = where you want order/lead notifications to land (can be the same mailbox)
3. `SMTP_HOST` and `SMTP_PORT` are already set to Hostinger's standard values and shouldn't need changes.

**Until this step is done**, orders and popup signups still save correctly
to the database, they just won't trigger an actual email. Nothing is lost,
you can always look up orders directly in phpMyAdmin (`orders` and
`order_items` tables) even before email is configured.

## 6. Log into the admin panel

Go to `https://yourdomain.com/admin/login.php`.

- Username: `admin`
- Password: `Admin@1234`

**Change this password soon after your first login.** To set a new one:
1. Generate a new hash by running this PHP snippet anywhere you can execute
   PHP (or ask me to generate one for a specific password):
   ```php
   <?php echo password_hash('YourNewPassword', PASSWORD_DEFAULT);
   ```
2. In phpMyAdmin, run:
   ```sql
   UPDATE admin_users SET password_hash = 'paste-the-generated-hash-here' WHERE username = 'admin';
   ```

## 7. Set up your promo codes

The popup ("Save 10% on Your First Order") won't send a real code to anyone
until you create one:

1. Log into `/admin/promos.php`.
2. Click **New Promo Code**.
3. Set: a code (e.g. `WELCOME10`), discount percentage, scope = **Sitewide**,
   source = **First-order popup**, and a start/end window.
4. Save. From then on, anyone who signs up through the popup gets emailed
   this code automatically.

Per-product promo codes (the "Save X% on This Product" banner) work the
same way, just set scope = **Specific product(s)** and pick which ones.

## 8. What's a placeholder vs. what's real

**Fully real and working once the steps above are done:**
- Product catalog, editable live from the admin panel
- Order submission, saved to the database and emailed
- Promo code validation, redemption, and one-per-customer enforcement
- The discount popup and per-product promo banners

**Still placeholder until you provide real values:**
- `SITE_URL` in `config.php`
- All the SMTP/email credentials in `config.php`
- The admin password (works as-is, but change it)

Nothing will silently break if you deploy before finishing steps 3 or 5, the
site will show a clear red banner if `config.php`'s database credentials are
wrong, and orders will still save to the database even without email
configured, so no order requests are ever lost.
