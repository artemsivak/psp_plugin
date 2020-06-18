<?php 

function createTransactionDB($order_id, $amount, $tp) {
	global $wpdb;
 	$table = $wpdb->prefix.'psp_plugin';
	$data = [
		"order_id" => $order_id,
		"amount" => $amount,
		"tp" => $tp,
		"status" => "pending"
	];
	$wpdb->insert($table, $data);
}
function updateSuccess($order_id) {
	global $wpdb;
	$table = $wpdb->prefix.'psp_plugin';
	$myData = ["status" => "success"];
	$wpdb->update($table, $myData, ["order_id" => $order_id]);
}
function GetCredentials() {
	global $wpdb;
    $credent = $wpdb->prefix . 'psp_plugin_credentials';
    $query = "SELECT * FROM $credent WHERE id = 0";
    $results = end($wpdb->get_results($query, OBJECT ));
    return $results;
}
function getTpAccounts($email) {

	global $wpdb;
	$results = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."leverate_tp_accounts WHERE email = '$email'");
	$tps = Model::Instance()->GetTpsAccountFromCrmByEmail($email);
	$actualTPs = array();
	foreach ($tps as $tp) {
		$result = CrmApi::Instance()->GetAccountBalance(AdminModel::Instance()->GetConfigVar(), $tp->Name);

		if ($result) array_push($actualTPs, array(
			"tp" =>	$tp->Name,
			"currency" => $tp->BaseCurrency->Code,
			"tp_text" => $tp->TradingPlatform->Type,
			'balance' => $result->Balance,
			"currencySymbol" => $tp->BaseCurrency->Symbol,
		));
	}
	return $actualTPs;
}

function getIdByName($tp_name) {

	$GetTpId = CrmApi::Instance()->GetAccountDetailsByTpName($tp_name);
	$tpInfo = $GetTpId->GetAccountDetailsResult->AccountsInfo->AccountInfo->TradingPlatformAccounts->TradingPlatformAccountInfo;

	if (is_array($tpInfo)) {
		
		foreach ($tpInfo as $value) {
			
			if ($value->Name == $tp_name) {
				$newtpId = $value->Id;
				break;
			}
		}

	}else{
		$newtpId = $tpInfo->Id;
	}

	return $newtpId;
}

function UserDepositPSP($tradingPlatformAccountId,$amount,$situation,$msg = ""){
			
		$leverateCrm = Model::Instance()->getCrm();
		$config = AdminModel::Instance()->GetConfigVar();
		try{
		$info = new MonetaryTransactionRequestInfo();
		$info->Amount = $amount;		
		$info->TradingPlatformAccountId = $tradingPlatformAccountId;
		$info->PaymentInfo = new CreditCardPaymentInfo();
		$info->OriginalAmount = null;
		$info->OriginalCurrency = null; 
		if(strncmp($situation,"Failure",strlen("Failure")) == 0){
					$tp_name = Model::Instance()->GetTradingAccountNameByTradingPlatformId($tradingPlatformAccountId);
					$createCase = new CreateCase(); 
					$caseCreationRequest = new CaseCreationRequest();
					$createCase->businessUnitName = $config['businessUnitName']; 
					$createCase->organizationName = $config['organization'];
					$createCase->ownerUserId = $config['ownerUserId']; 
					$caseCreationRequest->Description = $msg;
					$caseCreationRequest->Title = "Deposit Failure";
					$caseCreationRequest->AccountId = AdminModel::Instance()->GetCrmAccountIdByEmail($_SESSION['user']['email']);
					$createCase->caseCreationRequest = $caseCreationRequest;
					$createCaseResponse = new CreateCaseResponse(); 
					$createCaseResponse = $leverateCrm->CreateCase($createCase);
			return ;
		}		
		else{
		$request = new DepositRequest();
		$request->IsCancellationTransaction = false;
		$request->ShouldAutoApprove = true;
		$request->UpdateTPOnApprove = true;
		$request->MonetaryTransactionRequestInfo = $info;
		$query = new CreateMonetaryTransaction();
		$query->ownerUserId = $config['ownerUserId'];
		$query->organizationName = $config['organization'];
		$query->businessUnitName = $config['businessUnitName'];
		$query->monetaryTransactionRequest = $request;
		$response = $leverateCrm->CreateMonetaryTransaction($query);
        $ResultInfo = new ResultInfo();
        $ResultInfo = $response->CreateMonetaryTransactionResult->Result;
		$result = $ResultInfo->RequestId;
        $success = $ResultInfo->Code;
        $message = $ResultInfo->Message;
        return true;
        	}
    } catch (Exception $e) {
		return false;
    }
}
function getCountry($country) {
	global $wpdb;
    $contry_list = $wpdb->prefix . 'leverate_country_psp';
    $query = "SELECT country_code FROM $contry_list WHERE country_name LIKE '$country'";
    $results = end($wpdb->get_results($query, OBJECT ));
    return $results->country_code;
}

