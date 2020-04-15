<?php 

/*
Plugin Name: Open Web Analytics
Plugin URI: http://www.openwebanalytics.com
Description: This plugin enables Wordpress blog owners to use the Open Web Analytics Framework.
Author: Peter Adams
Version: master
Author URI: http://www.openwebanalytics.com
*/

//
// Open Web Analytics - An Open Source Web Analytics Framework
//
// Copyright 2008 Peter Adams. All rights reserved.
//
// Licensed under GPL v2.0 http://www.gnu.org/copyleft/gpl.html
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
// $Id$
//

// if this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define the plugin path constant 
define('OWA_WP_PATH', plugin_dir_path( __FILE__ ) );

// Hook package creation
add_action('plugins_loaded', array( 'owa_wp_plugin', 'getInstance'), 10 );

// Installation hook
register_activation_hook(__FILE__, array('owa_wp_plugin', 'install') );


/////////////////////////////////////////////////////////////////////////////////


/**
 * OWA WordPress Plugin Class
 *
 */
class owa_wp_plugin extends owa_wp_module {
	
	// cmd array
	var $cmds = array();
	// plugin options
	var $options = array(
		
		'track_feed_links'			=> true,
		'feed_tracking_medium' 		=> 'feed',
		'feed_subscription_param' 	=> 'owa_sid'
	);
	
	/**
	 * Constructor
	 *
	 */	
	function __construct() {
		
		// needed???
		ob_start();
		
		// setup plugin options
		$this->initOptions();
		
		// load parent constructor
		$params = array();
		$params['module_name'] = 'owa-wordpress';
		parent::__construct( $params ); 
		
		// register WordPress hooks and filters
		
		if ( $this->getOption('enable') ) {
			
			// insert javascript tracking tag	
			add_action('wp_head', array( $this,'insertTrackingTag' ), 100 );
			
			// track feeds
			if ( $this->getOption('trackFeeds') ) {
				// add tracking to feed entry permalinks
				add_filter('the_permalink_rss', array( $this, 'decorateFeedEntryPermalink' ) );
			
				// add tracking to feed subscription links
				add_filter('bloginfo_url', array($this, 'decorateFeedSubscriptionLink' ) );
			}
			
			// Track admin actions if OWA is available as a library
			if( $this->isOwaAvailable() ) {
				
				// handle API calls and other requests
				add_action('init', array( $this, 'handleSpecialActionRequest' ) );
				
				// @todo find a way for these methods to POST these to the OWA instance instead of via OWA's PHP Tracker
				$this->defineActionHooks();
				
				// Create a new tracked site in OWA.
				// @todo move this to REST API call when it's ready.
				add_action('wpmu_new_blog', array($this, 'createTrackedSiteForNewBlog'), 10, 6);
			}
		}
	}
	
	/**
	 * Singelton
	 */
	static function getInstance() {
		
		static $o;
	
		if ( ! isset( $o ) ) {
			
			$o = new owa_wp_plugin();
		}
		
		return $o;
	}
	
	private function initOptions() {
		
		// get user defaults from option page
		$user_defaults = array_combine( array_keys( $this->registerOptions() ), array_column( $this->registerOptions() , 'default_value') );
		
		if ( $user_defaults ) {
			
			$this->options = array_merge($this->options, $user_defaults);
		}
		
		// fetch plugin options from DB and combine them with defaults.
		$options = get_option( 'owa_wp' );
		//echo 'options from DB: '. print_r( $options, true );
		if ( $options ) {
			
			$this->options = array_merge($this->options, $options);
		}
	}
	
	/**
	 * Get an option value
	 */
	function getOption( $key ) {
		
		$options = array();
		$options = $this->options;
		if ( array_key_exists( $key, $options ) ) {
			
			return $this->options[ $key ];
		}
	}
	
	/**
	 * Set an option value
	 */
	function setOption( $key, $value ) {
		
		$this->options[ $key ] = $value;
	}
		
	/**
	 * Hooks for tracking WordPress Admin actions
	 */
	function defineActionHooks() {
		
		
		// These hooks rely on accessing OWA server-side 
		// as a PHP object. 	
		
		if ( $this->isOwaAvailable() ) {
		
			
			if ( $this->getOption( 'trackAdminActions' ) ) {
				
				// New Comment
				add_action( 'comment_post', array( $this, 'trackCommentAction' ), 10, 2);
				// Comment Edit
				add_action( 'transition_comment_status', array( $this, 'trackCommentEditAction' ), 10, 3);
				// User Registration
				add_action( 'user_register', array( $this, 'trackUserRegistrationAction' ) );
				// user login
				add_action( 'wp_login', array( $this, 'trackUserLoginAction' ) );
				// User Profile Update
				add_action( 'profile_update', array( $this, 'trackUserProfileUpdateAction' ), 10, 2);
				// Password Reset
				add_action( 'password_reset', array( $this, 'trackPasswordResetAction' ) );
				// Trackback
				add_action( 'trackback_post', array( $this, 'trackTrackbackAction' ) );
				// New Attachment
				add_action( 'add_attachment', array( $this, 'trackAttachmentCreatedAction' ) );
				// Attachment Edit
				add_action( 'edit_attachment', array( $this, 'trackAttachmentEditAction' ) );
				// Post Edit
				add_action( 'transition_post_status', array( $this, 'trackPostAction') , 10, 3);
				// New Blog (WPMU)
				add_action( 'wpmu_new_blog', array( $this, 'trackNewBlogAction') , 10, 5);
			}
			
			// track feeds
			
			if ( $this->getOption( 'trackFeeds' ) ) {
			
				add_action('init', array( $this, 'addFeedTrackingQueryParams'));
				add_action( 'template_redirect', array( $this, 'trackFeedRequest'), 1 );
			}
		}
		
	}	
	
	// Add query vars to WordPress
	function addFeedTrackingQueryParams() {
		
		global $wp; 
		
		// feed tracking param
		$wp->add_query_var('owa_sid'); 
		
	}
	
	/**
	 * Determines the title of the page being requested
	 *
	 * @param string $page_type
	 * @return string $title
	 */
	function getPageTitle() {
	
		$page_type = $this->getPageType();
		
		if ( $page_type == "Home" ) {
		
			$title = get_bloginfo( "name" );
		
		} elseif ( $page_type == "Search Results" ) {
			
			$title = "Search Results for \"" . get_search_query() . "\"";	
		
		} else {
			
			$title = wp_title($sep = '', $display = 0);
		}	
		
		return $title;
	}
	
	function setPageTitle() {
		
		$this->cmds[] = sprintf("owa_cmds.push(['setPageTitle', '%s' ]);", $this->getPageTitle() );
	}
	
	function setUserName() {
		
		$current_user = wp_get_current_user();
		$this->cmds[] = sprintf("owa_cmds.push(['setUserName', '%s' ]);", $current_user->user_login );
	}
	
	/**
	 * Determines the type of WordPress page
	 *
	 * @return string $type
	 */
	function getPageType() {	
		
		if ( is_home() ) {
			$type = "Home";
		} elseif ( is_attachment() ){
			$type = "Attachment";
		} elseif ( is_page() ) {
			$type = "Page";
		// general page catch, should be after more specific post types	
		} elseif ( is_single() ) {
			$type = "Post";
		} elseif ( is_feed() ) {
			$type = "Feed";
		} elseif ( is_author() ) {
			$type = "Author";
		} elseif ( is_category() ) {
			$type = "Category";
		} elseif ( is_search() ) {
			$type = "Search Results";
		} elseif ( is_month() ) {
			$type = "Month";
		} elseif ( is_day() ) {
			$type = "Day";
		} elseif ( is_year() ) {
			$type = "Year";
		} elseif ( is_time() ) {
			$type = "Time";
		} elseif ( is_tag() ) {
			$type = "Tag";
		} elseif ( is_tax() ) {
			$type = "Taxonomy";
		// general archive catch, should be after specific archive types	
		} elseif ( is_archive() ) {
			$type = "Archive";
		} else {
			$type = '(not set)';
		}
		
		return $type;
	}
	
