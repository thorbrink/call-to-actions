<?php
/**
 * Plugin Name: Call to Actions
 * Description: Call to actions, developed for Finax AB.
 * Version: 0.1
 * Author: Iseqqavoq
 * Author URI: http://www.iqq.se
 */
 
class Call_To_Actions
{
	function __construct()
	{
		$this->register_post_types();
		
		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
		add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts_and_styles') );
		add_action( 'wp_ajax_call_to_actions', array( $this, 'call_to_actions' ) );
		add_action( 'before_delete_post', array( $this, 'delete_call_to_action_section_children' ) );
		
		add_shortcode( 'puffsektion', array( $this, 'do_shortcode_puffsection' ) );
	}
	
	public function register_post_types()
	{
		$labels = array(
			'name'               => __('Puffsektioner', 'finax'),
			'singular_name'      => __('Puffsektion', 'finax'),
			'add_new'            => __('Lägg till ny', 'finax'),
			'add_new_item'       => __('Lägg till ny Puffsektion', 'finax'),
			'edit_item'          => __('Redigera Puffsektion', 'finax'),
			'new_item'           => __('Ny Puffsektion', 'finax'),
			'all_items'          => __('Alla Puffsektioner', 'finax'),
			'view_item'          => __('Visa Puffsektioner', 'finax'),
			'search_items'       => __('Sök Puffsektioner', 'finax'),
			'not_found'          => __('Inga Puffsektioner hittades', 'finax'),
			'not_found_in_trash' => __('Inga Puffsektioner hittades i Papperskorgen', 'finax'),
			'parent_item_colon'  => '',
			'menu_name'          => __('Puffsektioner', 'finax')
		);
		
		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'revisions' )
		);
		
		register_post_type( 'cta_section', $args );

		$labels = array(
			'name'               => __('Puffar', 'finax'),
			'singular_name'      => __('Puff', 'finax'),
			'add_new'            => __('Lägg till ny', 'finax'),
			'add_new_item'       => __('Lägg till ny Puff', 'finax'),
			'edit_item'          => __('Redigera Puff', 'finax'),
			'new_item'           => __('Ny Puff', 'finax'),
			'all_items'          => __('Alla Puffar', 'finax'),
			'view_item'          => __('Visa Puffar', 'finax'),
			'search_items'       => __('Sök Puffar', 'finax'),
			'not_found'          => __('Inga Puffar hittades', 'finax'),
			'not_found_in_trash' => __('Inga Puffar hittades i Papperskorgen', 'finax'),
			'parent_item_colon'  => '',
			'menu_name'          => __('Puffar', 'finax')
		);
		
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => true,
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'thumbnail', 'revisions' )
		);
		
		register_post_type( 'call_to_action', $args );
	}
	
	public function add_meta_boxes()
	{
    	// Add meta box for product information
    	add_meta_box(
    	    $id 		= 'call-to-actions',
    	    $title 		= __('Puffar', 'finax'),
    	    $callback 	= array( $this, 'call_to_actions_meta_box' ),
    	    $post_type 	= 'cta_section',
    	    $context 	= 'normal',
    	    $priority 	= 'default'
    	);
	}
	
	public function call_to_actions_meta_box( $post )
	{
	}
		
	public function admin_scripts_and_styles()
	{
		global $current_screen;
		
		if( $current_screen->base !== 'post' || $current_screen->post_type !== 'cta_section' ) return false;
		
		wp_enqueue_media();
		
		wp_enqueue_script(
		    $handle 	= 'admin',
		    $src 		= plugins_url() . '/call-to-actions/admin.js',
		    $deps 		= array('jquery', 'backbone', 'underscore'),
		    $ver		= (file_exists(__DIR__.'/admin.js')) ? filemtime(__DIR__.'/admin.js') : 1
		);
		
		global $post;
		
		$bootstrapped_ctas = $this->get_call_to_actions_by_parent($post->ID);
		
		$post_data = array(
			'id' 				=> $post->ID,
			'boostrapped_ctas' 	=> json_encode($bootstrapped_ctas)
		);
		
		wp_localize_script( 'admin', 'post_data', $post_data );
		
		wp_enqueue_style(
		    $handle 	= 'admin',
		    $src 		= plugins_url() . '/call-to-actions/admin.css',
		    $deps 		= array(),
		    $ver		= (file_exists(__DIR__.'/admin.css')) ? filemtime(__DIR__.'/admin.css') : 1
		);		
	}
	
	public function get_call_to_actions_by_parent( $parent_id )
	{
		$children = get_children( array(
			'post_parent'	=> $parent_id,
			'post_type'		=> 'call_to_action'
		) );
		
		foreach( $children as &$child )
		{
			$child->link 			= get_post_meta( $child->ID, '_link', true );
			$child->link_text		= get_post_meta( $child->ID, '_link_text', true );
			$child->featured_image 	= get_post_meta( $child->ID, '_thumbnail_id', true );
			
			if( $child->featured_image )
			{
				$child->featured_image_url = wp_get_attachment_image_src($child->featured_image, 'full')[0];
			}
		}
		
		return $children;
	}
	
	public function call_to_actions()
	{
		$model = json_decode( file_get_contents( "php://input" ) );
		
		if( $_SERVER['REQUEST_METHOD'] === 'DELETE' )
		{
			if( $model->ID )
			{
				echo ( $post = wp_delete_post( $model->ID, true ) ) ? $post->ID : false;
				die();
			}
		}
		
		$args = array(
			'post_title'	=> $model->post_title,
			'post_content'	=> $model->post_content,
			'post_parent'	=> $model->post_parent,
			'post_type'		=> 'call_to_action'
		);
		
		if( !$model->ID ) // If post does not exists yet...
		{
			$result = wp_insert_post($args, true);
		}
		else
		{
			$args['ID'] = $model->ID;
			$result = wp_update_post($args, true);
		}
		
		if( is_wp_error( $result ) )
		{
			// Something went wrong...
		}
		else
		{
			update_post_meta($result, '_link', $model->link);
			update_post_meta($result, '_link_text', $model->link_text);
			update_post_meta($result, '_thumbnail_id', $model->featured_image);
			
			echo $result; // Post ID.
		}
		
		die();
	}
	
	public function delete_call_to_action_section_children($id)
	{
		$post_type = get_post_type( $id );
		
		if( $post_type !== 'cta_section' ) return;

		$cta_query_args = array(
			'post_type'			=> 'call_to_action',
			'post_parent'		=> $id,
			'posts_per_page'	=> -1,
			'post_status'		=> 'any'
		);
		
		$cta_query = new WP_Query($cta_query_args);
		
		if( $cta_query->have_posts() )
		{
			while( $cta_query->have_posts() )
			{
				$cta_query->the_post();
				wp_delete_post( get_the_ID(), true );
			}
		}
	}
	
	public function do_shortcode_puffsection( $atts )
	{
		extract( shortcode_atts( array(
			'id' 		=> false,
			'style'		=> 'default',
			'columns'	=> 3,
			'title'		=> false
		), $atts ) );
		
		$output = '';
		
		if( !$id ) return $output;
		
		$cta_query_args = array(
			'post_type'			=> 'call_to_action',
			'post_parent'		=> $id,
			'posts_per_page'	=> -1,
			'post_status'		=> 'any'
		);
		
		$cta_query = new WP_Query($cta_query_args);
		
		if( $cta_query->have_posts() )
		{
			ob_start();
			echo '<section class="call-to-actions '.$style.' columns-'.$columns.'" data-columns="'.$columns.'">';
			echo ($title) ? "<h2 class=\"cta-section-title\">$title</h2>" : '';
			while( $cta_query->have_posts() )
			{
				$cta_query->the_post();
				$link = get_post_meta( get_the_ID(), '_link', true );
				$thumbnail = get_the_post_thumbnail( get_the_ID(), 'full', array( 'class' => 'cta-featured-image' ) );
				?>
				<article class="cta">
					<a href="<?php echo $link ?>"><?php echo $thumbnail ?></a>
					<h3><a href="<?php echo $link ?>"><?php the_title() ?></a></h3>
					<p><a href="<?php echo $link ?>"><?php the_content() ?></a></p>
				</article>
				<?php
			}
			echo '</section>';
			$output = ob_get_clean();
		}
		return $output;
	}
}

$call_to_actions = new Call_To_Actions();