<?php
/**
 * Plugin Name: Bihang
 * Plugin URI: https://github.com/bihanggit/bihang-wordpress
 * Description: Add bihang payment buttons to your WordPress site.
 * Version: 1.1
 * Author: Bihang Inc.
 * Author URI: https://bihang.com
 * License: GPLv2 or later
 */
define('BIHANG_PATH', plugin_dir_path( __FILE__ ));
define('BIHANG_URL', plugins_url( '', __FILE__ ));

require_once(BIHANG_PATH . 'lib/Bihang.php');
require_once(BIHANG_PATH . 'widget.php');

class WP_Bihang {

  private $plugin_path;
  private $plugin_url;
  private $l10n;
  private $wpsf;

  function __construct() {  
    $this->plugin_path = BIHANG_PATH;
    $this->plugin_url = BIHANG_URL;
    $this->l10n = 'wp-settings-framework';
    add_action( 'admin_menu', array(&$this, 'admin_menu'), 99 );
    add_action( 'admin_init', array(&$this, 'admin_init'), 99 );
    add_action('admin_enqueue_scripts', array(&$this, 'admin_styles'), 1);
    add_action('admin_enqueue_scripts', array(&$this, 'widget_scripts'));

    // Include and create a new WordPressSettingsFramework
    require_once( $this->plugin_path .'wp-settings-framework.php' );
    $this->wpsf = new WordPressSettingsFramework( $this->plugin_path .'settings/bihang.php' );

    add_shortcode('bihang_button', array(&$this, 'shortcode'));
  }

  function admin_menu() {
    add_submenu_page( 'options-general.php', __( 'bihang', $this->l10n ), __( 'bihang', $this->l10n ), 'update_core', 'bihang', array(&$this, 'settings_page') );
  }

  function admin_init() {
    register_setting ( 'bihang', 'bihang-tokens' );
  }

  function settings_page() {
   $api_key = wpsf_get_setting( 'bihang', 'general', 'api_key' );
   $api_secret = wpsf_get_setting( 'bihang', 'general', 'api_secret' );

    ?>
      <div class="wrap">
        <div id="icon-options-general" class="icon32"></div>
        <h2>Bihang</h2>

    <?php
        $this->wpsf->settings();
    ?>
      </div>
    <?php
  }

  function shortcode( $atts, $content = null ) {
    $defaults = array(
          'name'               => 'test',
          'price_string'       => '1.23',
          'price_currency_iso' => 'USD',
          'custom'             => 'Order123',
          'description'        => 'Sample description',
          'type'               => 'buy_now',
          'style'              => 'buy_now_large',
          'text'               => 'Pay with Bitcoin',
          'choose_price'       => false,
          'variable_price'     => false,
          'price1'             => '0.0',
          'price2'             => '0.0',
          'price3'             => '0.0',
          'price4'             => '0.0',
          'price5'             => '0.0',
    );

    $args = shortcode_atts($defaults, $atts, 'bihang_button');

    // Clear default price suggestions
    for ($i = 1; $i <= 5; $i++) {
      if ($args["price$i"] == '0.0') {
        unset($args["price$i"]);
      }
    }

    $transient_name = 'cb_ecc_' . md5(serialize($args));
    $cached = get_transient($transient_name);
    $cached = false;
    if($cached !== false) {
      return $cached;
    }

    $api_key = wpsf_get_setting( 'bihang', 'general', 'api_key' );
    $api_secret = wpsf_get_setting( 'bihang', 'general', 'api_secret' );
    if( $api_key && $api_secret ) {
      try {
        $client = Bihang::withApiKey($api_key, $api_secret);
        $params = array("price" => floatval($args['price_string']),"price_currency" => $args['price_currency_iso'],"name" => $args["name"]);
        $response = $client->buttonsButton($params);
        $button = $response->button;
        $button_html =  "<a class=\"bihang-button\" target=\"_blank\" data-style=\"\" data-code=\"\" href=\"".BihangBase::WEB_BASE."merchant/mPayOrderStemp1.do?buttonid=".$button->id."\"><img alt=\"\" src=\"https://www.bihang.com/image/merchant/button_one_small.png\"></a>";
      } catch (Exception $e) {
        $msg = $e->getMessage();
        error_log($msg);
        return "There was an error connecting to bihang: $msg. Please check your internet connection and API credentials.";
      }
      set_transient($transient_name, $button);
      return $button_html;
    } else {
      return "The bihang plugin has not been properly set up - please visit the bihang settings page in your administrator console.";
    }
  }

  public function admin_styles() {
    wp_enqueue_style( 'bihang-admin-styles', BIHANG_URL .'/css/bihang-admin.css', array(), '1', 'all' );
  }

  public function widget_scripts( $hook ) {
    if( 'widgets.php' != $hook )
      return;
    wp_enqueue_script( 'bihang-widget-scripts', BIHANG_URL .'/js/bihang-widget.js', array('jquery'), '', true );
  }

}
new WP_Bihang();

?>