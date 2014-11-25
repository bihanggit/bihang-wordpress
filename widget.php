<?php

/* 

Copyright (C) 2014 Coinbase Inc.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

/**
 * Registers the Coinbase Button widget
 *
 */

class Oklink_Button extends WP_Widget { 

  private $currencies = array( 'BTC' => 'BTC', 'USD' => 'USD', 'CNY' => 'CNY', );

  /**
   * Register widget with WordPress.
   */
  public function __construct() {
    //global $wid, $wname;
    parent::__construct(
      'Oklink_button', // Base ID
      'Oklink Button', // Name
      array( 'description' => __( 'Displays a Oklink button in your sidebar', 'text_domain' ), ) // Args
    );
    //add_action('admin_enqueue_scripts', array(&$this, 'admin_styles'), 1);
  }

  /**
   * Front-end display of widget.
   *
   * @see WP_Widget::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function widget( $args, $instance ) {

    extract( $args );
    $title = apply_filters( 'widget_title', $instance['title'] );

    echo $before_widget;
    if ( ! empty( $title ) )
      echo $before_title . $title . $after_title;

    $button_defaults = array(
              'name' => '',
              'price' => '',
              'price_currency' => '',
              'callback_url' => '',
              'success_url' => '',
               );

    $button_args = array();

    foreach ($instance as $k => $v) {
      if ( $k != 'title'){
        if( $k == 'price'){
            $button_args[$k] = (float)$v;
        }else{
            $button_args[$k] = $v;
        }
      }
    }

    $transient_name = 'cb_ecc_' . md5(serialize($button_args));
    $cached = get_transient($transient_name);
    if($cached !== false) {
      // Cached
      echo $cached;
    } else {

      $api_key = wpsf_get_setting( 'oklink', 'general', 'api_key' );
      $api_secret = wpsf_get_setting( 'oklink', 'general', 'api_secret' );
      if( $api_key && $api_secret ) {
        try {
          $Oklink = OkLink::withApiKey($api_key, $api_secret);
          $response = $Oklink->buttonsButton($button_args);
          $button = $response->button;
          $button_html =  OKLinkUtil::generateBUtton($button);
        } catch (Exception $e) {
          $msg = $e->getMessage();
          error_log($msg);
          echo "There was an error connecting to Oklink: $msg. Please check your internet connection and API credentials.";
        }
        set_transient($transient_name, $button_html);
        echo $button_html;
      } else {
        echo "The Oklink plugin has not been properly set up - please visit the Oklink settings page in your administrator console.";
      }
    }

    echo $after_widget;
  }

  /**
   * Sanitize widget form values as they are saved.
   *
   * @see WP_Widget::update()
   *
   * @param array $new_instance Values just sent to be saved.
   * @param array $old_instance Previously saved values from database.
   *
   * @return array Updated safe values to be saved.
   */
  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = esc_attr(strip_tags( $new_instance['title'] ));
    $instance['name'] = esc_attr(strip_tags( $new_instance['name'] ));
    $instance['price_currency'] = esc_attr(strip_tags( $new_instance['price_currency'] ));

    $price = $new_instance['price'];
    if (!is_numeric(substr($price, 0, 1)))
      $price = substr($price, 1);
    $instance['price'] = (string) $price;
    $instance['callback_url'] = esc_attr(strip_tags( $new_instance['callback_url'] ));
    $instance['success_url'] = esc_attr(strip_tags( $new_instance['success_url'] ));
    return $instance;
  }

  /**
   * Back-end widget form.
   *
   * @see WP_Widget::form()
   *
   * @param array $instance Previously saved values from database.
   */
  public function form( $instance ) {
    $defaults = array(
          'title' => '',
          'name' => '',
          'price' => '',
          'price_currency' => 'CYN',
          'callback_url' => '',
          'success_url' => '',
    );
    extract(wp_parse_args($instance, $defaults));
    $price = (float) $price;

    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'name' ); ?>"><?php _e( 'Item Name:' ); ?></label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'name' ); ?>" name="<?php echo $this->get_field_name( 'name' ); ?>" type="text" value="<?php echo esc_attr( $name ); ?>" />
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'currency' ); ?>"><?php _e( 'Amount:' ); ?></label>
      <span>
        <select class="widefat oklink-currency" id="<?php echo $this->get_field_id( 'price_currency' ); ?>" name="<?php echo $this->get_field_name('price_currency'); ?>">
            <?php
            foreach ($this->currencies as $k => $v) {
              echo '<option value="' . $k . '"'
                . ( $k == $price_currency_iso ? ' selected="selected"' : '' )
                . '>' . $v . "</option>\n";
            }
            ?>
        </select> 

        <input class="widefat oklink-price" id="<?php echo $this->get_field_id( 'price' ); ?>" name="<?php echo $this->get_field_name( 'price' ); ?>" type="text" value="<?php echo esc_attr( $price ); ?>" />
      </span>
    </p>
    <p>
    <label for="<?php echo $this->get_field_id( 'callback_url' ); ?>"><?php _e( 'Callback Url:' ); ?></label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'callback_url' ); ?>" name="<?php echo $this->get_field_name( 'callback_url' ); ?>" type="text" value="<?php echo esc_attr( $callback_url ); ?>" />
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'success_url' ); ?>"><?php _e( 'Success Url:' ); ?></label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'success_url' ); ?>" name="<?php echo $this->get_field_name( 'success_url' ); ?>" type="text" value="<?php echo esc_attr( $success_url ); ?>" />
    </p>
    <?php 
  }

} // Widget class 

// register the widget
add_action( 'widgets_init', create_function( '', "register_widget( 'Oklink_Button' );" ) );
