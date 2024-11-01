<?php
/**
 * Plugin Name: Simple Child Theme Creator
 * Description: Create a child theme properly in a single click!
 * Version: 1.0.0
 * Author: Cyrus David
 * Author URI: https://jcyr.us
 * Text Domain: sctc
 * Domain Path: /lang
 */

function sct_admin_menu() {
    if ( ! current_user_can( 'install_themes' ) ) {
        return;
    }
    add_submenu_page( 'themes.php', __( 'Create Child Theme', 'sctc' ), __( 'Create Child Theme', 'sctc' ), 'install_themes', 'sct', 'sct_admin_page' );
}
add_action( 'admin_menu', 'sct_admin_menu' );


function sct_admin_page() {
    if ( is_child_theme() ) {
        add_settings_error( 'sctc', 'sctc', __( 'The currently active theme is already a child theme.', 'sctc' ) );
    }

    include __DIR__ . '/view/admin.php';
}

function sct_admin_post() {
    if ( ! current_user_can( 'install_themes' ) ) {
        return;
    }

    do_action( 'wp_enqueue_scripts' );
    $redirect_uri = add_query_arg( 'settings-updated', '1', admin_url( 'themes.php?page=sct' ) );

    if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_nonce' ), 'sct_create_theme' ) ) {
        add_settings_error( 'sctc', 'sctc', __( 'Try again.', 'sct' ) );
        wp_redirect( $redirect_uri );
        exit;
    }

    if ( is_child_theme() ) {
        add_settings_error( 'sctc', 'sctc', __( 'The currently active theme is already a child theme.', 'sctc' ) );
        set_transient( 'settings_errors', get_settings_errors() );
        wp_redirect( $redirect_uri );
        exit;
    }

    $name = filter_input( INPUT_POST, 'theme_name' );
    if ( empty( $name ) ) {
        add_settings_error( 'sctc', 'sctc', __( 'No theme name provided.', 'sctc' ) );
        set_transient( 'settings_errors', get_settings_errors() );
        wp_redirect( $redirect_uri );
        exit;
    }

    $styles = wp_styles();
    $default = get_stylesheet_uri();
    $parent_handle = '';
    
    foreach ( $styles->registered as $style ) {
        if ( $default === $style->src ) {
            $parent_handle = $style->handle;
            break;
        }
    }

    if ( empty( $parent_handle ) ) {
        add_settings_error( 'sctc', 'sctc', __( 'Failed determining the current theme\'s stylesheet handler.', 'sctc' ) );
        set_transient( 'settings_errors', get_settings_errors() );
        wp_redirect( $redirect_uri );
        exit;
    }

    $theme = sct_create_child_theme( $name, $parent_handle );

    add_settings_error( 'sctc', 'sctc', sprintf( __( '"%s" created at %s. You can now activate the theme in Appearance > Themes.', 'sctc' ), $name, $theme ), 'updated' );
    set_transient( 'settings_errors', get_settings_errors() );
    wp_redirect( $redirect_uri );
    exit;
}
add_action( 'admin_post_sct_create_theme', 'sct_admin_post' );

function sct_create_child_theme( $name, $parent_handle ) {
    $slug = sanitize_title_with_dashes( $name );
    $parent_slug = basename( TEMPLATEPATH );
    $themes_dir = trailingslashit( get_theme_root() );
    $theme_dir = $themes_dir . wp_unique_filename( $themes_dir, $slug );
    $style = <<<EOT
/**
 * Theme Name: $name
 * Template: $parent_slug
 */
EOT;
    $functions = <<<EOT
<?php

function child_theme_enqueue_scripts() {
    wp_register_style( 'parent', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( '$parent_handle', get_stylesheet_uri(), array( 'parent' ) );
}
add_action( 'wp_enqueue_scripts', 'child_theme_enqueue_scripts' );
EOT;

    wp_mkdir_p( $theme_dir );
    file_put_contents( $theme_dir . '/style.css', $style );
    file_put_contents( $theme_dir . '/functions.php', $functions );

    return $theme_dir;
}
