<?php
/*
Plugin Name: bbRedirector
Plugin URI: http://www.burobjorn.nl
Description: bbRedirector makes it easy to redirect a page to another location. 
Author: Bjorn Wijers <burobjorn at burobjorn dot nl> 
Version: 1.0
Author URI: http://www.burobjorn.nl
*/   
   
/*  Copyright 2009  

bbRedirector is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

bbRedirector is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
* Guess the wp-content and plugin urls/paths
*/

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) ) {
  define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
}  

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
  define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_URL' ) ) {
  define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
  define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

if ( ! class_exists('bbRedirector')) {
  class bbRedirector {
  
    /**
    * @var string The options string name for this plugin
    */
    var $options_name = 'bbrd_options';
    
    /**
    * @var string $localization_domainDomain used for localization
    */
    var $localization_domain = "bbrd";
    
    /**
    * @var string $pluginurl The path to this plugin
    */ 
    var $this_plugin_url = '';
    /**
    * @var string $pluginurlpath The path to this plugin
    */
    var $this_plugin_path = '';
        
    /**
    * @var array $options Stores the options for this plugin
    */
    var $options = array();
    
    /**
    * PHP 4 Compatible Constructor
    */
    function bbRedirector(){ $this->__construct(); }
    
    /**
    * PHP 5 Constructor
    */        
    function __construct(){
      //Language Setup
      $locale = get_locale();
      $mo     = dirname(__FILE__) . "/languages/" . $this->localization_domain. "-".$locale.".mo";
      load_textdomain($this->localization_domain, $mo);

      //"Constants" setup
      $this->this_plugin_url  = PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
      $this->this_plugin_path = PLUGIN_PATH . '/' . dirname(plugin_basename(__FILE__)).'/';
      
      //Initialize the options
      //This is REQUIRED to initialize the options when the plugin is loaded!
      $this->getOptions();
      
      // Actions        
      add_action("admin_menu", array(&$this,"admin_menu_link"));
    }


    /**
     * Redirects to a given url with a chosen header http status code.
     *
     * How it works: 
     * First check if a valid url is supplied and use this for redirection,
     * otherwise check if a page id is supplied and use this with a metakey 
     * (supplied or set in the options) to retrieve an url for redirection.
     *
     * If no parameters have been supplied and the allow_default_redirection
     * is set we'll use the url and optional http status codes in the options to redirect.
     *   
     *
     * @param array | string 
     *
     **/
    function redirect($args = array() ) 
    {
      // parse arguments
      if( ! is_array($args) ) { wp_parse_str($args, $args); }

      if( ! empty ($args['redirect_url']) ) {
          return $this->_doRedirect($args['redirect_url'], $args['http_code']);  
      } else if ( ! empty($args['page_id']) ) {
          $metakey = empty($args['metakey']) ? $this->getOption('bbrd_metakey') : $args['metakey'];
          return $this->_doRedirect( get_post_meta($args['page_id'], $metakey, true), $args['http_code'] );  
      } else if( $this->getOption('bbrd_allow_default_redirection') ) {
          return $this->_doRedirect( $this->getOption('bbrd_redirect_url'), $this->getOption('bbrd_http_code') );
      } else {
        return false;
      }
    }  


    /** 
     * Check if an url is parseable by parse_url and contains either http or https as scheme
     *
     * @access private
     * @param string url
     * @return boolean true on parseable url with either http or https as scheme otherwise false
     */
    function _isURLValid($url) 
    {
      $parsed_url = parse_url($url);
      if( is_array($parsed_url) ) {
        if( array_key_exists( 'scheme', $parsed_url) ) {
          if('http' == $parsed_url['scheme'] || 'https' == $parsed_url['scheme']) {
            return true;
          }
        }
      }
      return false;
    }


    /**
     * Perform the redirection to a supplied url with an optional supplied http status code
     *
     * @acces private
     * @param string url
     * @param int http status code. At the moment only 301 and 302 are supported
     * @return void | boolean on succes redirect otherwise boolean false
     */
    function _doRedirect($url, $http_code = 302) 
    {
      if( $this->_isURLValid($url) ) {
        $url = trim($url);
        switch($http_code) {
        
          // moved permanently
          case 301: 
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: $url");
            exit();
            break; /* useless break? */  
            
            
          // temporarily redirected  
          case 302:  
          default:
            header("Location: $url");
            exit();
            break;
        }
      }
      return false;
    }


    
    
    /**
    * Retrieves the plugin options from the database.
    * @return array
    */
    function getOptions() 
    {
      //Don't forget to set up the default options
      if ( ! $the_options = get_option($this->options_name)) {
        $the_options = array('bbrd_metakey' =>'redirect_url', 'bbrd_redirect_url' => get_bloginfo('wpurl'), 'bbrd_http_code' => 302, 'bbrd_allow_default_redirection' => TRUE);
        update_option($this->options_name, $the_options);
      }
      $this->options = $the_options;
    }
    
    /** 
     * Get a single option
     *
     * @access public
     * @param string Option Name
     * @return mixed   
     */
    function getOption($name) 
    {
      if( sizeof($this->options) < 0 ) { $this->getOption(); }
      return $this->options[$name];
    }


    /** 
     * Set a single option
     *
     * @acces public
     * @param string Option name
     * @param mixed Option value
     * @return unknown
     */
    function setOption($name, $value)
    {
      $this->options[$name] = $value;
      return $this->saveAdminOptions();
    }


    /**
     * Saves the admin options to the database.
     * 
     * @access public
     * @return unknown
     */
    function saveAdminOptions(){
      return update_option($this->options_name, $this->options);
    }
    
    /**
    * @desc Adds the options subpanel
    */
    function admin_menu_link() {
      //If you change this from add_options_page, MAKE SURE you change the filter_plugin_actions function (below) to
      //reflect the page filename (ie - options-general.php) of the page your plugin is under!
      add_options_page('bbRedirector', 'bbRedirector', 10, basename(__FILE__), array(&$this,'admin_options_page'));
      add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
    }
    
    /**
    * @desc Adds the Settings link to the plugin activate/deactivate page
    */
    function filter_plugin_actions($links, $file) {
       $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
       array_unshift( $links, $settings_link ); // before other links
       return $links;
    }

    /**
     * Return or output Wordpress html for update and/or error messages
     *
     * @access private
     * @param string Message. Will be localized, if possible.
     * @param string Type of message. Currently only error and update are supported
     * @param boolean Set to true for echo'ing directly, false for returning the result
     * @return void|string Depending on setting echo to true or false. 
     * Either returns nothing, an empty string or the message
     */
    function _msg($msg, $type, $echo = TRUE) 
    {
      $result = '';

      switch($type) {
        case 'error':
          $result = '<div class="error"><p>' . __($msg, $this->localization_domain) . '</p></div>';
          break;

        case 'update':
          $result = '<div class="updated"><p>' . __($msg, $this->localization_domain) . '</p></div>';
          break;
      }

      if(true == $echo) {
        echo $result; 
      } else {
        return $result;
      }
    }

    /**
    * Adds settings/options page
    */
    function admin_options_page() { 
      if($_POST['bbrd_save']){
          if (! wp_verify_nonce($_POST['_wpnonce'], 'bbrd-update-options') ) die( __('Whoops! There was a problem with the data you posted. Please go back and try again.', $this->localization_domain) ); 
          if( ! $this->_isURLValid($_POST['bbrd_redirect_url']) ) { 
            $this->_msg('Whoops! Seems you did not supply an url or the url supplied was invalid. Maybe you forgot to put http:// in front?', 'error'); 
          } else {
            $this->options['bbrd_allow_default_redirection'] = ($_POST['bbrd_allow_default_redirection'] == 'on') ? true : false;
            $this->options['bbrd_redirect_url']              = $_POST['bbrd_redirect_url'];
            $this->options['bbrd_http_code']                 = $_POST['bbrd_http_code'];
            $this->options['bbrd_metakey']                   = $_POST['bbrd_metakey'];
            $this->saveAdminOptions();
            $this->_msg('Saved! The options have been successfully saved.', 'update');
          }
      }
?>                                   
      <div class="wrap">
      <h2>bbRedirector</h2>
      <form method="post" id="bbrd_options">
      <?php wp_nonce_field('bbrd-update-options'); ?>
          <table width="100%" cellspacing="2" cellpadding="5" class="form-table"> 
              <tr valign="top"> 
                  <th width="33%" scope="row"><label for="bbrd_allow_default_redirection"><?php _e('Allow default redirection:', $this->localization_domain); ?></label></th> 
                  <td><input name="bbrd_allow_default_redirection" type="checkbox" id="bbrd_allow_default_redirection" value="on" <?php if ($this->options['bbrd_allow_default_redirection']) { echo 'checked=checked'; } ?> />
              </td> 
              </tr>
              <tr valign="top"> 
                  <th width="33%" scope="row"><label for="bbrd_redirect_url"><?php _e('Default redirect url:', $this->localization_domain); ?></label></th> 
                  <td><input name="bbrd_redirect_url" type="text" id="bbrd_redirect_url" value="<?php echo $this->options['bbrd_redirect_url'] ;?>"/>
                  </td> 
              </tr>

              <tr valign="top"> 
                  <th width="33%" scope="row"><label for="bbrd_http_code"><?php _e('Default HTTP status code :', $this->localization_domain); ?></label></th> 
                  <td><input name="bbrd_http_code" type="text" id="bbrd_http_code" value="<?php echo $this->options['bbrd_http_code'] ;?>"/>
                  </td> 
              </tr>

              <tr valign="top"> 
                  <th width="33%" scope="row"><label for="bbrd_metakey"><?php _e('Metakey name (used to retrieve per-page redirect url):', $this->localization_domain); ?></label></th> 
                  <td><input name="bbrd_metakey" type="text" id="bbrd_metakey" value="<?php echo $this->options['bbrd_metakey'] ;?>"/>
                  </td> 
              </tr>
              <tr>
                  <th colspan=2><input type="submit" name="bbrd_save" value="Save" /></th>
              </tr>
          </table>
      </form>
            <?php
    }
  } //End Class
} //End if class exists statement


// makes life of themers easier by wrapping redirect call into wrapper
if (class_exists('bbRedirector')) {
  $bbrd_var = new bbRedirector();

  if( ! function_exists('bbrd_redirect') ) {  
    function bbrd_redirect($args = array() ) 
    {
      global $bbrd_var; 
      return $bbrd_var->redirect($args); 
    }
  } 
}
?>
