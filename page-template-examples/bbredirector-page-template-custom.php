<?php
/*
 * Template Name: Page Redirect Custom
 *
 * This template uses the bbRedirector plugin and makes it very easy to redirect a page to another location.
 *
 * How it works: 
 * - Copy this file to your theme directory.
 * - Select this template (Page Redirect) as your page template in the interface
 * - Adjust the array values to your wishes, the http_code is optional.
 */
if( function_exists('bbrd_redirect') ) { bbrd_redirect( array('redirect_url' => 'http://www.burobjorn.nl', 'http_code' => 301) ); } ?>
