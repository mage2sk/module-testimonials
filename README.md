# Panth Testimonials

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![License](https://img.shields.io/badge/License-Proprietary-red)]()

**Advanced Testimonials module** for Magento 2 with slider widget,
individual testimonial pages, categories, SEO structured data
(JSON-LD), and a frontend customer submission form. Supports both
Hyva and Luma themes out of the box.

---

## Features

- **Testimonials listing page** with category filter pills, smart
  pagination, star ratings, and author avatars.
- **Individual testimonial detail pages** with SEO-friendly URLs,
  breadcrumbs, and meta tags.
- **Testimonial categories** with URL keys — each category gets its
  own filterable listing page.
- **Frontend submission form** with star rating picker, honeypot
  bot protection, form-key validation, and admin approval workflow.
- **Testimonial Slider widget** — drop a configurable carousel into
  any CMS page, block, or layout XML. Supports autoplay, category
  filtering, featured-only mode, and responsive column counts.
- **JSON-LD structured data** — emits an `ItemList` of `Review`
  schema nodes on the testimonials listing page for rich snippets.
- **Admin grids** for testimonials and categories with mass-delete,
  inline editing, and status/featured filters.
- **System configuration** — enable/disable module, set page title,
  meta description, URL route, items per page, submission settings.
- **Dual-theme support** — ships separate Hyva (Alpine.js /
  Tailwind) and Luma (vanilla JS / inline CSS) templates.

---

## Installation

```bash
composer require mage2kishan/module-testimonials
bin/magento module:enable Panth_Core Panth_Testimonials
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Verify

```bash
bin/magento module:status Panth_Testimonials
# Module is enabled
```

---

## Requirements

| | Required |
|---|---|
| Magento | 2.4.4 — 2.4.8 (Open Source / Commerce / Cloud) |
| PHP | 8.1 / 8.2 / 8.3 / 8.4 |
| Panth_Core | ^1.0 (pulled in automatically via Composer) |

---

## Configuration

Navigate to **Stores > Configuration > Panth Extensions > Testimonials**.

| Setting | Default | Description |
|---|---|---|
| Enable Module | Yes | Enable or disable the testimonials frontend pages |
| Page Title | Customer Testimonials | H1 and meta title for the listing page |
| Meta Description | (auto-generated) | Meta description for the listing page |
| URL Route | testimonials | Base route for all testimonial URLs |
| Enable Submission | Yes | Show the frontend submission form |
| Require Approval | Yes | New submissions start as Pending and must be approved by an admin |
| Items Per Page | 12 | Number of testimonials per listing page |

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |

---

## License

Proprietary — see `LICENSE.txt`. Distribution is restricted to the
Adobe Commerce Marketplace.

---

## About the developer

Built and maintained by **Kishan Savaliya** — https://kishansavaliya.com.
Builds high-quality, security-focused Magento 2 extensions and themes
for both Hyva and Luma storefronts.
