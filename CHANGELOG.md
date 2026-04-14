# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] — Initial release

### Added
- **Testimonials listing page** with category filter pills, smart
  pagination, star ratings, author avatars, and back-to-top link.
- **Individual testimonial detail pages** with SEO-friendly URLs,
  breadcrumbs, and configurable meta title / description.
- **Testimonial categories** with URL keys, descriptions, and
  per-category filtered listing pages.
- **Frontend submission form** with interactive star rating picker,
  honeypot bot protection, form-key CSRF validation, and admin
  approval workflow (Pending / Approved / Rejected statuses).
- **Testimonial Slider widget** — configurable carousel for CMS
  pages and blocks with autoplay, category filtering, featured-only
  mode, and responsive column counts.
- **JSON-LD structured data** — emits `ItemList` of `Review` schema
  nodes on the testimonials listing page for rich search snippets.
- **Admin grids** for testimonials and categories with mass-delete,
  status filters, and full CRUD.
- **System configuration** under Stores > Configuration > Panth
  Extensions > Testimonials for module enable/disable, page title,
  meta description, URL route, items per page, and submission
  settings.
- **Dual-theme support** — ships separate Hyva (Alpine.js / Tailwind)
  and Luma (vanilla JS / inline CSS) templates. Theme detection is
  handled automatically via Panth_Core.
- **Custom router** for clean SEO URLs (`/testimonials`,
  `/testimonials/{slug}`, `/testimonials/category/{slug}`,
  `/testimonials/page/{num}`, `/testimonials/submit`).

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 — 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
