<?php
/*
 * Template Name: Page Redirect Metakey
 *
 * This template uses the bbRedirector plugin and makes it very easy to redirect a page to another location.
 *
 * How it works: 
 * - Copy this file to your theme directory.
 * - Select this template (Page Redirect) as your page template in the interface
 * - Add a custom field to the Page. The name of the custom field can be set in the bbRedirector settings.
 * The default name of the custom field is redirect_url, the value of this custom field is the absolute url to which this page schould redirect. 
 *
 * This is probably the most common usage in which you can have each page redirect to a location set in the custom field. This makes it very easy to change
 * redirection locations for each page.
 */
if( function_exists('bbrd_redirect') ) { bbrd_redirect( array('page_id' => $post->ID) ); } ?>
