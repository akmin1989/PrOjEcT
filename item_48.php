<?php
/****************************************
*
* item_48 購物車結帳 - 完成購物
*
* $_SESSION[$sys_arrWebsite['scar_no_cookie_name']] 		送結帳的購物車編號
* 
****************************************/
include_once( ROOT_PATH.'include/inc_shopping_checkout.php' );
include('sql.php');
$ShoppingCart = new ShoppingCart($web_lang); //購物車
$OrderMf = new OrderMf($web_lang); //訂單主檔
$OrderDelivery = new OrderDelivery($web_lang); //出貨單主檔
$OrderDf = new OrderDf($web_lang); //訂單明細檔
$Bonus = new BonusExt($web_lang); //會員紅利積點
$SerialNumber = new SerialNumber(); //序號碼記錄檔
$Product = new Product($web_lang); //商品主檔
$WebsiteMarketing = new WebsiteMarketing($web_lang);
//購物車編號
$_arrSCO['scar_no'] = $_SESSION[$sys_arrWebsite['scar_no_cookie_name']];
//依搜尋條件 取得購物車內容
$_arrSCO['m_arrShoppingCart'] = $ShoppingCart->s_shopping_cartBySearch($_arrSCO['scar_no'], "", "", "", "", 1);
/**
*		Alex Time
*/
$Now = date('Y/m/d H:i:s');
$Start = date('2017/06/05 00:00:00');
$End = date('2017/06/06 00:00:00');
$TimeDate = array('Now'=>$Now,'Start'=>$Start,'End'=>$End);
$root_smarty->assign('ActTime', $TimeDate);
if( !$_arrSCO['m_arrShoppingCart'] || $_POST['sco_order_uid'] ){
/*** 已完成訂單 ************************************************************/
	//依訂單編號 取得訂單所有資料
	$_arrSCO['m_arrOrderMf'] = $OrderMf->s_orderMfAllByOuid($_POST['sco_order_uid']);
	$_arrSCO['m_arrOrderMf']["order_df_delivery"] = $OrderDf->s_order_dfByOuid($_POST['sco_order_uid']);
	
	/*全館滿額活動*/
	$MarketingProd = array();
	foreach($_arrSCO ['m_arrOrderMf']["order_df_delivery"] as $key => $val){
		if($val['wmkt_uid'] == 0) continue;
		$sql = "SELECT *
				FROM website_marketing 
				WHERE wmkt_type = '31'
				AND wmkt_uid = '{$val['wmkt_uid']}'";
		$rs = $WebsiteMarketing->dbExecute($sql);
		$result = $rs->GetRows();
		if($result){
			$MarketingProd[$result[0]['wmkt_uid']]['title'] = $result[0]['wmkt_title'];				
			$MarketingProd[$result[0]['wmkt_uid']]['prod'][] = array(
				'prod_name'	=>	$val['prod_name'],
				'count'		=>	$val['order_df_qty'],
			);
			$_arrSCO ['m_arrOrderMf']["order_df_delivery"][$key]['wmkt_type'] = '31';
		}		
	}
	$root_smarty->assign('MarketingProd', $MarketingProd); //取得購物車內容
	/*全館滿額活動 END*/
	
	if($_arrSCO['m_arrOrderMf']) {
		$root_smarty->assign('sco_arrOrderMf', $_arrSCO['m_arrOrderMf']); //訂單資料
		$_arrParams ['Bonus_Used'] = $_POST['order_use_bonus'];	
		if($_arrParams ['Bonus_Used'])
			$root_smarty->assign('Bonus_Used', $_arrParams ['Bonus_Used']); //紅利抵扣資料
	//echo '<pre>';print_r($_arrSCO ['m_arrOrderMf']);exit;
		// 推薦商品TOP5
		// $m_arrRecommendProduct = $Product->s_recommendProductsTop5();
		
		// $_arrParam = array();
		// foreach($m_arrRecommendProduct as $_key => $_val){
			// if(strlen($_val['prod_no']) > 0){
				// 依商品貨號取得商品所有資料
				// $_arrParam[] = $Product->s_productAllByPno($_val['prod_no']);
			// }
		// }
		
		/*全館滿額活動*/
		$MarketingProd = array();
		foreach($_arrSCO ['m_arrOrderMf']["order_df_delivery"] as $key => $val){
			if($val['wmkt_uid'] == 0) continue;
			$sql = "SELECT *
					FROM website_marketing 
					WHERE wmkt_type = '31'
					AND wmkt_uid = '{$val['wmkt_uid']}'";
			$rs = $WebsiteMarketing->dbExecute($sql);
			$result = $rs->GetRows();
			if($result){
				$MarketingProd[$result[0]['wmkt_uid']]['title'] = $result[0]['wmkt_title'];				
				$MarketingProd[$result[0]['wmkt_uid']]['prod'][] = array(
					'prod_name'	=>	$val['prod_name'],
					'count'		=>	$val['order_df_qty'],
				);
				$_arrSCO ['m_arrOrderMf']["order_df_delivery"][$key]['wmkt_type'] = '31';
			}		
		}
		$root_smarty->assign('MarketingProd', $MarketingProd); //取得購物車內容
		/*全館滿額活動 END*/
		
		/*20140506*/
		$order_telcellphone = $_arrSCO['m_arrOrderMf']['Delivery'][0]['order_telcellphone'];
		$_arrSCO['m_arrOrderMf']['Delivery'][0]['order_telcellphone'] = substr_replace($order_telcellphone, '***', 4, 3);
		$order_telcellphone = $_arrSCO['m_arrOrderMf']['order_telcellphone'];
		$_arrSCO['m_arrOrderMf']['order_telcellphone'] = substr_replace($order_telcellphone, '***', 4, 3);
		$_arrSCO['m_arrOrderMf']['last_date'] = date('Y/m/d', strtotime('+3day', strtotime($_arrSCO['m_arrOrderMf']['order_date'])));
		$_arrSCO['m_arrOrderMf']['order_date1'] = date('Y年m月d日H時i分', strtotime($_arrSCO['m_arrOrderMf']['order_date']));
		if($_arrSCO['m_arrOrderMf']['Delivery'][0]['delivery_type'] == 101){
			// 到店取貨
			$mail_name = 'order.confirm2.xml';
			if($sys_arrWebsite['web_uid'] != 1603){		/*	20161229生鮮館START	*/
				$specify_date = substr($_arrSCO['m_arrOrderMf']['order_specify_arrival_date'], 0, 1);
				$specify_time = substr($_arrSCO['m_arrOrderMf']['order_specify_arrival_date'], 1, 2);
				switch($specify_date){
					case '1':
						$specify = date('Y/m/d', strtotime('+0day', strtotime($_arrSCO['m_arrOrderMf']['order_date'])));
						break;
					case '2':
						$specify = date('Y/m/d', strtotime('+1day', strtotime($_arrSCO['m_arrOrderMf']['order_date'])));
						break;
					case '3':
						$specify = date('Y/m/d', strtotime('+2day', strtotime($_arrSCO['m_arrOrderMf']['order_date'])));
						break;
				}
				$specify .= ' ('.$specify_time.'時~'.($specify_time+1).'時)';
				$_arrSCO['m_arrOrderMf']['order_specify_arrival_date'] = $specify;
			}
			else{
				$_arrSCO['m_arrOrderMf']['order_specify_arrival_date'] = $sco_arrForm['pickup_store_selection'] . ',<br >日期:' . $sco_arrForm['YFDATE'] . ',<br >時間:' . $sco_arrForm['YFTIME'];
			}	/*	20161229生鮮館END	*/
		}else{
			// 宅配服務
			$mail_name = 'order.confirm.xml';
		}
		/*20140506 end*/
		
		/*** 發送訂單確認信函 ***/
		$Mailer2 = new Mailer2();
		$mailXML = ROOT_PATH.'language/'.$web_lang.'/'."mail/$mail_name";
		$_arrParams['Web'] = $sys_arrWebsite;
		$_arrParams['Sys'] = $m_arrSystem;
		$_arrParams['OM'] = $_arrSCO['m_arrOrderMf'];
		// $_arrParams['HP'] = $_arrParam;

		try {
			$_arrMail 				= $Mailer2->analyzeEmail($mailXML, $_arrParams);
			
			$_arrMail['from_name']	= $sys_arrWebsite['web_name'];
			$_arrMail['from_mail']	= $sys_arrWebsite['web_service_mail'];
			$_arrMail['to_mail']	= $_arrSCO['m_arrOrderMf']['order_email'];
			$_arrMail['cc_mail']	= $_arrSCO['m_arrOrderMf']['Member']['mem_email_2'];
			$_arrMail['bcc_mail']	= $sys_arrWebsite['web_bcc_mail'];
			//arr_dump($_arrMail);
			$result = $Mailer2->send($_arrMail);
		} catch(Exception $e) {
			$SysLogs 				= new SysLogs(); // Logs 紀錄檔
			$log_f 					= "MailOrderConfirm";
			$log_msg 				= array();
			$log_msg ['Exception'] 	= $e->getMessage();
			$log_msg ['_arrParams'] = $_arrParams;
			$SysLogs->saveLogs ($log_f, $log_msg, 0);
		}
		/*** 發送訂單確認信函 END ***/
		
		/***訂單確認通知簡訊********
		
		$ch		= curl_init();
		$url		= "{$sys_arrWebsite['web_domainname']}/{$sys_arrWebsite['web_byeurl']}/background/bg.mobile_letter.php";
		$post	= array(
			'orderId'					=>	$_arrSCO['m_arrOrderMf']['order_uid'],											//訂單編號
			'DstAddr'				=>	$_arrSCO['m_arrOrderMf']['Delivery'][0]['order_telcellphone'],	//買家手機號碼
			'payment_type'		=>	$_arrSCO['m_arrOrderMf']['payment_type']									//付款方式
		);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); 
		curl_exec($ch); 
		curl_close($ch);
		
		
		/***訂單確認通知簡訊END***/
		
		/*** Google Analytics ***/
		if( $_SESSION['google_order_uid'] != $_arrSCO['m_arrOrderMf']['order_uid'] ){
			$GoogleAnalytics = new GoogleAnalytics( $sys_arrWebExtend['web_extension_1']['_uacct'] );
			$googleAnalyticsAddOrderJS = $GoogleAnalytics->getAddOrderJS( $_arrSCO['m_arrOrderMf']['order_uid'] );
            //$googlegetgatest = $GoogleAnalytics->gettest($_arrSCO['m_arrOrderMf']['order_uid']);
			setcookie("googleAnalyticsAddOrderJS",$googleAnalyticsAddOrderJS,time()+600,"/",$m_arrSystem['cookie_domain']);
			$root_smarty->assign('googleAnalyticsAddOrderJS', $googleAnalyticsAddOrderJS);
			$_SESSION['google_order_uid'] = $_arrSCO['m_arrOrderMf']['order_uid'];

		}
		/*** Google Analytics END ***/

		/* GTM Cookie START*/
		//訂單編號
		 setcookie("order_uid", $_arrSCO['m_arrOrderMf']['order_uid'], time()+600, "/", $m_arrSystem['cookie_domain'] );
        //訂單店別
		// setcookie("web_uid", $_arrSCO['m_arrOrderMf']['web_uid'], time()+600, "/", $m_arrSystem['cookie_domain'] );
        //訂單金額
		 setcookie("pay_money_ssl", $_arrSCO['m_arrOrderMf']['pay_money_ssl'], time()+600, "/", $m_arrSystem['cookie_domain'] );
		 //存入商品金額
		 //setcookie("order_df_price", $_arrSCO['m_arrOrderMf']["order_df_delivery"]['0']['order_df_price'], time()+600, "/", $m_arrSystem['cookie_domain'] );
         //存入商品貨號
		 //setcookie("prod_no_old", $_arrSCO['m_arrOrderMf']["order_df_delivery"]['0']['prod_no_old'], time()+600, "/", $m_arrSystem['cookie_domain'] );
         
         //將陣列寫入cookie測試
         /*$count = count($_arrSCO['m_arrOrderMf']["order_df_delivery"]);
         for($i = 0; $i<$count;$i++)
         {
	          $id[$i] = $_arrSCO['m_arrOrderMf']["order_df_delivery"][$i]['prod_no_old'];
	          $price[$i] = $_arrSCO['m_arrOrderMf']["order_df_delivery"][$i]['order_df_price'];
	          $prod_name[$i] = $_arrSCO['m_arrOrderMf']["order_df_delivery"][$i]['prod_name'];
	          $quantity[$i] = $_arrSCO['m_arrOrderMf']["order_df_delivery"][$i]['order_df_qty'];
         }
		 setcookie("prod_no_old",json_encode($id),time()+600,"/",$m_arrSystem['cookie_domain']);	
         setcookie("price",json_encode($price),time()+600,"/",$m_arrSystem['cookie_domain']);
         setcookie("name",json_encode($prod_name),time()+600,"/",$m_arrSystem['cookie_domain']);
         setcookie("quantity",json_encode($quantity),time()+600,"/",$m_arrSystem['cookie_domain']);
         /*foreach($prod_name as $name => $value){
		 		setcookie($name,$value,time()+600,"/",$m_arrSystem['cookie_domain']);		 	    
         }*/
    
         

		/* GTM Cookie END */
		
		/*** 結帳付款資料 SESSION ***/
		if( $_SESSION[$shopping_checkout_pay_session_name] ){
			$sco_arrPayForm = json_decode($_SESSION[$shopping_checkout_pay_session_name], true);
			$root_smarty->assign('sco_arrPayForm', $sco_arrPayForm); //結帳付款資料
			//arr_dump($sco_arrPayForm);
			if( $sco_arrPayForm['session_unregister'] )
				unset ($_SESSION[$shopping_checkout_pay_session_name]); //清除結帳資料
		}
		/*** 結帳付款資料 SESSION END ***/
	}
/*** 已完成訂單 END ************************************************************/
// } else if ($sco_arrForm['payment_type'] && $sco_arrForm['delivery_type'])
} else { 
	//依購物車編號 取得總價 + 運費 - 紅利
	$sco_arrForm['scarPriceTotal'] = $ShoppingCart->getScarPriceTotalByScno($_arrSCO['scar_no']) + $sco_arrForm['order_carriage'] - $sco_arrForm['order_use_bonus'];
	
	/*** 付款方式處理 ***/
	switch( $sco_arrForm['payment_type'] ){
		case "100": //100 : 線上刷卡
		case "101": //101 : 信用卡紅利折抵
		case "102": //102 : 美國運通信用卡
		case "203": //203 : 刷卡分期(3)
		case "206": //206 : 刷卡分期(6)
		case "209": //209 : 刷卡分期(9)
		case "212": //212 : 刷卡分期(12)
		case "218": //218 : 刷卡分期(18)
		case "224": //224 : 刷卡分期(24)
		case "236": //236 : 刷卡分期(36)
			if( $sco_arrForm['payment_type'] == "100" ){
				$sco_arrForm['ssl_payment_form'] = $m_arrSystem['jsonSSLPaymentForm']['ssl'];
			}elseif( $sco_arrForm['payment_type'] == "101" ){
				$sco_arrForm['ssl_payment_form'] = $m_arrSystem['jsonSSLPaymentForm']['bonus'];
			}elseif( $sco_arrForm['payment_type'] == "102" ){
				$sco_arrForm['ssl_payment_form'] = $m_arrSystem['jsonSSLPaymentForm']['ssl'];
			}else{
				$sco_arrForm['ssl_payment_form'] = $m_arrSystem['jsonSSLPaymentForm']['installment'];
			}
			
			if( $sco_arrForm['ssl_payment_form'] == "twNccc" || 
				$sco_arrForm['ssl_payment_form'] == "twNewebmPP" ||
				$sco_arrForm['ssl_payment_form'] == "twHiTRUST" ||
						$sco_arrForm['ssl_payment_form'] == "Taishin" ){
				//twNccc : 聯合刷卡, twNewebmPP : 藍新刷卡(mPP), twHiTRUST : 網際威信
				
				if( $_POST['execstate'] == 1 ){
					$sco_arrForm['credit_card_no'] 			= $_POST['credit_card_no']; 		//信用卡卡號
					$sco_arrForm['ssl_approval_code'] 		= $_POST['ssl_approval_code']; 		//交易授權碼
					$sco_arrForm['payment_business_no'] 	= $_POST['payment_business_no']; 	//付款企業代碼
					$sco_arrForm['payment_case_no'] 		= $_POST['payment_case_no']; 		//付款對應接單號
					$sco_arrForm['credit_pay_time'] 		= $_POST['credit_pay_time']; 		//分期期數
					$sco_arrForm['payment_pct'] 			= $_POST['payment_pct']; 			//手續費率 %
					
					if( $sco_arrForm['payment_pct'] > 0 ) 
						$sco_arrForm['ssl_fees_charge'] = m_floor( $sco_arrForm['scarPriceTotal'] * $sco_arrForm['payment_pct'] / 100, $m_arrSystem['money_decimal'] ); //分期付款手續費
				}
				else {
					$SysMessage->replyActionMessages('', $_POST['execmsg'], 'index.php?action=shopping_checkout_2');
				}
			}
			elseif( $sco_arrForm['ssl_payment_form'] == "twNeweb" || $sco_arrForm['ssl_payment_form'] == "twNewebInstallment"){
				//藍新刷卡或分期
				
				$sco_arrForm['credit_card_no'] = $sco_arrForm['credit_card_no_1'].$sco_arrForm['credit_card_no_2'].$sco_arrForm['credit_card_no_3'].$sco_arrForm['credit_card_no_4']; //信用卡卡號
				$sco_arrForm['credit_expire'] = $sco_arrForm['credit_expire_y'].$sco_arrForm['credit_expire_m']; //信用卡有效日期 yyyymm
				
				$SSLPayment = new SSLPaymentNeweb($sco_arrForm['payment_type']); //藍星線上刷卡
					
				if( $sco_arrForm['payment_pct'] > 0 ){
					//分期付款手續費
					$sco_arrForm['ssl_fees_charge'] = m_floor( $sco_arrForm['scarPriceTotal'] * $sco_arrForm['payment_pct'] / 100, $m_arrSystem['money_decimal'] );
					//應付金額
					$sco_arrForm['payAmount'] = $sco_arrForm['scarPriceTotal'] + $sco_arrForm['ssl_fees_charge'];
				}
				else{
					$sco_arrForm['payAmount'] = $sco_arrForm['scarPriceTotal'];
				}

				if (!$sco_arrForm['payment_case_no']) {
					$sco_arrForm['payment_case_no'] = $SerialNumber->get_serial_number('neweb_pay_orderno'); //藍星刷卡訂單編號
				}
				
				
				if (in_array($sco_arrForm['credit_type'], array("MASTER", "VISA")) && !isset($_SESSION['MPIReceive']) && $m_arrSystem['jsonNewebMPI'])
				{
					// MASTER 和 VISA 的信用卡要進行 3D 認證
				    $_sessionid = session_id();
					$_MPIvars = $m_arrSystem['jsonNewebMPI'];
				    $_XID = str_pad($_MPIvars['MerchantNumber'], 10, "0", STR_PAD_LEFT) . str_pad($sco_arrForm['payment_case_no'], 10, "0", STR_PAD_LEFT);
				    
					$_SESSION[$shopping_checkout_json_session_name] = json_encode($sco_arrForm);
				    ?>
					<html>
					<head></head>
					<body>
					<form name="MPIform" method="POST" action="<?php echo $_MPIvars['MPIURL']; ?>">
					     <input type="hidden" name="merchantID" value="<?php echo $_MPIvars['merchantID'] ?>">
					     <input type="hidden" name="terminalID" value="<?php echo $_MPIvars['terminalID'] ?>">
					     <input type="hidden" name="acquirerBIN" value="<?php echo $_MPIvars['acquirerBIN'][$sco_arrForm['credit_type']]; ?>">
					     <input type="hidden" name="cardNumber" value="<?php echo $sco_arrForm['credit_card_no'] ?>">
					     <input type="hidden" name="expYear" value="<?php echo substr($sco_arrForm['credit_expire'], 0, 4) ?>">
					     <input type="hidden" name="expMonth" value="<?php echo substr($sco_arrForm['credit_expire'], 4) ?>">
					     <input type="hidden" name="totalAmount" value="<?php echo $sco_arrForm['payAmount'] ?>">
					     <input type="hidden" name="XID" value="<?php echo $_XID ?>">
					     <input type="hidden" name="RetUrl" value="http://<?php echo $sys_arrWebsite['web_domainname']; ?>/website/background/bg.shopping_checkout_ok.php?sessionid=<?php echo $_sessionid; ?>">
					</form>
					<script language="javascript" type="text/javascript">
					//<![CDATA[
						document.MPIform.submit();
					//]]>
					</script>
					
					</body>
					</html>
					<?php
					exit();
				}
				else {
					// 不需ECI的或己經取得了ECI
					$_arrSSLPay['OrderNumber'] 		= $sco_arrForm['payment_case_no']; 		//訂單編號 (不可大於9 碼之數字,第一碼不得為零)
					$_arrSSLPay['OrgOrderNumber'] 	= $sco_arrForm['payment_case_no']."-".$sys_arrWebsite['web_uid']; 		//商家訂單編號(30 碼內之英數字)
					$_arrSSLPay['Amount'] 			= $sco_arrForm['payAmount']; 			//訂單金額 (含小數位二位 ex:1.00)
					$_arrSSLPay['CardType'] 		= $sco_arrForm['credit_type']; 			//信用卡卡別，可用參數值 (VISA、MAST、JCB、UCARD)
					$_arrSSLPay['CardNumber'] 		= $sco_arrForm['credit_card_no']; 		//信用卡卡號
					$_arrSSLPay['CardExpiry'] 		= $sco_arrForm['credit_expire']; 		//信用卡有效期限 (yyyymm)
					$_arrSSLPay['CVC2'] 			= $sco_arrForm['credit_cd2no']; 		//信用卡背面末三碼(3 碼數字)
					$_arrSSLPay['OrderURL'] 		= $sys_arrWebsite['website_url']; 		//訂單對應網址(商家自定)
					$_arrSSLPay['Period'] 			= $sco_arrForm['credit_pay_time']; 		//分期期數
					$_arrSSLPay['ID'] 				= $sco_arrForm['credit_idcard']; 		//持卡人身份證字號
					$_arrSSLPay['MPIReceive']		= json_decode($_SESSION['MPIReceive'], true); // ECI 回傳

					//$SSLPayment->debug = true;
					
					$_arrSSLReturn = $SSLPayment->acceptPayment(	$_arrSSLPay['OrderNumber'], $_arrSSLPay['OrgOrderNumber'], $_arrSSLPay['Amount'], 
																	$_arrSSLPay['CardType'], $_arrSSLPay['CardNumber'], $_arrSSLPay['CardExpiry'], 
																	$_arrSSLPay['CVC2'], $_arrSSLPay['OrderURL'], $_arrSSLPay['Period'],
																	$_arrSSLPay['ID'], $_arrSSLPay['MPIReceive']);
					
				}
				
				if( !$_arrSSLReturn['execState'] ) {
					unset ($_SESSION['MPIReceive']); //清除ECI回應

					header("Status: 302 Found");
					header("Location: index.php?action=shopping_checkout_3&err_alert=" . urlencode($_arrSSLReturn['execMsg']));
					exit();
				}
				else {
					$sco_arrForm['ssl_approval_code'] 	= $_arrSSLReturn['ApprovalCode']; 		//交易授權碼
					$sco_arrForm['payment_business_no'] = $_arrSSLReturn['MerchantNumber']; 	//付款企業代碼
					
					//$sco_arrForm['credit_card_no'] 		= $sco_arrForm['credit_card_no_1']."xxxx".$sco_arrForm['credit_card_no_3']."xxxx"; //信用卡卡號
					$sco_arrForm['credit_card_no'] 		= "xxxxxxxxxxxxxxxx";					//信用卡卡號
					$sco_arrForm['credit_cd2no'] 		= "xxx"; 								//信用卡背面末三碼(3 碼數字)
					$sco_arrForm['credit_expire'] 		= "xxxxxx"; 							//信用卡有效日期
					$sco_arrForm['credit_type'] 		= "xxxx"; 								//信用卡卡別
				}
				
				
			}
			else {
				exit("sslPaymentForm Error");
			}
			
			$sco_arrForm['payment_web_uid'] = 0; //金流網站編號
			break;
		case "400": // 貨到付款
			$sco_arrForm['delivery_is_cod'] = 1;
			$sco_arrForm['credit_pay_time'] = 1; //付款分期數
			$sco_arrForm['payment_pct'] = 0; //手續費率 %
			$sco_arrForm['ssl_fees_charge'] = 0; //分期付款手續費
			$sco_arrForm['payment_web_uid'] = 0; //金流網站編號
			break;
		case "402":
			$sco_arrForm['delivery_is_cod'] = 1;
			$sco_arrForm['store_no'] = $sco_arrForm['storeid'];
			$sco_arrForm['store_name'] = $sco_arrForm['storename'];
			$sco_arrForm['store_address'] = $sco_arrForm['address'];
			$sco_arrForm['order_city'] = '';
			$sco_arrForm['order_zip'] = '';
			$sco_arrForm['order_address'] = '';
			break;
		case "403": //403 : 萊爾富貨到付款
			$sco_arrForm['credit_pay_time'] = 1; //付款分期數
			$sco_arrForm['payment_pct'] = 0; //手續費率 %
			$sco_arrForm['ssl_fees_charge'] = 0; //分期付款手續費
			$sco_arrForm['payment_web_uid'] = $m_arrSystem['jsonHilifeService']['logistics_web_uid']; //金流網站編號 (萊爾富)
			break;
		case "501": 
			// Stevenson Kuo 9/28 校正 ibon 付款可能出現信用卡刷卡的情況
			$sco_arrForm['ssl_payment_form']	= "";	// 藍星還是聯信什麼等等的
			$sco_arrForm['credit_type']			= ""; 			//信用卡卡別，可用參數值 (VISA、MAST、JCB、UCARD)
			$sco_arrForm['credit_card_no']		= ""; 		//信用卡卡號
			$sco_arrForm['credit_expire']		= ""; 		//信用卡有效期限 (yyyymm)
			$sco_arrForm['credit_cd2no']		= ""; 		//信用卡背面末三碼(3 碼數字)
			// Stevenson Kuo 9/28 校正 ibon 付款可能出現信用卡刷卡的情況 END
			$sco_arrForm['credit_pay_time'] = 1; //付款分期數
			$sco_arrForm['payment_pct'] = 0; //手續費率 %
			$sco_arrForm['ssl_fees_charge'] = 0; //分期付款手續費
			$sco_arrForm['payment_web_uid'] = 0; //金流網站編號
			break;
		case "601":
			if( $_POST['execstate'] != 1 ){
				
				$SysMessage->replyActionMessages('', $_POST['execmsg'], 'index.php?action=shopping_checkout_2');
			} else {
				// @TODO 訂單成立需要變數
			}
			break;
		default:
			$sco_arrForm['credit_pay_time'] = 1; //付款分期數
			$sco_arrForm['payment_pct'] = 0; //手續費率 %
			$sco_arrForm['ssl_fees_charge'] = 0; //分期付款手續費
			$sco_arrForm['payment_web_uid'] = 0; //金流網站編號
			break;
	}
	/*** 付款方式處理 END ***/
	
	if( $sco_arrForm['delivery_is_cod'] ){
		//帳款確認 (取貨付款)
		$sco_arrForm['order_status'] = 11; //訂單狀態
		$sco_arrForm['delivery_status'] = 11; //出貨單狀態
		$sco_arrForm['order_df_status'] = 11; //訂單明細檔狀態
	}else{
		//帳款確認中
		$sco_arrForm['order_status'] = 0; //訂單狀態
		$sco_arrForm['delivery_status'] = 0; //出貨單狀態
		$sco_arrForm['order_df_status'] = 0; //訂單明細檔狀態
	}
	
	$sco_arrForm['invoice_get_type'] = $sys_arrWebsite['web_inv_get_type']; //發票開立方式
	
	//物流網站編號
	if( $sys_arrWebsite['web_logistics'] ){ //物流中心出貨
		$sco_arrForm['logistics_web_uid'] = 0;
	}else{
		$sco_arrForm['logistics_web_uid'] = $sys_arrWebsite['web_uid'];
	}
	
	//物流網站編號 (萊爾富)
	if( $sco_arrForm['delivery_type'] == "500" || $sco_arrForm['delivery_type'] == "501" ){
		$sco_arrForm['logistics_web_uid'] = $m_arrSystem['jsonHilifeService']['logistics_web_uid'];
		$sco_arrForm['invoice_get_type'] = 2;
		$sco_arrForm['invoice_donation'] = 0;
	}
	
	/*** 拆出貨單 ***/
	// 直接先插入運費 5/20 Stevenson Kuo
	$_arrSCO['m_arrSCartAll'] = array();
	$_delivery_carriage = 0;
	if( $_delivery_type != "900" ){
		$_delivery_carriage = $sco_arrForm['order_carriage'];
		$sco_arrForm['order_carriage'] -= $_delivery_carriage;
	}
	if ($_delivery_carriage > 0) {
		$_delivery_type = $sco_arrForm['delivery_type'];
		$_logistics_web_uid = $sco_arrForm['logistics_web_uid'];
		$key = $_delivery_type."_".$_logistics_web_uid;
		$_arrSCO['m_arrSCartAll'][$key] = array('delivery_type' => $_delivery_type,
												'logistics_web_uid' => $_logistics_web_uid,
												'delivery_carriage' => $_delivery_carriage);
	}
	/* 先插入運費 end */
	
	foreach($_arrSCO['m_arrShoppingCart'] as $key=>$_arrShoppingCart)
	{
		//運送方式
		$_delivery_type = ($_arrShoppingCart['prod_type'] == 9) ? "900" : $sco_arrForm['delivery_type'];
		//物流網站編號
		
		//arr_dump($sco_arrForm);
		
		$_logistics_web_uid = $sco_arrForm['logistics_web_uid'];
		if( $_delivery_type == "100" || $_delivery_type == "101"
				|| $_delivery_type == "200" || $_delivery_type == "201"
				|| $_delivery_type == "302" || $_delivery_type == "900" ) 
		{
			

			if ( $_arrShoppingCart['websup_web_uid'] == "0")
			{
				$_logistics_web_uid = $sco_arrForm['logistics_web_uid'];
			}
			else
			{
				$_arrLogisticsWebsite = $Website->s_websiteByUid($_arrShoppingCart['websup_web_uid']);
				$_logistics_web_uid = ( $_arrLogisticsWebsite[0]['web_logistics'] == 1 ) ? $sco_arrForm['logistics_web_uid'] : $_arrShoppingCart['websup_web_uid'];
			}
			

			//arr_dump($_arrShoppingCart);0
		}
		//運費
		$_delivery_carriage = 0;
		/* 移到外面去的第一筆處理了
		if( $_delivery_type != "900" ){
			$_delivery_carriage = $sco_arrForm['order_carriage'];
			$sco_arrForm['order_carriage'] -= $_delivery_carriage;
		}
		*/
		
		$_key = $_delivery_type."_".$_logistics_web_uid;
		
		if( $_delivery_type == "900" && $_arrShoppingCart['prod_type'] == 9 )
		{ //虛擬票卷			
			//計算抵扣紅利
			$_offset_bonus_s = $_arrShoppingCart['scar_offset_bonus']; //剩下可抵扣紅利
			$_offset_bonus_one = m_ceil( $_arrShoppingCart['scar_offset_bonus']/$_arrShoppingCart['scar_count'], $m_arrSystem['money_decimal'] );
			
			for( $i=0; $i<$_arrShoppingCart['scar_count']; $i++ )
			{
				$d_key = $_delivery_type."_".$_logistics_web_uid."_".$key."_".$i;
				
				$_arrShoppingCartOne = $_arrShoppingCart;
				$_arrShoppingCartOne['scar_count'] = 1;
				//抵扣紅利
				$_arrShoppingCartOne['scar_offset_bonus'] = ( $_offset_bonus_s > $_offset_bonus_one ) ? $_offset_bonus_one : $_offset_bonus_s;
				$_offset_bonus_s -= $_arrShoppingCartOne['scar_offset_bonus'];
				
				$_arrSCO['m_arrSCartAll'][$d_key]['delivery_type'] = $_delivery_type;
				$_arrSCO['m_arrSCartAll'][$d_key]['logistics_web_uid'] = $_logistics_web_uid;
				if( $_delivery_carriage ) $_arrSCO['m_arrSCartAll'][$d_key]['delivery_carriage'] = $_delivery_carriage;
				$_arrSCO['m_arrSCartAll'][$d_key]['OL'][$key] = $_arrShoppingCartOne;
				/*
				$_arrSCart = array();
				$_arrSCart['prod_uid'] = $_arrShoppingCartOne['prod_uid'];
				$_arrSCart['scar_price'] = $_arrShoppingCartOne['scar_price'];
				$_arrSCart['prod_name'] = $_arrShoppingCartOne['prod_name'];
				$_arrSCart['scar_count'] = $_arrShoppingCartOne['scar_count'];
				$_arrSCart['scar_offset_bonus'] = $_arrShoppingCartOne['scar_offset_bonus'];
				$_arrSCO['m_arrSCartAll'][$d_key]['OL'][$key] = $_arrSCart;
				*/
			}
		}
		else {
			$d_key = $_delivery_type."_".$_logistics_web_uid."_0_0";

			
			$_arrSCO['m_arrSCartAll'][$d_key]['delivery_type'] = $_delivery_type;
			$_arrSCO['m_arrSCartAll'][$d_key]['logistics_web_uid'] = $_logistics_web_uid;
			
			if( $_delivery_carriage ) $_arrSCO['m_arrSCartAll'][$d_key]['delivery_carriage'] = $_delivery_carriage;
			$_arrSCO['m_arrSCartAll'][$d_key]['OL'][$key] = $_arrShoppingCart;
			/*
			$_arrSCart = array();
			$_arrSCart['prod_uid'] = $_arrShoppingCart['prod_uid'];
			$_arrSCart['scar_price'] = $_arrShoppingCart['scar_price'];
			$_arrSCart['prod_name'] = $_arrShoppingCart['prod_name'];
			$_arrSCart['scar_count'] = $_arrShoppingCart['scar_count'];
			$_arrSCart['scar_offset_bonus'] = $_arrShoppingCart['scar_offset_bonus'];
			$_arrSCO['m_arrSCartAll'][$d_key]['OL'][$key] = $_arrSCart;
			*/
		}
	}
	/*** 拆出貨單 END ***/
	//arr_dump($_arrSCO['m_arrSCartAll']); exit();
	
	//arr_dump($sco_arrForm); 
	//arr_dump($_arrSCO); exit();
	
	if( $sco_arrForm['order_remarks'] == "(如需指定收件時間或其他收件注意事項，請填寫於此；恕不接受以備註欄修改訂單內容或數量)")
	{
		$sco_arrForm['order_remarks'] = "";
	}
	
	//$sco_arrForm['order_remarks'] = '消費者備註:' . $sco_arrForm['order_remarks'] . ',' .
	//								'取貨人性別:' . $sco_arrForm['order_remarks_gender'] . ',' .
	//	'會員卡編號:' . $sco_arrForm ['order_remarks_enterprise'];
	
    //到店取貨記錄取貨人性別
    if( $sco_arrForm['order_remarks_gender'] != "")
	{
	$sco_arrForm['order_remarks'] = $sco_arrForm['order_remarks'] . '取貨人性別:' . $sco_arrForm['order_remarks_gender'] . ',' ; 
	}
    //會員卡號備註
	if( $sco_arrForm['order_remarks_enterprise'] != "")
	{
	$sco_arrForm['order_remarks'] = $sco_arrForm['order_remarks'] . '會員卡編號:' . $sco_arrForm ['order_remarks_enterprise'];
	}
    //Jessie	
	if( $sco_arrForm['YFDATE'] != "" && $sco_arrForm['YFTIME'] != ""){
		/*	20161228 生 鮮 預 購 館 START	*/
		if($sys_arrWebsite['web_uid'] == 1599)
        	$sco_arrForm['order_remarks'] = $sco_arrForm['order_remarks'] . '年菜到貨日:' . $sco_arrForm['YFDATE'].','.$sco_arrForm['YFTIME'];
        else{
        	/*	$sco_arrForm['order_remarks'] .= '店家:' . $sco_arrForm['pickup_store_selection']  . ',領取日期:' . $sco_arrForm['YFDATE'] . ',領取時間:' . $sco_arrForm['YFTIME'] . '!!';	*/
        	$store_info = array('st02' => '新竹市新竹市湳雅街97號',
									'st03' => '台中市忠明路499號',
									'st06' => '彰化縣埔心鄉瓦南村中山路319號',
									'st11' => '台南市郡平路42號',
									'st09' => '新北市中和區中山路2段228號B1',
									'st13' => '桃園市中壢區中北路2段468號',
									'st14' => '雲林縣斗南鎮西岐里文化街119巷21號',
									'st18' => '台北市內湖區舊宗路1段128號',
									'st32' => '台南市佳里區民安里同安寮80之2號',
									'st33' => '桃園市八德區介壽路2段148號',
									'st34' => '台北市八德路2段306號地下2樓',
									'st36' => '嘉義市西區博愛路2段281號',
									'st37' => '高雄市鳳山區文化路59號',
									'st31' => '新北市中和區景平路182號B1',
									'st01' => '平鎮店到店取貨區取貨',
									'st07' => '台南店到店取貨區取貨',
									'st10' => '土城店到店取貨區取貨',
									'st12' => '忠孝店到店取貨區取貨',
									'st16' => '內湖二店到店取貨區取貨',
									'st17' => '台東店到店取貨區取貨',
									'st30' => '碧潭店到店取貨區取貨',
									'st39' => '頭份店到店取貨區取貨');
			$pick_infortaion = $store_info[$sco_arrForm['pickup_store_selection']];
			$sco_arrForm['order_remarks'] .= '<br >'. $pick_infortaion . ',<br >日期:' . $sco_arrForm['YFDATE'] . ',<br >時間:' . $sco_arrForm['YFTIME'];
    	}
        /*	20161228 生 鮮 預 購 館 END	*/
    }
    //
    if( $sco_arrForm['order_remarks_secondfloor'] != "" || $sco_arrForm['order_remarks_elevator'] !="" || $sco_arrForm['order_remarks_oldmachine'] !="")
	{
	$sco_arrForm['order_remarks'] = $sco_arrForm['order_remarks'] . '大家電資料:' . $sco_arrForm['order_remarks_oldmachine'].','. $sco_arrForm['order_remarks_secondfloor']. ','. $sco_arrForm['order_remarks_elevator'];
	}
    
    //判斷訂單主檔備註欄有沒有被寫東西進去
	if( $sco_arrForm['order_remarks'] != "") 
	{
		if($sys_arrWebsite['web_uid'] != 1603)
			$sco_arrForm['order_remarks'] = '消費者備註:' . $sco_arrForm['order_remarks'];
		else{
			//生鮮預購館不做任何動作
		}
	}

	//arr_dump($sco_arrForm); exit();
	//新增訂單
	$sco_arrForm['order_uid'] = $OrderMf->i_order_mf($sys_arrWebsite['web_uid'], $sys_arrMember['mem_uid'], $sco_arrForm['buyer_order_addressee'], 
													$sco_arrForm['order_email'], $sco_arrForm['order_tel'], $sco_arrForm['buyer_order_telcellphone'], 
													$sco_arrForm['cou_code_num'], $sco_arrForm['order_state'], $sco_arrForm['buyer_order_city'], 
													$sco_arrForm['buyer_order_zip'], $sco_arrForm['buyer_order_address'], $sco_arrForm['payment_type'], 
													$sco_arrForm['payment_case_no'], $sco_arrForm['bank_no'], $sco_arrForm['bank_name'], 
													$sco_arrForm['atm_account'], $sco_arrForm['credit_name'], $sco_arrForm['credit_idcard'], 
													$sco_arrForm['credit_birthday'], $sco_arrForm['credit_card_no'], $sco_arrForm['credit_cd2no'], 
													$sco_arrForm['credit_expire'], $sco_arrForm['credit_pay_time'], $sco_arrForm['ssl_fees_charge'], 
													$sco_arrForm['credit_type'], $sco_arrForm['invoice_type'], $sco_arrForm['invoice_title'], 
													$sco_arrForm['invoice_utcode'], $sco_arrForm['invoice_state'], $sco_arrForm['invoice_city'], 
													$sco_arrForm['invoice_zip'], $sco_arrForm['invoice_address'], $sco_arrForm['invoice_addressee'], $sco_arrForm['invoice_electronic_device_code'],
													$sco_arrForm['invoice_donatee'], $sco_arrForm['order_status'], $sco_arrForm['order_remarks'],
													$sco_arrForm['payment_business_no'], $sys_rbye, $sco_arrForm['ssl_approval_code'],
													$sco_arrForm['ssl_payment_form'], $sco_arrForm['pick_date']);
	
	if( $sco_arrForm['order_uid'] ){
		foreach($_arrSCO['m_arrSCartAll'] as $d_key=>$_arrDM){
			/*** 其他備用參數 ***/
			$_arrODTempvar = array();
			if( $sco_arrForm['webdvr_uid'] && $_arrDM['delivery_type'] != "900" ) $_arrODTempvar['webdvr_uid'] = $sco_arrForm['webdvr_uid'];
			if( $sco_arrForm['webpay_uid'] ) $_arrODTempvar['webpay_uid'] = $sco_arrForm['webpay_uid'];
			$_arrODTempvar['delivery_carriage'] = ($_arrDM['delivery_carriage']) ? $_arrDM['delivery_carriage'] : 0;
			$_delivery_tempvar = ( $_arrODTempvar ) ? json_encode($_arrODTempvar) : "";
			
			// FIXME 付款方式 401 7-11 超商取貨對應付款方式是在這裡寫死指定的		
			// FIXME 這樣的話其實 delivery_tempvar 裡的值會不準	
			$_arrDM['delivery_type'] = ($sco_arrForm['payment_type'] == 402) ? 401 : $_arrDM['delivery_type'];
			$_arrDM['delivery_type'] = ($sco_arrForm['payment_type'] == 400) ? 101 : $_arrDM['delivery_type'];
			/*** 其他備用參數 ***/
			
			//新增出貨單主檔
			$sco_arrForm['delivery_uid'] = $OrderDelivery->i_order_delivery($sco_arrForm['order_uid'], $_arrDM['logistics_web_uid'], $_arrDM['delivery_type'], 
																			$sco_arrForm['delivery_case_no'], $sco_arrForm['order_addressee'], $sco_arrForm['order_tel'], 
																			$sco_arrForm['order_telcellphone'], $sco_arrForm['cou_code_num'], $sco_arrForm['order_state'], 
																			$sco_arrForm['order_city'], $sco_arrForm['order_zip'], $sco_arrForm['order_address'], 
																			$sco_arrForm['store_no'], $sco_arrForm['store_rono'], $sco_arrForm['store_name'], 
																			$sco_arrForm['store_tel'], $sco_arrForm['store_address'], $sco_arrForm['delivery_status'], 
																			$sco_arrForm['payment_web_uid'], $sco_arrForm['payment_type'], $sco_arrForm['payment_case_no'], 
																			$sco_arrForm['invoice_get_type'], $sco_arrForm['invoice_donation'], $_arrDM['delivery_carriage'],
																			$sco_arrForm['delivery_business_no'], $sco_arrForm['payment_business_no'], $_delivery_tempvar);
			//新增訂單明細檔
			if (count($_arrDM['OL']) > 0) {
				foreach($_arrDM['OL'] as $_arrSCart){
					/*** 其他備用參數 ***/
					$_arrTempvar = json_decode( $_arrSCart['prod_tempvar'], true );
					if( trim($sco_arrForm['pickup_df_date']) != "" ) $_arrTempvar['pickup_df_date'] = $sco_arrForm['pickup_df_date']; //取貨日
					if( $_arrSCart['pdsc_uid'] ){
						$ProductDiscount = new ProductDiscount($web_lang); //商品折扣設定檔
						$_arrPdsc = $ProductDiscount->s_product_discountByUid($_arrSCart['pdsc_uid']);
						if( $_arrPdsc[0]['pdsc_old_no'] ) $_arrTempvar['marketing_case_no'] = $_arrPdsc[0]['pdsc_old_no']; //廠商活動編號
					}
					$_arrTempvar['order_offset_bonus'] = ($_arrSCart['scar_offset_bonus']) ? $_arrSCart['scar_offset_bonus'] : 0; //抵扣紅利
					$_order_df_tempvar = ( $_arrTempvar ) ? json_encode($_arrTempvar) : "";
					/*** 其他備用參數 END ***/
					
					$OrderDf->i_order_df($sco_arrForm['order_uid'], $sco_arrForm['delivery_uid'], $_arrSCart['prod_uid'], 
											$_arrSCart['prod_selling_price'], $_arrSCart['prod_cost_price'], $_arrSCart['scar_price'],
											$_arrSCart['scar_offset_bonus'], $_arrSCart['scar_count'], $_arrSCart['scar_group_no'],
											$_arrSCart['scar_type'], $_arrSCart['pdsc_uid'], $_arrSCart['pmkt_uid'],
											$_arrSCart['wmkt_uid'], $sco_arrForm['order_df_status'], $sco_arrForm['pickup_df_date'],
											$_arrSCart['prod_case_no'], $_order_df_tempvar, $m_arrSystem['mem_web_uid'],
											$_arrSCart['prod_name'] );
					//依 商品編號 扣除庫存
					$Product->u_productStockByUid($_arrSCart['prod_uid'], $_arrSCart['scar_count']);
				}
			}
		}
		
		/*** 萊爾富 WebService, 確認[完成結帳] ***
		if( $m_arrSystem['jsonHilifeService'] && ( $sco_arrForm['delivery_type'] == "500" || $sco_arrForm['delivery_type'] == "501" ) ){
			$HilifeWebService = new HilifeWebService($web_lang); 
			$_hilifePayConfirm = $HilifeWebService->payConfirm($sco_arrForm['delivery_uid']);
			if( !$_hilifePayConfirm ){
				//萊爾富訂單取消
				$OrderDelivery->orderDeliveryStatusByUid($sco_arrForm['delivery_uid'], "31");
				session_unregister($sys_arrWebsite['scar_no_cookie_name']); //清除購物車
				session_unregister($shopping_checkout_json_session_name); //清除結帳資料
				?>
				<form name="frm_alert" method="post" action="<?php echo $sys_arrWebsite['website_url']?>index.php">
					<input type="hidden" name="err_alert" value="萊爾富預購單號超過時效，訂單已取消！">
					<input type="submit" name="btn_submit" style="display:none;">
				</form>
				<script language="javascript">
					document.frm_alert.btn_submit.click();
				</script>
				<?php 				exit();	
			}
		}
		*** 萊爾富 WebService, 確認[完成結帳] END ***/
		
		/*** 自動請款 ***/
		if( ( $sco_arrForm['ssl_payment_form'] == "twNewebmPP" && $m_arrSystem['jsonNewebSSLmPP']['DepositFlag'] == 1 ) ||  //藍新 mPP 5
			( $sco_arrForm['ssl_payment_form'] == "twHiTRUST" && $m_arrSystem['jsonHiTRUST']['DepositFlag'] == 1  ) ||
			 $sco_arrForm['ssl_payment_form'] == "Taishin" 	//網際威信
		){
			//依 訂單編號 完成線上付款
			// Stevenson Kuo 9/28 修正ibon不可能用信用卡付款的問題
			if ($sco_arrForm['payment_type'] != '501' && $sco_arrForm['webpay_uid'] != 4) {
				$ac_pay_money_ssl = $sco_arrForm['scarPriceTotal'] + $sco_arrForm['ssl_fees_charge'];
				$OrderMf->u_order_mfSSLPayByOuid($sco_arrForm['order_uid'], $ac_pay_money_ssl);
			}
		}
		/*** 自動請款 END ***/
		
		/*** 藍新 NPC 請款 ***/
		if( $sco_arrForm['ssl_payment_form'] == "twNeweb" && $m_arrSystem['jsonNewebSSL']['DepositFlag'] == 1 ){ //藍新 NPC
			$SSLPayment = new SSLPaymentNeweb($sco_arrForm['payment_type']); //藍新線上刷卡
			$_arrSSLPay['OrderNumber'] = $sco_arrForm['payment_case_no'];
			$_arrSSLPay['Amount'] = $sco_arrForm['scarPriceTotal'] + $sco_arrForm['ssl_fees_charge'];
			$_arrSSLPay['MerchantNumber'] = $sco_arrForm['payment_business_no'];
			
			//arr_dump($_arrSSLReturn); exit();
			
			$_arrSSLReturn = $SSLPayment->deposit($_arrSSLPay['OrderNumber'], $_arrSSLPay['Amount'], $_arrSSLPay['MerchantNumber']);
			
                      
			
                        
			if( $_arrSSLReturn['execState'] ){
				//依 訂單編號 完成線上付款
				$OrderMf->u_order_mfSSLPayByOuid($sco_arrForm['order_uid'], $_arrSSLPay['Amount']);
			}
		}
		/*** 藍新 NPC 請款 END ***/
		/*** 藍新 NPC 分期請款 ***/
		if( $sco_arrForm['ssl_payment_form'] == "twNewebInstallment" && $m_arrSystem['jsonNewebInstallment']['DepositFlag'] == 1 ){ //藍新 NPC
			$SSLPayment = new SSLPaymentNeweb($sco_arrForm['payment_type']); //藍新線上刷卡
			$_arrSSLPay['OrderNumber'] = $sco_arrForm['payment_case_no'];
			$_arrSSLPay['Amount'] = $sco_arrForm['scarPriceTotal'] + $sco_arrForm['ssl_fees_charge'];
			$_arrSSLPay['MerchantNumber'] = $sco_arrForm['payment_business_no'];
			
			//arr_dump($_arrSSLReturn); exit();
			
			$_arrSSLReturn = $SSLPayment->deposit($_arrSSLPay['OrderNumber'], $_arrSSLPay['Amount'], $_arrSSLPay['MerchantNumber']);
			
                      
			
                        
			if( $_arrSSLReturn['execState'] ){
				//依 訂單編號 完成線上付款
				$OrderMf->u_order_mfSSLPayByOuid($sco_arrForm['order_uid'], $_arrSSLPay['Amount']);
			}
		}
		/*** 藍新 NPC 分期請款 END ***/

		if( $sco_arrForm['order_use_bonus'] > 0 ) {
			
			// 把扣抵金額還原成紅利點數
			$_used_bonus = $Bonus->restoreMoney2Bonus($sys_arrMember['mem_uid'], $sco_arrForm['order_use_bonus']);
			//依網站編號, 會員編號, 使用紅利積點
			$Bonus->useBonusByWuidMuid( $m_arrSystem['mem_web_uid'], $sys_arrMember['mem_uid'], $_used_bonus,
										"", $sco_arrForm['order_uid'], "Use OrderNo︰".$sco_arrForm['order_uid'] );
		}
		
		//會員收件人備忘錄
		$MemberAddressee = new MemberAddressee(); 
		$MemberAddressee->i_member_addressee($sys_arrMember['mem_uid'], $sco_arrForm['order_addressee'], $sco_arrForm['order_tel'],
												$sco_arrForm['order_telcellphone'], $sco_arrForm['order_state'], $sco_arrForm['order_city'],
												$sco_arrForm['order_zip'], $sco_arrForm['order_address']);
		
		//依購物車編號 修改訂單編號
		$ShoppingCart->u_sCartOrderNoByScno($_arrSCO['scar_no'], $sco_arrForm['order_uid']);
		unset ($_SESSION[$sys_arrWebsite['scar_no_cookie_name']]); //清除購物車
		unset ($_SESSION[$shopping_checkout_json_session_name]); //清除結帳資料
		unset ($_SESSION[md5($webdvr_uid['webdvr_uid'])]); //清除運費金碼
		unset ($_SESSION['MPIReceive']); //清除ECI回應
		if (!$_SESSION['forceCheckout']) {
			setcookie($sys_arrWebsite['scar_no_cookie_name'], "", strtotime("-90 days"), "/", $m_arrSystem['cookie_domain'] ); //清除 購物車編號
		} else {
			unset($_SESSION['forceCheckout']);
		}
		
		
		//20170105滿1500 Alex 實體卡號綁定送贈品
		$order_alex = $sco_arrForm['order_uid'];
		
		$query = "SELECT if(now() between '2017-06-05 00:00:00' and '2017-06-07 00:00:00' ,1,0);";
		$result = mysql_query($query,$con);
		$mem_date = mysql_fetch_array($result);
		
		if($mem_date[0] ==1){
			$query = "select prod_store_name from product where prod_no_old = 1925 && web_uid = 2;";
			$result = mysql_query($query,$con);
			$gifts = mysql_fetch_array($result);
			
		$query = "select a.pay_money_ssl from order_mf a join member b  
		where a.mem_uid = b.mem_uid &&  a.web_uid in (2,4,1599,1603)  && b.hypercard_status > 0 && a.order_uid = $order_alex;";
			$result = mysql_query($query,$con);
			$pay = mysql_fetch_array($result);	
			
			if($pay[0]>=1500){
				
				if($gifts[0]>0){
				
						$query = "update order_mf set order_service_remarks = $gifts[0] where order_uid = $order_alex ;";
						$result = mysql_query($query,$con);
						$gifts[0] = $gifts[0] -1;
						$query = "UPDATE product SET prod_store_name= $gifts[0] WHERE prod_uid = 604703;";
						$result = mysql_query($query,$con);
		
				}else{
					$query = "select prod_store_name from product where prod_no_old = 1925 && web_uid = 2;";
					$result = mysql_query($query,$con);
					$gifts2 = mysql_fetch_array($result);
					
					if($gifts2[0]>0){
						$query = "update order_mf set order_service_remarks = $gifts2[0] where order_uid = $order_alex ;";
						$result = mysql_query($query,$con);
						$gifts2[0] = $gifts2[0] -1;
						$query = "UPDATE product SET prod_store_name= $gifts2[0] WHERE prod_uid = 604703;";
						$result = mysql_query($query,$con);
						
					}else{
						
						count;
						
						
					}
					
					
					
				}
			}else{
				
				$query = "select prod_store_name from product where prod_no_old = 1925 && web_uid = 2;";
					$result = mysql_query($query,$con);
					$gifts2 = mysql_fetch_array($result);
				
				if($gifts2[0]>0){
				
				$query = "select a.pay_money_ssl from order_mf a join member b  
		where a.mem_uid = b.mem_uid &&  a.web_uid in (2,4,1599,1603)  && b.hypercard_status > 0 && a.order_uid = $order_alex;";
			$result = mysql_query($query,$con);
			$pay2 = mysql_fetch_array($result);	
					if($pay2[0]>=1500){
						$query = "update order_mf set order_service_remarks = $gifts2[0] where order_uid = $order_alex ;";
						$result = mysql_query($query,$con);
						$gifts2[0] = $gifts2[0] -1;
						$query = "UPDATE product SET prod_store_name= $gifts2[0] WHERE prod_uid = 604703;";
						$result = mysql_query($query,$con);
					}else{
						$query = "update order_mf set order_service_remarks = '-1' where order_uid = $order_alex ;";
						$result = mysql_query($query,$con);
						
					}
				}else{
					count;
				}	
					
			}	
		}else{
			count;
		}
		
		
		
		
		
		/*
		////////20170114 Alex 同類總數3贈品
			
		$order_alex = $sco_arrForm['order_uid'];
		
		if($order_alex !=0){
			
			$query = "select prod_stock from product where prod_no_old = 1644 && web_uid = 2;";
			$result = mysql_query($query,$con);
			$gifts = mysql_fetch_array($result);
			
			
			$query = "select ifnull(sum(b.order_df_qty),0)  order_df_qty from order_mf a join order_df b
		where a.order_uid = b.order_uid && a.order_uid = $order_alex && b.prod_uid in (
        153898,
		153899,
		350670,
		350671,
		351508,
		351509,
		621504,
		621505
		) ;";
			$result = mysql_query($query,$con);
			$order_df_qty = mysql_fetch_array($result);
			
			
			
			
			
			if($order_df_qty[0]>=3){
				
				if($gifts[0]>0){
						
						
						$query = "update order_mf set order_service_remarks = $gifts[0] where order_uid = $order_alex ;";
						$result = mysql_query($query,$con);
						

						$gifts[0] = $gifts[0] -1;
						
						$query = "update product set prod_stock= $gifts[0] where prod_uid = 196551;";
						$result = mysql_query($query,$con);
						
						
						
		
				}else{
					count;
					
				}
			}else{
				
				if($gifts[0]>0){
				
						
						$query = "update order_mf set order_service_remarks = '-1' where order_uid = $order_alex ;";
						$result = mysql_query($query,$con);

					
				}else{
					count;
				}	
					
			}	
		
		}
		/////////
		*/
		
		
		
		// @todo 改用比較不安全的方法，再看看未來有沒有其他方法解決；
		//header("Status: 302 Found");
		//header("Location: " . $_SERVER['REQUEST_URI'] . "&sco_order_uid=" . $sco_arrForm['order_uid']);

		//exit();
		?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<form name="frm_alert" method="post" action="">
			<input type="hidden" name="sco_order_uid" value="<?php echo $sco_arrForm['order_uid']?>">
			<input type="hidden" name="order_use_bonus" value="<?php echo $sco_arrForm['order_use_bonus']?>">
			<input type="submit" name="btn_submit" style="display:none;">
		</form>
		<script language="javascript">
			document.frm_alert.btn_submit.click();
		</script>
		<?php
	}
/*** 寫入訂單 END ************************************************************/
}