function createCountriesDB($table_name) {
	$sql = "CREATE TABLE $table_name (
				  `id` int(11) NOT NULL,
				  `country_long_id` varchar(300) CHARACTER SET latin1 NOT NULL,
				  `country_name` text CHARACTER SET latin1 NOT NULL,
				  `country_code` text CHARACTER SET latin1 NOT NULL,
				  `country_phone_code` varchar(500) CHARACTER SET latin1 NOT NULL,
				  `country_states` text CHARACTER SET latin1 NOT NULL,
				  PRIMARY KEY (id)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;

				INSERT INTO $table_name (`id`, `country_long_id`, `country_name`, `country_code`, `country_phone_code`, `country_states`) VALUES
			(1, 'e738c10a-fdb0-df11-ab2e-005056990011', 'Afghanistan', 'AF', '93', ''),
			(2, 'e838c10a-fdb0-df11-ab2e-005056990011', 'Aland Islands', 'AX', '358', ''),
			(3, 'e938c10a-fdb0-df11-ab2e-005056990011', 'Albania', 'AL', '355', ''),
			(4, 'ea38c10a-fdb0-df11-ab2e-005056990011', 'Algeria', 'DZ', '213', ''),
			(5, 'eb38c10a-fdb0-df11-ab2e-005056990011', 'American Samoa', 'AS', '1684', ''),
			(6, 'ec38c10a-fdb0-df11-ab2e-005056990011', 'Andorra', 'AD', '376', ''),
			(7, 'ed38c10a-fdb0-df11-ab2e-005056990011', 'Angola', 'AO', '244', ''),
			(8, 'ee38c10a-fdb0-df11-ab2e-005056990011', 'Anguilla', 'AI', '1264', ''),
			(9, 'ef38c10a-fdb0-df11-ab2e-005056990011', 'Antarctica', 'AQ', '0', ''),
			(10, 'f038c10a-fdb0-df11-ab2e-005056990011', 'Antigua And Barbuda', 'AG', '1268', ''),
			(11, 'f138c10a-fdb0-df11-ab2e-005056990011', 'Argentina', 'AR', '54', ''),
			(12, 'f238c10a-fdb0-df11-ab2e-005056990011', 'Armenia', 'AM', '374', ''),
			(13, 'f338c10a-fdb0-df11-ab2e-005056990011', 'Aruba', 'AW', '297', ''),
			(14, 'f438c10a-fdb0-df11-ab2e-005056990011', 'Australia', 'AU', '61', ''),
			(15, 'f538c10a-fdb0-df11-ab2e-005056990011', 'Austria', 'AT', '43', ''),
			(16, 'f638c10a-fdb0-df11-ab2e-005056990011', 'Azerbaijan', 'AZ', '994', ''),
			(17, 'f738c10a-fdb0-df11-ab2e-005056990011', 'Bahamas', 'BS', '1242', ''),
			(18, 'f838c10a-fdb0-df11-ab2e-005056990011', 'Bahrain', 'BH', '973', ''),
			(19, 'f938c10a-fdb0-df11-ab2e-005056990011', 'Bangladesh', 'BD', '880', ''),
			(20, 'fa38c10a-fdb0-df11-ab2e-005056990011', 'Barbados', 'BB', '1246', ''),
			(21, 'fb38c10a-fdb0-df11-ab2e-005056990011', 'Belarus', 'BY', '375', ''),
			(22, 'fc38c10a-fdb0-df11-ab2e-005056990011', 'Belgium', 'BE', '32', ''),
			(23, 'fd38c10a-fdb0-df11-ab2e-005056990011', 'Belize', 'BZ', '501', ''),
			(24, 'fe38c10a-fdb0-df11-ab2e-005056990011', 'Benin', 'BJ', '229', ''),
			(25, 'ff38c10a-fdb0-df11-ab2e-005056990011', 'Bermuda', 'BM', '1441', ''),
			(26, '0039c10a-fdb0-df11-ab2e-005056990011', 'Bhutan', 'BT', '975', ''),
			(27, '0139c10a-fdb0-df11-ab2e-005056990011', 'Bolivia', 'BO', '591', ''),
			(28, '0239c10a-fdb0-df11-ab2e-005056990011', 'Bosnia And Herzegovina', 'BA', '387', ''),
			(29, '0339c10a-fdb0-df11-ab2e-005056990011', 'Botswana', 'BW', '267', ''),
			(30, '0439c10a-fdb0-df11-ab2e-005056990011', 'Bouvet Island', 'BV', '0', ''),
			(31, '0539c10a-fdb0-df11-ab2e-005056990011', 'Brazil', 'BR', '55', ''),
			(32, '0639c10a-fdb0-df11-ab2e-005056990011', 'British Indian Ocean Territory', 'IO', '246', ''),
			(33, '0739c10a-fdb0-df11-ab2e-005056990011', 'Brunei Darussalam', 'BN', '673', ''),
			(34, '0839c10a-fdb0-df11-ab2e-005056990011', 'Bulgaria', 'BG', '359', ''),
			(35, '0939c10a-fdb0-df11-ab2e-005056990011', 'Burkina Faso', 'BF', '226', ''),
			(36, '0a39c10a-fdb0-df11-ab2e-005056990011', 'Burundi', 'BI', '257', ''),
			(37, '0b39c10a-fdb0-df11-ab2e-005056990011', 'Cambodia', 'KH', '855', ''),
			(38, '0c39c10a-fdb0-df11-ab2e-005056990011', 'Cameroon', 'CM', '237', ''),
			(39, '0d39c10a-fdb0-df11-ab2e-005056990011', 'Canada', 'CA', '1', ''),
			(40, '0e39c10a-fdb0-df11-ab2e-005056990011', 'Cape Verde', 'CV', '238', ''),
			(41, '0f39c10a-fdb0-df11-ab2e-005056990011', 'Cayman Islands', 'KY', '1345', ''),
			(42, '1039c10a-fdb0-df11-ab2e-005056990011', 'Central African Republic', 'CF', '236', ''),
			(43, '1139c10a-fdb0-df11-ab2e-005056990011', 'Chad', 'TD', '235', ''),
			(44, '1239c10a-fdb0-df11-ab2e-005056990011', 'Chile', 'CL', '56', ''),
			(45, '1339c10a-fdb0-df11-ab2e-005056990011', 'China', 'CN', '86', ''),
			(46, '1439c10a-fdb0-df11-ab2e-005056990011', 'Christmas Island', 'CX', '61', ''),
			(47, '1539c10a-fdb0-df11-ab2e-005056990011', 'Cocos (Keeling) Islands', 'CC', '61', ''),
			(48, '1639c10a-fdb0-df11-ab2e-005056990011', 'Colombia', 'CO', '57', ''),
			(49, '1739c10a-fdb0-df11-ab2e-005056990011', 'Comoros', 'KM', '269', ''),
			(50, '1839c10a-fdb0-df11-ab2e-005056990011', 'Congo', 'CG', '242', ''),
			(51, '1939c10a-fdb0-df11-ab2e-005056990011', 'Congo  The Democratic Republic Of The', 'CD', '243', ''),
			(52, '1a39c10a-fdb0-df11-ab2e-005056990011', 'Cook Islands', 'CK', '682', ''),
			(53, '1b39c10a-fdb0-df11-ab2e-005056990011', 'Costa Rica', 'CR', '506', ''),
			(54, '1d39c10a-fdb0-df11-ab2e-005056990011', 'Croatia', 'HR', '385', ''),
			(55, '1e39c10a-fdb0-df11-ab2e-005056990011', 'Cuba', 'CU', '53', ''),
			(56, '1f39c10a-fdb0-df11-ab2e-005056990011', 'Cyprus', 'CY', '537', ''),
			(57, '2039c10a-fdb0-df11-ab2e-005056990011', 'Czech Republic', 'CZ', '420', ''),
			(58, '2139c10a-fdb0-df11-ab2e-005056990011', 'Denmark', 'DK', '45', ''),
			(59, '2239c10a-fdb0-df11-ab2e-005056990011', 'Djibouti', 'DJ', '253', ''),
			(60, '2339c10a-fdb0-df11-ab2e-005056990011', 'Dominica', 'DM', '1767', ''),
			(61, '2439c10a-fdb0-df11-ab2e-005056990011', 'Dominican Republic', 'DO', '', ''),
			(62, '2539c10a-fdb0-df11-ab2e-005056990011', 'Ecuador', 'EC', '593', ''),
			(63, '2639c10a-fdb0-df11-ab2e-005056990011', 'Egypt', 'EG', '20', ''),
			(64, '2739c10a-fdb0-df11-ab2e-005056990011', 'El Salvador', 'SV', '503', ''),
			(65, '2839c10a-fdb0-df11-ab2e-005056990011', 'Equatorial Guinea', 'GQ', '240', ''),
			(66, '2939c10a-fdb0-df11-ab2e-005056990011', 'Eritrea', 'ER', '291', ''),
			(67, '2a39c10a-fdb0-df11-ab2e-005056990011', 'Estonia', 'EE', '372', ''),
			(68, '2b39c10a-fdb0-df11-ab2e-005056990011', 'Ethiopia', 'ET', '251', ''),
			(69, '2c39c10a-fdb0-df11-ab2e-005056990011', 'Falkland Islands (Malvinas)', 'FK', '500', ''),
			(70, '2d39c10a-fdb0-df11-ab2e-005056990011', 'Faroe Islands', 'FO', '298', ''),
			(71, '2e39c10a-fdb0-df11-ab2e-005056990011', 'Fiji', 'FJ', '679', ''),
			(72, '2f39c10a-fdb0-df11-ab2e-005056990011', 'Finland', 'FI', '358', ''),
			(73, '3039c10a-fdb0-df11-ab2e-005056990011', 'France', 'FR', '33', ''),
			(74, '3139c10a-fdb0-df11-ab2e-005056990011', 'French Guiana', 'GF', '594', ''),
			(75, '3239c10a-fdb0-df11-ab2e-005056990011', 'French Polynesia', 'PF', '689', ''),
			(76, '3339c10a-fdb0-df11-ab2e-005056990011', 'French Southern Territories', '', '0', ''),
			(77, '3439c10a-fdb0-df11-ab2e-005056990011', 'Gabon', 'GA', '241', ''),
			(78, '3539c10a-fdb0-df11-ab2e-005056990011', 'Gambia', 'GM', '220', ''),
			(79, '3639c10a-fdb0-df11-ab2e-005056990011', 'Georgia', 'GE', '995', ''),
			(80, '3739c10a-fdb0-df11-ab2e-005056990011', 'Germany', 'DE', '49', ''),
			(81, '3839c10a-fdb0-df11-ab2e-005056990011', 'Ghana', 'GH', '233', ''),
			(82, '3939c10a-fdb0-df11-ab2e-005056990011', 'Gibraltar', 'GI', '350', ''),
			(83, '3a39c10a-fdb0-df11-ab2e-005056990011', 'Greece', 'GR', '30', ''),
			(84, '3b39c10a-fdb0-df11-ab2e-005056990011', 'Greenland', 'GL', '299', ''),
			(85, '3c39c10a-fdb0-df11-ab2e-005056990011', 'Grenada', 'GD', '1', ''),
			(86, '3d39c10a-fdb0-df11-ab2e-005056990011', 'Guadeloupe', 'GP', '590', ''),
			(87, '3e39c10a-fdb0-df11-ab2e-005056990011', 'Guam', 'GU', '1', ''),
			(88, '3f39c10a-fdb0-df11-ab2e-005056990011', 'Guatemala', 'GT', '502', ''),
			(89, '4039c10a-fdb0-df11-ab2e-005056990011', 'Guernsey', 'GG', '44', ''),
			(90, '4139c10a-fdb0-df11-ab2e-005056990011', 'Guinea', 'GN', '224', ''),
			(91, '4239c10a-fdb0-df11-ab2e-005056990011', 'Guinea-Bissau', 'GW', '245', ''),
			(92, '4339c10a-fdb0-df11-ab2e-005056990011', 'Guyana', 'GY', '595', ''),
			(93, '4439c10a-fdb0-df11-ab2e-005056990011', 'Haiti', 'HT', '509', ''),
			(94, '4539c10a-fdb0-df11-ab2e-005056990011', 'Heard Island And Mcdonald Islands', '', '0', ''),
			(95, '4639c10a-fdb0-df11-ab2e-005056990011', 'Holy See (Vatican City State)', 'VA', '379', ''),
			(96, '4739c10a-fdb0-df11-ab2e-005056990011', 'Honduras', 'HN', '504', ''),
			(97, '4839c10a-fdb0-df11-ab2e-005056990011', 'Hong Kong', 'HK', '852', ''),
			(98, '4939c10a-fdb0-df11-ab2e-005056990011', 'Hungary', 'HU', '36', ''),
			(99, '4a39c10a-fdb0-df11-ab2e-005056990011', 'Iceland', 'IS', '354', ''),
			(100, '4b39c10a-fdb0-df11-ab2e-005056990011', 'India', 'IN', '91', ''),
			(101, '4c39c10a-fdb0-df11-ab2e-005056990011', 'Indonesia', 'ID', '62', ''),
			(102, '4d39c10a-fdb0-df11-ab2e-005056990011', 'Iran  Islamic Republic Of', '', '0', ''),
			(103, '4e39c10a-fdb0-df11-ab2e-005056990011', 'Iraq', 'IQ', '964', ''),
			(104, '4f39c10a-fdb0-df11-ab2e-005056990011', 'Ireland', 'IE', '353', ''),
			(105, '5039c10a-fdb0-df11-ab2e-005056990011', 'Isle Of Man', 'IM', '44', ''),
			(106, '5139c10a-fdb0-df11-ab2e-005056990011', 'Israel', 'IL', '972', ''),
			(107, '5239c10a-fdb0-df11-ab2e-005056990011', 'Italy', 'IT', '39', ''),
			(108, '5339c10a-fdb0-df11-ab2e-005056990011', 'Jamaica', 'JM', '1', ''),
			(109, '5439c10a-fdb0-df11-ab2e-005056990011', 'Japan', 'JP', '81', ''),
			(110, '5539c10a-fdb0-df11-ab2e-005056990011', 'Jersey', 'JE', '44', ''),
			(111, '5639c10a-fdb0-df11-ab2e-005056990011', 'Jordan', 'JO', '962', ''),
			(112, '5739c10a-fdb0-df11-ab2e-005056990011', 'Kazakhstan', 'KZ', '7', ''),
			(113, '5839c10a-fdb0-df11-ab2e-005056990011', 'Kenya', 'KE', '254', ''),
			(114, '5939c10a-fdb0-df11-ab2e-005056990011', 'Kiribati', 'KI', '686', ''),
			(115, '5b39c10a-fdb0-df11-ab2e-005056990011', 'Korea  Republic Of', '', '0', ''),
			(116, '5c39c10a-fdb0-df11-ab2e-005056990011', 'Kuwait', 'KW', '965', ''),
			(117, '5d39c10a-fdb0-df11-ab2e-005056990011', 'Kyrgyzstan', 'KG', '996', ''),
			(118, '5f39c10a-fdb0-df11-ab2e-005056990011', 'Latvia', 'LV', '371', ''),
			(119, '6039c10a-fdb0-df11-ab2e-005056990011', 'Lebanon', 'LB', '961', ''),
			(120, '6139c10a-fdb0-df11-ab2e-005056990011', 'Lesotho', 'LS', '266', ''),
			(121, '6239c10a-fdb0-df11-ab2e-005056990011', 'Liberia', 'LR', '231', ''),
			(122, '6339c10a-fdb0-df11-ab2e-005056990011', 'Libyan Arab Jamahiriya', 'LY', '218', ''),
			(123, '6439c10a-fdb0-df11-ab2e-005056990011', 'Liechtenstein', 'LI', '423', ''),
			(124, '6539c10a-fdb0-df11-ab2e-005056990011', 'Lithuania', 'LT', '370', ''),
			(125, '6639c10a-fdb0-df11-ab2e-005056990011', 'Luxembourg', 'LU', '352', ''),
			(126, '6739c10a-fdb0-df11-ab2e-005056990011', 'Macao', 'MO', '853', ''),
			(127, '6839c10a-fdb0-df11-ab2e-005056990011', 'Macedonia  The Former Yugoslav Republic Of', '', '0', ''),
			(128, '6939c10a-fdb0-df11-ab2e-005056990011', 'Madagascar', 'MG', '261', ''),
			(129, '6a39c10a-fdb0-df11-ab2e-005056990011', 'Malawi', 'MW', '265', ''),
			(130, '6b39c10a-fdb0-df11-ab2e-005056990011', 'Malaysia', 'MY', '60', ''),
			(131, '6c39c10a-fdb0-df11-ab2e-005056990011', 'Maldives', 'MV', '960', ''),
			(132, '6d39c10a-fdb0-df11-ab2e-005056990011', 'Mali', 'ML', '223', ''),
			(133, '6e39c10a-fdb0-df11-ab2e-005056990011', 'Malta', 'MT', '356', ''),
			(134, '6f39c10a-fdb0-df11-ab2e-005056990011', 'Marshall Islands', 'MH', '692', ''),
			(135, '7039c10a-fdb0-df11-ab2e-005056990011', 'Martinique', 'MQ', '596', ''),
			(136, '7139c10a-fdb0-df11-ab2e-005056990011', 'Mauritania', 'MR', '222', ''),
			(137, '7239c10a-fdb0-df11-ab2e-005056990011', 'Mauritius', 'MU', '230', ''),
			(138, '7339c10a-fdb0-df11-ab2e-005056990011', 'Mayotte', 'YT', '262', ''),
			(139, '7439c10a-fdb0-df11-ab2e-005056990011', 'Mexico', 'MX', '52', ''),
			(140, '7539c10a-fdb0-df11-ab2e-005056990011', 'Micronesia  Federated States Of', '', '0', ''),
			(141, '7639c10a-fdb0-df11-ab2e-005056990011', 'Moldova  Republic Of', '', '0', ''),
			(142, '7739c10a-fdb0-df11-ab2e-005056990011', 'Monaco', 'MC', '377', ''),
			(143, '7839c10a-fdb0-df11-ab2e-005056990011', 'Mongolia', 'MN', '976', ''),
			(144, '7939c10a-fdb0-df11-ab2e-005056990011', 'Montserrat', 'MS', '1664', ''),
			(145, '7a39c10a-fdb0-df11-ab2e-005056990011', 'Morocco', 'MA', '212', ''),
			(146, '7b39c10a-fdb0-df11-ab2e-005056990011', 'Mozambique', 'MZ', '258', ''),
			(147, '7c39c10a-fdb0-df11-ab2e-005056990011', 'Myanmar', 'MM', '95', ''),
			(148, '7d39c10a-fdb0-df11-ab2e-005056990011', 'Namibia', 'NA', '264', ''),
			(149, '7e39c10a-fdb0-df11-ab2e-005056990011', 'Nauru', 'NR', '674', ''),
			(150, '7f39c10a-fdb0-df11-ab2e-005056990011', 'Nepal', 'NP', '977', ''),
			(151, '8039c10a-fdb0-df11-ab2e-005056990011', 'Netherlands', 'NL', '31', ''),
			(152, '8139c10a-fdb0-df11-ab2e-005056990011', 'Netherlands Antilles', 'AN', '599', ''),
			(153, '8239c10a-fdb0-df11-ab2e-005056990011', 'New Caledonia', 'NC', '687', ''),
			(154, '8339c10a-fdb0-df11-ab2e-005056990011', 'New Zealand', 'NZ', '64', ''),
			(155, '8439c10a-fdb0-df11-ab2e-005056990011', 'Nicaragua', 'NI', '505', ''),
			(156, '8539c10a-fdb0-df11-ab2e-005056990011', 'Niger', 'NE', '227', ''),
			(157, '8639c10a-fdb0-df11-ab2e-005056990011', 'Nigeria', 'NG', '234', ''),
			(158, '8739c10a-fdb0-df11-ab2e-005056990011', 'Niue', 'NU', '683', ''),
			(159, '8839c10a-fdb0-df11-ab2e-005056990011', 'Norfolk Island', 'NF', '672', ''),
			(160, '8939c10a-fdb0-df11-ab2e-005056990011', 'Northern Mariana Islands', 'MP', '1', ''),
			(161, '8a39c10a-fdb0-df11-ab2e-005056990011', 'Norway', 'NO', '47', ''),
			(162, '8b39c10a-fdb0-df11-ab2e-005056990011', 'Oman', 'OM', '968', ''),
			(163, '8c39c10a-fdb0-df11-ab2e-005056990011', 'Pakistan', 'PK', '92', ''),
			(164, '8d39c10a-fdb0-df11-ab2e-005056990011', 'Palau', 'PW', '680', ''),
			(165, '8e39c10a-fdb0-df11-ab2e-005056990011', 'Palestinian Territory  Occupied', '', '0', ''),
			(166, '8f39c10a-fdb0-df11-ab2e-005056990011', 'Panama', 'PA', '507', ''),
			(167, '9039c10a-fdb0-df11-ab2e-005056990011', 'Papua New Guinea', 'PG', '675', ''),
			(168, '9139c10a-fdb0-df11-ab2e-005056990011', 'Paraguay', 'PY', '595', ''),
			(169, '9239c10a-fdb0-df11-ab2e-005056990011', 'Peru', 'PE', '51', ''),
			(170, '9339c10a-fdb0-df11-ab2e-005056990011', 'Philippines', 'PH', '63', ''),
			(171, '9439c10a-fdb0-df11-ab2e-005056990011', 'Pitcairn', 'PN', '872', ''),
			(172, '9539c10a-fdb0-df11-ab2e-005056990011', 'Poland', 'PL', '48', ''),
			(173, '9639c10a-fdb0-df11-ab2e-005056990011', 'Portugal', 'PT', '351', ''),
			(174, '9739c10a-fdb0-df11-ab2e-005056990011', 'Puerto Rico', 'PR', '1', ''),
			(175, '9839c10a-fdb0-df11-ab2e-005056990011', 'Qatar', 'QA', '974', ''),
			(176, '9939c10a-fdb0-df11-ab2e-005056990011', 'Reunion', 'RE', '262', ''),
			(177, '9a39c10a-fdb0-df11-ab2e-005056990011', 'Romania', 'RO', '40', ''),
			(178, '9b39c10a-fdb0-df11-ab2e-005056990011', 'Russian Federation', '', '0', ''),
			(179, '9c39c10a-fdb0-df11-ab2e-005056990011', 'Rwanda', 'RW', '250', ''),
			(180, '9d39c10a-fdb0-df11-ab2e-005056990011', 'Saint Helena', '', '0', ''),
			(181, '9e39c10a-fdb0-df11-ab2e-005056990011', 'Saint Kitts And Nevis', 'KN', '1', ''),
			(182, '9f39c10a-fdb0-df11-ab2e-005056990011', 'Saint Lucia', 'LC', '1', ''),
			(183, 'a039c10a-fdb0-df11-ab2e-005056990011', 'Saint Pierre And Miquelon', 'PM', '508', ''),
			(184, 'a139c10a-fdb0-df11-ab2e-005056990011', 'Saint Vincent And The Grenadines', 'VC', '1', ''),
			(185, 'a239c10a-fdb0-df11-ab2e-005056990011', 'Samoa', 'WS', '685', ''),
			(186, 'a339c10a-fdb0-df11-ab2e-005056990011', 'San Marino', 'SM', '378', ''),
			(187, 'a439c10a-fdb0-df11-ab2e-005056990011', 'Sao Tome And Principe', 'ST', '239', ''),
			(188, 'a539c10a-fdb0-df11-ab2e-005056990011', 'Saudi Arabia', 'SA', '966', ''),
			(189, 'a639c10a-fdb0-df11-ab2e-005056990011', 'Senegal', 'SN', '221', ''),
			(190, 'a739c10a-fdb0-df11-ab2e-005056990011', 'Serbia And Montenegro', '', '0', ''),
			(191, 'a839c10a-fdb0-df11-ab2e-005056990011', 'Seychelles', 'SC', '248', ''),
			(192, 'a939c10a-fdb0-df11-ab2e-005056990011', 'Sierra Leone', 'SL', '232', ''),
			(193, 'aa39c10a-fdb0-df11-ab2e-005056990011', 'Singapore', 'SG', '65', ''),
			(194, 'ab39c10a-fdb0-df11-ab2e-005056990011', 'Slovakia', 'SK', '421', ''),
			(195, 'ac39c10a-fdb0-df11-ab2e-005056990011', 'Slovenia', 'SI', '386', ''),
			(196, 'ad39c10a-fdb0-df11-ab2e-005056990011', 'Solomon Islands', 'SB', '677', ''),
			(197, 'ae39c10a-fdb0-df11-ab2e-005056990011', 'Somalia', 'SO', '252', ''),
			(198, 'af39c10a-fdb0-df11-ab2e-005056990011', 'South Africa', 'ZA', '27', ''),
			(199, 'b039c10a-fdb0-df11-ab2e-005056990011', 'South Georgia And The South Sandwich Islands', 'GS', '500', ''),
			(200, 'b139c10a-fdb0-df11-ab2e-005056990011', 'Spain', 'ES', '34', ''),
			(201, 'b239c10a-fdb0-df11-ab2e-005056990011', 'Sri Lanka', 'LK', '94', ''),
			(202, 'b339c10a-fdb0-df11-ab2e-005056990011', 'Sudan', 'SD', '249', ''),
			(203, 'b439c10a-fdb0-df11-ab2e-005056990011', 'Suriname', 'SR', '597', ''),
			(204, 'b539c10a-fdb0-df11-ab2e-005056990011', 'Svalbard And Jan Mayen', 'SJ', '47', ''),
			(205, 'b639c10a-fdb0-df11-ab2e-005056990011', 'Swaziland', 'SZ', '268', ''),
			(206, 'b739c10a-fdb0-df11-ab2e-005056990011', 'Sweden', 'SE', '46', ''),
			(207, 'b839c10a-fdb0-df11-ab2e-005056990011', 'Switzerland', 'CH', '41', ''),
			(208, 'b939c10a-fdb0-df11-ab2e-005056990011', 'Syrian Arab Republic', 'SY', '963', ''),
			(209, 'ba39c10a-fdb0-df11-ab2e-005056990011', 'Taiwan  Province Of China', '', '0', ''),
			(210, 'bb39c10a-fdb0-df11-ab2e-005056990011', 'Tajikistan', 'TJ', '992', ''),
			(211, 'bc39c10a-fdb0-df11-ab2e-005056990011', 'Tanzania  United Republic Of', '', '0', ''),
			(212, 'bd39c10a-fdb0-df11-ab2e-005056990011', 'Thailand', 'TH', '66', ''),
			(213, 'be39c10a-fdb0-df11-ab2e-005056990011', 'Timor-Leste', 'TL', '670', ''),
			(214, 'bf39c10a-fdb0-df11-ab2e-005056990011', 'Togo', 'TG', '228', ''),
			(215, 'c039c10a-fdb0-df11-ab2e-005056990011', 'Tokelau', 'TK', '690', ''),
			(216, 'c139c10a-fdb0-df11-ab2e-005056990011', 'Tonga', 'TO', '676', ''),
			(217, 'c239c10a-fdb0-df11-ab2e-005056990011', 'Trinidad And Tobago', 'TT', '1', ''),
			(218, 'c339c10a-fdb0-df11-ab2e-005056990011', 'Tunisia', 'TN', '216', ''),
			(219, 'c439c10a-fdb0-df11-ab2e-005056990011', 'Turkey', 'TR', '90', ''),
			(220, 'c539c10a-fdb0-df11-ab2e-005056990011', 'Turkmenistan', 'TM', '993', ''),
			(221, 'c639c10a-fdb0-df11-ab2e-005056990011', 'Turks And Caicos Islands', 'TC', '1', ''),
			(222, 'c739c10a-fdb0-df11-ab2e-005056990011', 'Tuvalu', 'TV', '688', ''),
			(223, 'c839c10a-fdb0-df11-ab2e-005056990011', 'Uganda', 'UG', '256', ''),
			(224, 'c939c10a-fdb0-df11-ab2e-005056990011', 'Ukraine', 'UA', '380', ''),
			(225, 'ca39c10a-fdb0-df11-ab2e-005056990011', 'United Arab Emirates', 'AE', '971', ''),
			(226, 'cb39c10a-fdb0-df11-ab2e-005056990011', 'United Kingdom', 'GB', '44', ''),
			(227, 'cc39c10a-fdb0-df11-ab2e-005056990011', 'United States', 'US', '1', ''),
			(228, 'cd39c10a-fdb0-df11-ab2e-005056990011', 'United States Minor Outlying Islands', '', '0', ''),
			(229, 'ce39c10a-fdb0-df11-ab2e-005056990011', 'Uruguay', 'UY', '598', ''),
			(230, 'cf39c10a-fdb0-df11-ab2e-005056990011', 'Uzbekistan', 'UZ', '998', ''),
			(231, 'd039c10a-fdb0-df11-ab2e-005056990011', 'Vanuatu', 'VU', '678', ''),
			(232, 'd139c10a-fdb0-df11-ab2e-005056990011', 'Venezuela', '', '0', ''),
			(233, 'd239c10a-fdb0-df11-ab2e-005056990011', 'Viet Nam', 'VN', '84', ''),
			(234, 'd339c10a-fdb0-df11-ab2e-005056990011', 'Virgin Islands  British', '', '0', ''),
			(235, 'd439c10a-fdb0-df11-ab2e-005056990011', 'Virgin Islands  U.S.', '', '0', ''),
			(236, 'd539c10a-fdb0-df11-ab2e-005056990011', 'Wallis And Futuna', 'WF', '681', ''),
			(237, 'd639c10a-fdb0-df11-ab2e-005056990011', 'Western Sahara', '', '0', ''),
			(238, 'd739c10a-fdb0-df11-ab2e-005056990011', 'Yemen', 'YE', '967', ''),
			(239, 'd839c10a-fdb0-df11-ab2e-005056990011', 'Zambia', 'ZM', '260', ''),
			(240, 'd939c10a-fdb0-df11-ab2e-005056990011', 'Zimbabwe', 'ZW', '263', '');

			ALTER TABLE $table_name
			  ADD PRIMARY KEY (`id`);
			ALTER TABLE $table_name
			  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;
			COMMIT;
				";
	return $sql;
}
?>