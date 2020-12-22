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
 * Only the search text is required from the client
 */
function endpoint_ep_autosuggest_options($options) {
    $options['query'] = '{"q":"'.$options['placeholder'].'"}';
    $options['highlightingEnabled'] = false;
    $options['action'] = 'select';

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
 * gets host and index name from ElasticPress
 *
 * @param \WP_REST_Request $data
 * @return array|callable
 */
function ep_autosuggest( \WP_REST_Request $data ) {
    
    //replace double quotes with spaces
    $q = str_replace('"', ' ', $data->get_param('q')); 

    //Elastic query to get suggestion from post_content
    $qry = json_encode(array(
        'suggest'=>array(
            'text' => $q,
            'simple_phrase' => array(
                'phrase'=>array(
                    'field' => 'post_content.term_suggest',
                    'size' => 5,
                    'gram_size' => 3,
                    'direct_generator' => array(array(
                        'field' => 'post_content.term_suggest',
                        'suggest_mode' => 'popular'
                    )),
                    'highlight' => array(
                        'pre_tag' => "<mark class=ep-highlight>", //no quotes around class because ElasticPress Autosuggest js escapes double quotes :(
                        'post_tag' => '</mark>'
                    )
                )
            )
        )));

    //get elasticsearch host and index name from elasticpress
    $host = ElasticPress\Utils\get_host();
    $index = ElasticPress\Indexables::factory()->get( 'post' )->get_index_name();

    //run the elastic query
    $request = wp_remote_request( "{$host}$index/_search", array('method'=>'POST', 'body'=>$qry, 'headers'=>array('Content-Type' => 'application/json'))); 

    $resp = json_decode(wp_remote_retrieve_body( $request ));

    //format results
    $resp->hits->hits = [];
    foreach ($resp->suggest->simple_phrase[0]->options as $hit) {
        $hit->_source = array('post_title'=>$hit->highlighted, 'permalink'=>'#');
        $resp->hits->hits[] = $hit;
    };

    return $resp;
}