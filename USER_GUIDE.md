# Panth Testimonials — User Guide

This guide walks store administrators through installing, configuring,
and using the Panth Testimonials extension for Magento 2.

---

## Table of contents

1. [Installation](#1-installation)
2. [Configuration](#2-configuration)
3. [Managing testimonials](#3-managing-testimonials)
4. [Managing categories](#4-managing-categories)
5. [Frontend pages](#5-frontend-pages)
6. [Testimonial Slider widget](#6-testimonial-slider-widget)
7. [SEO structured data](#7-seo-structured-data)
8. [Frontend submission form](#8-frontend-submission-form)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Installation

### Composer (recommended)

```bash
composer require mage2kishan/module-testimonials
bin/magento module:enable Panth_Core Panth_Testimonials
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual zip

1. Download the extension package zip
2. Extract to `app/code/Panth/Testimonials`
3. Run the same `module:enable ... cache:flush` commands above

---

## 2. Configuration

Navigate to **Stores > Configuration > Panth Extensions > Testimonials**.

| Setting | Default | Description |
|---|---|---|
| **Enable Module** | Yes | Enable or disable the testimonials frontend pages |
| **Page Title** | Customer Testimonials | H1 and meta title for the listing page |
| **Meta Description** | (auto-generated) | Meta description for the listing page |
| **URL Route** | testimonials | Base route for all testimonial URLs (e.g. `yourstore.com/testimonials`) |
| **Enable Submission** | Yes | Show the frontend submission form |
| **Require Approval** | Yes | New submissions start as Pending; admin must approve before they appear on the frontend |
| **Items Per Page** | 12 | Number of testimonials shown per listing page |

---

## 3. Managing testimonials

### Admin grid

Navigate to **Panth Infotech > Testimonials > Manage Testimonials**.

The grid shows all testimonials with columns for ID, customer name,
title, rating, status, featured flag, category, and dates. You can
filter, sort, and mass-delete from this grid.

### Adding / editing a testimonial

Click **Add New Testimonial** or **Edit** on an existing row. The
form includes:

- **Customer Name** (required)
- **Customer Email**
- **Title / Position** — job title (optional)
- **Company** — company name (optional)
- **Customer Image** — photo path (optional)
- **Rating** — 1 to 5 stars
- **Title** (required) — headline for the testimonial
- **Content** (required) — full testimonial text
- **Short Content** — excerpt shown on listing cards
- **URL Key** — auto-generated from title if left blank
- **Status** — Pending / Approved / Rejected
- **Is Featured** — featured testimonials can be filtered in the widget
- **Sort Order** — lower numbers appear first
- **Store ID** — 0 for All Store Views
- **Meta Title / Meta Description** — SEO fields for the detail page

### Statuses

| Status | Value | Meaning |
|---|---|---|
| Pending | 0 | Awaiting admin review (default for frontend submissions) |
| Approved | 1 | Visible on the frontend |
| Rejected | 2 | Hidden from the frontend |

---

## 4. Managing categories

Navigate to **Panth Infotech > Testimonials > Manage Categories**.

Categories let you group testimonials. Each category has:

- **Name** (required)
- **URL Key** — auto-generated from name if blank
- **Description** — optional; used as the meta description for the category page
- **Is Active** — only active categories appear on the frontend
- **Sort Order** — controls display order of category pills
- **Store ID** — 0 for All Store Views

---

## 5. Frontend pages

The module registers a custom router that creates SEO-friendly URLs:

| URL | Page |
|---|---|
| `/testimonials` | Listing page with all approved testimonials |
| `/testimonials/page/2` | Paginated listing |
| `/testimonials/submit` | Frontend submission form |
| `/testimonials/category/{url_key}` | Category filtered listing |
| `/testimonials/{url_key}` | Individual testimonial detail page |

All pages include breadcrumbs and proper meta tags.

---

## 6. Testimonial Slider widget

The module ships a CMS widget called **Testimonial Slider**.

### Adding via CMS

1. Edit any CMS page or block
2. Click **Insert Widget**
3. Select **Testimonial Slider**
4. Configure parameters:

| Parameter | Default | Description |
|---|---|---|
| Title | What Our Customers Say | Slider heading |
| Count | 8 | Max testimonials to show |
| Show Rating | Yes | Display star ratings |
| Show Company | Yes | Display company name |
| Show Image | Yes | Display customer avatar |
| Autoplay | Yes | Auto-advance slides |
| Autoplay Interval | 5000 | Milliseconds between slides |
| Category ID | (all) | Filter by specific category |
| Featured Only | No | Only show featured testimonials |

The widget auto-detects the active theme and renders the Hyva or
Luma template accordingly.

---

## 7. SEO structured data

The module emits JSON-LD `ItemList` schema containing `Review` nodes
on the testimonials listing page. This helps search engines display
rich snippets with star ratings.

The schema is emitted via `Panth\Testimonials\Block\Schema` and
rendered in `schema.phtml` in the page head.

---

## 8. Frontend submission form

When enabled, customers can submit testimonials via
`/testimonials/submit`. The form includes:

- Name, email, title, company fields
- Interactive star rating picker
- Testimonial title and content
- Photo upload (Luma template only)
- Honeypot bot protection
- Form-key CSRF validation

Submissions default to Pending status when approval is required.

---

## 9. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Testimonials page returns 404 | Module disabled or cache stale | Check `bin/magento module:status Panth_Testimonials`; flush cache |
| Submissions not appearing | Require Approval is enabled | Go to admin grid and change status to Approved |
| Widget not rendering | Theme detection issue | Check Panth_Core display mode setting |
| Categories not showing | No active categories | Create at least one active category in admin |

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
