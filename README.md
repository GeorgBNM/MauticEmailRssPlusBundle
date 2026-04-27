# Mautic Email RSS Plus Bundle v7

<div align="center">
  <img src="Assets/rss-icon.png" alt="RSS Plus Icon" width="100"/>
  <br />
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4" alt="PHP 8.2+" />
  <img src="https://img.shields.io/badge/Mautic-7.x-4E5D9D" alt="Mautic 7" />
  <img src="https://img.shields.io/badge/Database-MySQL%20|%20MariaDB-00758F" alt="Database" />
  <img src="https://img.shields.io/badge/GrapeJS-Email%20Builder-9146FF" alt="GrapeJS" />
  <br />
  <img src="https://img.shields.io/badge/Feature-RSS%20Feeds-orange" alt="RSS Feeds" />
  <img src="https://img.shields.io/badge/Feature-Dynamic%20Tokens-blue" alt="Dynamic Tokens" />
  <img src="https://img.shields.io/badge/Feature-REST%20API-green" alt="REST API" />
  <img src="https://img.shields.io/badge/Feature-Pure%20HTML-yellow" alt="Pure HTML" />
  <br />

  **Dynamic RSS content injection for Mautic 7 with GrapeJS integration and runtime token parsing.**
  <br />
  **Version:** 1.2.0  
  **Compatibility:** Mautic 7.x | PHP 8.2+  
  **Author:** aczepod
  **License:** MIT  
</div>

---

## 🎯 Overview
The **Mautic Email RSS Plus Bundle v7** transforms how you handle RSS content in Mautic 7. Instead of static imports or manual copy-pasting, it introduces **dynamic runtime tokens** that fetch and render fresh RSS content exactly when an email is previewed or sent.

Built for Mautic 7's Symfony 7 architecture, it uses pure **email-safe HTML templates** (no MJML, Node.js, or external compilers required), integrates natively with the GrapeJS builder, and optimizes bulk sends with intelligent static caching.

---

## ✨ Key Features
- 🔄 **Runtime Token Parsing** – `{RssPlus:feed:X:template:Y}` resolves automatically via `EmailEvents::EMAIL_ON_SEND` & `EmailEvents::EMAIL_ON_DISPLAY`
- 📄 **Pure HTML Templates** – Table-based, inline-styled HTML ready for all email clients. Zero compilation step
- ⚡ **Bulk-Send Optimized** – Static RSS caching per PHP process prevents redundant HTTP calls during mass emails
- 🎨 **GrapeJS Native Integration** – Drag-and-drop token blocks directly in the Mautic email builder
- 🛡️ **Safe Fallback** – If a feed or template fails, the original token remains intact to prevent email delivery errors
- 🔌 **REST API** – Public endpoints for feeds, templates, and on-demand RSS fetching

---

## 📦 Requirements
| Component | Minimum Version |
|-----------|-----------------|
| **PHP**   | `>= 8.2` |
| **Mautic**| `7.x` (Symfony 7) |
| **Database** | MySQL / MariaDB |
| **PHP Extensions** | `xml`, `curl`, `mbstring`, `simplexml` |

---

## 🚀 Installation

### Method 1: Manual (Git)
```bash
cd /path/to/mautic/plugins
git clone https://github.com/aczepod/MauticEmailRssPlusBundle.git
```
### Method 2: Composer
```bash
composer require aczepod/mautic-email-rss-plus-bundle-v7:dev-mautic7-compatibility --prefer-source
```
### Finalize Setup
```bash
php bin/console cache:clear
php bin/console mautic:plugins:reload
php bin/console doctrine:migrations:migrate --no-interaction
```

Navigate to Settings → Plugins
Find RSS Plus and set Published to Yes
Click Save & Close


## 🔄 How It Works
Tokens are resolved at runtime using Mautic 7's event system:
 1. **Trigger:** Email is previewed (EMAIL_ON_DISPLAY) or queued for sending (EMAIL_ON_SEND)
 2. **Detection:** EmailTokenSubscriber scans content for {RssPlus:feed:X:template:Y}
 3. **Fetch:** RSS feed is retrieved (cached statically per process to avoid duplicate calls)
 4. **Render:** Each item is injected into the HTML template via str_replace
 5. **Replace:** Token is swapped with fully rendered HTML
 6. **Deliver:** Final email contains fresh, client-safe content


## 🤝 Contributing
Contributions are welcome! Please follow these steps:
1. Fork the repository
2. Create a feature branch (git checkout -b feature/amazing-feature)
3. Commit your changes (git commit -m 'Add amazing feature')
4. Push to the branch (git push origin feature/amazing-feature)
5. Open a Pull Request

## 📝 License
MIT License. See LICENSE file for details.
Built for Mautic – Open Source Marketing Automation
Made with ❤️ for the Mautic community
