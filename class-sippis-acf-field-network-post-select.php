<?php if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class sippis_acf_field_network_post_select extends acf_field {

  function __construct( $settings ) {
    $this->settings = $settings;

    $this->name     = 'network_post_select';
    $this->label    = __('Network posts select', 'sippis-acf-field-network-post-select');
    $this->category = 'relational';
    $this->defaults = [
      'post_type'   => [],
      'taxonomy'    => [],
      'allow_null'  => false,
      'multiple'    => false,
      'ui'          => true,
    ];

    add_action( 'wp_ajax_acf/fields/network_post_select/query',         [ $this, 'ajax_query' ] );
    add_action( 'wp_ajax_nopriv_acf/fields/network_post_select/query',  [ $this, 'ajax_query' ] );

    parent::__construct();
  } // end __construct

  function ajax_query() {
    
    $nonce = acf_request_arg( 'nonce', '' );
		$key   = acf_request_arg( 'field_key', '' );
    
    if ( ! acf_verify_ajax( $nonce, $key ) ) {
      die();
    }

    $response = $this->get_ajax_query( $_POST );

    acf_send_ajax_results( $response );
  } // end ajax_query

  /**
   * Get posts to return for AJAX query.
   *
   * @param  array  $options  options from AJAX
   * @return array            array of posts
   */
  function get_ajax_query( $options = [] ) {
    $results    = [];
    $args       = [];
    $s          = false;
    $is_search  = false;

    // default options
    $options = acf_parse_args( $options, [
      'post_id'   => false,
      's'         => '',
      'field_key' => '',
      'paged'     => true,
    ] );

    // var_dump( $options );

    // load field
    $field = acf_get_field( $options['field_key'] );
    if ( ! $field ) {
      return false;
    }

    // allowed sites
    $get_sites_args = [];
    if ( ! empty( $field['site_id'] ) ) {
      $get_sites_args['site__in'] = acf_get_array( $field['site_id'] );
    }

    // filters for get_sites query
    $get_sites_args = apply_filters( 'acf/fields/network_post_select/get_sites', $get_sites_args, $field, $options['post_id'] );
    $get_sites_args = apply_filters( 'acf/fields/network_post_select/get_sites/name=' . $field['name'], $get_sites_args, $field, $options['post_id'] );
    $get_sites_args = apply_filters( 'acf/fields/network_post_select/get_sites/key=' . $field['key'], $get_sites_args, $field, $options['post_id'] );

    // get sites in network
    $sites = get_sites( $get_sites_args );

    // store current site id
    $current_site_id = get_current_blog_id();

    // is the query paged
    $args['posts_per_page'] = 20;
    $args['paged'] = $options['paged'];

    // is the query search
    if ( $options['s'] !== '' ) {
      $s = wp_unslash( strval( $options['s'] ) ); // strip slashes (search may be integer)
      $args['s'] = $s;
      $is_search = true;
    }

    // loop sites
    foreach ( $sites as $site ) {
      switch_to_blog( $site->blog_id );

      // post types for query
      if ( ! empty( $field['post_type'] ) ) {
        $args['post_type'] = acf_get_array( $field['post_type'] );
      } else {
        $args['post_type'] = acf_get_post_types();
      }

      // taxonomies for query
      if ( ! empty( $field['taxonomy'] ) ) {
        $terms = acf_decode_taxonomy_terms( $field['taxonomy'] );

        $args['tax_query'] = [];
        foreach( $terms as $k => $v ) {
          $args['tax_query'][] = [
            'taxonomy'  => $k,
            'field'     => 'slug',
            'terms'     => $v,
          ];
        }
      }

      // filters
      $args = apply_filters( 'acf/fields/network_post_select/query', $args, $field, $options['post_id'] );
      $args = apply_filters( 'acf/fields/network_post_select/query/name=' . $field['name'], $args, $field, $options['post_id'] );
      $args = apply_filters( 'acf/fields/network_post_select/query/key=' . $field['key'], $args, $field, $options['post_id'] );
      
      if ( ! array_key_exists( 's', $args ) ) {
        $is_search = false;
      }

      // get posts grouped by post type
      $groups = acf_get_grouped_posts( $args );

      // loop post groups
      foreach ( array_keys( $groups ) as $group_title ) {
        $posts = acf_extract_var( $groups, $group_title );

        $data = [
          'text'      => get_bloginfo( 'name' ) . ' - ' . strtolower( $group_title ),
          'children'  => []
        ];

        // convert post objects to post titles
        foreach ( array_keys( $posts ) as $post_id ) {
          $posts[ $post_id ] = $this->get_post_title( $posts[ $post_id ], $field, $options['post_id'], $is_search );
        }

        // order posts by search
        if ( $is_search && empty( $args['orderby'] ) ) {
          $posts = acf_order_by_search( $posts, $args['s'] );
        }

        // append to $data
        foreach ( array_keys( $posts ) as $post_id ) {
          $data['children'][] = $this->get_post_result( $post_id, $posts[ $post_id ], $site->blog_id );
        }

        $results[] = $data;
      }
    }

    // switch back to current site
    switch_to_blog( $current_site_id );

    // optgroup or single
    $post_type = acf_get_array( $args['post_type'] );
    if ( count( $post_type ) === 1 ) {
      if ( isset( $results[0] ) ) {
        $results = $results[0]['children'];
      }
    }

    $response = [
      'results' => $results,
      'limit'   => $args['posts_per_page']
    ];

    return $response;
  } // end get_ajax_query

  /**
   * Shape single post to AJAX query result suitable form.
   *
   * @param  integer  $id      post ID
   * @param  string   $text    text of option
   * @param  integer  $site_id network site ID
   * @return array
   */
  function get_post_result( $id, $text, $site_id ) {
    $result = [
      'id'    => $site_id . '|' . $id,
      'text'  => $text
    ];

    $search = '| ' . __( 'Parent', 'acf' ) . ':';
    $pos = strpos( $text, $search );

    if ( $pos !== false ) {
      $result['description'] = substr( $text, $pos+2 );
      $result['text'] = substr( $text, 0, $pos );
    }

    return $result;
  } // end get_post_result

  /**
   * Get title for the post.
   *
   * @param  object   $post      post object
   * @param  array    $field     field options
   * @param  integer  $post_id   post ID
   * @param  integer  $is_search if request comes from search
   * @return string
   */
  function get_post_title( $post, $field, $post_id = 0, $is_search = 0 ) {
    $current_site_id = get_current_blog_id();

    // switch to correct site for getting the title from right post
    switch_to_blog( $post->blog_id );

    // get post_id
    if ( ! $post_id ) {
      $post_id = acf_get_form_data( 'post_id' );
    }

    $title = acf_get_post_title( $post, $is_search );

    // add site name to title
    $site = get_blog_details( $post->blog_id );
    $title = $title . ' (' . $site->blogname . ')';

    // filters
    $title = apply_filters('acf/fields/network_post_select/result', $title, $post, $field, $post_id);
    $title = apply_filters('acf/fields/network_post_select/result/name=' . $field['_name'], $title, $post, $field, $post_id);
    $title = apply_filters('acf/fields/network_post_select/result/key=' . $field['key'], $title, $post, $field, $post_id);

    // switch back to current site
    switch_to_blog( $current_site_id );

    return $title;
  } // end get_post_title

  /**
   * Change the format in which field value is saved on databse.
   *
   * @param  mixed    $value   value which will be saved
   * @param  integer  $post_id post ID of which the value will be saved
   * @param  array    $field   field options
   * @return array
   */
  function update_value( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
      return $value;
    }

    // store site_id and post_id separately
    $exploded = explode( '|', $value );
    $value = [
      'site_id'  => acf_get_numeric( $exploded[0] ),
      'post_id'  => acf_get_numeric( $exploded[1] ),
    ];

    return $value;
  } // end update_value


  /*
  *  get_posts
  *
  *  This function will return an array of posts for a given field value
  *
  *  @type  function
  *  @date  13/06/2014
  *  @since 5.0.0
  *
  *  @param $value (array)
  *  @return  $value
  */

  /**
   * Get array of posts with given ID's.
   *
   * @param  mixed  $value post ID's
   * @param  array  $field field options
   * @return array
   */
  function get_posts( $value, $field ) {
    $posts = [];

    // allowed sites
    $get_sites_args = [];
    if ( ! empty( $field['site_id'] ) ) {
      $get_sites_args['site__in'] = acf_get_array( $field['site_id'] );
    }

    // filters for get_sites query
    $get_sites_args = apply_filters( 'acf/fields/network_post_select/get_sites', $get_sites_args, $field, $options['post_id'] );
    $get_sites_args = apply_filters( 'acf/fields/network_post_select/get_sites/name=' . $field['name'], $get_sites_args, $field, $options['post_id'] );
    $get_sites_args = apply_filters( 'acf/fields/network_post_select/get_sites/key=' . $field['key'], $get_sites_args, $field, $options['post_id'] );
    
    // get sites in network
    $sites = get_sites( $get_sites_args );

    // store current site id
    $current_site_id = get_current_blog_id();
    
    $args = [
      'post__in'  => $value,
      'post_type' => $field['post_type'],
    ];

    if ( empty( $value ) ) {
      $args['posts_per_page'] = $this->query_defaults['posts_per_page'];
    }

    // loop sites
    foreach ( $sites as $site ) {
      switch_to_blog( $site->blog_id );

      // get posts
      $new_posts = acf_get_posts( $args );

      foreach ( $new_posts as $key => $new_post ) {
        $new_posts[ $key ]->blog_id = $site->blog_id;
      }

      $posts = array_merge( $posts, $new_posts );
    }

    // switch back to current site
    switch_to_blog( $current_site_id );

    return $posts;
  } // end get_posts

  /**
   * Render the field in admin.
   *
   * @param  array  $field field options
   */
  function render_field( $field ) {
    $field['type']      = 'select';
    // $field['multiple']  = false;
    $field['ui']        = true;
    $field['ajax']      = true;
    $field['nonce']     = wp_create_nonce( $field['key'] );
    $field['choices']   = [];

    // try to get posts based on field value
    $posts = $this->get_posts( $field['value'], $field );

    if ( $posts ) {
      foreach ( array_keys( $posts ) as $i ) {
        $post = acf_extract_var( $posts, $i );

        // add posts found to choices available without select2
        $field['choices'][ $post->blog_id . '|' . $post->ID ] = $this->get_post_title( $post, $field );
      }
    }

    // change field value format so it's in same format with AJAX query return.
    $field['value'] = isset($field['value'], $field['value']['site_id'], $field['value']['post_id']) ? $field['value']['site_id'] . '|' . $field['value']['post_id'] : '';

    acf_render_field( $field );
  } // end render_field

  function input_admin_enqueue_scripts() {
    $url      = $this->settings['url'];
    $version  = $this->settings['version'];

    wp_register_script( 'acf-field-network-post-select', "{$url}assets/js/input.js", [ 'acf-input' ], $version );
    wp_enqueue_script( 'acf-field-network-post-select' );
  } // end input_admin_enqueue_scripts

  /**
   * Render field settings when adding the field.
   *
   * @param  array  $field field options
   */
  function render_field_settings( $field ) {
    $sites = [];
    $sites_raw = get_sites( [
      'number'  => apply_filters( 'acf/fields/network_post_select/settings/max_sites', 100, $field ),
    ] );

    foreach ( $sites_raw as $site ) {
      $site = get_blog_details( $site->blog_id );
      $sites[ $site->blog_id ] = $site->blogname;
    }

    acf_render_field_setting( $field, [
      'label'         => __( 'Filter by Post Type', 'acf' ),
      'instructions'  => '',
      'type'          => 'select',
      'name'          => 'post_type',
      'choices'       => acf_get_pretty_post_types(),
      'multiple'      => true,
      'ui'            => true,
      'allow_null'    => true,
      'placeholder'   => __( 'All post types', 'acf' ),
    ] );

    acf_render_field_setting( $field, [
      'label'         => __( 'Filter by Taxonomy', 'acf' ),
      'instructions'  => '',
      'type'          => 'select',
      'name'          => 'taxonomy',
      'choices'       => acf_get_taxonomy_terms(),
      'multiple'      => true,
      'ui'            => true,
      'allow_null'    => true,
      'placeholder'   => __( 'All taxonomies', 'acf' ),
    ] );

    acf_render_field_setting( $field, [
      'label'         => __( 'Filter by Site', 'sippis-acf-field-network-post-select' ),
      'instructions'  => '',
      'type'          => 'select',
      'name'          => 'site_id',
      'choices'       => $sites,
      'multiple'      => true,
      'ui'            => true,
      'allow_null'    => true,
      'placeholder'   => __( 'All sites', 'sippis-acf-field-network-post-select' ),
    ] );

    acf_render_field_setting( $field, [
      'label'         => __( 'Allow Null?', 'acf' ),
      'instructions'  => '',
      'name'          => 'allow_null',
      'type'          => 'true_false',
      'ui'            => true,
    ] );

    acf_render_field_setting( $field, [
      'label'         => __( 'Select multiple values?', 'acf' ),
      'instructions'  => '',
      'name'          => 'multiple',
      'type'          => 'true_false',
      'ui'            => true,
    ] );
  } // end render_field_settings
} // end class

// initialize
new sippis_acf_field_network_post_select( $this->settings );