	function setPageType() {
		
		$this->cmds[] = sprintf("owa_cmds.push(['setPageType', '%s' ]);", $this->getPageType() );
	}
	
	function cmdsToString() {
		
		$out = '';
		
		foreach ( $this->cmds as $cmd ) {
			
			$out .= $cmd . " \n";	
		}
		
		return $out;
	}
		
	// check to see if OWA is available as a php library on the same server
	function isOwaAvailable() {
		
		return true;
	}
	
			
	/**
	 * Insert Tracking Tag
	 *
	 * Adds javascript tracking tag int <head> of all pages.
	 * 
	 */
	function insertTrackingTag() {
		
		// Don't log if the page request is a preview - Wordpress 2.x or greater
		if ( function_exists( 'is_preview' ) ) {
			
			if ( is_preview() ) {
				
				return;
			}
		}
		
		// dont log customizer previews either.
		if ( function_exists( 'is_customize_preview' ) ) {
			
			if ( is_customize_preview() ) {
				
				return;
			}
		}
		
		// dont log requests for admin interface pages
		if ( ! $this->getOption( 'trackAdminPages') && function_exists( ' is_admin' ) && is_admin() ) {
			
			return;
		}
		
		// set user name in tracking for names users with wp-admin accounts
		if ( $this->getOption( 'trackNamedUsers') ) {
			
			$this->setUserName();
		}
	
		
		// get instance of OWA
		$owa = self::getOwaInstance();
		
		// set any cmds
		$this->setPageType();
		$this->setPageTitle();
		
		// convert cmds to string and pass to tracking tag template	
		$options = array( 'cmds' => $this->cmdsToString() );
		
		// place the tracking tag
		$owa->placeHelperPageTags(true, $options);	
	}	
	
	/**
	 * Adds tracking source param to links in feeds
	 *
	 * @param string $link
	 * @return string
	 */
	function decorateFeedEntryPermalink($link) {
		
		// check for presence of '?' which is not present under URL rewrite conditions
	
		if ( $this->getOption( 'track_feed_links' ) ) {
		
			if ( strpos($link, "?") === false ) {
				// add the '?' if not found
				$link .= '?';
			}
			
			// setup link template
			$link_template = "%s&amp;%s=%s&amp;%s=%s";
				
			return sprintf($link_template,
						   $link,
						   'owa_medium',
						   $this->getOption( 'feed_tracking_medium' ),
						   $this->getOption( 'feed_subscription_param' ),
						   $_GET[ $this->getOption( 'feed_subscription_param' ) ] 
			);
		}
	}
	
	/**
	 * Wordpress filter function adds a GUID to the feed URL.
	 *
	 * @param array $binfo
	 * @return string $newbinfo
	 */
	function decorateFeedSubscriptionLink( $binfo ) {
		
		$is_feed = strpos($binfo, "feed=");
		
		if ( $is_feed && $this->getOption( 'track_feed_links' ) ) {
			
			$guid = crc32(getmypid().microtime());
		
			$newbinfo = $binfo . "&amp;" . $this->getOption('feed_subscription_param') . "=" . $guid;
		
		} else { 
			
			$newbinfo = $binfo;
		}
		
		return $newbinfo;
	}
	
	// create a new tracked site.
	function createTrackedSiteForNewBlog($blog_id, $user_id, $domain, $path, $site_id, $meta) {
	
		$owa = self::getOwaInstance();
		$sm = owa_coreAPI::supportClassFactory( 'base', 'siteManager' );
		$sm->createNewSite( $domain, $domain, '', ''); 
	}
	
	
	/**
	 * New Blog Action Tracker
	 */
	function trackNewBlogAction( $blog_id, $user_id, $domain, $path, $site_id ) {
	
		$owa = self::getOwaInstance();
		$owa->trackAction('WordPress', 'Blog Created', $domain);
	}
	
	/**
	 * Edit Post Action Tracker
	 */
	function trackedPostEditAction( $post_id, $post ) {
		
		// we don't want to track autosaves...
		if( wp_is_post_autosave( $post ) ) {
			
			return;
		}
		
		$owa = self::getOwaInstance();
		$label = $post->post_title;
		$owa->trackAction( 'WordPress', $post->post_type.' edited', $label );
	}
	
	/**
	 * Post Action Tracker
	 *
	 * Trackes new and edited post actions. Including custom post types.
	 */
	function trackPostAction( $new_status, $old_status, $post ) {
		
		$action_name = '';
		
		// we don't want to track autosaves...
		if(wp_is_post_autosave( $post ) ) {
			
			return;
		}
		
		// or drafts
		if ( $new_status === 'draft' && $old_status === 'draft' ) {
			
			return;
		
		} 
		
		// set action label
		if ( $new_status === 'publish' && $old_status != 'publish' ) {
			
			$action_name = $post->post_type.' publish';
		
		} elseif ( $new_status === $old_status ) {
		
			$action_name = $post->post_type.' edit';
		}
		
		// track action
		if ( $action_name ) {	
		

			$owa = self::getOwaInstance();
			owa_coreAPI::debug(sprintf("new: %s, old: %s, post: %s", $new_status, $old_status, print_r($post, true)));
			$label = $post->post_title;
			
			$owa->trackAction('WordPress', $action_name, $label);
		}
	}
	
	/**
	 * Edit Attachment Action Tracker
	 */
	function trackAttachmentEditAction( $post_id ) {
	
		$owa = self::getOwaInstance();
		$post = get_post( $post_id );
		$label = $post->post_title;
		$owa->trackAction('WordPress', 'Attachment Edit', $label);
	}
	
	/**
	 * New Attachment Action Tracker
	 */
	function trackAttachmentCreatedAction( $post_id ) {
	
		$owa = self::getOwaInstance();
		$post = get_post($post_id);
		$label = $post->post_title;
		$owa->trackAction('WordPress', 'Attachment Created', $label);
	}
	
	/**
	 * User Registration Action Tracker
	 */
	function trackUserRegistrationAction( $user_id ) {
		
		$owa = self::getOwaInstance();
		$user = get_userdata($user_id);
		if (!empty($user->first_name) && !empty($user->last_name)) {
			$label = $user->first_name.' '.$user->last_name;	
		} else {
			$label = $user->display_name;
		}
		
		$owa->trackAction('WordPress', 'User Registration', $label);
	}
	
	/**
	 * User Login Action Tracker
	 */
	function trackUserLoginAction( $user_id ) {
	
		$owa = self::getOwaInstance();
		$label = $user_id;
		$owa->trackAction('WordPress', 'User Login', $label);
	}
	
	/**
	 * Profile Update Action Tracker
	 */
	function trackUserProfileUpdateAction( $user_id, $old_user_data = '' ) {
	
		$owa = self::getOwaInstance();
		$user = get_userdata($user_id);
		if (!empty($user->first_name) && !empty($user->last_name)) {
			$label = $user->first_name.' '.$user->last_name;	
		} else {
			$label = $user->display_name;
		}
		
		$owa->trackAction('WordPress', 'User Profile Update', $label);
	}
	
	/**
	 * Password Reset Action Tracker
	 */
	function trackPasswordResetAction( $user ) {
		
		$owa = self::getOwaInstance();
		$label = $user->display_name;
		$owa->trackAction('WordPress', 'User Password Reset', $label);
	}
	
