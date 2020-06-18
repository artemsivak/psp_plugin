<?php 
if ($_GET["replyCode"]) {
		if ($_GET["replyCode"] == "000") { ?>
			<h3 style="color: green;"><?php _e('Transaction successfully completed', 'theme'); ?></h3>
<?php	}else{	?>
			<h3><?php _e('Transaction status', 'theme'); ?>: <?= $_GET["replyDesc"] ?></h3>
<?php	}

}else if ($_POST["whichTp"]) {
	if (strlen($_POST["whichTp"]) > 4) {

	$information = isset($_SESSION['account_info']) ? $_SESSION['account_info'] : CrmApi::Instance()->GetAccountDetailsFromCrm("Email",$_SESSION['user']['email'])->GetAccountDetailsResult->AccountsInfo->AccountInfo; 
	$creds = GetCredentials();
	$url_link = "https://uiservices.coriunder.cloud/hosted/?";
	$curr_url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
	$merchantID = $creds->MerchantID;
	$hash = $creds->Hash;
	$amount = $_POST["amount"];
	$currency = $_POST["Currency"];
	$tp = $_POST["whichTp"];
	$order_id = rand(1, 100000)."-".$tp;
	$sign = ""; $url = "";
	$getCountry = getCountry($information->Country);

	$request = [
		"notification_url" => 'https://'.$_SERVER['HTTP_HOST']."/notify-page-psp/",
	//	"url_redirect" => $curr_url,
	 	"merchantID" => $merchantID,
	 	"trans_amount" => $amount,
	 	"trans_currency" => $currency,
	 	"trans_type" => 0,
	 	"trans_installments" => 1,
	 	"trans_refNum" => $order_id,
	 	"client_fullName" => $information->FirstName." ". $information->LastName,
	 	"client_email" => $_SESSION['user']['email'],
	 	"client_billCity" => $information->City,
	 	"client_billZipcode" => $information->ZipCode,
	 	"client_billAddress1" => $information->Address1,
	 	"client_billCountry" => $getCountry
	];

	foreach ($request as $key => $value) {
		$sign .= $value;
		$url .= $key."=".$value."&";
	}
	$sign = $sign.$hash; 
	$signature = base64_encode(hash("sha256", $sign, true));
	$signature = urlencode($signature);
	$redirect = $url_link.$url."signature=".$signature;
	createTransactionDB($order_id, $amount, $tp);

?>
<iframe class="iframe" src="<?= $redirect ?>"></iframe>
<?php 
	}else{
		?> <h3><?php _e('Something went wrong, please try again later.', 'theme'); ?></h3><?php
	}
}else{
	$currentTPs = getTpAccounts($_SESSION['user']['email']);
	$currency0 = $currentTPs[0]['currency'];
?>	
<form action="" method="post">
	<h4 class="headerH"><?php _e('Select TP login', 'theme'); ?>:</h4>
	<div class="TpAndCurrency">
		<select name="whichTp" class="whichTp" onchange="currentCurrency()">
	       <?php foreach ($currentTPs as $tp) { 
	        	if ($tp["tp_text"] == "Real") { ?>
	            <option data-currency="<?= $tp['currency'] ?>" value="<?= $tp['tp'] ?>">
	                <?= $tp["tp"] ?>
	            </option>
	        <?php } } ?>
    	</select>
    	<span class="currH"><?= $currency0 ?></span>
	</div>
	<h4 class="headerH"><?php _e('Amount', 'theme'); ?>:</h4>
	<input type="number" name="amount" class="amountH" required="" min="50">
	<input type="hidden" name="Currency" class="currencyH" value="<?= $currency0 ?>">
	<input type="submit" value="<?php _e('Deposit', 'theme'); ?>" class="depositButton btn-info">
</form>

<script type="text/javascript">
	function currentCurrency() {
		var curr = $(".whichTp").find(':selected').data('currency');
		$(".currH").text(curr);
		$(".currencyH").val(curr);
	}
	function closePopUpX() {
		$(".popup").attr("style", "display:none;");
	}
	jQuery(document).ready(function($) {
		currentCurrency();
	});
</script>
<?php 
	} 
?>
<style type="text/css">
	.TpAndCurrency {
    	display: flex;
    	width: 180px;
    	margin-bottom: 20px;
	}
	select.whichTp {
	    border: 2px solid rgba(0, 0, 0, 0.29);
	    -moz-appearance: button;
	    -webkit-appearance: button;
	    cursor: pointer;
	    width: 126px;
	    padding: 0px 0px 0px 8px;
	    border-radius: 5px 0px 0px 5px;
	}
	span.currH {
	    border: 2px solid #b5b5b5;
	    border-left: 0px;
	    display: flex;
	    align-items: center;
	    padding: 0px 0px;
	    border-radius: 0px 5px 5px 0px;
	    background: #f2f2f2;
	    font-weight: 600;
	    width: 54px;
	    justify-content: center;
	}
	h4.headerH {
    	margin-bottom: 4px;
    	text-transform: uppercase;
    	font-size: 12pt;
	}
	input.amountH {
    	border: 2px solid #b5b5b5;
    	border-radius: 5px;
    	padding: 11px 13px;
    	width: 180px;
	}
	input.depositButton {
	    display: block;
	    width: 180px;
	    padding: 12px 0px;
	    border-radius: 5px;
	    margin-top: -2px;
	    text-transform: uppercase;
	    font-weight: 600;
	    font-size: 12pt;
	}
	.popup > div > iframe {
	    width: 94%;
	    height: 94%;
	}
	.popup {
	    position: absolute;
	    z-index: 999999;
	    width: 100%;
	    height: 100%;
	    display: flex;
	    align-items: center;
	    background: rgba(0, 0, 0, 0.59);
	}
	.popup > div {
	    margin: 0px auto;
	    width: 650px;
	    height: 740px;
	    background: white;
	    display: flex;
	    align-items: center;
	    justify-content: center;
	    flex-wrap: wrap;
	} 
	.closePopUp {
    	width: 100%;
    	text-align: right;
	}
	.closePopUp i {
	    margin-right: 18px;
	    font-size: 17pt;
	    margin-top: 2px;
	    cursor: pointer;
	}
	.closePopUp i:hover {
    	color: #bb1010d6;
	}
iframe.iframe {
    width: 100%;
    min-height: 800px;
}
</style>
