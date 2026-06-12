# Product Search Interface

A PHP product search sample prepared by Jeric Rodriguez. The project connects to the SAS/EURAS EED gateway through a small server-side API layer and presents the results in a responsive product catalogue UI.

## Features

- Real-time search with debounce
- Product cards with image, name, price, manufacturer, article number, and availability
- Product detail overlay when a card is selected
- PHP API proxy so service credentials stay outside frontend JavaScript
- Local sample data fallback for UI review when the remote API is unavailable

## Requirements

- PHP 8+
- PHP cURL extension enabled
- Internet access when using the live EED endpoint

## Run locally

```bash
cd eed-product-search-jeric
php -S localhost:8001
```

Open:

```text
http://localhost:8001
```

## Configuration

Edit `config.php`:

```php
'eed_id' => 'YOUR_REAL_EED_ID',
'shop_url' => 'https://your-shop-domain.com/',
```

For UI-only testing, force local sample data:

```bash
MOCK_ONLY=true php -S localhost:8001
```

## Main files

- `index.php` — main interface
- `config.php` — API and runtime settings
- `api/EedClient.php` — EED gateway client and response normalizer
- `api/products.php` — search endpoint
- `api/product.php` — detail endpoint
- `assets/js/app.js` — frontend behaviour
- `assets/css/styles.css` — custom UI styling
- `screenshots/` — sample screenshots