	/**
	 * Trackback Action Tracker
	 */
	function trackTrackbackAction( $comment_id ) {
		
		$owa = self::getOwaInstance();
		$label = $comment_id;
		$owa->trackAction('WordPress', 'Trackback', $label);
	}
	
	function trackCommentAction( $id, $comment_data = '' ) {

		if ( $comment_data === 'approved' || $comment_data === 1 ) {
	
			$owa = self::getOwaInstance();
			$label = '';
			$owa->trackAction('WordPress', 'comment', $label);
		}
	}
	
	function trackCommentEditAction( $new_status, $old_status, $comment ) {
		
		if ($new_status === 'approved') {
			
			if (isset($comment->comment_author)) {
				
				$label = $comment->comment_author; 
			
			} else {
			
				$label = '';
			}
			
			$owa = self::getOwaInstance();
			$owa->trackAction('WordPress', 'comment', $label);
		}
	}
	
	// Tracks feed requests
	function trackFeedRequest() {
		
		if ( is_feed() ) {
		
			$owa = self::getOwaInstance();
	
			if( $owa->getSetting( 'base', 'log_feedreaders') ) {
				
				owa_coreAPI::debug('Tracking WordPress feed request');			
				
				$event = $owa->makeEvent();
				// set event type
				$event->setEventType( 'base.feed_request' );
				// determine and set the type of feed
				$event->set( 'feed_format', get_query_var( 'feed' ) );
				$event->set( 'feed_subscription_id', get_query_var( 'owa_sid' ) );
				//$event->set( 'feed_subscription_id', $_GET['owa_sid'] );
				// track
				$owa->trackEvent( $event );
			}
		}
	}
	
	public function registerOptions() {		

		return array(
		
			'enable'				=> array(
			
				'default_value'							=> true,
				'field'									=> array(
					'type'									=> 'boolean',
					'title'									=> 'Enable OWA ',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'general',
					'description'							=> 'Enable OWA.',
					'label_for'								=> 'Enable OWA.',
					'error_message'							=> 'You must select On or Off.'		
				)				
			),
/*
			
			'siteId'				=> array(
			
				'default_value'							=> '',
				'field'									=> array(
					'type'									=> 'text',
					'title'									=> 'Website ID',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'general',
					'description'							=> 'The ID of the website being tracked.',
					'label_for'								=> 'Tracked website ID',
					'length'								=> 70,
					'error_message'							=> ''		
				)				
			),
			
			'apiKey'				=> array(
			
				'default_value'							=> '',
				'field'									=> array(
					'type'									=> 'text',
					'title'									=> 'API Key',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'general',
					'description'							=> 'API key for accessing your OWA instance.',
					'label_for'								=> 'OWA API Key',
					'length'								=> 70,
					'error_message'							=> ''		
				)				
			),
			
			'OwaEndpoint'			=> array(
			
				'default_value'							=> '',
				'field'									=> array(
					'type'									=> 'url',
					'title'									=> 'OWA Endpoint',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'general',
					'description'							=> 'The URL of your OWA instance. (i.e. http://www.mydomain.com/path/to/owa/)',
					'label_for'								=> 'OWA Endpoint',
					'length'								=> 70,
					'error_message'							=> ''		
				)				
			),
*/


			// site ID
			// 
			// 
	
			
			'trackClicks'				=> array(
			
				'default_value'							=> '',
				'field'									=> array(
					'type'									=> 'boolean',
					'title'									=> 'Track Clicks',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'tracking',
					'description'							=> 'Track the clicks visitors make on your web pages.',
					'label_for'								=> 'Track clicks within a web page',
					'error_message'							=> 'You must select On or Off.'		
				)				
			),
			
			'trackDomstreams'				=> array(
			
				'default_value'							=> false,
				'field'									=> array(
					'type'									=> 'boolean',
					'title'									=> 'Track Domstreams',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'tracking',
					'description'							=> 'Record visitor mouse movements on each web page.',
					'label_for'								=> 'Record mouse movements',
					'error_message'							=> 'You must select On or Off.'		
				)				
			),
			
			'trackFeeds'				=> array(
			
				'default_value'							=> true,
				'field'									=> array(
					'type'									=> 'boolean',
					'title'									=> 'Track Feed Requests',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'tracking',
					'description'							=> 'Track requests for RSS/ATOM syndication feeds.',
					'label_for'								=> 'Track RSSS/ATOM Feeds',
					'error_message'							=> 'You must select On or Off.'		
				)				
			),
			
			'trackNamedUsers'				=> array(
			
				'default_value'							=> true,
				'field'									=> array(
					'type'									=> 'boolean',
					'title'									=> 'Track Named Users',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'tracking',
					'description'							=> 'Track names and email addresses of WordPress users.',
					'label_for'								=> 'Track named users',
					'error_message'							=> 'You must select On or Off.'		
				)				
			),
			
			'trackAdminPages'				=> array(
			
				'default_value'							=> false,
				'field'									=> array(
					'type'									=> 'boolean',
					'title'									=> 'Track WP Admin pages (/wp-admin...)',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'tracking',
					'description'							=> 'Track WordPress admin interface pages',
					'label_for'								=> 'Track WP admin pages',
					'error_message'							=> 'You must select On or Off.'		
				)				
			),
			
			'trackAdminActions'				=> array(
			
				'default_value'							=> false,
				'field'									=> array(
					'type'									=> 'boolean',
					'title'									=> 'Track WP Admin Actions',
					'page_name'								=> 'owa-wordpress',
					'section'								=> 'tracking',
					'description'							=> 'Track WordPress admin actions such as login, new posts, edits, etc.',
					'label_for'								=> 'Track WP admin actions',
					'error_message'							=> 'You must select On or Off.'		
				)				
			)

		);
	}

	public function registerSettingsPages() {
		
		$pages = array(
		
			'owa-wordpress'			=> array(
				
				'parent_slug'					=> 'owa-wordpress',
				'is_top_level'					=> true,
				'top_level_menu_title'			=> 'OWA',
				'title'							=> 'Open Web Analytics',
				'menu_title'					=> 'Tracking Settings',
				'required_capability'			=> 'manage_options',
				'menu_slug'						=> 'owa-wordpress-settings',
				'description'					=> 'Settings for Open Web Analytics.',
				'sections'						=> array(
					'general'						=> array(
						'id'							=> 'general',
						'title'							=> 'General',
						'description'					=> 'These settings control the integration between Open Web Analytics and WordPress.'
					),
					'tracking'						=> array(
						'id'							=> 'tracking',
						'title'							=> 'Tracking',
						'description'					=> 'These settings control how Open Web Analytics will track your WordPress website.'
					)
				)
			)
		);
		
		if ( $this->isOwaAvailable() ) {
			
			$pages['owa-analytics']	= array(
				
				'parent_slug'					=> 'owa-wordpress',
				'title'							=> 'OWA Analytics',
				'menu_title'					=> 'Analytics',
				'required_capability'			=> 'manage_options',
				'menu_slug'						=> 'owa-analytics',
				'description'					=> 'OWA Analytics dashboard.',
				'render_callback'				=> array( $this, 'pageController')
			);
			
			$pages['owa-configuration']	= array(
				
				'parent_slug'					=> 'owa-wordpress',
				'title'							=> 'OWA Instance Configuration',
				'menu_title'					=> 'Instance Configuration',
				'required_capability'			=> 'manage_options',
				'menu_slug'						=> 'owa-configuration',
				'description'					=> 'Instance Configuration for Open Web Analytics.',
				'render_callback'				=> array( $this, 'options_page')
			);
		
		}
		
		return $pages;
	}
	
	/////////////////////// Methods that require OWA on server ////////////////////////////
	
