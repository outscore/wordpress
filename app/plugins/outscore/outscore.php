<?php
/*
	Plugin Name: outscore
	Plugin URI: https://www.outscore.de/
	Description: Kernfunktionalitäten
	Version: 1.2.1
	Author: Lukas Heuser
	Author URI: https://www.outscore.de/
*/


require 'plugin-update-checker/plugin-update-checker.php';
$outscore_updates = PucFactory::buildUpdateChecker(
	'http://updates.outscore.de/?action=get_metadata&slug=outscore',
	__FILE__,
	'outscore'
);

if( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', array( 'outscore', 'load' ), 10 );
add_action( 'plugins_loaded', array( 'outscore', 'static_files' ), 10 );
add_action( 'plugins_loaded', array( 'outscore', 'media_files' ), 10 );

class outscore{
	public $version = '1.2.1';

	private static $_instance = null;
	public static function load() {
		if( is_null( self::$_instance ) ) {
			$class = __CLASS__;
			self::$_instance = new $class;
		}
		return self::$_instance;
	}

	public function __construct() {
		add_action('wp_footer', array( 'outscore', 'static_files_url_for_javascript' ));
	}

	public static function activate() {
	}

	/*
		Sets up custom media directory and URL
	 */
	public static function media_files(){
		$path	=	get_option( 'upload_path' );
		$url	=	get_option( 'upload_url_path' );

		if( OUTSCORE_MEDIA_DIR !== $path || OUTSCORE_MEDIA_URL !== $url ) {
			update_option( 'upload_path', OUTSCORE_MEDIA_DIR, true );
			update_option( 'upload_url_path', OUTSCORE_MEDIA_URL, true );
		}
	}
	public static function static_files( $in_footer = false ) {
		$path	=	get_option( 'static_files_path' );
		$url	=	get_option( 'static_files_url' );
		if( OUTSCORE_STATIC_PATH !== $path || OUTSCORE_STATIC_URL !== $url ){
			update_option( 'static_files_url', OUTSCORE_STATIC_URL, true );
			update_option( 'static_files_path', OUTSCORE_STATIC_PATH, true );
		}
	}

	/*
		Add custom script URL to footer
	 */
	public static function static_files_url_for_javascript() {
		echo '<script type="text/javascript">'."\r\n";
		echo "\t".'var base = "'.get_bloginfo('wpurl').'";'."\r\n";
		$str = plugins_url();
		$str = preg_replace('#^https?://#', 'http://www.', $str);
		echo "\t".'var plugin_files = "'.$str.'";'."\r\n";
		echo "\t".'var static_files = "'.get_option( 'static_files_url' ).'";'."\r\n";
		echo "\t".'var media_files = "'.get_option( 'upload_url_path' ).'";'."\r\n";
		echo '</script>'."\r\n";
	}

	/*
		Add SVG to allowed mime types
	 */
	public static function enable_svg( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}
}

function outscore_activate(){
	$outscore = new outscore;
	$outscore->media_files();
	$outscore->static_files(true);
}
register_activation_hook( __FILE__, 'outscore_activate' );

if( defined( 'OUTSCORE_STATIC_URL' ) && ! function_exists('outscore_get_static_url')) {
	function outscore_get_static_url(){
		return get_option( 'static_files_url' );
	}
	function outscore_static_url(){
		echo outscore_get_static_url();
	}
	function outscore_get_static_path(){
		return get_option( 'static_files_path' );
	}
	function outscore_static_path(){
		echo outscore_get_static_path();
	}
}
add_filter('upload_mimes', array( 'outscore', 'enable_svg' ));

if( ! ( function_exists( 'wp_get_attachment_by_post_name' ) ) ) {
	function wp_get_attachment_by_post_name( $post_name ) {
		$args = array(
			'post_per_page' => 1,
			'post_type'     => 'attachment',
			'name'          => trim ( $post_name ),
		);
		$get_posts = new WP_Query( $args );

		if ( isset($get_posts->posts[0]) )
			return $get_posts->posts[0];
		else
		  return false;
	}
}


if ( !function_exists( 'outscore_css' ) ) {
	function outscore_css(){
		$filetime = filemtime(outscore_get_static_path().'/css.css');
		echo '<link type="text/css" rel="stylesheet" href="'.outscore_get_static_url().'/css.css?v='.$filetime.'">'."\r\n";
	}
}

if ( ! function_exists( 'outscore_logo' ) ) {
	function outscore_get_logo(){
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		return wp_get_attachment_image_src( $custom_logo_id , 'full' );
	}
	function outscore_logo($size = ''){
		$image = outscore_get_logo();
		if( $size == '' ){
			$size[0] = $image[1];
			$size[1] = $image[2];
		}
		echo '<img src="'.$image[0].'" width="'.$size[0].'" height="'.$size[1].'" alt="'.get_option('blogname').'">';
	}
}

function changeRestPrefix(){
	return "api";
}
add_filter( 'rest_url_prefix', 'changeRestPrefix');
?>