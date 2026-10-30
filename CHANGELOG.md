# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.2.11] - 2026-10-30

### Changed
- Released under the MIT License: this module is free and open-source software.

## [1.2.10] - 2026-10-05

### Fixed
- Hyva listing, detail page and slider no longer depend on Tailwind classes that are missing from the theme build: customer photos stay inside their 44px/40px avatar circle instead of rendering at full image size and causing horizontal scrolling, and the submission modal gets its backdrop and stacking order.
- The Luma testimonial slider now starts: the slider ID is reduced to letters, digits, dashes and underscores, so widget instances whose layout name contains a backslash initialise Swiper again.
- The Hyva listing submission modal shows the server message when a submission is rejected instead of always thanking the visitor, sends the honeypot field, validates the email address, uses translatable messages, labels every field, works as a dialog (focus moves in, Tab stays inside, Escape and Close return focus) and uses 16px inputs on phones.
- Star ratings expose their value to screen readers on the Hyva listing, detail page and both sliders; the Luma rating rows use role img.
- Rating pickers on the submit forms and the modal behave as a radio group: only the chosen star is checked, one star is in the tab order and the arrow, Home and End keys change the rating.
- The Hyva and Luma submit forms send the honeypot field the controller already checks, and the Luma form messages are translatable; a response that cannot be read is reported as an error instead of a success.
- Sliders with autoplay get a Pause/Start button, stop while focused, and do not autoplay when the visitor prefers reduced motion; the Hyva slider shows two columns from 640px, clamps quotes to three lines and no longer registers its pager twice.
- Tap targets of at least 44px for category pills, pagination, submit buttons, back links, slider arrows, slider dots and rating stars on both storefronts.
- Listing pages beyond the last page return the 404 page instead of repeating page one.
- Saving a testimonial in the admin keeps an empty category as NULL instead of 0.
- Customer initials use multibyte-safe functions, so names that start with a non-ASCII letter no longer show a broken character.

### Added
- Approve, Reject and Set as Pending mass actions in the Manage Testimonials grid.
- Store View field in the testimonial edit form, email validation and a non-negative Sort Order check.