	// gets an instance of your OWA as a php object
	public static function getOwaInstance() {
		
		static $owa;
		
		if( empty( $owa ) ) {
		
			
				
				require_once('owa_env.php');
				require_once(OWA_BASE_CLASSES_DIR.'owa_php.php');
				
				// create owa instance w/ config
				$owa = new owa_php();
				$owa->setSiteId( md5( get_option( 'siteurl' ) ) );
				$owa->setSetting( 'base', 'report_wrapper', 'wrapper_wordpress.tpl' );
				$owa->setSetting( 'base', 'link_template', '%s&%s' );
				$owa->setSetting( 'base', 'main_url', '../wp-admin/admin.php?page=owa-analytics' );
				$owa->setSetting( 'base', 'main_absolute_url', get_bloginfo('url').'/wp-admin/admin.php?page=owa-analytics' );
				$owa->setSetting( 'base', 'action_url', get_bloginfo('url').'/admin.php?owa_specialAction' );
				$owa->setSetting( 'base', 'api_url', get_bloginfo('url').'/admin.php?owa_apiAction' );
				$owa->setSetting( 'base', 'rest_api_url', $owa->getSetting( 'base', 'rest_api_url' ).'?');
				$owa->setSetting( 'base', 'is_embedded', true );
				
				
				// register allowedSitesList filter
				$dispatch = owa_coreAPI::getEventDispatch();
				// alternative auth method, sets auth status, role, and allowed sites list.
				$dispatch->attachFilter('auth_status', 'owa_wp_plugin::wpAuthUser', 0);	
			
		}
		
		return $owa;
	}
	
	/**
	 * Callback for reporting dashboard/pages 
	 */
	function pageController( $params = array() ) {
		
		if ( $this->isOwaAvailable() ) {
		
			$owa = self::getOwaInstance();	
			
			echo $owa->handleRequest( $params );
		}
	}
	
	/**
	 * Callback for OWA settings page
	 */
	function options_page() {
	
		$this->pageController( array('do' => 'base.optionsGeneral') );
	}
	
	/**
	 * OWA Schema and setting installation
	 *
	 */
	public static function install() {
		
	
		define('OWA_INSTALLING', true);
		
		//owa_coreAPI::notice('Starting Embedded Install...');	
		
		$params = array();
		//$params['do_not_fetch_config_from_db'] = true;
	
		$owa = self::getOwaInstance($params);
		$owa->setSetting('base', 'cache_objects', false);	
		$public_url =  get_bloginfo('wpurl').'/wp-content/plugins/owa/';
		
		$install_params = array('site_id' => md5(get_option('siteurl')), 
								'name' => get_bloginfo('name'),
								'domain' => get_option('siteurl'), 
								'description' => get_bloginfo('description'),
								'action' => 'base.installEmbedded',
								'db_type' => 'mysql',
								'db_name' => DB_NAME,
								'db_host' => DB_HOST,
								'db_user' => DB_USER,
								'db_password' => DB_PASSWORD,
								'public_url' =>  $public_url
								);
		
		$owa->handleRequest($install_params);
	}
	
	function handleSpecialActionRequest() {

		$owa = self::getOwaInstance();
		
		owa_coreAPI::debug("hello from WP special action handler");
		
		return $owa->handleSpecialActionRequest();
	}


	/**
	 * OWA Authenication filter
	 *
	 * Uses WordPress priviledge system to determine OWA authentication levels.
	 *
	 * @return boolean
	 */
	static function wpAuthUser($auth_status) {

		$current_user = wp_get_current_user();
		
	    if ( $current_user instanceof WP_User ) { 
	    	// logged in, authenticated
	    	$cu = owa_coreAPI::getCurrentUser();
	    	
	    	$cu->setAuthStatus(true);
	    	
	    	if (isset($current_user->user_login)) {
				$cu->setUserData('user_id', $current_user->user_login);
				owa_coreAPI::debug("Wordpress User_id: ".$current_user->user_login);
				$cu->load( $current_user->user_login );
			}
			
			if (isset($current_user->user_email)) {	
				$cu->setUserData('email_address', $current_user->user_email);
			}
			
			if (isset($current_user->first_name)) {
				$cu->setUserData('real_name', $current_user->first_name.' '.$current_user->last_name);
				$cu->setRole( owa_wp_plugin::translateAuthRole( $current_user->roles ) );
			}
			
			owa_coreAPI::debug("Wordpress User Role: ".print_r($current_user->roles, true));
			owa_coreAPI::debug("Wordpress Translated OWA User Role: ".$cu->getRole());
			
			// fetch the list of allowed blogs from WP
			$domains = array();
			$allowedBlogs = get_blogs_of_user($current_user->ID);
		
			foreach ( $allowedBlogs as $blog) {
				$domains[] = $blog->siteurl;		
			}
			
			// check to see if we are installing before trying to load sites
			// other wise you run into a race condition as config file
			// might not be created.
			if (! defined('OWA_INSTALLING') ) {
				// load assigned sites list by domain
	    		$cu->loadAssignedSitesByDomain($domains);
	    	}
	    	
			$cu->setInitialized();
	    
	    	return true;
	    
	    } else {
	    	// not logged in to WP and therefor not authenticated
	    	return false;
	    }	
	}
	
	/**
	 * Translate WordPress to OWA Authentication Roles
	 *
	 * @param $roles	array	array of WP roles
	 * @return	string
	 */ 
	static function translateAuthRole( $roles ) {
		
		if (!empty($roles)) {
		
			if (in_array('administrator', $roles)) {
				$owa_role = 'admin';
			} elseif (in_array('editor', $roles)) {
				$owa_role = 'viewer';
			} elseif (in_array('author', $roles)) {
				$owa_role = 'viewer';
			} elseif (in_array('contributor', $roles)) {
				$owa_role = 'viewer';
			} elseif (in_array('subscriber', $roles)) {
				$owa_role = 'everyone';
			} else {
				$owa_role = 'everyone';
			}
			
		} else {
			$owa_role = 'everyone';
		}
		
		return $owa_role;
	}

}

class owa_wp_module {
	
	public $module_name;
	public $controllers;
	public $entities;
	public $views;
	public $ns;
	public $package_name;
	public $options;
	public $settings;
	public $settings_pages;
	
	public function __construct( $params = array() ) {
	
		$this->controllers = array();
		$this->entities	= array();
		$this->views = array();
		$this->settings_pages = array();
		
		
		// set module name
		if ( array_key_exists( 'module_name', $params ) ) {
			
			$this->module_name = $params['module_name'];
		}
		
		// set package name
		if ( array_key_exists( 'package_name', $params ) ) {
			
			$this->package_name = $params['package_name'];
		}
		
		// set namespace
		if ( array_key_exists( 'ns', $params ) ) {
			
			$this->ns = $params['ns'];
		}
	
		// kick off the init sequence for each module during Wordpress 'init' hook.
		add_action('init', array( $this, 'init'), 15, 0 );
	}
	
	public function init() {
	
		// needs to be first as default Options are set here and used downstream in
		// all other hooks and classes.
		$this->processAdminConfig();
		// load public hooks
		$this->definePublicHooks();
		// load admin hooks during WordPress 'admin_init' hook
	
		owa_wp_util::addAction( 'admin_init', array( $this, 'defineAdminHooks') );
	}
	
		/**
	 * Inititalizes Settings Page Objects
	 *
	 */
	public function initSettingsPage() {
		
		// check for prior initialization as I'm not sure if the WP hook admin_init or admin_menu 
		// gets called first.
		if ( ! $this->settings_pages ) {			
			
			$sp_params = array(
			
				'ns'				=> $this->ns,
				'package'			=> $this->package_name,
				'module'			=> $this->module_name
			);
			
			$pages = $this->registerSettingsPages();
			
			if ( $pages ) {
				
				foreach ( $pages as $k => $params ) {
					
					$new_params = array_merge($params, $sp_params);
					$new_params['name'] = $k;
					
					$this->settings_pages[ $k ] = new owa_wp_settingsPage( $new_params, $this->options );
				}
			}
		}
	}
	
