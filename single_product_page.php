<?php

/**
 * Делаем вывод всех вариаций на странице продукта для возможности оформления заказа.
 * Всё делается на основе дочерней темы, все 3 файла можно разместить в папке дочерней темы
 * и в function.php подключить данный файл
 * @author     Кирилл Шрейдер aka sh8der <info@sh8der.ru>
 */

/* Отключем стандартный вывод цены и блока добавления в корзину */
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

// Цепляем свою функцию вывода списка вариаций, за место вывода стандартного блока цены
add_action('woocommerce_single_product_summary', 'render_all_variables_in_table', 10);
// Создаём WP ADMIN AJAX событие для обработки ajax добавления вариации в корзину
add_action('wp_ajax_woocommerce_add_to_cart_variable_rc', 'woocommerce_add_to_cart_variable_rc_callback');
// Подключаем стили и скрипты для офомления списка вариаций и ajax добавления вариации в корзину
add_action('wp_enqueue_scripts', 'sh8der_enc_assets_file');

function sh8der_enc_assets_file() {
	wp_enqueue_style(
		'sh8der_variations_list',
		get_template_directory_uri() . '-child/woocommerce/variations_list_style.css'
	);
	wp_enqueue_script(
		'sh8der-variations-list-logic',
		get_template_directory_uri() . '-child/woocommerce/variations_list_logic.js',
		array('jquery')
	);
}

function get_variable_row_html($variation, $product_name, $product_id) {
	ob_start();
	$variation_attr_value = $variation['attributes']['attribute_pa_obem'];
	$variation_name = "$product_name - <b>$variation_attr_value</b> мл.";
	$is_sale = ($variation['display_price'] !== $variation['display_regular_price']);
	$is_availability = (strlen($variation['availability_html']) == 0);
	?>
        <li class="sh8der_variations_list__item sh8der_variations_list_item">
            <?php if (isset($variation['image']['gallery_thumbnail_src'])): ?>
            <img src="<?php echo $variation['image']['gallery_thumbnail_src'] ?>"
                 alt="<?php echo $variation_name ?>"
                 class="sh8der_variations_list__img"/>
            <?php endif;?>
            <span class="sh8der_variations_list_item__name"><?php echo $variation_name ?></span>
            <div class="sh8der_variations_list_item_price_wrap">
            <?php if ($is_availability): ?>
            <?php 
                $price_class = ['sh8der_variations_list_item__price'];
            ?>
            <?php if ($is_sale): ?>
            <span class="<?php echo implode(' ', $price_class); ?>">
                <?php echo wc_price($variation['display_price'], ['decimals' => 0]); ?>
            </span>
            <?php endif;?>
            <?php 
                if ($is_sale) array_push($price_class, 'sh8der_variations_list_item__sale_style');
            ?>
            <span class="<?php echo implode(' ', $price_class); ?>">
                <?php echo wc_price($variation['display_regular_price'], ['decimals' => 0]); ?>
            </span>
            <?php else: ?>
            <?php echo $variation['availability_html'] ?>
            <?php endif;?>
            </div>
            <?php if ($is_availability): ?>
            <button
                class="single_add_to_cart_button button alt sh8der_variations_list_item__add_cart_btn"
                data-product-id="<?php echo $product_id; ?>"
                data-variation-id="<?php echo $variation['variation_id']; ?>"
                data-quantity="1"
                data-variation-att-val="<?php echo $variation_attr_value ?>"
                ><?php echo __('В корзину'); ?></button>
            <?php endif;?>
        </li>
    <?php
    $row_html = ob_get_contents();
	ob_clean();
	return $row_html;
}

function render_all_variables_in_table() {

	$product = wc_get_product();

    /*
    * Проверяем, если у товара есть вариации то выводим список вариаций,
    * если нет вариаций, тогда вызываем стандартные функции woocommerce 
    * для вывода стандартного блока цены и добавления в корзину
    */
	if ($product->is_type('variable')) {
		$variations = $product->get_available_variations();
		$product_id = $product->get_id();
		$table_variation_html = '';
		// cLog($variations);
		foreach ($variations as $variation) {
			$table_variation_html .= get_variable_row_html($variation, $product->get_name(), $product_id);
		}
		printf('<ul class="sh8der_variations_list">%s</ul>', $table_variation_html);
	} else {
		call_user_func('woocommerce_template_single_price');
		call_user_func('woocommerce_template_single_add_to_cart');
	}

}

function woocommerce_add_to_cart_variable_rc_callback() {
	$product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
	$quantity = empty($_POST['quantity']) ? 1 : apply_filters('woocommerce_stock_amount', $_POST['quantity']);
	$variation_id = $_POST['variation_id'];
	$variation = $_POST['variation'];
	$passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
	if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation)) {
		do_action('woocommerce_ajax_added_to_cart', $product_id);
		if (get_option('woocommerce_cart_redirect_after_add') == 'yes') {
			wc_add_to_cart_message($product_id);
		}
		// Выводим данные для обновления корзины на фронте
        WC_AJAX::get_refreshed_fragments();
	} else {
		$data = array(
			'error' => true,
			'product_url' => apply_filters(
				'woocommerce_cart_redirect_after_error',
				get_permalink($product_id),
				$product_id
			),
		);
		echo json_encode($data);
	}
	die();
}

?>
