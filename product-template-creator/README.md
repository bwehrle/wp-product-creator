# Product Template Creator

WordPress plugin to quickly create WooCommerce products by cloning a configured Elementor template per product and inserting the cloned template shortcode into the new product description.

## Features

- Admin page to create a new product
- Uses a fixed Elementor template configured in plugin settings as a source template
- Clones the source template for each new product and renames it to `Product Name-template`
- Uses the cloned template shortcode (`[elementor-template id="..."]`) in product description
- Lets you choose virtual or physical product type
- Lets you choose product status (draft or publish)
- Requires a product price
- Sets stock quantity and stock status
- Lets you select a product category
- Generates a random unique SKU (format: `NP-XXXXXXXX`)

## Install

1. Copy the `product-template-creator` folder into your WordPress `wp-content/plugins/` directory.
2. Activate **Product Template Creator** in WordPress admin plugins.
3. Make sure WooCommerce is active.

## Usage

1. In WordPress admin, go to **Product Creator > Settings**.
2. Select the fixed Elementor template containing your shortcode and save.
3. Go to **Product Creator**.
4. Enter the product name, type, status, price, quantity, and category.
5. Click **Create Product**.

## Notes

- The plugin creates a simple WooCommerce product.
- You can choose status `draft` or `publish`.
- Product description is set to the cloned Elementor template shortcode.
