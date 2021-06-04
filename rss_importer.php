<?php

/**
 * @package RSS_Importer
 * @version 1.0.0
 */

/*
Plugin Name: RSS Importer
Description: Plugin que importa posts de um feed RSS
Version: 1.0.0
Author: Victor Braga
*/

require_once('lib/Feed.php');
require_once('lib/OpenGraph.php');

function rssimporter_register_external_post_type() {

	register_post_type('external_post', array(
		'label'       => 'Posts Externos',
		'description' => 'Link para um post externo, que deve aparecer junto aos posts comuns',
		'public'      => true,
		'supports'    => array(
			'title', 
			'editor', 
			'excerpt', 
			'thumbnail', 
			'custom-fields'
		)
	));
}

function rssimporter_admin_page() {

	add_action('admin_enqueue_scripts', wp_enqueue_script('tabulator_js', plugin_dir_url(__FILE__) . '/js/tabulator.min.js'));
	$ajax_url = admin_url('admin-ajax.php');
	include('admin_page.php');
}

function rssimporter_add_admin_menu() {

	add_submenu_page('edit.php?post_type=external_post', 'Importar', 'Importar', 'manage_options', 'importar-posts', 'rssimporter_admin_page' );
}

function rssimporter_ajax_rss_to_json() {

	echo json_encode(array_map(function($v) {
		return [
			'title'   => $v['title'],
            'link'    => $v['link'],
            'pubDate' => DateTime::createFromFormat('D, d M Y H:i:s O', $v['pubDate'])->format('d/m/Y H:i')
		];
	}, Feed::loadRss($_GET['url'])->toArray()['item']));

	wp_die();
}

function rssimporter_ajax_add_urls() {

	$dbg = [];

	foreach($_POST['urls'] as $url) {

		$meta = OpenGraph::fetch($url);

		$dbg[] = (array) $meta;

		wp_insert_post(array(
			'post_title'   => $meta->title,
			'post_excerpt' => $meta->description,
			'post_date'    => $meta->published_time,
			'post_type'    => 'external_post',
			'post_status'  => 'publish',
			'post_author'  => 'Meio Bit',
			'meta_input'   => array(
				'_thumbnail_id'      => '-1',
				'external_url'       => $url,
				'external_thumbnail' => $meta->image
			)
		));
	}
	
	echo json_encode($dbg);

	wp_die();
}

function rssimporter_permalinks( $permalink, $post ) {
	return (get_post_type( $post->ID ) == 'external_post') ?
		   get_post_meta($post->ID, 'external_url')[0] :
		   $permalink;
}

function rssimporter_add_to_home($query) {
	if ( is_home() && $query->is_main_query() )
	$query->set( 'post_type', array( 'post', 'external_post' ) );
	return $query;
}

function rssimporter_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {

    $id = get_post_thumbnail_id();
    $src = wp_get_attachment_image_src($id, $size);
    $alt = get_the_title($id);
    $class = $attr['class'];

    if (get_post_type($post_id) == 'external_post') {
		$img_src = get_post_meta($post_id, 'external_thumbnail')[0];
    } else {
		$img_src = $src[0];
    }

    return "<img src='$img_src' alt='$alt' class='$class' />";
}

add_action('wp_ajax_rssimporter_ajax_rss_to_json', 'rssimporter_ajax_rss_to_json');
add_action('wp_ajax_rssimporter_ajax_add_urls', 'rssimporter_ajax_add_urls');
add_action('init', 'rssimporter_register_external_post_type');
add_action('admin_menu', 'rssimporter_add_admin_menu');
add_action('pre_get_posts', 'rssimporter_add_to_home');
add_filter('post_type_link', 'rssimporter_permalinks', 10, 2);
add_filter('post_thumbnail_html', 'rssimporter_thumbnail', 99, 5);
