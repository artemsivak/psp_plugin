<?php
/*
Plugin Name: PSP by Leverate Web team
Plugin URI: http://leverate.com/
Description: PSP by <strong>Leverate Web team</strong> Plugin
Version: 0.0s.1
Author: Leverate
Author URI: https://leverate.com/
License: GPLv2 or later
Text Domain: Leverate
*/

defined( 'ABSPATH' ) or die( 'No scripts');
include("functions.php");

function psp_plugin() {
	include("deposit_page.php");
}
add_shortcode("psp_plugin", "psp_plugin");

add_filter( 'plugin_action_links_psp_plugin/psp_plugin.php', 'nc_settings_link');
function nc_settings_link( $links ) {
	$url = "/wp-admin/admin.php?page=pspGateWay";
	$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
	array_push(
		$links,
		$settings_link
	);
	return $links;
}


function create_table_psp() {
	global $wpdb;
	$table_name = $wpdb->prefix."psp_plugin";
		if ($wpdb->get_var('SHOW TABLES LIKE '.$table_name) != $table_name) {
			 $sql = 'CREATE TABLE '.$table_name.'(
			        id INTEGER NOT NULL AUTO_INCREMENT,
			        order_id VARCHAR(30),
			        amount VARCHAR(10),
			        tp VARCHAR(30),
			        date VARCHAR(30),
			        status VARCHAR(30),
			        PRIMARY KEY (id))';

		    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		    dbDelta($sql);
		}
	$table_name = $wpdb->prefix."psp_plugin_credentials";
		if ($wpdb->get_var('SHOW TABLES LIKE '.$table_name) != $table_name) {
			 $sql = 'CREATE TABLE '.$table_name.'(
			        id INTEGER NOT NULL,
			        MerchantID VARCHAR(10),
			        Hash VARCHAR(10),
			        PRIMARY KEY (id))';

		    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		    dbDelta($sql);
		}

	$table_name = $wpdb->prefix."leverate_country_psp";
		if ($wpdb->get_var('SHOW TABLES LIKE '.$table_name) != $table_name) {

			$sql = createCountriesDB($table_name);
		    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		    dbDelta($sql);
		}

}
register_activation_hook(__FILE__, 'create_table_psp');

function notification_url() {
	$path = str_replace([$_SERVER['HTTP_HOST'].'/', 'https://'], '', plugins_url('notification_url.php', __FILE__ ));
	add_rewrite_rule('notify-page-psp/', $path);
}
add_action('init', 'notification_url');

function setup_GateWayMenu() {

	add_submenu_page('Leverate-Admin-Menu-Page', 'PSP GateWay', 'PSP GateWay', "manage_options", "pspGateWay", 'ShowSubmenuGateWay');

}
function ShowSubmenuGateWay() {

	global $wpdb;
    $credent = $wpdb->prefix . 'psp_plugin_credentials';
    $query = "SELECT * FROM $credent WHERE id = 0";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     
     extract($_POST);
     
     $array = array( 
            'MerchantID' => $MerchantID, 
            'Hash' => $Hash,
        );

        $results = $wpdb->get_results($query, OBJECT );
        if (isset($results[0])) {
            $wpdb->update( $credent, $array, array( 'ID' => 0 ) );
        }else{
            $wpdb->insert( $credent, $array);
        }
     
    }

    $results = end($wpdb->get_results($query, OBJECT ));

	?>
	<div class="wrap">
    <h1>Connection to PSP Gateway</h1><br/>
    <form action="#" method="post">
    <div>
    	<label>MerchantID:</label><br/>
    	<input type="text" name="MerchantID" value="<?= $results->MerchantID ?>" style="width: 400px;">
    </div>
    <br/>
    <div>
    	<label>Hash:</label><br/>
    	<input type="text" name="Hash" value="<?= $results->Hash ?>" style="width: 400px;">
    </div><br/>
	<input type="submit" value="submit" style="    
    width: 170px;
    height: 39px;
    background: #424242;
    color: white;
    border: 0px;
    font-weight: bold;
    text-transform: uppercase;
    cursor: pointer;">
    </form><br/>
    <p><strong>Instruction:</strong> 
    	<ol>
    		<li>Put credentials above.</li>
    		<li>Go to Settings->Permalinks and just update it without changing.</li>
    		<li>Place this shortcode on deposit page: <i>[psp_plugin]</i></li>
    	</ol>
    </p>
	</div>
	<?php
}

add_action('admin_menu', 'setup_GateWayMenu');