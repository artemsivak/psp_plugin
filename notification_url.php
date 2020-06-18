<?php 
require_once ('../../../wp-config.php');

	$request = $_POST;
	extract($request);
	$creds = GetCredentials();
	$hash = $creds->Hash;
	$sign = $trans_id.$trans_order.$reply_code.$trans_amount.$trans_currency.$hash;
	$sign = base64_encode(hash("sha256", $sign, true));

	global $wpdb;
	$table_name = $wpdb->prefix."psp_plugin";
	$sql = "SELECT * FROM  $table_name WHERE order_id LIKE '$trans_order'";
	$result = end($wpdb->get_results($sql));

	if ($signature == $sign and $result->status != "success" and $reply_code == "000") {
		$tp_id = getIdByName($result->tp);
		updateSuccess($trans_order);
		UserDepositPSP($tp_id,$result->amount,"Success",$msg = "");
	}
?>