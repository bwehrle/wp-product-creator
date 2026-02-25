<?php
/**
 * Plugin Name: Product Template Creator
 * Description: Adds an admin page to create WooCommerce products from a page template shortcode.
 * Version: 1.0.0
 * Author: Local Dev
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: product-template-creator
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('PTC_Product_Template_Creator')) {
	class PTC_Product_Template_Creator {
		private const NONCE_ACTION = 'ptc_create_product_action';
		private const NONCE_NAME = 'ptc_create_product_nonce';
		private const SETTINGS_NONCE_ACTION = 'ptc_save_settings_action';
		private const SETTINGS_NONCE_NAME = 'ptc_save_settings_nonce';
		private const MENU_SLUG = 'ptc-product-template-creator';
		private const SETTINGS_MENU_SLUG = 'ptc-product-template-creator-settings';
		private const OPTION_TEMPLATE_PAGE_ID = 'ptc_template_page_id';
		private const TEMPLATE_POST_TYPE = 'elementor_library';

		public function __construct() {
			add_action('admin_menu', array($this, 'register_admin_menu'));
		}

		public function register_admin_menu(): void {
			add_menu_page(
				__('Product Template Creator', 'product-template-creator'),
				__('Product Creator', 'product-template-creator'),
				'manage_woocommerce',
				self::MENU_SLUG,
				array($this, 'render_admin_page'),
				'dashicons-products',
				56
			);

			add_submenu_page(
				self::MENU_SLUG,
				__('Product Creator Settings', 'product-template-creator'),
				__('Settings', 'product-template-creator'),
				'manage_woocommerce',
				self::SETTINGS_MENU_SLUG,
				array($this, 'render_settings_page')
			);
		}

		public function render_admin_page(): void {
			if (!current_user_can('manage_woocommerce')) {
				wp_die(esc_html__('You do not have permission to access this page.', 'product-template-creator'));
			}

			$this->handle_form_submission();
			$template_id   = absint(get_option(self::OPTION_TEMPLATE_PAGE_ID, 0));
			$template_post = $template_id > 0 ? get_post($template_id) : null;
			$has_template  = (bool) $template_post && $template_post->post_type === self::TEMPLATE_POST_TYPE;
			$product_categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			?>
			<div class="wrap">
				<h1><?php echo esc_html__('Create Product From Elementor Template', 'product-template-creator'); ?></h1>
				<p>
					<?php echo esc_html__('A fixed Elementor template is configured in plugin settings. It will be cloned per product and the cloned template shortcode will be used in the product description.', 'product-template-creator'); ?>
				</p>
				<?php if (!$has_template) : ?>
					<div class="notice notice-warning">
						<p>
							<?php
							printf(
								/* translators: %s: settings page URL */
								wp_kses(
									__('No Elementor template configured. <a href="%s">Go to settings</a> to select one.', 'product-template-creator'),
									array('a' => array('href' => array()))
								),
								esc_url(admin_url('admin.php?page=' . self::SETTINGS_MENU_SLUG))
							);
							?>
						</p>
					</div>
				<?php else : ?>
					<p>
						<?php
						printf(
							/* translators: 1: template title, 2: template id */
							esc_html__('Using Elementor template: %1$s (#%2$d)', 'product-template-creator'),
							esc_html($template_post->post_title),
							absint($template_post->ID)
						);
						?>
					</p>
				<?php endif; ?>

				<form method="post">
					<?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
					<input type="hidden" name="ptc_action" value="create_product" />
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="ptc_product_name"><?php echo esc_html__('Product Name', 'product-template-creator'); ?></label>
								</th>
								<td>
									<input name="ptc_product_name" type="text" id="ptc_product_name" class="regular-text" required />
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php echo esc_html__('Product Type', 'product-template-creator'); ?>
								</th>
								<td>
									<label>
										<input type="radio" name="ptc_product_type" value="physical" checked />
										<?php echo esc_html__('Physical', 'product-template-creator'); ?>
									</label>
									<br />
									<label>
										<input type="radio" name="ptc_product_type" value="virtual" />
										<?php echo esc_html__('Virtual', 'product-template-creator'); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php echo esc_html__('Product Status', 'product-template-creator'); ?>
								</th>
								<td>
									<label>
										<input type="radio" name="ptc_product_status" value="draft" checked />
										<?php echo esc_html__('Draft', 'product-template-creator'); ?>
									</label>
									<br />
									<label>
										<input type="radio" name="ptc_product_status" value="publish" />
										<?php echo esc_html__('Publish', 'product-template-creator'); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="ptc_price"><?php echo esc_html__('Price', 'product-template-creator'); ?></label>
								</th>
								<td>
									<input name="ptc_price" type="number" min="0" step="0.01" id="ptc_price" required />
									<p class="description"><?php echo esc_html__('Required. Use 0 for free products.', 'product-template-creator'); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="ptc_stock_qty"><?php echo esc_html__('Quantity Available', 'product-template-creator'); ?></label>
								</th>
								<td>
									<input name="ptc_stock_qty" type="number" min="0" step="1" id="ptc_stock_qty" value="0" required />
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="ptc_category_id"><?php echo esc_html__('Product Category', 'product-template-creator'); ?></label>
								</th>
								<td>
									<select name="ptc_category_id" id="ptc_category_id">
										<option value="0"><?php echo esc_html__('-- No Category --', 'product-template-creator'); ?></option>
										<?php if (!is_wp_error($product_categories)) : ?>
											<?php foreach ($product_categories as $category) : ?>
												<option value="<?php echo esc_attr((string) $category->term_id); ?>">
													<?php echo esc_html($category->name); ?>
												</option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button(__('Create Product', 'product-template-creator'), 'primary', 'submit', true, $has_template ? array() : array('disabled' => 'disabled')); ?>
				</form>
			</div>
			<?php
		}

		public function render_settings_page(): void {
			if (!current_user_can('manage_woocommerce')) {
				wp_die(esc_html__('You do not have permission to access this page.', 'product-template-creator'));
			}

			$this->handle_settings_submission();

			$templates = get_posts(
				array(
					'post_type'      => self::TEMPLATE_POST_TYPE,
					'post_status'    => array('publish', 'draft', 'private'),
					'posts_per_page' => 200,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);
			$current_template_id = absint(get_option(self::OPTION_TEMPLATE_PAGE_ID, 0));
			?>
			<div class="wrap">
				<h1><?php echo esc_html__('Product Creator Settings', 'product-template-creator'); ?></h1>
				<p><?php echo esc_html__('Set the fixed Elementor template used for all product creation.', 'product-template-creator'); ?></p>
				<form method="post">
					<?php wp_nonce_field(self::SETTINGS_NONCE_ACTION, self::SETTINGS_NONCE_NAME); ?>
					<input type="hidden" name="ptc_action" value="save_settings" />
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="ptc_template_page_id"><?php echo esc_html__('Elementor Template', 'product-template-creator'); ?></label>
								</th>
								<td>
									<select name="ptc_template_page_id" id="ptc_template_page_id" required>
										<option value=""><?php echo esc_html__('-- Select Elementor Template --', 'product-template-creator'); ?></option>
										<?php foreach ($templates as $template) : ?>
											<option value="<?php echo esc_attr((string) $template->ID); ?>" <?php selected($current_template_id, (int) $template->ID); ?>>
												<?php echo esc_html($template->post_title . ' (#' . $template->ID . ')'); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php echo esc_html__('The template shortcode will be generated automatically from the selected Elementor template ID.', 'product-template-creator'); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button(__('Save Settings', 'product-template-creator')); ?>
				</form>
			</div>
			<?php
		}

		private function handle_form_submission(): void {
			if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
				return;
			}
			if (!isset($_POST['ptc_action']) || sanitize_key(wp_unslash($_POST['ptc_action'])) !== 'create_product') {
				return;
			}

			if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
				$this->render_notice(__('Security check failed. Please try again.', 'product-template-creator'), 'error');
				return;
			}

			if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) {
				$this->render_notice(__('WooCommerce is required for this plugin.', 'product-template-creator'), 'error');
				return;
			}

			$product_name = isset($_POST['ptc_product_name']) ? sanitize_text_field(wp_unslash($_POST['ptc_product_name'])) : '';
			$template_id  = absint(get_option(self::OPTION_TEMPLATE_PAGE_ID, 0));
			$product_type = isset($_POST['ptc_product_type']) ? sanitize_key(wp_unslash($_POST['ptc_product_type'])) : 'physical';
			$product_status = isset($_POST['ptc_product_status']) ? sanitize_key(wp_unslash($_POST['ptc_product_status'])) : 'draft';
			$raw_price      = isset($_POST['ptc_price']) ? wc_format_decimal(wp_unslash($_POST['ptc_price']), 2) : '';
			$stock_qty    = isset($_POST['ptc_stock_qty']) ? max(0, absint($_POST['ptc_stock_qty'])) : 0;
			$category_id  = isset($_POST['ptc_category_id']) ? absint($_POST['ptc_category_id']) : 0;

			if ($product_name === '' || $template_id <= 0) {
				$this->render_notice(__('Please provide product name and configure an Elementor template in settings.', 'product-template-creator'), 'error');
				return;
			}
			if ($raw_price === '' || !is_numeric($raw_price) || (float) $raw_price < 0) {
				$this->render_notice(__('Please provide a valid price (0 or greater).', 'product-template-creator'), 'error');
				return;
			}

			if (!in_array($product_status, array('draft', 'publish'), true)) {
				$product_status = 'draft';
			}

			$template_post = get_post($template_id);
			if (!$template_post || $template_post->post_type !== self::TEMPLATE_POST_TYPE) {
				$this->render_notice(__('Elementor template not found.', 'product-template-creator'), 'error');
				return;
			}

			$cloned_template_id = $this->clone_elementor_template($template_post, $product_name);
			if ($cloned_template_id <= 0) {
				$this->render_notice(__('Failed to clone Elementor template.', 'product-template-creator'), 'error');
				return;
			}

			$shortcode = $this->build_elementor_template_shortcode($cloned_template_id);

			$product = new WC_Product_Simple();
			$product->set_name($product_name);
			$product->set_status($product_status);
			$product->set_description($shortcode);
			$product->set_virtual($product_type === 'virtual');
			$product->set_regular_price((string) $raw_price);
			$product->set_manage_stock(true);
			$product->set_stock_quantity($stock_qty);
			$product->set_stock_status($stock_qty > 0 ? 'instock' : 'outofstock');
			$product->set_sku($this->generate_unique_sku());
			if ($category_id > 0) {
				$category = get_term($category_id, 'product_cat');
				if ($category && !is_wp_error($category)) {
					$product->set_category_ids(array($category_id));
				}
			}

			$product_id = $product->save();
			if (!$product_id) {
				$this->render_notice(__('Failed to create product.', 'product-template-creator'), 'error');
				return;
			}

			$edit_link = get_edit_post_link($product_id, '');
			$message   = sprintf(
				/* translators: 1: Product ID, 2: Edit link URL */
				__('Product created successfully (ID: %1$d). <a href="%2$s">Edit product</a>.', 'product-template-creator'),
				$product_id,
				esc_url($edit_link)
			);
			$this->render_notice($message, 'success');
		}

		private function handle_settings_submission(): void {
			if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
				return;
			}
			if (!isset($_POST['ptc_action']) || sanitize_key(wp_unslash($_POST['ptc_action'])) !== 'save_settings') {
				return;
			}

			if (!isset($_POST[self::SETTINGS_NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::SETTINGS_NONCE_NAME])), self::SETTINGS_NONCE_ACTION)) {
				$this->render_notice(__('Security check failed. Please try again.', 'product-template-creator'), 'error');
				return;
			}

			$template_id = isset($_POST['ptc_template_page_id']) ? absint($_POST['ptc_template_page_id']) : 0;
			$template    = $template_id > 0 ? get_post($template_id) : null;
			if (!$template || $template->post_type !== self::TEMPLATE_POST_TYPE) {
				$this->render_notice(__('Please select a valid Elementor template.', 'product-template-creator'), 'error');
				return;
			}

			update_option(self::OPTION_TEMPLATE_PAGE_ID, $template_id);
			$this->render_notice(__('Settings saved.', 'product-template-creator'), 'success');
		}

		private function build_elementor_template_shortcode(int $template_id): string {
			return sprintf('[elementor-template id="%d"]', absint($template_id));
		}

		private function clone_elementor_template(WP_Post $source_template, string $product_name): int {
			$new_template_title = $product_name . '-template';

			$new_template_id = wp_insert_post(
				array(
					'post_type'    => self::TEMPLATE_POST_TYPE,
					'post_status'  => 'draft',
					'post_title'   => $new_template_title,
					'post_content' => $source_template->post_content,
					'post_excerpt' => $source_template->post_excerpt,
					'post_author'  => get_current_user_id(),
					'post_parent' => $post->post_parent
				),
				true
			);
			if (is_wp_error($new_template_id) || empty($new_template_id)) {
				return 0;
			}

			$this->copy_elementor_template_meta($source_template->ID, $new_template_id);

			$taxonomies = get_object_taxonomies(self::TEMPLATE_POST_TYPE);
			foreach ($taxonomies as $taxonomy) {
				$terms = wp_get_object_terms($source_template->ID, $taxonomy, array('fields' => 'ids'));
				if (!is_wp_error($terms)) {
					wp_set_object_terms($new_template_id, $terms, $taxonomy);
				}
			}

			$thumbnail_id = get_post_thumbnail_id($source_template->ID);
			if (!empty($thumbnail_id)) {
				set_post_thumbnail($new_template_id, $thumbnail_id);
			}

			$this->sync_elementor_clone_assets($new_template_id);

			return absint($new_template_id);
		}

		private function copy_elementor_template_meta(int $source_template_id, int $new_template_id): void {
			$post_meta_keys = get_post_custom_keys( $source_template_id );
			if(!empty($post_meta_keys)){
				// Skip editor lock metadata so cloned templates do not inherit stale editor session state.
				$excluded_meta_keys = array('_edit_lock', '_edit_last');
				foreach ( $post_meta_keys as $meta_key ) {
					if (in_array($meta_key, $excluded_meta_keys, true)) {
						continue;
					}
					$meta_values = get_post_custom_values( $meta_key, $source_template_id );
					foreach ( $meta_values as $meta_value ) {
						$meta_value = maybe_unserialize( $meta_value );
						update_post_meta( $new_template_id, $meta_key, wp_slash( $meta_value ) );
					}
				}
			}
		}

		private function sync_elementor_clone_assets(int $new_template_id): void {
			if (!class_exists('\Elementor\Plugin')) {
				return;
			}

			$elementor = \Elementor\Plugin::$instance;

			// Regenerate CSS/assets so frontend output matches editor output.
			if(is_plugin_active( 'elementor/elementor.php' )){
				$css = Elementor\Core\Files\CSS\Post::create( $new_template_id );
				$css->update();
		 	} 

			if (isset($elementor->files_manager) && method_exists($elementor->files_manager, 'clear_cache')) {
				$elementor->files_manager->clear_cache();
			}
		}

		private function generate_unique_sku(): string {
			do {
				$random = strtoupper(wp_generate_password(8, false, false));
				$sku    = 'NP-' . $random;
				$exists = wc_get_product_id_by_sku($sku);
			} while (!empty($exists));

			return $sku;
		}

		private function render_notice(string $message, string $type = 'success'): void {
			$allowed = array(
				'a' => array(
					'href' => array(),
				),
			);
			?>
			<div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
				<p><?php echo wp_kses($message, $allowed); ?></p>
			</div>
			<?php
		}
	}
}

new PTC_Product_Template_Creator();
