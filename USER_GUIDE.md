# Panth Testimonials - User Guide

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
| **Meta Description** | (built-in default text) | Meta description for the listing page |
| **URL Route** | testimonials | Base route for all testimonial URLs (e.g. `yourstore.com/testimonials`) |
| **Enable Submit Form** | Yes | Show the frontend submission form |
| **Require Approval** | Yes | Yes saves submissions as Pending, so an admin must approve them before they appear on the frontend. No saves them as Approved, so they appear immediately |
| **Assign Submissions to Store View** | No | Yes saves a submission under the store view it was sent from, so it is shown only there. No saves it under store ID 0 (all store views) |
| **Items Per Page** | 12 | Number of testimonials shown per listing page |

---

## 3. Managing testimonials

### Admin grid

Navigate to **Panth Extensions > Testimonials > Manage Testimonials**.

The grid shows all testimonials with columns for ID, customer name,
title, rating, status, featured flag and created date. You can
filter, sort, and mass-delete from this grid.

### Adding / editing a testimonial

Click **Add New Testimonial** or **Edit** on an existing row. The
form includes:

- **Customer Name** (required)
- **Email**
- **Job Title** (optional)
- **Company** (optional)
- **Rating (1-5)** (required) - values outside 1 to 5 are clamped
- **Testimonial Title** (required) - headline for the testimonial
- **Full Content** (required) - full testimonial text
- **Short Excerpt (for cards)** - excerpt shown on listing cards
- **URL Key** - auto-generated from customer name and title if left blank
- **Status** - Pending / Approved / Rejected
- **Featured** - featured testimonials can be filtered in the widget
- **Category**
- **Sort Order** - lower numbers appear first

The database also has `customer_image`, `store_id`, `meta_title` and
`meta_description` columns that the storefront reads, but the admin
form has no fields for them. Records saved from the admin keep store
ID 0 (all store views).

### Statuses

| Status | Value | Meaning |
|---|---|---|
| Pending | 0 | Awaiting admin review (default for frontend submissions) |
| Approved | 1 | Visible on the frontend |
| Rejected | 2 | Hidden from the frontend |

---

## 4. Managing categories

Navigate to **Panth Extensions > Testimonials > Manage Categories**.

Categories let you group testimonials. Each category has:

- **Name** (required)
- **URL Key** - auto-generated from name if blank
- **Description** - optional; used as the meta description for the category page
- **Active** - only active categories appear on the frontend
- **Sort Order** - controls display order of category pills

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

All pages include breadcrumbs and meta tags. When **Enable Module** is
No, these pages return 404, including the standard `testimonials/*`
routes. Only testimonials and categories assigned to the current store
view or to store ID 0 are shown.

---

## 6. Testimonial Slider widget

The module ships a CMS widget called **Panth Testimonial Slider**.

### Adding via CMS

1. Edit any CMS page or block
2. Click **Insert Widget**
3. Select **Panth Testimonial Slider**
4. Configure parameters:

| Parameter | Default | Description |
|---|---|---|
| Widget Title | (empty) | Slider heading; "What Our Customers Say" is shown when empty |
| Number of Testimonials | 8 | Max testimonials to show, in random order |
| Show Rating | Yes | Display star ratings |
| Show Company | Yes | Display company name |
| Show Image | Yes | Display customer avatar |
| Autoplay | Yes | Auto-advance slides |
| Autoplay Interval | 5000 | Milliseconds between slides |
| Category | (all) | Filter by specific category |
| Featured Only | No | Only show featured testimonials |

The widget auto-detects the active theme and renders the Hyva or
Luma template accordingly.

---

## 7. SEO structured data

The module emits a JSON-LD `ItemList` containing up to 50 approved
`Review` nodes on the listing, category and detail pages. The
`itemReviewed` type, name and optional `@id` are set under
**Structured Data (JSON-LD)** in the configuration.

The schema is emitted via `Panth\Testimonials\Block\Schema` and
rendered by `schema.phtml` at the end of the page body.

---

## 8. Frontend submission form

When enabled, customers can submit testimonials via
`/testimonials/submit`. The form includes:

- Name, email, title, company fields
- Interactive star rating picker
- Testimonial title and content
- Honeypot field (`website_url`) on the Hyva listing modal
- Form key validation; the save action accepts POST requests only

The server requires name, a valid email, title and content, limits
name, email, title, job title and company to 255 characters and the
testimonial text to 5000 characters, strips HTML tags and accepts at
most 5 submissions per client IP address per hour. With Require
Approval set to Yes (default) submissions are saved with status Pending
and appear on the frontend only after an admin approves them; with No
they are saved as Approved. Submissions get store ID 0 unless Assign
Submissions to Store View is Yes, in which case they get the ID of the
store view they were sent from.

---

## 9. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Testimonials page returns 404 | Module disabled or cache stale | Check `bin/magento module:status Panth_Testimonials`; flush cache |
| Submissions not appearing | Submissions are saved as Pending | Go to admin grid and change status to Approved |
| Widget not rendering | Theme detection issue | Check Panth_Core display mode setting |
| Categories not showing | No active categories | Create at least one active category in admin |

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
