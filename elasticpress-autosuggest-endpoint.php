<?php
/*
 Plugin Name:  Elasticpress Autosuggest Endpoint
 Plugin URI:   https://github.com/amanank/elasticpress-autosuggest-endpoint
 Description:  Basic WordPress Plugin Header Comment
 Version:      0.4
 Author:       Atta Khalid
 License:      GPL2
 License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

/** 
 * Hook to override elasticpress exposing the entire elastic search query to the client.
 * Only the search text is required from teh client
 */
function endpoint_ep_autosuggest_options($options) {
    $options['query'] = '{"q":"'.$options['placeholder'].'"}';
    return $options;
}
add_filter('ep_autosuggest_options', 'endpoint_ep_autosuggest_options', 10, 1);



/**
 * Register Elasticpress Autosuggest Endpoint
 *
 * This is the endpoint you have to specify in the admin
 * like this: http(s)://domain.com/wp-json/elasticpress/autosuggest/
 */
add_action( 'rest_api_init', function() {
	register_rest_route( 'elasticpress', '/autosuggest/', [
		'methods' => \WP_REST_Server::CREATABLE,
		'callback' => 'ep_autosuggest',
	] );
} );


/**
 * Elasticpress Autosuggest Endpoint Callback
 *
 * gets host and index name dynamically. Otherwise,
 * if not specified, host would default to localhost:9200
 * and index name would default to 'index'
 *
 * @param \WP_REST_Request $data
 * @return array|callable
 */
function ep_autosuggest( \WP_REST_Request $data ) {
    
    //replace double quotes with spaces
    $q = str_replace('"', ' ', $data->get_param('q')); 

    //get the search query from elasticpress the one we removed fro client
    $features = ElasticPress\Features::factory();
    $qry = $features->get_registered_feature('autosuggest')->generate_search_query();

    //replace the search text with the query placeholder
    $qry = str_replace($qry['placeholder'], $q, $qry['body']);

    //get elasticsearch host and index name from elasticpress
    $host = ElasticPress\Utils\get_host();
    $index = ElasticPress\Indexables::factory()->get( 'post' )->get_index_name();

    //run the elastic query
    $request = wp_remote_request( "{$host}$index/_search", array('method'=>'POST', 'body'=>$qry, 'headers'=>array('Content-Type' => 'application/json'))); 

    return json_decode(wp_remote_retrieve_body( $request ));
}