	/**
	 * Callback function for WordPress admin_menu hook
	 *
	 * Hooks create Menu Pages.
	 */
	public function addSettingsPages() {
	
		$this->initSettingsPage();
		
		$pages = $this->settings_pages;
		
		if ( $pages ) {
			
			foreach ( $pages as $k => $page ) {
				
				$menu_slug = '';
				
				$menu_slug = $page->get('menu_slug');
				
				// check for custom callback function.
				if ( $page->get( 'render_callback' ) ) {
					
					$callback = $page->get( 'render_callback' );
					
				} else {
					
					$callback = array( $page, 'renderPage' );
				}
			
				if ( $page->get('is_top_level') ) {
					
					add_menu_page( 
						$page->get('title'), 
						$page->get('top_level_menu_title'), 
						$page->get('required_capability'), 
						$page->get('parent_slug'), 
						$callback, 
						'', 
						6 
					);
					
					$menu_slug = $page->get('parent_slug');
				}
				
				// register the page with WordPress admin navigation.
				add_submenu_page( 
					$page->get('parent_slug'), 
					$page->get('title'), 
					$page->get('menu_title'), 
					$page->get('required_capability'),
					$menu_slug,
					$callback 
				);			
			}
		}
	}
	
	public function processAdminConfig() {
		
		$config = $this->registerOptions();
		
		if ( $config ) {
		
			foreach ( $config as $k => $v ) {
				
				// register setting field with module
				if ( array_key_exists( 'field', $v ) ) {
					// check for page_name, if not set it as 'default'
					if ( ! array_key_exists( 'page_name', $v['field'] ) ) {
						
						$v['field']['page_name'] = 'default';
					}
					
					// add field to settings array
					$this->settings[ $v['field']['page_name'] ][ $k ] = $v[ 'field' ];
				}
				
				// register default option value with module
				if (array_key_exists( 'default_value', $v ) ) {
				
					//$this->options[ $k ] = $v[ 'default_value' ];
				}
			}
			
			// hook settings fields into WordPress		
			if ( $this->settings ) {
				
				// we need ot init the settings page objects here 
				// as they are needed by two the callbacks to seperate WordPress Hooks admin_init and admin_menu.
				//$this->initSettingsPage();
				
				add_action( 'admin_init', array($this, 'registerSettings'),10,0);
				// regsiter the settings pages with WordPress
				add_action( 'admin_menu', array($this, 'addSettingsPages'), 11,0);
		
			}				
		}
	}
	
	public function registerAdminConfig() {
		
		return false;
	}
	
	public function registerSettings() {
					
		// process options
		
		$this->initSettingsPage();
		
		//add_action( 'admin_menu', array($this, 'addSettingsPages'), 10, 0 );
		
		// iterate throught group of settings fields.
		
		foreach ( $this->settings as $group_name => $group ) {
		
			// iterate throug thhe fields in the group
			foreach ( $group as $k => $v ) {
				
				// register each field with WordPress
				$this->settings_pages[ $group_name ]->registerField( $k, $v );
			}
			
			// register the group
			$this->settings_pages[ $group_name ]->registerSettings( $group_name );
			
			// register the sections
			
			$sections = $this->settings_pages[ $group_name ]->get('sections');
			
			if ( $sections ) {
				
				foreach ( $sections as $section_name => $section ) {
				
					$this->settings_pages[ $group_name ]->registerSection( $section );		
				}
			}
		}
	}
	
	/**
	 * Get Options Key 
	 *
	 * Gets the key under which options for the module should be persisted.
	 *
	 * @return string
	 */
	public function getOptionsKey() {
		
		//return owa_wp_util::getModuleOptionKey( $this->package_name, $this->module_name );
	}
	
	public function registerController( $action_name, $class, $path ) {
		
		$this->controllers[ $action_name ] = array(
			'class'			=> $class,
			'path'			=> $path
		);
	}
	
	public function registerControllers( $controllers = array() ) {
		
		return $controllers;
	}
	
	public function loadDependancies() {
			
		return false;
	}
	
	public function registerOptions() {
		
		return false;
	}
	
	public function setDefaultOptions( $options ) {
		
		//$options[ $this->getOptionsKey() ] = $this->options;
		return $this->options;
		//return $options;
	} 
	
