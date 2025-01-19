<form id="ypf-billing-form" method="post" class="needs-validation" novalidate>
    <h3 class="mb-3"><?php esc_html_e('Billing Information', 'yourpropfirm-checkout'); ?></h3>
    <div class="row g-3">
        <!-- First Name -->
        <div class="col-md-6">
            <label for="first_name" class="form-label"><?php esc_html_e('First Name', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo esc_attr($form_data['first_name'] ?? ''); ?>" required>
            <div class="invalid-feedback"><?php esc_html_e('Please enter your first name.', 'yourpropfirm-checkout'); ?></div>
        </div>

        <!-- Last Name -->
        <div class="col-md-6">
            <label for="last_name" class="form-label"><?php esc_html_e('Last Name', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo esc_attr($form_data['last_name'] ?? ''); ?>" required>
            <div class="invalid-feedback"><?php esc_html_e('Please enter your last name.', 'yourpropfirm-checkout'); ?></div>
        </div>

        <!-- Email -->
        <div class="col-md-6">
            <label for="email" class="form-label"><?php esc_html_e('Email', 'yourpropfirm-checkout'); ?></label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo esc_attr($form_data['email'] ?? ''); ?>" required>
            <div class="invalid-feedback"><?php esc_html_e('Please enter a valid email address.', 'yourpropfirm-checkout'); ?></div>
        </div>

        <!-- Phone -->
        <div class="col-md-6">
            <label for="phone" class="form-label"><?php esc_html_e('Phone Number', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo esc_attr($form_data['phone'] ?? ''); ?>" required>
            <div class="invalid-feedback"><?php esc_html_e('Please enter your phone number.', 'yourpropfirm-checkout'); ?></div>
        </div>

        <!-- Address -->
        <div class="col-12">
            <label for="address" class="form-label"><?php esc_html_e('Address', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="address" id="address" class="form-control" value="<?php echo esc_attr($form_data['address'] ?? ''); ?>" required>
            <div class="invalid-feedback"><?php esc_html_e('Please enter your address.', 'yourpropfirm-checkout'); ?></div>
        </div>

        <!-- Country -->
        <div class="col-md-6">
            <label for="country" class="form-label"><?php esc_html_e('Country', 'yourpropfirm-checkout'); ?></label>
            <select name="country" id="country" class="form-select" value="<?php echo esc_attr($form_data['first_name'] ?? ''); ?>" required>
                <option value=""><?php esc_html_e('Select Country', 'yourpropfirm-checkout'); ?></option>
                <?php 
                $selected_country = $form_data['country'] ?? '';
                ?>
                <?php foreach ($countries as $code => $name) : ?>
                    <option value="<?php echo esc_attr($code); ?>" 
                    	<?php selected($selected_country, $code); ?>>
                        <?php echo esc_html($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?php esc_html_e('Please select your country.', 'yourpropfirm-checkout'); ?></div>
        </div>

        <!-- State -->
		<div class="col-md-6">
		    <label for="state" class="form-label"><?php esc_html_e('State/Region', 'yourpropfirm-checkout'); ?></label>
		    <div id="state-container">
		        <!-- State field will be dynamically inserted here -->
		    </div>
		    <div class="invalid-feedback"><?php esc_html_e('Please enter your state/region.', 'yourpropfirm-checkout'); ?></div>
		</div>

        <!-- City -->
        <div class="col-md-6">
            <label for="city" class="form-label"><?php esc_html_e('City', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="city" id="city" class="form-control" value="<?php echo esc_attr($form_data['city'] ?? ''); ?>" required>
            <div class="invalid-feedback"><?php esc_html_e('Please enter your city.', 'yourpropfirm-checkout'); ?></div>
        </div>

        <!-- Postal Code -->
        <div class="col-md-6">
            <label for="postal_code" class="form-label"><?php esc_html_e('Postal Code', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="postal_code" id="postal_code" class="form-control" value="<?php echo esc_attr($form_data['postal_code'] ?? ''); ?>" required>
            <div class="invalid-feedback"><?php esc_html_e('Please enter your postal code.', 'yourpropfirm-checkout'); ?></div>
        </div>


        <div class="col-12 mb-3 mt-5 text-center">
		    <label class="form-label">
		        Trading Platforms <span class="text-danger">*</span>
		    </label>
		    <div class="d-flex justify-content-center gap-4">
		        <div class="form-check">
		            <input class="form-check-input mt-3 me-3" type="radio" name="yourpropfirm_mt_version" id="yourpropfirm_mt_version_MT4" value="MT4" checked>
		            <label class="form-check-label" for="yourpropfirm_mt_version_MT4">
		                MT4
		            </label>
		        </div>
		        
		        <div class="form-check">
		            <input class="form-check-input mt-3 me-3" type="radio" name="yourpropfirm_mt_version" id="yourpropfirm_mt_version_MT5" value="MT5">
		            <label class="form-check-label" for="yourpropfirm_mt_version_MT5">
		                MT5
		            </label>
		        </div>
		        
		        <div class="form-check">
		            <input class="form-check-input mt-3 me-3" type="radio" name="yourpropfirm_mt_version" id="yourpropfirm_mt_version_CTrader" value="CTrader">
		            <label class="form-check-label" for="yourpropfirm_mt_version_CTrader">
		                CTrader
		            </label>
		        </div>
		    </div>
		</div>	        

        <div class="d-flex justify-content-center my-4">
		    <div class="card text-white bg-dark p-3 shadow-sm" style="width: 80%;">
		        <div class="card-body">

		            <?php
		            // Get the last item in cart
		            $cart_items = WC()->cart->get_cart();
		            $last_item = end($cart_items);
		            
		            if ($last_item) {
		                $_product = apply_filters('woocommerce_cart_item_product', $last_item['data'], $last_item, key($cart_items));
		                $product_name = $_product->get_name();
		                ?>
		                <div class="d-flex flex-column justify-content-center align-items-center mb-3">
		                    <div class="woocommerce-product-name text-center">
		                        <strong><?php echo wp_kses_post($product_name); ?></strong>
		                    </div>
		                    <div class="woocommerce-product-price text-center">
		                    	<h3>
			                    	<strong>
			                        <?php 
			                        echo apply_filters(
			                            'woocommerce_cart_item_subtotal',
			                            WC()->cart->get_product_subtotal($_product, $last_item['quantity']),
			                            $last_item,
			                            key($cart_items)
			                        ); 
			                        ?>
			                        </strong>
		                    	</h3>
		                    </div>
		                </div>
		            <?php } ?>

		        	<!-- Coupon Code -->
			        <div class="col-12">
			            <label for="coupon_code" class="form-label"><?php esc_html_e('Coupon Code', 'yourpropfirm-checkout'); ?></label>
			            <input type="text" name="coupon_code" id="coupon_code" class="form-control" placeholder="<?php esc_html_e('Enter Coupon Code', 'yourpropfirm-checkout'); ?>">
			        </div>
 
		            <!-- Terms and Conditions -->
					<div class="mt-4 mb-3">
					    <div class="d-flex flex-column align-items-center">
					        <div class="form-check">
					            <input class="form-check-input me-2 mt-3" type="checkbox" name="terms" id="terms" required>
					            <label class="form-check-label" for="terms">
					                <?php printf(
					                    __('I agree to the <a href="%s" target="_blank" class="text-warning">Terms and Conditions</a>', 'yourpropfirm-checkout'),
					                    esc_url('https://cdn.prod.website-files.com/65087adb975c6a28d41fbb5a/66d5813fdcd49d516156c376_FORFX%20T%26C%20(4).pdf')
					                ); ?>
					            </label>
					            <div class="invalid-feedback">
					                <?php esc_html_e('You must agree to the terms and conditions.', 'yourpropfirm-checkout'); ?>
					            </div>
					        </div>
					    </div>
					</div>

					<!-- Privacy Policy -->
					<div class="mb-3">
					    <div class="d-flex flex-column align-items-center">
					        <div class="form-check">
					            <input class="form-check-input me-2 mt-3" type="checkbox" name="privacy_policy" id="privacy_policy" required>
					            <label class="form-check-label" for="privacy_policy">
					                <?php printf(
					                    __('I have read and agree to the <a href="%s" target="_blank" class="text-warning">Privacy Policy</a>', 'yourpropfirm-checkout'),
					                    esc_url('https://cdn.prod.website-files.com/65087adb975c6a28d41fbb5a/66717db7ae186627a255b318_forfx%20privacy.pdf')
					                ); ?>
					            </label>
					            <div class="invalid-feedback">
					                <?php esc_html_e('You must agree to the privacy policy.', 'yourpropfirm-checkout'); ?>
					            </div>
					        </div>
					    </div>
					</div>

		            <!-- Submit Button -->
			        <div class="col-12 text-center">
			            <button type="submit" id="ypf-submit-button" class="btn btn-primary mt-4"><?php esc_html_e('Proceed With Payment', 'yourpropfirm-checkout'); ?></button>
			        </div>
		        </div>
		    </div>
		</div>


        
    </div>
</form>
