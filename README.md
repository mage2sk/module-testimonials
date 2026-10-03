# Magento 2 Testimonials

Panth_Testimonials adds customer testimonials to a Magento 2 store. It stores testimonials and testimonial categories in their own database tables, manages them from two admin grids, and publishes them on the storefront as a paginated listing page, category pages, one detail page per testimonial and a "Panth Testimonial Slider" widget for CMS pages and layout containers. Customers can submit a testimonial through a storefront form; submissions are saved as pending until an administrator approves them, or published at once when Require Approval is set to No. The listing, category and detail pages emit schema.org JSON-LD (`ItemList` of `Review` nodes).

The module adds a custom frontend router for the testimonial URLs, an admin menu under "Panth Extensions", a system configuration section and two database tables. It does not change any core Magento behaviour. It ships separate templates for Hyva (Alpine.js, Hyva snap slider) and Luma (Swiper) and picks the template set through `Panth_Core`.

Product page: [kishansavaliya.com/magento-2-testimonials.html](https://kishansavaliya.com/magento-2-testimonials.html)

## Features

- Testimonial records with customer name, email, job title, company, image path, 1 to 5 star rating, title, full content, short excerpt, URL key, status (Pending, Approved, Rejected), featured flag, sort order, store ID, meta title and meta description.
- Testimonial categories with name, URL key, description, active flag, sort order and store ID.
- Storefront listing page at `/<route>` (default `/testimonials`) with category links, pagination (`/<route>/page/<n>`), breadcrumbs, configurable page title and meta description.
- Category pages at `/<route>/category/<url_key>` and detail pages at `/<route>/<url_key>` served by a custom router; only active categories and approved testimonials resolve; other URLs below the route return the 404 page.
- Detail page title and meta description taken from the testimonial's meta title and meta description, falling back to the testimonial title.
- Frontend submission form at `/<route>/submit`, posting to `testimonials/submit/save` (POST only) with form key validation, plain-text cleaning of every text field, email validation, length limits and a limit of 5 submissions per client IP per hour. Submissions are saved with status Pending.
- "Panth Testimonial Slider" widget with title, category, count, featured-only, rating, company, image and autoplay parameters. Testimonials are shown in random order.
- JSON-LD `ItemList` of up to 50 approved `Review` nodes on the listing, category and detail pages, with a configurable `itemReviewed` type, name and optional `@id`.
- Admin grids for testimonials and categories with filters, column controls, bookmarks and mass delete; edit forms with automatic URL key generation.
- Per store view configuration; testimonials and categories with store ID 0 are shown on every store view.
- Hyva templates (Alpine.js, `x-snap-slider`) and Luma templates (Swiper from `Panth_Core`, initialised lazily with `IntersectionObserver`).

## Compatibility

| Platform | Versions |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 |
| Adobe Commerce | 2.4.4 to 2.4.8 |
| PHP | 8.1, 8.2, 8.3, 8.4 |
| Themes | Hyva, Luma |

Composer constraints on Magento packages: `magento/framework ^103.0`, `magento/module-backend ^102.0`, `magento/module-config ^101.0`, `magento/module-store ^101.0`, `magento/module-ui ^101.0`, `magento/module-widget ^101.0`, `magento/module-cms ^104.0`.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8
- PHP 8.1, 8.2, 8.3 or 8.4 (`~8.1.0||~8.2.0||~8.3.0||~8.4.0`)
- `mage2kishan/module-core` `^1.0` (`Panth_Core`), installed automatically by Composer
- `Magento_Cms`, `Magento_Store` and `Magento_Widget` (part of every Magento installation)

## Installation

```bash
composer require mage2kishan/module-testimonials
bin/magento module:enable Panth_Core Panth_Testimonials
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

`setup:di:compile` is only needed in production mode. `setup:static-content:deploy` is needed because the module ships CSS under `view/frontend/web`.

Check the result with:

```bash
bin/magento module:status Panth_Testimonials
```

## Configuration

Go to Stores > Configuration > Panth Extensions > Testimonials. All settings can be set at default, website and store view scope.

### General Settings

| Setting | Default | What it does |
|---|---|---|
| Enable Module | Yes | Enables the custom router and the storefront pages. When disabled the testimonial URLs are not matched and the standard `testimonials/*` actions return 404. |
| Page Title | Customer Testimonials | Page title, H1 and breadcrumb label of the listing page. |
| URL Route | testimonials | Base path of all testimonial URLs, for example `testimonials` gives `/testimonials`. |
| Meta Description | (empty) | Meta description of the listing page. When empty a built-in default text is used. |

### Display Settings

| Setting | Default | What it does |
|---|---|---|
| Items Per Page | 12 | Number of testimonials per listing page. |

### Submit Form Settings

| Setting | Default | What it does |
|---|---|---|
| Enable Submit Form | Yes | Enables the `/<route>/submit` page and the `testimonials/submit/save` POST action. When disabled the submit page returns 404. |
| Require Approval | Yes | Shown only when Enable Submit Form is Yes. Yes saves submissions with status Pending, so an administrator has to approve them. No saves submissions with status Approved, so they are published immediately. The confirmation message changes accordingly. |
| Assign Submissions to Store View | No | Shown only when Enable Submit Form is Yes. Yes saves a submission with the ID of the store view it was sent from, so it is shown only on that store view. No saves it with store ID 0, so it is shown on every store view. |

### Structured Data (JSON-LD)

| Setting | Default | What it does |
|---|---|---|
| Reviewed Item Type | Organization | schema.org `@type` written into every `Review.itemReviewed`. Options: Organization, LocalBusiness, ProfessionalService, Product, Service. |
| Reviewed Item Name | (empty) | Name written into `Review.itemReviewed.name`. When empty the store name from General > Store Information is used, then the store view name, then the website name. |
| Reviewed Item @id (optional) | (empty) | Full URL, or a fragment such as `#organization` appended to the store base URL, written as `Review.itemReviewed.@id`. When empty no `@id` is written. |

Config paths:

- `panth_testimonials/general/enabled`
- `panth_testimonials/general/page_title`
- `panth_testimonials/general/route`
- `panth_testimonials/general/meta_description`
- `panth_testimonials/display/items_per_page`
- `panth_testimonials/submit/enabled`
- `panth_testimonials/submit/require_approval`
- `panth_testimonials/submit/assign_store`
- `panth_testimonials/schema/item_reviewed_type`
- `panth_testimonials/schema/item_reviewed_name`
- `panth_testimonials/schema/item_reviewed_id`

With the defaults the module is active after installation: the listing page is served at `/testimonials`, the submit form is available and submissions wait for approval.

### Admin grids

The admin menu Panth Extensions > Testimonials contains:

- "Manage Testimonials" (`panth_testimonials/testimonial/index`): grid with keyword search (customer name, company, title and content), columns ID, Customer Name, Title, Rating, Status, Featured and Created, an Edit and Delete action per row and a Delete mass action. The edit form has Save and Save and Continue Edit buttons and the fields Customer Name (required), Email, Job Title, Company, Rating (select, 1-5 stars) (required), Testimonial Title (required), Full Content (required), Short Excerpt (for cards), URL Key, Status, Featured, Category and Sort Order. When URL Key is left empty it is generated from the customer name and the title. A URL key that is already used by another testimonial gets a numeric suffix (`-2`, `-3`, ...).
- "Manage Categories" (`panth_testimonials/category/index`): grid with columns ID, Name, URL Key, Active and Sort Order, row actions and a Delete mass action. The form has the fields Name (required), URL Key, Description, Active and Sort Order. When URL Key is left empty it is generated from the name. A URL key that is already used by another category gets a numeric suffix.
- "Configuration": opens the configuration section described above.

The columns `customer_image`, `store_id`, `meta_title` and `meta_description` exist in the database and are read by the storefront templates, but the admin form has no fields for them. `customer_image` may hold a full `http(s)://` URL, a path starting with `/`, or a path relative to `pub/media` (for example `testimonials/alice.jpg`); relative paths are turned into media URLs by every template, including the slider widgets.

## Usage

### Storefront pages

With the default route the module serves:

| URL | Page |
|---|---|
| `/testimonials` | Listing of approved testimonials, sorted by sort order and then newest first, paginated by Items Per Page |
| `/testimonials/page/2` | Page 2 of the listing |
| `/testimonials/category/<url_key>` | Listing filtered by an active category; the page title is `<Category name> - <Page Title>` and the meta description is the category description or a generated text |
| `/testimonials/<url_key>` | Detail page of an approved testimonial, or the category page when the URL key belongs to an active category |
| `/testimonials/submit` | Submission form (when Enable Submit Form is Yes) |

The standard route `testimonials/*` is also registered, so `testimonials/index/index`, `testimonials/view/index?url_key=...`, `testimonials/category/view?url_key=...` and `testimonials/submit/index` resolve as well while Enable Module is Yes. The custom router runs with sort order 40 and only when Enable Module is Yes.

Testimonials and categories with store ID 0 appear on every store view; other values restrict them to that store view. This applies to the listing, category and detail pages and to the custom router.

### Submission form

The form posts `customer_name`, `customer_email`, `customer_title`, `customer_company`, `rating`, `title`, `content` and the form key to `testimonials/submit/save`. Name, a valid email, title and content are required; name, email, title, job title and company are limited to 255 characters and the content to 5000 characters; a rating outside 1 to 5 is stored as 5; HTML tags are stripped from all fields, and before any save (storefront or admin) the model observer decodes HTML entities and strips tags again until the text no longer changes, so entity-encoded markup such as `&lt;img&gt;` is removed too, double quotes in the customer name become single quotes, and a required field left empty by this cleaning is rejected; `category_id` is kept only when it belongs to an active category; the URL key is generated from the title and gets a random suffix when it is already used. At most 5 submissions per client IP address are accepted per hour (counter kept in the Magento cache). Requests sent with `X-Requested-With: XMLHttpRequest` receive a JSON response `{"success": bool, "message": string}`; other requests are redirected with a session message. The record is saved with status Pending when Require Approval is Yes (it appears on the storefront only after an administrator sets the status to Approved) and with status Approved when it is No. The record gets store ID 0, or the current store view ID when Assign Submissions to Store View is Yes. The Hyva listing page also contains an inline submission modal with a honeypot field (`website_url`); a filled honeypot discards the submission silently. The submit forms have no photo upload field; a customer image can only be set directly in the `customer_image` column.

### Widget

Insert the widget "Panth Testimonial Slider" (`panth_testimonial_slider`) from a CMS page, a CMS block or a layout XML widget instance. Parameters:

| Parameter | Default | Effect |
|---|---|---|
| Widget Title | (empty) | Heading above the slider; the template falls back to "What Our Customers Say" |
| Category | all | Restricts the slider to one category |
| Number of Testimonials | 8 | Maximum number of approved testimonials shown, picked in random order |
| Featured Only | No | Shows only testimonials with the Featured flag |
| Show Rating | Yes | Shows the star rating on each card |
| Show Company | Yes | Shows the company name |
| Show Customer Image | Yes | Shows the customer image when the record has one |
| Autoplay | Yes | Advances the slider automatically |
| Autoplay Interval (ms) | 5000 | Delay between automatic slides |

The widget renders nothing when Enable Module is No for the current store view.

Layout XML example:

```xml
<block class="Panth\Testimonials\Block\Widget\TestimonialSlider" name="homepage.testimonials.slider">
    <arguments>
        <argument name="title" xsi:type="string">What Our Customers Say</argument>
        <argument name="count" xsi:type="string">8</argument>
        <argument name="featured_only" xsi:type="string">1</argument>
    </arguments>
</block>
```

On Luma the widget renders `widget/slider.phtml` with Swiper, whose CSS and JS are loaded from `Panth_Core` on every frontend page by `view/frontend/layout/default.xml`; the slider is initialised when it scrolls into view and shows 1, 2 and 3 slides on mobile, tablet and desktop. On Hyva the block switches to `hyva/widget/slider.phtml`, which uses the Hyva `x-snap-slider` directive and an Alpine.js pager; `default_hyva.xml` removes the Swiper assets and loads `Panth_Testimonials::css/testimonials.css` instead.

### Structured data

`Panth\Testimonials\Block\Schema` renders a `<script type="application/ld+json">` in `before.body.end` on the listing, category and detail pages. The JSON-LD is an `ItemList` with up to 50 approved testimonials of the current store view, each as a `Review` with `author` (`Person` with `name`, and `jobTitle` and `affiliation` when set), `name`, `reviewBody`, `reviewRating` (1 to 5), `datePublished` and the configured `itemReviewed`. Nothing is rendered when no approved testimonial exists.

### Templates

Templates that can be overridden in a theme under `Panth_Testimonials/templates/`:

- Luma: `listing.phtml`, `view.phtml`, `submit.phtml`, `widget/slider.phtml`, `schema.phtml`
- Hyva: `hyva/listing.phtml`, `hyva/view.phtml`, `hyva/submit.phtml`, `hyva/widget/slider.phtml`

Layout handles: `testimonials_index_index`, `testimonials_category_view`, `testimonials_view_index`, `testimonials_submit_index` and their `hyva_` prefixed counterparts that swap in the Hyva templates. Luma styles are in `view/frontend/web/css/source/_module.less`; Hyva styles in `view/frontend/web/css/testimonials.css`.

The module has no cron jobs, console commands, web API endpoints, plugins or observers.

## Developer Notes

- Module name: `Panth_Testimonials`; Composer package: `mage2kishan/module-testimonials`; namespace: `Panth\Testimonials`.
- Load sequence: `Panth_Core`, `Magento_Cms`, `Magento_Store`, `Magento_Widget`.
- Models: `Model\Testimonial` (constants `STATUS_PENDING` = 0, `STATUS_APPROVED` = 1, `STATUS_REJECTED` = 2; cache tag `panth_testimonial`; event prefix `panth_testimonial`) and `Model\Category` (cache tag and event prefix `panth_testimonial_category`), with resource models and collections under `Model\ResourceModel`. Collection helpers: `addApprovedFilter()`, `addStoreFilter()`, `addCategoryFilter()`, `addFeaturedFilter()`, `addDefaultOrder()`, `addActiveFilter()`.
- Grid collections `Model\ResourceModel\Testimonial\Grid\Collection` and `Model\ResourceModel\Category\Grid\Collection` are registered as `panth_testimonials_listing_data_source` and `panth_testimonials_category_listing_data_source` in `etc/di.xml`.
- `Helper\Data` exposes the configuration (`isEnabled()`, `getPageTitle()`, `getMetaDescription()`, `getCategoryMetaDescription()`, `getBaseUrl()`, `isSubmitEnabled()`, `requireApproval()`, `isAssignSubmissionStore()`, `getItemsPerPage()`) and the `XML_PATH_*` constants.
- Blocks: `Block\Testimonials` (listing and category pages), `Block\View` (detail page), `Block\Submit` (form), `Block\Schema` (JSON-LD), `Block\Widget\TestimonialSlider` (widget; `getTestimonials()`, `getSliderConfig()`, `getSliderId()`).
- Router: `Controller\Router`, registered in `etc/frontend/di.xml` with sort order 40. Frontend controllers: `Index\Index`, `View\Index`, `Category\View`, `Submit\Index`, `Submit\Save`. Admin controllers under `Controller\Adminhtml\Testimonial` and `Controller\Adminhtml\Category` (Index, NewAction, Edit, Save, Delete, MassDelete).
- Source models: `Model\Config\Source\Status`, `Model\Config\Source\CategoryList`, `Model\Config\Source\ItemReviewedType`.
- Theme detection uses `Panth\Core\Helper\Theme::isHyva()`; the module registers itself with `Panth\Core\ViewModel\ThemeConfig` in `etc/frontend/di.xml`, and `etc/theme-config.json` holds its colour tokens.
- ACL resources: `Panth_Testimonials::testimonials` (menu root), `Panth_Testimonials::manage_testimonials` (testimonial controllers and menu item), `Panth_Testimonials::manage_categories` (category controllers and menu item), `Panth_Testimonials::configuration` (configuration section and menu item).
- Admin Save, Delete and MassDelete actions accept POST requests only; the grid Delete row action and the form Delete button post the request.
- Full page cache: `Model\Testimonial` and `Model\Category` implement `IdentityInterface`. Saving or deleting an approved testimonial, or any category, cleans the `panth_testimonial` or `panth_testimonial_category` tag, which the listing, detail and widget blocks report as their identities.
- Database tables (`etc/db_schema.xml`): `panth_testimonial` and `panth_testimonial_category`.
- Admin route: `panth_testimonials`; frontend route: `testimonials`; UI components: `panth_testimonials_listing`, `panth_testimonials_form`, `panth_testimonials_category_listing`, `panth_testimonials_category_form`.

## Uninstallation

```bash
bin/magento module:disable Panth_Testimonials
composer remove mage2kishan/module-testimonials
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

The tables `panth_testimonial` and `panth_testimonial_category` and the `panth_testimonials/*` values in `core_config_data` are not removed; drop or delete them manually if they are no longer needed. `mage2kishan/module-core` stays installed if other Panth modules depend on it.

## Support

- Product page: [kishansavaliya.com/magento-2-testimonials.html](https://kishansavaliya.com/magento-2-testimonials.html)
- Contact: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- GitHub issues: [github.com/mage2sk/module-testimonials/issues](https://github.com/mage2sk/module-testimonials/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) covers installation, configuration, managing testimonials and categories in the admin, the storefront pages, the slider widget, structured data, the submission form and troubleshooting.

## License

Commercial software license. See [LICENSE.txt](LICENSE.txt) in this repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [kishansavaliya.com/magento-extensions.html](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [github.com/mage2sk/module-testimonials](https://github.com/mage2sk/module-testimonials)
- Packagist: [packagist.org/packages/mage2kishan/module-testimonials](https://packagist.org/packages/mage2kishan/module-testimonials)