	/**
	 * Register all of the hooks related to the module
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function defineAdminHooks() {
		
		return false;
	}
	
	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function definePublicHooks() {
		
		return false;
	}
}

class owa_wp_util {

	public static function getTaxonomies( $args ) {
	
		return get_taxonomies( $args );
	}
	
	public static function getPostTypes( $args, $type = 'names', $operator = 'and') {
		
		return get_post_types( $args, $type, $operator );
	}
	
	public static function getRemoteUrl( $url ) {
		
		return wp_remote_get ( urlencode ( $url ) );
	}
	
	public static function getModuleOptionKey( $package_name, $module_name ) {
		
		return sprintf( '%s_%s_%s', 'owa_wp', $package_name, $module_name );
	}
	
	public static function setDefaultParams( $defaults, $params, $class_name = '' ) {
		
		$newparams = $defaults;
		
		foreach ( $params as $k => $v ) {
			
			$newparams[$k] = $v;
		}
		
		return $newparams;
	}
	
	public static function addFilter( $hook, $callback, $priority = '', $accepted_args = '' ) {
		
		return add_filter( $hook, $callback, $priority, $accepted_args );
	}
	
	public static function addAction( $hook, $callback, $priority = '', $accepted_args = '' ) {
		
		return add_action( $hook, $callback, $priority, $accepted_args );
	}
	
	public static function escapeOutput( $string ) {
		
		return esc_html( $string );
	}
	
	//
	 // Outputs Localized String
	 //
	 //
	public static function out( $string ) {
		
		echo ( owa_wp_util::escapeOutput ( $string ) );
	}
	
	//
	 // Localize String
	 //
	 //
	public static function localize( $string ) {
		
		return $string;
	}
	
	//
	 // Flushes WordPress rewrite rules.
	 //
	 //
	public static function flushRewriteRules() {
		
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	
	//
	 // Get a direct link to install or update a plugin
	 //
	 //
	public static function getWpPluginInstallUrl( $slug, $action = 'install-plugin' ) {
		
		return wp_nonce_url(
		    add_query_arg(
		        array(
		            'action' => $action,
		            'plugin' => $slug
		        ),
		        admin_url( 'update.php' )
		    ),
		    $action . '_' . $slug
		);
	}
}

/////// settings class ///////////

class owa_wp_settingsPage {
	
	public $page_slug;
	
	public $package;
	
	public $module;
	
	public $ns;
	
	public $name;
	
	public $option_group_name; // owa-package-module-groupname
	
	public $fields;
	
	public $properties;
	
	public $options;
	
	public function __construct( $params, $options ) {
		

		$defaults = array(
			
			'ns'					=> 'owa_wp',
			'package'				=> '',
			'module'				=> '',
			'page_slug'				=> '',
			'name'					=> '',
			'title'					=> 'Placeholder Title',
			'description'			=> 'Placeholder description.',
			'sections'				=> array(),
			'required_capability'	=> 'manage_options'	
		
		);
		
		$params = owa_wp_util::setDefaultParams( $defaults, $params );
		$this->options = $options;
		$this->ns 				= $params['ns'];
		$this->package 			= $params['package'];
		$this->module 			= $params['module'];
		$this->name 			= $params['name'];
	
		if ( ! $params['page_slug'] ) {
						
			$params['page_slug'] = $this->generatePageSlug();		
		}
		
		$this->page_slug = $params['page_slug'];
		
		$this->default_options = array();
		
		$this->properties = $params;
				
		owa_wp_util::addFilter('owa_wp_settings_field_types', array( $this, 'registerFieldTypes'), 10, 1);
		
		// add error display callback.
		add_action( 'admin_notices', array( $this, 'displayErrorNotices' ) );
	}
	
	public function registerFieldTypes( $types = array() ) {
		
		
		$types['text'] = 'owa_wp_settings_field_text';
		
		$types['boolean'] = 'owa_wp_settings_field_boolean';
			
		$types['integer'] = 'owa_wp_settings_field_integer';
		
		$types['boolean_array'] = 'owa_wp_settings_field_booleanarray';
		
		$types['on_off_array'] = 'owa_wp_settings_field_onoffarray';
		
		$types['comma_separated_list'] = 'owa_wp_settings_field_commaseparatedlist';
		
		$types['select'] = 'owa_wp_settings_field_select';
		
		$types['textarea'] = 'owa_wp_settings_field_textarea';
		
		$types['url'] = 'owa_wp_settings_field_url';

		
		return $types;
	}
	
	public function get( $key ) {
		
		if (array_key_exists( $key, $this->properties ) ) {
			
			return $this->properties[ $key ];
		} 
	}
	
	public function generatePageSlug() {
		
		return sprintf( '%s-%s', $this->ns, $this->name );
	}
	
	public function registerSettings() {

			register_setting( $this->getOptionGroupName(), 'owa_wp', array( $this, 'validateAndSanitize' ) );
	}
	
	public function validateAndSanitize( $options ) {
	
		$sanitized = '';
		
		if ( is_array( $options ) ) {	
			
			$sanitized = array();
			
			foreach ( $this->fields as $k => $f ) {
				
				// if the option is present
				if ( array_key_exists( $k, $options ) ) {	
					
					$value = $options[ $k ] ;
					
					// check if value is required.
					if ( ! $value && $f->isRequired() ) {
						
						$f->addError( $k, $f->get('label_for'). ' field is required' );
						continue;
					}
					
					// sanitize value
					$value = $f->sanitize( $options[ $k ] );
					
					// validate value. Could be empty at this point.
					if ( $f->isValid( $value ) ) {
						//sanitize
						$sanitized[ $k ] =  $value;
					}
					
				} else {
				
					// set a false value in case it's a boollean type
					$sanitized[ $k ] = $f->setFalseValue();
				}
			}			
		}
		
		return $sanitized;
	}
	
	public function getOptionGroupName() {
		
		return sprintf( '%s_group', $this->get('page_slug') );
	}
	
	//
	 //Register a Settings Section with WordPress.
	 //
	 //
	public function registerSection( $params ) {
		
		// todo: add in a class type lookup here to use a custom section object
		// so that we can do custom rendering of section HTML if we 
		// ever need to.
		// $section = somemaplookup( $params['type']);
		
		$section = new owa_wp_settings_section($params);
		
		// Store the section object in case we need it later or want to inspect
		$this->sections[ $section->get( 'id' ) ] = $section;
		
		// register the section with WordPress
		add_settings_section( $section->get('id'), $section->get('title'), $section->get('callback'), $this->page_slug );
	}
	
	public function echoHtml( $html ) {
		
		echo $html;
	}
	
	public function registerField( $key, $params ) {
		
		// Add to params array
		// We need to pack params because ultimately add_settings_field 
		// can only pass an array to the callback function that renders
		// the field. Sux. wish it would accept an object...
			
		$params['id'] = $key;
		$params['package'] = $this->package;
		$params['module'] = $this->module;
		
		// make field object based on type
		
		$types = apply_filters( 'owa_wp_settings_field_types', array() );
		
		$field = new $types[ $params['type'] ]($params, $this->options);
		
		if ( $field ) {
			// park this field object for use later by validation and sanitization 			
			$this->fields[ $key ] = $field;
				
			// register label formatter callback
			$callback = $field->get( 'value_label_callback' );
			if ( $callback ) {
				owa_wp_util::addFilter( $field->get( 'id' ) . '_field_value_label', $callback, 10, 1 );
			}
			// add setting to wordpress settings api
			add_settings_field( 
				$key, 
				$field->get( 'title' ), 
				array( $field, 'render'), 
				$this->page_slug, 
				$field->get( 'section' ), 
				$field->getProperties() 
			); 
		} else {
			
			error_log("No field of type {$params['type']} registered.");
		}
	}
		
	public function renderPage() {
		
		wp_enqueue_script('jquery','','','',true);
		wp_enqueue_script('jquery-ui-core','','','',true);
		wp_enqueue_script('jquery-ui-tabs','','','',true);
		//add_settings_field( $id, $title, $callback, $page, $section = 'default', $args = array() )
		
		if ( ! current_user_can( $this->get('required_capability') ) ) {
    
        	wp_die(__( 'You do not have sufficient permissions to access this page!' ) );
		}
    
		echo '<div class="wrap">';
		echo	'<div class="icon32" id="icon-options-general"><br></div>';
		echo	sprintf('<h2>%s</h2>', $this->get( 'title') );
		echo	$this->get('description');
		
		if ( $this->fields ) {
			settings_errors();
			echo	sprintf('<form id=%s" action="options.php" method="post">', $this->page_slug);
			settings_fields( $this->getOptionGroupName() );
			//do_settings_sections( $this->get('page_slug') );
			$this->doTabbedSettingsSections( $this->get('page_slug') );
			echo	'<p class="submit">';
			echo	sprintf('<input name="Submit" type="submit" class="button-primary" value="%s" />', 'Save Changes' );
			echo	'</p>';
			echo	'</form>';
		}

		echo    '</div>';
	}
	
	///
	 // Outputs Settings Sections and Fields
	 //
	 // Sadly this is a replacement for WP's do_settings_sections template function
	 // because it doesn't allows for filtered output which we need for adding tabs.
	 //
	 // var $page	string	name of the settings page.
	 //
	public function doTabbedSettingsSections( $page ) {
		
		global $wp_settings_sections, $wp_settings_fields;
 
	    if ( ! isset( $wp_settings_sections[$page] ) ) {
	    
	        return;
		}
		
		echo '<div class="owa_wp_admin_tabs">';
		echo '<h2 class="nav-tab-wrapper">';
		echo '<ul style="padding:0px;margin:0px;">';
		foreach ( (array) $wp_settings_sections[$page] as $section ) {
			
			echo  sprintf('<li class="nav-tab" style=""><a href="#%s" class="%s">%s</a></li>', $section['id'], '', $section['title']);
			
		}
		echo '</ul>';
		echo '</h2>';
		
	    foreach ( (array) $wp_settings_sections[$page] as $section ) {
	    	
	    	echo sprintf( '<div id="%s">', $section['id'] );
	        if ( $section['title'] )
	            echo "<h3>{$section['title']}</h3>\n";
	 
	        if ( $section['callback'] )
	            call_user_func( $section['callback'], $section );
	 
	        if ( ! isset( $wp_settings_fields ) || !isset( $wp_settings_fields[$page] ) || !isset( $wp_settings_fields[$page][$section['id']] ) )
	            continue;
	        echo '<table class="form-table">';
	        do_settings_fields( $page, $section['id'] );
	        echo '</table>';
	        echo '</div>';
	    }
	    echo '</div>';
	    
	    echo'   <script>
					jQuery(function() { 
					
						jQuery( ".owa_wp_admin_tabs" ).tabs({
							 
							create: function(event, ui) {
								
								// CSS hackery to match up with WP built in tab styles.
								jQuery(this).find("li a").css({"text-decoration": "none", color: "grey"});
								ui.tab.find("a").css({color: "black"});
								ui.tab.addClass("nav-tab-active");
								// properly set the form action to correspond to active tab
								// in case it is resubmitted
								target = jQuery(".owa_wp_admin_tabs").parent().attr("action");
								new_target = target + "" + window.location.hash;
								jQuery(".owa_wp_admin_tabs").parent().attr("action", new_target);
							},
							
							activate: function(event, ui) {
								
								// CSS hackery to match up with WP built in tab styles.
								ui.oldTab.removeClass("nav-tab-active");
								ui.oldTab.find("a").css({color: "grey"});
								ui.newTab.addClass("nav-tab-active");
								ui.newTab.find("a").css({color: "black"});
								
								// get target tab nav link.
								new_tab_anchor = ui.newTab.find("a").attr("href");
								// set the url anchor
								window.location.hash = new_tab_anchor;
								// get current action attr of the form
								target = jQuery(".owa_wp_admin_tabs").parent().attr("action");
								// clear any existing hash from form target
								if ( target.indexOf("#") > -1 ) {
								
									pieces = target.split("#");
									new_target = pieces[0] + "" + new_tab_anchor;
									
								} else {
								
									new_target = target + "" + new_tab_anchor;
								}
								// add the anchor hash to the form action so that
								// the user returns to the correct tab after submit
								jQuery(".owa_wp_admin_tabs").parent().attr("action", new_target);
								
							}
						});
					});
					
			
				</script>';
	}
	
	public function displayErrorNotices() {
	
    	settings_errors( 'your-settings-error-slug' );
	}
}

class owa_wp_settings_field {
	
	public $id;
	
	public $package;
	
	public $module;
	
	public $properties;
	
	public $options;
	
	//
	 // name of the validator callback to be used
	 //
	public $validator_callback;
	
	//
	 // name of the santizer callback to be used
	 //
	public $santizer_callback;
	
	public function __construct( $params = '', $options ) {
		
		$defaults = array(
			
			'title'			=> 'Sample Title',
			'type'			=> 'text',
			'section'		=> '',
			'default_value'	=> '',
			'dom_id'		=> '',
			'name'			=> '',
			'id'			=> '',
			'package'		=> '',
			'module'		=> '',
			'required'		=> false,
			'label_for'		=> ''
			
		);
		
		$params = owa_wp_util::setDefaultParams( $defaults, $params );
		
		$this->options = $options;
		
		$this->package 		= $params['package'];
		$this->module		= $params['module'];
		$this->id 			= $params['id'];
		$this->properties 	= $params;
		
		$this->properties['name'] = $this->setName();
		$this->properties['dom_id'] = $this->setDomId();
	}
	
	public function get( $key ) {
		
		if (array_key_exists( $key, $this->properties) ) {
			
			return $this->properties[ $key ];
		}
	}
	
	public function getProperties() {
		
		return $this->properties;
	}
	
	public function setName( ) {
		
		return sprintf( 
			'%s[%s]', 
			'owa_wp', 
			$this->id
		);
	}
	
	public function render( $field ) {
		
		return false;
	}	
	
	public function setDomId( ) {
		
		return sprintf( 
			'%s_%s', 
			'owa_wp', 
			$this->id
		);
	}	
	
	public function sanitize( $value ) {
		
		return $value;
	}
	
	public function isValid( $value ) {
		
		return true;
	}
		
	public function addError( $key, $message ) {
		
		add_settings_error(
			$this->get( 'id' ),
			$key,
			$message,
			'error'
		);
		
	}
	
	public function setFalseValue() {
		
		return 0;
	}
	
	public function isRequired() {
		
		return $this->get('required');
	}
	
	public function getErrorMessage() {
		
		return $this->get('error_message');
	}
}

class owa_wp_settings_field_text extends owa_wp_settings_field {

	public function render( $attrs ) {
	//print_r();
		$value = $this->options[ $attrs['id'] ];
		
		if ( ! $value ) {
			
			//$value = pp_api::getDefaultOption( $this->package, $this->module, $attrs['id'] );
		}
		
		$size = $attrs['length'];
		
		if ( ! $size ) {
			
			$size = 30;
		}
		
		echo sprintf(
			'<input name="%s" id="%s" value="%s" type="text" size="%s" /> ', 
			esc_attr( $attrs['name'] ), 
			esc_attr( $attrs['dom_id'] ),
			esc_attr( $value ),
			$size 
		);
		
		echo sprintf('<p class="description">%s</p>', $attrs['description']);
	}	
	
	public function sanitize( $value ) {
		
		return trim($value);
	}
}

class owa_wp_settings_field_url extends owa_wp_settings_field {

	public function render( $attrs ) {
	
		$value = $this->options[ $attrs['id'] ];
				
		$size = $attrs['length'];
		
		if ( ! $size ) {
			
			$size = 60;
		}
		
		echo sprintf(
			'<input name="%s" id="%s" value="%s" type="text" size="%s" /> ', 
			esc_attr( $attrs['name'] ), 
			esc_attr( $attrs['dom_id'] ),
			esc_attr( $value ),
			$size 
		);
		
		echo sprintf('<p class="description">%s</p>', $attrs['description']);
	}	
	
	public function sanitize( $value ) {
		
		$value = trim( $value );
		
		$value = $url = filter_var( $value, FILTER_SANITIZE_URL );

/*
		if ( ! strpos( $value, '/', -1 ) ) {
			
			$value .= '/'; 
		}
*/
			
