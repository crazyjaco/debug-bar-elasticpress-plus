<?php

class EPP_Debug_Bar_ElasticPress_Plus extends Debug_Bar_Panel {

	/**
	 * Panel menu title
	 */
	public $title;

	public $query_stack;
	public $search_stack;
	public $new_post_stack;

	/**
	 * Dummy construct method
	 */
	public function __construct() { }

	/**
	 * Initial debug bar stuff
	 */
	public function setup() {
		$this->title( esc_html__( 'ElasticPress Plus', 'debug-bar' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		add_action( 'ep_wp_query_search', array( $this, 'handle_query' ), 10, 3 );
		// do_action( 'ep_wp_query_search', $new_posts, $search, $query );

		// ep_wp_query_search  looks like this has the query and the results? - in class-ep-wp-query-integration.php
		// ep_add_query_log  - in class-ep-api.php
		// ep_get_plugins
		 
		add_action( 'ep_add_query_log', [ $this, 'handle_logging' ] );
	}

	public function handle_query( $new_posts, $search, $query ) {
		$this->query_stack[] = $query;
		error_log('GOT HERE');
		$this->search_stack[] = $search;
		$this->new_post_stack[] = $new_posts;
	}

	public function handle_logging( $query ) {
		
	}

	/**
	 * Enqueue scripts for front end and admin
	 */
	public function enqueue_scripts_styles() {
		wp_enqueue_script( 'debug-bar-elasticpress-plus', plugins_url( '../assets/js/main.js' , __FILE__ ), array( 'jquery' ), EPP_DEBUG_VERSION, true );
		wp_enqueue_style( 'debug-bar-elasticpress-plus', plugins_url( '../assets/css/main.css' , __FILE__ ), array(), EPP_DEBUG_VERSION );
	}

	/**
	 * Get class instance
	 *
	 * @return object
	 */
	public static function factory() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * Show the menu item in Debug Bar.
	 */
	public function prerender() {
		$this->set_visible( true );
	}

	/**
	 * Show the contents of the panel
	 */
	public function render() {
		if ( ! function_exists( 'ep_get_query_log' ) ) {
			esc_html_e( 'ElasticPress not activated or not at least version 1.8.', 'debug-bar' );
			return;
		}

		$queries = ep_get_query_log();
		$total_query_time = 0;

		foreach ( $queries as $query ) {
			if ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) {
				$total_query_time += ( $query['time_finish'] - $query['time_start'] );
			}
		}

		?>

		<h2><?php printf( __( '<span>Total ElasticPress Queries:</span> %d', 'debug-bar' ), count( $queries ) ); ?></h2>
		<h2><?php printf( __( '<span>Total Blocking ElasticPress Query Time:</span> %d ms', 'debug-bar' ), (int) ( $total_query_time * 1000 ) ); ?></h2>
		<?php
//global $wp_post_types; 
//error_log( 'WP POST TYPES: ' . var_export( $wp_post_types, true ) );
		if ( ! class_exists( 'Debug_Bar_Pretty_Output' ) ) {
			require_once plugin_dir_path( __FILE__ ) . '../inc/debug-bar-pretty-output/class-debug-bar-pretty-output.php';
		}

		$properties = array();
		$properties['indexable_post_types']    = ep_get_indexable_post_types();
		$properties['indexable_post_statuses'] = ep_get_indexable_post_status();
		$properties['searchable_post_types']   = ep_get_searchable_post_types();
		echo Debug_Bar_Pretty_Output::get_table( $properties, __( 'Property', 'debug-bar-screen-info' ), __( 'Value', 'debug-bar-screen-info' ), 'epp-debug-bar' );


		if ( empty( $queries ) ) :

			?><ol class="wpd-queries">
				<li><?php esc_html_e( 'No queries to show', 'debug-bar' ); ?></li>
			</ol><?php

		else :
			?>
			<br/><Br/><Br/>Queries<br/><pre>
			<?php
//print_r($queries);
			?></pre>
			<br/>
			Query Stack:<br/><pre>
			<?php print_r($this->query_stack); ?>
			</pre><?php







			?><ol class="wpd-queries ep-queries-debug"><?php

				foreach ( $queries as $query ) :
					$query_time = ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) ? $query['time_finish'] - $query['time_start'] : false;

					$result = wp_remote_retrieve_body( $query['request'] );
					$response = wp_remote_retrieve_response_code( $query['request'] );

					$class = $response < 200 || $response >= 300 ? 'epp-query-failed' : '';

					?><li class="epp-query-debug hide-query-body hide-query-results hide-query-errors hide-query-args <?php echo sanitize_html_class( $class ); ?>">
						<div class="epp-query-host">
							<strong><?php esc_html_e( 'Host:', 'debug-bar' ); ?></strong>
							<?php echo esc_html( $query['host'] ); ?>
						</div>

						<div class="epp-query-time"><?php
							if ( ! empty( $query_time ) ) :
								printf( __( '<strong>Time Taken:</strong> %d ms', 'debug-bar' ), ( $query_time * 1000 ) );
							else :
								_e( '<strong>Time Taken:</strong> -', 'debug-bar' );
							endif;
						?></div>

						<div class="epp-query-url">
							<strong><?php esc_html_e( 'URL:', 'debug-bar' ); ?></strong>
							<?php echo esc_url( $query['url'] ); ?>
						</div>

						<div class="epp-query-method">
							<strong><?php esc_html_e( 'Method:', 'debug-bar' ); ?></strong>
							<?php echo esc_html( $query['args']['method'] ); ?>
						</div>

						<?php if ( ! empty( $query['query_args'] ) ) : ?>
							<div clsas="epp-query-args">
								<strong><?php esc_html_e( 'Query Args:', 'debug-bar' ); ?> <div class="query-args-toggle dashicons"></div></strong>
								<pre class="query-args"><?php echo var_dump( $query['query_args'] ); ?></pre>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $query['args']['body'] ) ) : ?>
							<div clsas="epp-query-body">
								<strong><?php esc_html_e( 'Query Body:', 'debug-bar' ); ?> <div class="query-body-toggle dashicons"></div></strong>
								<pre class="query-body"><?php echo esc_html( stripslashes( json_encode( json_decode( $query['args']['body'], true ), JSON_PRETTY_PRINT ) ) ); ?></pre>
							</div>
						<?php endif; ?>

						<?php if ( ! is_wp_error( $query['request'] ) ) : ?>

							<div class="epp-query-response-code">
								<?php printf( __( '<strong>Query Response Code:</strong> HTTP %d', 'debug-bar' ), (int) $response ); ?>
							</div>

							<div class="epp-query-result">
								<strong><?php esc_html_e( 'Query Result:', 'debug-bar' ); ?> <div class="query-result-toggle dashicons"></div></strong>
								<pre class="query-results"><?php echo esc_html( stripslashes( json_encode( json_decode( $result, true ), JSON_PRETTY_PRINT ) ) ); ?></pre>
							</div>
						<?php else : ?>
							<div class="epp-query-response-code">
								<strong><?php esc_html_e( 'Query Response Code:', 'debug-bar' ); ?></strong> <?php esc_html_e( 'Request Error', 'debug-bar' ); ?>
							</div>
							<div clsas="epp-query-errors">
								<strong><?php esc_html_e( 'Errors:', 'debug-bar' ); ?> <div class="query-errors-toggle dashicons"></div></strong>
								<pre class="query-errors"><?php echo esc_html( stripslashes( json_encode( $query['request']->errors, JSON_PRETTY_PRINT ) ) ); ?></pre>
							</div>
						<?php endif; ?>
					</li><?php
					
				endforeach;

			?></ol><?php

		endif;
	}

}
