<?php
/**
 * Plugin Name: Oklink
 * Plugin URI: https://github.com/oklink/oklink-wordpress
 * Description: Add Oklink payment buttons to your WordPress site.
 * Version: 1.0
 * Author: Coinlink Inc.
 * Author URI: https://oklink.com
 * License: GPLv2 or later
 */
define('OKLINK_PATH', plugin_dir_path( __FILE__ ));
define('OKLINK_URL', plugins_url( '', __FILE__ ));

require_once(OKLINK_PATH . 'lib/OKLink.php');
require_once(OKLINK_PATH . 'widget.php');

class WP_Oklink {

  private $plugin_path;
  private $plugin_url;
  private $l10n;
  private $wpsf;

  function __construct() {  
    $this->plugin_path = OKLINK_PATH;
    $this->plugin_url = OKLINK_URL;
    $this->l10n = 'wp-settings-framework';
    add_action( 'admin_menu', array(&$this, 'admin_menu'), 99 );
    add_action( 'admin_init', array(&$this, 'admin_init'), 99 );
    add_action('admin_enqueue_scripts', array(&$this, 'admin_styles'), 1);
    add_action('admin_enqueue_scripts', array(&$this, 'widget_scripts'));

    // Include and create a new WordPressSettingsFramework
    require_once( $this->plugin_path .'wp-settings-framework.php' );
    $this->wpsf = new WordPressSettingsFramework( $this->plugin_path .'settings/oklink.php' );

    add_shortcode('oklink_button', array(&$this, 'shortcode'));
  }

  function admin_menu() {
    add_submenu_page( 'options-general.php', __( 'oklink', $this->l10n ), __( 'oklink', $this->l10n ), 'update_core', 'oklink', array(&$this, 'settings_page') );
  }

  function admin_init() {
    register_setting ( 'oklink', 'oklink-tokens' );
  }

  function settings_page() {
   $api_key = wpsf_get_setting( 'oklink', 'general', 'api_key' );
   $api_secret = wpsf_get_setting( 'oklink', 'general', 'api_secret' );

    ?>
      <div class="wrap">
        <div id="icon-options-general" class="icon32"></div>
        <h2>Oklink</h2>

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

    $args = shortcode_atts($defaults, $atts, 'oklink_button');

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

    $api_key = wpsf_get_setting( 'oklink', 'general', 'api_key' );
    $api_secret = wpsf_get_setting( 'oklink', 'general', 'api_secret' );
    if( $api_key && $api_secret ) {
      try {
        $client = OKlink::withApiKey($api_key, $api_secret);
        $params = array("price" => floatval($args['price_string']),"price_currency" => $args['price_currency_iso'],"name" => $args["name"]);
        $response = $client->buttonsButton($params);
        var_dump($response->button);
        $button_html = OKLinkUtil::generateButton($response->button);
      } catch (Exception $e) {
        $msg = $e->getMessage();
        error_log($msg);
        return "There was an error connecting to oklink: $msg. Please check your internet connection and API credentials.";
      }
      set_transient($transient_name, $button);
      return $button_html;
    } else {
      return "The oklink plugin has not been properly set up - please visit the oklink settings page in your administrator console.";
    }
  }

  public function admin_styles() {
    wp_enqueue_style( 'oklink-admin-styles', OKLINK_URL .'/css/oklink-admin.css', array(), '1', 'all' );
  }

  public function widget_scripts( $hook ) {
    if( 'widgets.php' != $hook )
      return;
    wp_enqueue_script( 'oklink-widget-scripts', OKLINK_URL .'/js/oklink-widget.js', array('jquery'), '', true );
  }

}
new WP_Oklink();

?>