		return $value;
	}
	
	public function isValid( $value ) {
		
	
		if ( substr( $value, -4 ) === '.php' ) {
			
			$this->addError( 
		    	$this->get('dom_id'), 
				sprintf(
					'%s %s',
					$this->get( 'label_for' ),
					owa_wp_util::localize( 'URL should be the base directory of your OWA instance, not a file endpoint.' ) ) );
					
			return false;
		}
		
		if ( ! substr( $value, 0, 4 ) === "http" ) {
			
			$this->addError( 
	    	$this->get('dom_id'), 
			sprintf(
				'%s %s',
				$this->get( 'label_for' ),
				owa_wp_util::localize( 'URL scheme required. (i.e. http:// or https://)' ) ) );
			
			return false;
		}
			
		if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
			
			return true;	
			
		} else {
			
			// not a valid url
			$this->addError( 
			    	$this->get('dom_id'), 
					sprintf(
						'%s %s',
						$this->get( 'label_for' ),
						owa_wp_util::localize( 'Not a valid URL' ) ) );
		}		
	}
	
}

class owa_wp_settings_field_textarea extends owa_wp_settings_field {

	public function render( $attrs ) {
	//print_r();
		$value = $this->options[ $attrs['id'] ];
		
		echo sprintf(
			'<textarea name="%s" rows="%s" cols="%s" />%s</textarea> ', 
			esc_attr( $attrs['name'] ), 
			esc_attr( $attrs['rows'] ),
			esc_attr( $attrs['cols'] ),
			esc_attr( $value ) 
		);
		
		echo sprintf('<p class="description">%s</p>', $attrs['description']);
	}	
	
	public function sanitize( $value ) {
		
		return trim($value);
	}
}



class owa_wp_settings_field_commaseparatedlist extends owa_wp_settings_field_text {
	
	public function sanitize( $value ) {
		
		$value = trim( $value );
		$value = str_replace(' ', '', $value ); 
		$value = trim( $value, ',');
		
		return $value;
	}
	
