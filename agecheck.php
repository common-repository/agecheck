<?php
/*
Plugin Name: AgeChecked for WooCommerce 
Description: Perform age verifications with AgeChecked for WooCommerce.
Version: 1.10
Author: AgeChecked
Author URI: http://www.agechecked.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


// Save agecheckID
if (isset($_REQUEST['agecheckID'])) {
	$uid = isset($_REQUEST['agecheckID']) ? $_REQUEST['agecheckID'] : null;
    $options = get_option( 'agecheck_settings' );
    $options['agechecked_settings_agecheckid'] = $uid;
    update_option( "agecheck_settings", $options );
}

// Curl request for Client API
if (isset($_REQUEST['privateKey'])) {

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Build data array from front end customer details with merchant secret key
$data_from_front_end = array(
"merchantSecretKey" => $_REQUEST['privateKey'],
"name"=> $_REQUEST['name'],
"surname" => $_REQUEST['surname'],
"building" => $_REQUEST['building'],
"street" => $_REQUEST['street'],
"postCode" => $_REQUEST['postCode'],
"countryCode" => $_REQUEST['countryCode'],
"email" => $_REQUEST['email'],
);
$data_string = json_encode($data_from_front_end);
# Create a connection
$ch = curl_init($_REQUEST['enteredURL']);
# Setting our options
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/json',
'Content-Length: ' . strlen($data_string))
);

# Get the response
$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode != 200){
echo "Return code is {$httpCode} \n"
.curl_error($ch);
} else {
echo $response;
}
curl_close($ch);
exit;
}

add_action( 'admin_menu', 'agecheck_add_admin_menu' );
add_action( 'admin_init', 'agecheck_settings_init' );
add_action('wp_enqueue_scripts', 'agechecked_enqueue_scripts');


function agecheck_add_admin_menu(  ) { 
	add_menu_page( 'AgeChecked', 'AgeChecked', 'manage_options', 'agechecked_for_wordpress', 'admin_page_html', plugin_dir_url( __FILE__ ) . 'assets/images/agechecked-icon.png');
	}

function admin_page_html() {

  //Get the active tab from the $_GET param
  $default_tab = null;
  $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

  ?>
  <img style='margin-left: -30px; padding-top: 30px; padding-bottom: 10px;' title='' alt='' src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/images/agechecked-logo.png'; ?>">

  <!-- Our admin page content should all be inside .wrap -->
  <div class="wrap">
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="?page=agechecked_for_wordpress" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Settings</a>
      <a href="?page=agechecked_for_wordpress&tab=about" class="nav-tab <?php if($tab==='about'):?>nav-tab-active<?php endif; ?>">About</a>
      <a href="?page=agechecked_for_wordpress&tab=support" class="nav-tab <?php if($tab==='support'):?>nav-tab-active<?php endif; ?>">Support</a>
      <!-- <a href="?page=agechecked_for_wordpress&tab=faqs" class="nav-tab <?php if($tab==='faqs'):?>nav-tab-active<?php endif; ?>">FAQs</a> -->
    </nav>

    <div class="tab-content">
    <?php switch($tab) :
      case 'about':
        ?>
        <h1>About</h1>
        <p>AgeChecked offers a range of simple solutions for you to verify your customers.</p>
        <p>Batch Upload, Client API, Consumer Gateway or a combination.<br/></p>
		<p>Please rate <strong>AgeChecked</strong> <a href='https://wordpress.org/plugins/agecheck/#reviews' target='_blank' rel='noopener noreferrer'>&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href='https://wordpress.org/plugins/agecheck/#reviews' target='_blank' rel='noopener'>WordPress.org</a> to help us spread the word. Thank you from the AgeChecked team!	</p>

        <iframe id="iframe-player-44" data-id="44" title="AgeChecked" src="https://player.vimeo.com/video/171649523?dnt=1&amp;app_id=122963" width="600" height="337" frameborder="0" allow="autoplay; fullscreen" allowfullscreen=""></iframe>
	<?php
        break;
      case 'support':
        ?>
        <h1>Support</h1>        
        <p>If you have any questions or require any support, please review our <a target='_blank' href='https://wordpress.org/plugins/agecheck/#description'>support documentation</a>.<br/></p>
	<?php
        break;
      case 'faqs':
        ?>
        <h1>Lorem Ipsum<br></h1>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.<br></p>
        <h1>Lorem Ipsum<br></h1>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.<br></p>
        <h1>Lorem Ipsum<br></h1>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.<br></p>
        <h1>Lorem Ipsum<br></h1>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.<br></p>
	<?php
          
          
        break;
      default:
        ?>
	<form action='options.php' method='post'>
		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>
	</form>
	<?php
        break;
    endswitch; ?>
    </div>
  </div>
  <?php
}

function save_order_id_against_agecheckid($order_id)
{
	$options = get_option( 'agecheck_settings' );

	if (!isset($options['agechecked_settings_agecheckid'])) {
		return $order_id;
	}

	$agecheckid = $options['agechecked_settings_agecheckid'];
	$orderHistoryArrayFromMemory = isset($options['agechecked_settings_order_history']) ? $options['agechecked_settings_order_history'] : array();
	$arrayString = "Order ID: $order_id Agecheck ID: $agecheckid";

	if( in_array( $arrayString , $orderHistoryArrayFromMemory ) ){
		return $order_id;
	}

	if (!$orderHistoryArrayFromMemory) {
	$options['agechecked_settings_order_history'] = array( "Order ID: $order_id Agecheck ID: $agecheckid");
	update_option( "agecheck_settings", $options );
	} else {
		$updatedOrderHistoryArray = array_merge($orderHistoryArrayFromMemory, array( $arrayString )); 
		$options['agechecked_settings_order_history'] = $updatedOrderHistoryArray;
		update_option( "agecheck_settings", $options );
	}
	return $order_id;
}

add_action('woocommerce_thankyou_order_id','save_order_id_against_agecheckid');

function agecheck_settings_init(  ) { 

	register_setting( 'pluginPage', 'agecheck_settings' );

	add_settings_section(
		'agecheck_pluginPage_section', 
		__( '', 'agecheck' ), 
		'agecheck_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'agechecked_settings_url', 
		__( 'API URL', 'agecheck' ), 
		'agechecked_settings_url_render', 
		'pluginPage', 
		'agecheck_pluginPage_section' 
	);

	add_settings_field( 
		'agechecked_settings_public_key', 
		__( 'Public Key', 'agecheck' ), 
		'agechecked_settings_public_key_render', 
		'pluginPage', 
		'agecheck_pluginPage_section' 
	);

	add_settings_field( 
		'agechecked_settings_private_key', 
		__( 'Secret Key', 'agecheck' ), 
		'agechecked_settings_private_key_render', 
		'pluginPage', 
		'agecheck_pluginPage_section' 
	);

	add_settings_field(
		'agechecked_settings_order_history', 
		__( 'AgeChecked Order History', 'agecheck' ), 
		'agechecked_settings_order_history_render', 
		'pluginPage', 
		'agecheck_pluginPage_section' 
	);

	add_settings_field( 
		' ', 
		__( '<h2>2. WooCommerce Product Categories</h2>', 'agecheck' ), 
		' ', 
		'pluginPage', 
		'agecheck_pluginPage_section'
	);

	add_settings_field( 
		'agechecked_settings_select_product_categories', 
		__( 'Select Product Categories', 'agecheck' ), 
		'agechecked_settings_select_product_categories_render', 
		'pluginPage', 
		'agecheck_pluginPage_section' 
	);
	
	add_settings_field( 
		'', 
		__( '<h2>3. Extra Settings</h2>', 'agecheck' ), 
		'', 
		'pluginPage', 
		'agecheck_pluginPage_section' 
	);

	add_settings_field( 
		'agechecked_settings_enabled', 
		__( 'Enable/Disable', 'agecheck' ), 
		'agechecked_settings_enabled_render', 
		'pluginPage', 
		'agecheck_pluginPage_section' 
	);

	add_settings_field( 
		'agechecked_settings_select_pages', 
		__( 'Select Individual Pages', 'agecheck' ), 
		'agechecked_settings_select_pages_render', 
		'pluginPage', 
		'agecheck_pluginPage_section' 
	);
	
	add_settings_field( 
		'agechecked_settings_button_class_name', 
		__( 'Button onClick Event', 'agecheck' ), 
		'agechecked_settings_button_class_name_render', 
		'pluginPage', 
		'agecheck_pluginPage_section' 
	);

}


function agechecked_settings_enabled_render(  ) { 

	$options = get_option( 'agecheck_settings' );
	?>
	<input type='checkbox' name='agecheck_settings[agechecked_settings_enabled]' <?php checked( isset($options['agechecked_settings_enabled']) ? $options['agechecked_settings_enabled'] : false, 1 ); ?> value='1'>Click here to enable age checks for individual pages 
	<?php
		echo "<br/><p id='description-text' style='color: #666; font-size: 12px; margin-top: 10px'>Enables AgeCheck to work on your chosen pages.</p>";
	?>
	<?php

}


function agechecked_settings_url_render(  ) { 

	$options = get_option( 'agecheck_settings' );
	?>
	<input class="regular-text"  type='text' name='agecheck_settings[agechecked_settings_url]' value='<?php echo $options['agechecked_settings_url']; ?>'>
	<?php
	echo "<br/><p id='description-text' style='color: #666; font-size: 12px;'>Add your API URL. Please ensure the URL does not end with a '/'.</p>";
	?>
	<script>

</script>

<?php
}


function agechecked_settings_public_key_render(  ) { 

	$options = get_option( 'agecheck_settings' );
	?>
	<input class="regular-text"  type='text' name='agecheck_settings[agechecked_settings_public_key]' value='<?php echo $options['agechecked_settings_public_key']; ?>'>
	<?php
	echo "<br/><p id='description-text' style='color: #666; font-size: 12px;'>Add your Public Key.</p>";
	?>
	<script>
</script>

<?php

}


function agechecked_settings_private_key_render(  ) { 

	$options = get_option( 'agecheck_settings' );
	?>
	<input class="regular-text" type='text' name='agecheck_settings[agechecked_settings_private_key]' value='<?php echo $options['agechecked_settings_private_key']; ?>'>
	<?php	
	echo "<br/><p id='description-text' style='color: #666; font-size: 12px;'>Add your Secret Key.</p>";
	?>
	<script>
</script>

<?php
}

function agechecked_settings_select_pages_render(  ) { 
	$options = get_option( 'agecheck_settings' );
	$pageTitles = wp_list_pluck( get_pages(), 'post_title' );
    $numberOfPages = sizeof($pageTitles);
	$selectedPages = isset($options['agechecked_settings_select_pages']) ? $options['agechecked_settings_select_pages'] : array();

	?>

	<select name="agecheck_settings[agechecked_settings_select_pages][]" multiple size = $numberOfPages style="width: 400px;">
		<?php
		// Iterating through the pageTitles array
		foreach($pageTitles as $item):
		$selected = in_array($item, $selectedPages) ? 'selected' : '';
		echo '<option '.$selected.' value="'.$item.'">'.$item.'</option>';
		endforeach;
		?>
	</select>

	<?php
		echo "<br/><p id='description-text' style='color: #666; font-size: 12px; margin-top: 10px'>By default all pages are selected. Please hold the Ctrl key to select multiple pages</p>";
	?>
	<script>
</script>

<?php
}

function agechecked_settings_order_history_render(  ) {
	$options = get_option( 'agecheck_settings' );
	$orderHistoryData = isset($options['agechecked_settings_order_history']) ? $options['agechecked_settings_order_history'] : array();
	$numberOfOrders = sizeof($orderHistoryData);

	?>
	<select name="agecheck_settings[agechecked_settings_order_history][]" multiple size = $numberOfOrders style="width: 400px;">
	<?php
	// Iterating through the orderHistoryData array
	foreach($orderHistoryData as $item){
		?>
		<option selected value='<?php echo $item ?>'><?php echo $item ?></option>
		<?php
	}
	?>
</select>
<?php

}

function agechecked_settings_select_product_categories_render(  ) { 

	$orderby = 'name';
	$order = 'asc';
	$hide_empty = false ;
	$cat_args = array(
		'orderby'    => $orderby,
		'order'      => $order,
		'hide_empty' => $hide_empty,
	);
	 
	$product_categories = get_terms( 'product_cat', $cat_args );

	$productCategoryNames = array();
	 
	if( !empty($product_categories) ){	 
		foreach ($product_categories as $key => $category) {
			array_push($productCategoryNames, $category->name);
		}
	}

	$options = get_option( 'agecheck_settings' );
    $numberOfProductCategories = sizeof($productCategoryNames);
	$selectedProductCategories= isset($options['agechecked_settings_select_product_categories']) ? $options['agechecked_settings_select_product_categories'] : array();
	
	?>

	<select name="agecheck_settings[agechecked_settings_select_product_categories][]" multiple size = $numberOfProductCategories style="width: 400px;">
		<?php
        // Iterating through the productCategoryNames array
		foreach($productCategoryNames as $item):
		$selected = in_array($item, $selectedProductCategories) ? 'selected' : '';
		echo '<option '.$selected.' value="'.$item.'">'.$item.'</option>';
		endforeach;
		?>
	</select>

	<?php
		echo "<br/><p id='description-text' style='color: #666; font-size: 12px; margin-top: 10px'>Please hold the Ctrl key to select multiple product categories.</p>";
	?>
	<script>

</script>

<?php
}

function agechecked_settings_button_class_name_render(  ) { 
	echo "<strong style='padding: 5px;background: #eaeaea;'>agechecked-on-click-class-name</strong>";
	?>
	<?php
		echo "<br/><p id='description-text' style='color: #666; font-size: 12px; margin-top: 10px'>Add this className to the button you would like to activate the AgeChecked plugin. For an example of how to do this, please review the screenshots found on the <a target='_blank' href='https://wordpress.org/plugins/agecheck/#description'>support documentation</a></p>";
	?>
	<script>
</script>

<?php
}

function agecheck_settings_section_callback(  ) {
    	echo "<br/><b style='font-weight: bold' >1:</b> Enter your API URL, Public Key and Secret Key that was sent to you from AgeChecked into the below fields.<br/>";
    	echo "<b style='font-weight: bold' >2:</b> Select the WooCommerce product category that you would like to perform age checks on.<br/>";
    	echo "<b style='font-weight: bold' >3:</b> By default the AgeChecked plugin is enabled for all pages, however if you want to only enable the plugin on certain pages then please select those individual pages.<br/><br/>To perform agechecks when a user clicks a button on your website, add the following className to the button - <span style='padding: 5px; background: #eaeaea;'>agechecked-on-click-class-name</span><br/><br/>";
    	echo "If you have any questions or require any support, please review our <a target='_blank' href='https://wordpress.org/plugins/agecheck/#description'>support documentation</a>.";
		echo "<br/><br/><h2>1. API Keys and URL</h2>";

checks();
}

 function checks() {
	 	$options = get_option( 'agecheck_settings' );
	 
			if ( checked( isset($options['agechecked_settings_enabled']) ? $options['agechecked_settings_enabled'] : false, 0 ) ) {
				return false;
			}

			// PHP Version
			if ( version_compare( phpversion(), '5.3', '<' ) ) {
				echo '<div class="error"><p>' . sprintf( __( 'AgeChecked Error: AgeChecked requires PHP 5.3 and above. You are using version %s.', 'agecheck' ), phpversion() ) . '</p></div>';
				return false;
			}
			
			// Check required fields
			if (!$options['agechecked_settings_url']) {
				echo '<div class="error"><p>' . __( 'AgeChecked Error: Please enter your API URL', 'agecheck' ) . '</p></div>';
				return false;
			}
			
			// Check required fields
			if ( ! $options['agechecked_settings_public_key'] || ! $options['agechecked_settings_private_key'] ) {
				echo '<div class="error"><p>' . __( 'AgeChecked: Please enter your public and secret keys', 'agecheck' ) . '</p></div>';
				return false;
			}
			
			return true;
		}


function agecheck_options_page() { 
	?>
	<form action='options.php' method='post'>
		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>
	</form>
	<?php
}

function agechecked_enqueue_scripts()
{
	$options = get_option( 'agecheck_settings' );

	if(checked( isset($options['agechecked_settings_enabled']) ? $options['agechecked_settings_enabled'] : false, 0 ) || !checks()) return false;

	$public_key = $options['agechecked_settings_public_key'];
	$url = $options['agechecked_settings_url'];

	// Enqueues required JS files
	wp_enqueue_script('wp_cookie', plugins_url('agecheck/assets/js/jquery.cookie.js'), array('jquery'));

	$url = add_query_arg(array('merchantkey' => $public_key, 'version' => '1.0'), $url . '/jsapi/getjavascript');
	wp_enqueue_script('wp_agechecked_js', $url, array('jquery'), null);
	wp_enqueue_style('wp_agechecked_style', plugins_url('agecheck/assets/css/style.css'), array());
}

add_action( 'get_footer', 'agechecked_ui_for_class_name' );

//Loading AgeChecked UI on button click event
function agechecked_ui_for_class_name()
{
	?>
	<script>
var el = document.getElementsByClassName("agechecked-on-click-class-name");

if (el) {
	var i;

for (i = 0; i < el.length; i++) {
el[i].addEventListener("click", function() {

	const cookiestoview = jQuery.cookie();
	const isAgeCheckId = 'agechecked_agecheckid' in cookiestoview;
	const isAgeVerifiedId = 'agechecked_ageverifiedid' in cookiestoview;

	if(!isAgeCheckId && !isAgeVerifiedId){
		if (typeof Agechecked !== "undefined" && Agechecked.API.isloaded()) {
		Agechecked.API.registerreturn(function(d){
			Agechecked.API.modalclose();
			//alert(d.data);
			var msg = JSON.parse(d.data);
			if (msg.status == 6 || msg.status == 7 || msg.status == 12) {
				jQuery.cookie('agechecked_agecheckid', msg.agecheckid);
				jQuery.cookie('agechecked_ageverifiedid', msg.ageverifiedid);
			}
			else {
				// Let the user close the popup and continue to use the site
				// if (msg.agecheckedpopupurl) {
				// 	Agechecked.API.modalopen(msg.agecheckedpopupurl)
				// }
			}
		});
		Agechecked.API.createagecheckjson({
				mode: 'javascript',
				avtype: 'agechecked',
		}).done(function(json){
				Agechecked.API.modalopen(json.agecheckurl);
		});
		}
		else{
		alert("The AgeChecked service has not loaded correctly. Please ensure that you have entered the correct details. If the issue persists please get in contact with AgeChecked.");
		} 
	}	
	});
}
}
	</script>
	<?php
}

add_action( 'get_footer', 'agechecked_ui_for_woocommerce_submit' );

//Loading AgeChecked UI on button click event
function agechecked_ui_for_woocommerce_submit()
{
		$options = get_option( 'agecheck_settings' );
		$private_key = $options['agechecked_settings_private_key'];
		$url = $options['agechecked_settings_url'];

		// //Check if the current selected product categories is in the checkout cart

		if(!isset($options['agechecked_settings_select_product_categories'])) {
			return;
		}

		$selectedProductCategories = $options['agechecked_settings_select_product_categories'];
		
		$productCategoriesInCart = array();
	
		// Loop through cart items
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			// checking for the specific product category name
			$new_term_slugs = wp_get_post_terms( $cart_item['product_id'], 'product_cat', array('fields' => 'names') );
			array_push($productCategoriesInCart, ...$new_term_slugs);
		}


		if (!count(array_intersect($selectedProductCategories, $productCategoriesInCart))) {
			return;
		}

	?>
	<script>

var el = document.getElementsByClassName("checkout woocommerce-checkout");

if (el) {
	var i;

for (i = 0; i < el.length; i++) {
	el[i].addEventListener('submit', (event) => {
		triggerClientAPI();
});
}
}

function getElement( element ) {
	return (element != null) ? element.value : null;
}

function triggerClientAPI() {
var nameValue = getElement(document.getElementById("billing_first_name"));
var lastNameValue = getElement(document.getElementById("billing_last_name"));
var buildingValue = getElement(document.getElementById("billing_address_2"));
var streetValue = getElement(document.getElementById("billing_address_1"));
var postCodeValue = getElement(document.getElementById("billing_postcode"));
var emailValue = getElement(document.getElementById("billing_email"));
// var countryCodeValue = document.getElementById("select2-billing_country-container").value; //This needs to be mapped to a properties file which can convert United Kingdom for example 
// TO DO : No longer hard code this
var countryCodeValue = "GB";

// var billingCountrySelectElement = document.getElementById("billing_country");
// var countryCodeValue = billingCountrySelectElement.options[billingCountrySelectElement.selectedIndex].text;

event.preventDefault();
event.stopImmediatePropagation();

var privateKey = "<?php echo"$private_key"?>";
var enteredURL = "<?php echo"$url"?>" + "/acapiremote/cceroll";

jQuery.ajax({
	type: "POST",
	data: {
		"name": nameValue,
		"surname": lastNameValue,
		"building": buildingValue,
		"street": streetValue,
		"postCode": postCodeValue,
		"countryCode": countryCodeValue,
		"email": emailValue,
		"privateKey": privateKey,
		"enteredURL": enteredURL
	},
	success: function(data) {
		//console.log('data: ' + JSON.stringify(data));
		if (data.authenticated === true) {

			var agecheckid = data.avstatus.agecheckid || null;

				// Call to save agecheckid to options table
				jQuery.ajax({
					type: "POST",
					data: {"agecheckID": agecheckid},
					success: function(data)
					{
						jQuery('.woocommerce-checkout').submit();
					},
					error: function(data) {
						alert("something went wrong");
					}
				});
			
		} else {
			triggerConsumerGateway();
		}
	},
	error: function(data) {
		//console.log('data: ' + JSON.stringify(data));
		alert("something went wrong")
	}
});
}

function triggerConsumerGateway() {
	const cookiestoview = jQuery.cookie();
	const isAgeCheckId = 'agechecked_agecheckid' in cookiestoview;
	const isAgeVerifiedId = 'agechecked_ageverifiedid' in cookiestoview;

	if(!isAgeCheckId && !isAgeVerifiedId){
		if (typeof Agechecked !== "undefined" && Agechecked.API.isloaded()) {
		Agechecked.API.registerreturn(function(d){
			Agechecked.API.modalclose();
			//alert(d.data);
			var msg = JSON.parse(d.data);
			if (msg.status == 6 || msg.status == 7 || msg.status == 12) {
				jQuery.cookie('agechecked_agecheckid', msg.agecheckid);
				jQuery.cookie('agechecked_ageverifiedid', msg.ageverifiedid);
				jQuery('.woocommerce-checkout').submit();
			}
			else {
				// Let the user close the popup and continue to use the site
				// if (msg.agecheckedpopupurl) {
				// 	Agechecked.API.modalopen(msg.agecheckedpopupurl)
				// }
			}
		});
		Agechecked.API.createagecheckjson({
				mode: 'javascript',
				avtype: 'agechecked',
		}).done(function(json){
				var agecheckid = json.agecheckid || null;

				// Call to save agecheckid to options table
				jQuery.ajax({
					type: "POST",
					data: {"agecheckID": agecheckid},
					success: function(data)
					{
						Agechecked.API.modalopen(json.agecheckurl);
					},
					error: function(data) {
						alert("something went wrong");
					}
				});

		});
		}
		else{
		alert("The AgeChecked service has not loaded correctly. Please ensure that you have entered the correct details. If the issue persists please get in contact with AgeChecked.");
		} 
	} else {
		jQuery('.woocommerce-checkout').submit();
	}
}

	</script>
	<?php
}

add_action( 'get_footer', 'agechecked_ui' );

//Loading AgeChecked UI on website loading
function agechecked_ui()
{
	$options = get_option( 'agecheck_settings' );

	//echo "<script>console.log('PHP: " . json_encode($options) . "');</script>";

	if(!array_key_exists( 'agechecked_settings_enabled', $options ) || !checks()) return false;

	//Check if the current page is in the selected pages array	
	$selectedPages = isset($options['agechecked_settings_select_pages']) ? $options['agechecked_settings_select_pages'] : array();
  	$currentpagetitle = get_the_title();

  		if (!in_array( $currentpagetitle ,$selectedPages ) && !empty($selectedPages)) {
  		      		return false;
  		}
		
	?>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>

	<script>
	const cookiestoview = jQuery.cookie();
	const isAgeCheckId = 'agechecked_agecheckid' in cookiestoview;
	const isAgeVerifiedId = 'agechecked_ageverifiedid' in cookiestoview;

	if(!isAgeCheckId && !isAgeVerifiedId){
		if (typeof Agechecked !== "undefined" && Agechecked.API.isloaded()) {
		   Agechecked.API.registerreturn(function(d){
			   Agechecked.API.modalclose();
			   //alert(d.data);
			   var msg = JSON.parse(d.data);
			   if (msg.status == 6 || msg.status == 7 || msg.status == 12) {
				jQuery.cookie('agechecked_agecheckid', msg.agecheckid);
				jQuery.cookie('agechecked_ageverifiedid', msg.ageverifiedid);
			   }
			   else {
				// Let the user close the popup and continue to use the site
				//  if (msg.agecheckedpopupurl) {
				// 	Agechecked.API.modalopen(msg.agecheckedpopupurl)
				//  }
			   }
		   });
		   Agechecked.API.createagecheckjson({
				mode: 'javascript',
				avtype: 'agechecked',
		   }).done(function(json){
				Agechecked.API.modalopen(json.agecheckurl);
		   });
		}
		else{
			alert("The AgeChecked service has not loaded correctly. Please ensure that you have entered the correct details. If the issue persists please get in contact with AgeChecked.");
		 exit;
		} 
	}	
	</script>
	<?php
}

function my_plugin_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=agechecked_for_wordpress">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'my_plugin_settings_link' );

?>