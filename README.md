# PHP Any Form Handler

A self-hosted, open-source form submission handler that works with **any website**. Accept form submissions from anywhere, store them in your own database, and get email notifications - all without limits.

**The perfect alternative to Netlify Forms, Formspree, or other hosted form services.**

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-Support-yellow?style=for-the-badge&logo=buy-me-a-coffee)](https://www.buymeacoffee.com/abdeali.c)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1+-blue?style=for-the-badge&logo=php)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

---

## Why Use This?

| Feature | Netlify Forms | Formspree | **PHP Any Form Handler** |
|---------|--------------|-----------|-------------------------|
| Monthly submissions | 100 (free) | 50 (free) | **Unlimited** |
| Self-hosted | No | No | **Yes** |
| Data ownership | No | No | **100% yours** |
| Multi-tenant | No | No | **Yes** |
| Custom database | No | No | **Yes** |
| No vendor lock-in | No | No | **Yes** |
| Cost | $19+/mo | $10+/mo | **Free** |

---

## Features

- **Universal Form Handler** - Works with any HTML form from any website
- **Multi-Tenant Support** - Host multiple clients/projects with separate databases
- **Email Notifications** - Get notified via SMTP when forms are submitted
- **JSON Storage** - Flexible schema-less storage using MySQL JSON columns
- **Simple Dashboard** - View submissions with just a Tenant ID (no password needed)
- **CORS Support** - Accept submissions from any domain
- **Bot Protection** - Built-in honeypot field to catch spam bots
- **Hidden Field Overrides** - Override recipients dynamically per form
- **Secure by Design** - SQL injection protection, XSS prevention, input sanitization

---

## Quick Start

### 1. Clone & Install

```bash
git clone https://github.com/yourusername/php-any-form-handler.git
cd php-any-form-handler
composer install
```

### 2. Create Your Tenant Config

Copy the example config and customize it:

```bash
cp configs/example.json configs/mysite.json
```

Edit `configs/mysite.json`:

```json
{
    "tenant_id": "my-company",
    "database": {
        "host": "localhost",
        "port": 3306,
        "name": "forms_mycompany",
        "username": "db_user",
        "password": "db_password"
    },
    "email": {
        "enabled": true,
        "to": ["admin@mycompany.com"],
        "cc": [],
        "bcc": ["archive@mycompany.com"],
        "from_email": "forms@myserver.com",
        "from_name": "Contact Form",
        "subject_prefix": "[Website Contact]"
    },
    "smtp": {
        "host": "smtp.gmail.com",
        "port": 587,
        "encryption": "tls",
        "username": "your-email@gmail.com",
        "password": "your-app-password"
    },
    "allowed_origins": ["https://mycompany.com", "https://www.mycompany.com"]
}
```

### 3. Create Database

Create a MySQL database and run the schema:

```bash
mysql -u root -p -e "CREATE DATABASE forms_mycompany"
mysql -u root -p forms_mycompany < schema.sql
```

### 4. Configure Web Server

Point your web server's document root to the `public/` folder.

**Apache (.htaccess already included):**
```apache
DocumentRoot /var/www/php-any-form-handler/public
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name forms.myserver.com;
    root /var/www/php-any-form-handler/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Add Form to Your Website

```html
<form action="https://forms.myserver.com/index.php" method="POST">
    <!-- Required: Your config ID -->
    <input type="hidden" name="configId" value="mysite">

    <!-- Optional: Form identifier -->
    <input type="hidden" name="_formname" value="contact">

    <!-- Optional: Redirect after submission -->
    <input type="hidden" name="_redirect" value="https://mycompany.com/thank-you">

    <!-- Honeypot: Hide this with CSS -->
    <input type="text" name="_honeypot" style="display:none" tabindex="-1" autocomplete="off">

    <!-- Your form fields -->
    <input type="text" name="name" placeholder="Your Name" required>
    <input type="email" name="email" placeholder="Your Email" required>
    <input type="tel" name="phone" placeholder="Phone Number">
    <textarea name="message" placeholder="Your Message" required></textarea>

    <button type="submit">Send Message</button>
</form>
```

---

## Hidden Field Reference

Control form behavior with these hidden fields:

| Field | Required | Description |
|-------|----------|-------------|
| `configId` | **Yes** | Which tenant config to load |
| `_formname` | No | Identifier for this form (shown in dashboard) |
| `_redirect` | No | URL to redirect after successful submission |
| `_honeypot` | No | Bot trap - must be empty (hide with CSS) |
| `_subject` | No | Override email subject line |
| `tomail` | No | Override recipient email(s) |
| `cc` | No | Override CC recipients |
| `bcc` | No | Override BCC recipients |

---

## Dashboard

View your submissions at `/dashboard.php`. Just enter your Tenant ID - no password required.

**Dashboard URL:** `https://forms.myserver.com/dashboard.php`

The dashboard provides:
- List of all form submissions
- Filter by form name
- Pagination for large datasets
- View full submission details including IP and referrer

---

## Multi-Tenant Setup

Host multiple clients by creating separate config files:

```
configs/
├── client-a.json      → Tenant ID: "client-a-company"
├── client-b.json      → Tenant ID: "client-b-corp"
├── my-personal.json   → Tenant ID: "personal-site"
└── example.json       → Template (ignored by git)
```

Each tenant gets:
- Their own database (complete data isolation) or use a shared database
- Custom email settings
- Separate dashboard access
- Independent allowed origins

---

## API Response

### Success (JSON)
```json
{
    "success": true,
    "message": "Form submitted successfully"
}
```

### Error (JSON)
```json
{
    "success": false,
    "error": "Missing configId parameter"
}
```

### With Redirect
If `_redirect` is set, users are redirected instead of receiving JSON.

---

## Security

- **SQL Injection**: All queries use PDO prepared statements
- **XSS Prevention**: All output is escaped with `htmlspecialchars()`
- **Bot Protection**: Honeypot field catches automated submissions
- **CORS Control**: Per-tenant allowed origins configuration
- **Directory Traversal**: Config IDs are sanitized
- **Config Protection**: `.htaccess` blocks direct access to config files

---

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.2+ (with JSON support)
- Composer
- Web server (Apache/Nginx)

---

## Project Structure

```
php-any-form-handler/
├── public/                    # Web root
│   ├── index.php              # Form submission endpoint
│   ├── dashboard.php          # Tenant dashboard
│   └── assets/css/style.css   # Dashboard styles
├── src/
│   ├── ConfigLoader.php       # Loads tenant configurations
│   ├── Database.php           # MySQL/JSON storage
│   ├── FormHandler.php        # Core processing logic
│   ├── Mailer.php             # Email notifications
│   └── Response.php           # CORS & JSON responses
├── configs/
│   └── example.json           # Config template
├── templates/
│   ├── dashboard-login.php    # Login page
│   └── dashboard-list.php     # Submissions list
├── composer.json
├── schema.sql
└── README.md
```

---

## Contributing

Contributions are welcome! Here's how you can help:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Ideas for Contribution

- [ ] File upload support
- [ ] Webhook notifications (Slack, Discord)
- [ ] Rate limiting
- [ ] reCAPTCHA integration
- [ ] Export submissions to CSV
- [ ] Email templates customization
- [ ] PostgreSQL support
- [ ] REST API for submissions
- [ ] Admin dashboard for managing tenants

---

## Support the Project

If this project saves you money or time, consider buying me a coffee!

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-Support%20Development-yellow?style=for-the-badge&logo=buy-me-a-coffee)](https://www.buymeacoffee.com/abdeali.c)

Your support helps keep this project maintained and free for everyone.

---

## License

This project is open source and available under the [MIT License](LICENSE).

---

## Acknowledgments

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Email sending library
- Built as a free alternative to paid form handling services

---

**Made with determination to keep the web open and self-hosted.**
