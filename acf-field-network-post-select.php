<?php
/*
Plugin Name: Advanced Custom Fields: Network posts select field
Plugin URI: https://github.com/timiwahalahti/acf-field-post-object-network/
Description: Adds a ACF field that allows selecting posts across the network sites.
Version: 1.2.1
Author: Timi Wahalahti
Author URI: https://sipp.is
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Network: true
*/

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class sippis_acf_plugin_network_post_select {

  var $settings;

  function __construct() {
    $this->settings = array(
      'version'  => '1.2.0',
      'url'      => plugin_dir_url( __FILE__ ),
      'path'     => plugin_dir_path( __FILE__ )
    );

    add_action( 'acf/include_field_types',  array( $this, 'include_field' ) );
  } // end __construct

  function include_field( $version = false ) {
    load_plugin_textdomain( 'sippis-acf-field-network-post-select', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );
    include_once( 'class-sippis-acf-field-network-post-select.php' );
  } // end include_field
} // end class

new sippis_acf_plugin_network_post_select();