	public function isValid( $value ) {
		
		$re = '/^\d+(?:,\d+)*$/';
	
		if ( preg_match( $re, $value ) ) {
		    
		    return true;
		
		} else {
		
		    $this->addError( 
		    	$this->get('dom_id'), 
				sprintf(
					'%s %s',
					$this->get( 'label_for' ),
					owa_wp_util::localize( 'can only contain a list of numbers separated by commas.' ) 
				)
			);
		}
	}
}

class owa_wp_settings_field_onoffarray extends owa_wp_settings_field {

	public function render ( $attrs ) {
		
		// get persisted options
		$values = $this->options[ $attrs['id'] ];
		
		// get the default options
		//$defaults = pp_api::getDefaultOption( $this->package, $this->module, $attrs['id'] );
		
		$options = $attrs['options'];
		
		if ( ! $values ) {
		
			$values = $defaults;
		}
	
		echo sprintf('<p class="description">%s</p>', $attrs['description']);
		
		foreach ( $options as $k => $label ) {
			
			$checked = '';
			$check = false;
			
			if ( in_array( trim( $k ), array_keys( $values ), true ) && $values[ trim( $k ) ] == true ) {
				
				$check = true;
			} 
				
			$on_checked = '';
			$off_checked = '';
			
			if ( $check ) {
				
				$on_checked = 'checked=checked';
				
			} else {
				
				$off_checked = 'checked';
			}
			
			//$callback = $this->get('value_label_callback');
				
			//$dvalue_label = apply_filters( $this->get('id').'_field_value_label', $ovalue );
			
			echo sprintf(
				'<p>%s: <label for="%s_on"><input class="" name="%s[%s]" id="%s_on" value="1" type="radio" %s> On</label>&nbsp; &nbsp; ', 
				$label,
				esc_attr( $attrs['dom_id'] ),
				esc_attr( $attrs['name'] ), 
				esc_attr( $k ),
				esc_attr( $attrs['dom_id'] ),
				$on_checked
			);
			
			echo sprintf(
				'<label for="%s_off"><input class="" name="%s[%s]" id="%s" value="0" type="radio" %s> Off</label></p>', 
				
				esc_attr( $attrs['dom_id'] ),
				esc_attr( $attrs['name'] ), 
				esc_attr( $k ),
				esc_attr( $attrs['dom_id'] ),
				$off_checked
			);
		}
	}
	
	public function setFalseValue() {
		
		return array();
	}
}

class owa_wp_settings_field_booleanarray extends owa_wp_settings_field {

	public function render ( $attrs ) {
		
		// get persisted options
		$values = $this->options[ $attrs['id'] ];
		
		// get the default options
		//$defaults = pp_api::getDefaultOption( $this->package, $this->module, $attrs['id'] );
		
		if ( ! $values ) {
		
			$values = array();
		}
	
		echo sprintf('<p class="description">%s</p>', $attrs['description']);
		
		foreach ( $defaults as $dvalue ) {
			
			$checked = '';
			$check = in_array( trim($dvalue), $values, true ); 
				
			if ( $check ) {
				
				$checked = 'checked="checked"';
			}
			
			$callback = $this->get('value_label_callback');
				
			$dvalue_label = apply_filters( $this->get('id').'_field_value_label', $dvalue );
			
			echo sprintf(
				'<p><input name="%s[]" id="%s" value="%s" type="checkbox" %s> %s</p>', 
				esc_attr( $attrs['name'] ), 
				esc_attr( $attrs['dom_id'] ),
				esc_attr( $dvalue ),
				$checked,
				esc_html( $dvalue_label )
			);
		}
	}
	
	public function setFalseValue() {
		
		return array();
	}
}

class owa_wp_settings_field_integer extends owa_wp_settings_field_text {
	
	
	public function sanitize( $value ) {
		
		return intval( trim( $value ) );
	}
	
	public function isValid( $value ) {
		
		if ( is_numeric( $value ) && $value > $this->get('min_value') ) {
			
			return true;
			
		} else {
		
			$this->addError( 
				$this->get('dom_id'), 
				sprintf(
					'%s %s %s %s %s.',
					$this->get('label_for'),
					owa_wp_util::localize('must be a number between'),
					$this->get('min_value'),
					owa_wp_util::localize('and'),
					$this->get('max_value')
				)
			);
		}
	}
}

class owa_wp_settings_field_select extends owa_wp_settings_field {
	
	public function sanitize ( $value ) {
		
		return $value;
	}
	
	public function render( $attrs ) {
		
		$selected = $this->options[ $attrs['id'] ];
		
		//$default = pp_api::getDefaultOption( $this->package, $this->module, $attrs['id'] );
		
		$options = $attrs['options'];
		$opts = '';
		
		foreach ($options as $option) {
			
			$selected_attr = '';
			
			if ($option === $selected) {
				
				$selected_attr = 'selected';
			}
			
			$opts .= sprintf(
				'<option value="%s" %s>%s</option> \n',
				$option,
				$selected_attr,
				ucwords( $option )
			);
		}
		
		
		echo sprintf(
			'<select id="%s" name="%s">%s</select>', 
			
			esc_attr( $attrs['dom_id'] ),
			esc_attr( $attrs['name'] ), 
			$opts
		);
		
		echo sprintf('<p class="description">%s</p>', $attrs['description']);
	
	}
}

class owa_wp_settings_field_boolean extends owa_wp_settings_field {
	
	public function isValid( $value ) {
	
		$value = intval($value);
		
		if ( $value === 1 || $value === 0 ) {
			
			return true;
		} else {
		
			$this->addError( $this->get('dom_id'), $this->get('label_for') . ' ' . owa_wp_util::localize( 'field must be On or Off.' ) );
		}

	}
	
	public function sanitize ( $value ) {
		
		return intval( $value );
	}
	
	public function render( $attrs ) {
		//print_r($attrs);
		//print_r($this->options);
		$value = $this->options[ $attrs['id'] ];
		
		if ( ! $value && ! is_numeric( $value )  ) {
			
			//$value = pp_api::getDefaultOption( $this->package, $this->module, $attrs['id'] );
		}
		
		$on_checked = '';
		$off_checked = '';
		
		if ( $value ) {
			
			$on_checked = 'checked=checked';
			
		} else {
			
			$off_checked = 'checked';
		}
		
		echo sprintf(
			'<label for="%s_on"><input class="" name="%s" id="%s_on" value="1" type="radio" %s> On</label>&nbsp; &nbsp; ', 
			
			esc_attr( $attrs['dom_id'] ),
			esc_attr( $attrs['name'] ), 
			esc_attr( $attrs['dom_id'] ),
			$on_checked
		);
		
		echo sprintf(
			'<label for="%s_off"><input class="" name="%s" id="%s" value="0" type="radio" %s> Off</label>', 
			esc_attr( $attrs['dom_id'] ),
			esc_attr( $attrs['name'] ), 
			esc_attr( $attrs['dom_id'] ),
			$off_checked
		);
		
		echo sprintf('<p class="description">%s</p>', $attrs['description']);
	}
}

class owa_wp_settings_section {
	
	public $properties;
	
	public function __construct( $params ) {
	
		$this->properties = array();
		
		$defaults = array(
			
			'id'			=> '',
			'title'			=> '',
			'callback'		=> array( $this, 'renderSection'),
			'description'	=> ''
		);
		
		$this->properties = owa_wp_util::setDefaultParams( $defaults, $params );
	}
	
	public function get( $key ) {
		
		if ( array_key_exists( $key, $this->properties ) ) {
			
			return $this->properties[ $key ];
		}
	}
	
	//
	 // Renders the html of the section header
	 //
	 // Callback function for 
	 //
	 // wordpress passes a single array here that contains ID, etc..
	 //
	public function renderSection( $arg ) {
	
		echo $this->get('description');
	}
}

?>