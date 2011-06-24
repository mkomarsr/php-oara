<?php
/**
 * Api Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Wg
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_Wg_Api extends Oara_Network_Base{
    /**
     * Soap client.
     */
	private $_soapClient = null;
	/**
     * Web client.
     */
	private $_webClient = null;
	/**
     * Export Merchant Parameters
     * @var array
     */
    private $_exportMerchantParameters = null;
	/**
     * Export Transaction Parameters
     * @var array
     */
    private $_exportTransactionParameters = null;
    /**
     * Export Overview Parameters
     * @var array
     */
    private $_exportOverviewParameters = null;
	/**
	 * Converter configuration for the merchants.
	 * @var array
	 */
	private $_merchantConverterConfiguration = Array ('programID'=>'cid',
                                                      'programName'=>'name',
                                                      'programURL'=>'url',
	                                                  'programDescription'=>'description'
	                                                  );
	/**
     * Converter configuration for the transactions.
     * @var array
     */
	private $_transactionConverterConfiguration = Array ('status'=>'status',
	                                                     'saleValue'=>'amount',
	                                                     'commission'=>'commission',
	                                                     'date'=>'date',
	                                                     'campaignName'=>'website',
	                                                     'merchantId'=>'merchantId'
	                                                    );
	/**
	 * Array with the id from the campaigns
	 * @var array
	 */                                                    
	private $_campaignMap = array();
    /**
     * Constructor.
     * @param $webgains
     * @return Oara_Network_Wg_Api
     */
	public function __construct($webgains, $groupId, $mode)
	{
        $configuration = $webgains->AffiliateNetworkConfig->toArray();
        //Reading the different parameters.
        $user = Oara_Utilities::arrayFetchValue($configuration,'key','user');
        $user = Oara_Utilities::decodePassword($user['value']);
        $password = Oara_Utilities::arrayFetchValue($configuration,'key','password');
        $password = Oara_Utilities::decodePassword($password['value']);
        
        $wsdlUrl = 'http://ws.webgains.com/aws.php';
        //Setting the client.
		$this->_soapClient = new Zend_Soap_Client($wsdlUrl, array('login' => $user,
		                                                      'encoding' => 'UTF-8',
		                                                      'password' => $password,
		                                                      'compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
		                                                      'soap_version' => SOAP_1_1));
		
		$loginUrl = 'http://www.webgains.com/index.html';
		
		$valuesLogin = array(
                             new Oara_Curl_Parameter('screenwidth', 1280),
                             new Oara_Curl_Parameter('screenheight', 768),
                             new Oara_Curl_Parameter('colourdepth', 32),
                             new Oara_Curl_Parameter('usertype', 'affiliateuser'),
                             new Oara_Curl_Parameter('username', $user),
                             new Oara_Curl_Parameter('password', $password),
                             new Oara_Curl_Parameter('submitbutton', 'PLS WAIT')
                             );
		
		$this->_webClient = new Oara_Curl_Access($loginUrl, $valuesLogin, $webgains, $groupId, $mode);
		
		$this->_exportMerchantParameters = array('username' => $user,
												 'password' => $password
												);
		$this->_exportTransactionParameters = array('username' => $user,
												 	'password' => $password
												   );
		$this->_exportOverviewParameters = array('username' => $user,
												 'password' => $password
												);
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;
		if (self::getCampaignMap()){
			$connection = true;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array())
	{
		$this->_campaignMap = self::getCampaignMap();
		
		$merchantList = Array();
		foreach ($this->_campaignMap as $campaignKey => $campaignValue){
			$merchants = $this->_soapClient->getProgramsWithMembershipStatus($this->_exportMerchantParameters['username'], $this->_exportMerchantParameters['password'], $campaignKey);
			foreach ($merchants as $merchant){
				if ($merchant->programMembershipStatusName == 'Live'){
					$merchantList[$merchant->programID] = $merchant;
				}	
			}
			
		}
		$merchantList = Oara_Utilities::soapConverter($merchantList, $this->_merchantConverterConfiguration);

		return $merchantList;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
     */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null)
	{	
		$totalTransactions = Array();
		
		$dStartDate = clone $dStartDate;
	    $dStartDate->setHour("00");
        $dStartDate->setMinute("00");
        $dStartDate->setSecond("00");
        $dEndDate = clone $dEndDate;
        $dEndDate->setHour("23");
        $dEndDate->setMinute("59");
        $dEndDate->setSecond("59");
			
        foreach ($this->_campaignMap as $campaignKey => $campaignValue){
        
			$transactionList = $this->_soapClient->getDetailedEarnings($dStartDate->getIso(), $dEndDate->getIso(),
																	   $campaignKey, $this->_exportTransactionParameters['username'],
																	   $this->_exportTransactionParameters['password']);
			foreach ($transactionList as $transaction){
				if (in_array($transaction->programID, $merchantList)){
					$transaction->merchantId = $transaction->programID;
					if ($transaction->status == 'confirmed'){
						$transaction->status = Oara_Utilities::STATUS_CONFIRMED;
					} else if ($transaction->status == 'delayed') {
						$transaction->status = Oara_Utilities::STATUS_PENDING;
					} else if ($transaction->status == 'cancelled'){
						$transaction->status = Oara_Utilities::STATUS_DECLINED;
					} else{
						throw new Exception('Error in the transaction status');
					}
					$transactionDate = new Zend_Date($transaction->date,"yyyy-MM-ddTHH:mm:ss");
					$transaction->date = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
			    	$totalTransactions[] = $transaction;
				}
			}
		
        }
		
        $totalTransactions = Oara_Utilities::soapConverter($totalTransactions, $this->_transactionConverterConfiguration);
		return $totalTransactions;
	}
	/**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
     */
	public function getOverviewList($transactionList = array(), $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null)
	{
		$totalOverview = array();
		//At first, we need to be sure that there are some data.
		$monthTransactionList = $transactionList;
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
	    $dateArraySize = count($dateArray);
	    
	    $auxStartDate = clone $dStartDate;
	    $auxStartDate->setHour("00");
        $auxStartDate->setMinute("00");
        $auxStartDate->setSecond("00");
        $auxEndDate = clone $dEndDate;
        $auxEndDate->setHour("23");
        $auxEndDate->setMinute("59");
        $auxEndDate->setSecond("59");
		
        foreach ($this->_campaignMap as $campaignKey => $campaignValue){

	        $overviewList = $this->_soapClient->getProgramReport($auxStartDate->getIso(), $auxEndDate->getIso(),
																 $campaignKey, $this->_exportOverviewParameters['username'],
																 $this->_exportOverviewParameters['password']);
			$exist = false;
			$j = 0;
			while ( $j < count($overviewList) && !$exist){
				if (in_array($overviewList[$j]->programID ,$merchantList)){
					$exist = true;
				}
				$j++;
			}								 
			
			if ($exist){
				
				$transactionList = array();
				$auxTransactionList = array();
				foreach ($monthTransactionList as $transaction){
					if ($transaction['website'] == $campaignValue){
						$auxTransactionList[] = $transaction; 
					}
				}
				$transactionList = Oara_Utilities::transactionMapPerDay($auxTransactionList);
				
	            for ($i = 0; $i < $dateArraySize; $i++){
	            	$auxStartDayDate = clone $dateArray[$i];
		        	$auxStartDayDate->setHour("00");
		            $auxStartDayDate->setMinute("00");
		            $auxStartDayDate->setSecond("00");
		            
		            $auxEndDayDate = clone $dateArray[$i];
		            $auxEndDayDate->setHour("23");
		            $auxEndDayDate->setMinute("59");
		            $auxEndDayDate->setSecond("59");
		            
		            $overviewList = $this->_soapClient->getProgramReport($auxStartDayDate->getIso(), $auxEndDayDate->getIso(),
																		 $campaignKey, $this->_exportOverviewParameters['username'],
																		 $this->_exportOverviewParameters['password']);

					
					for ($j = 0; $j < count($overviewList) ;$j++){
						
						if (in_array($overviewList[$j]->programID ,$merchantList)){
							
							$overviewArray = array();
							
							$overviewArray['merchantId'] = $overviewList[$j]->programID;
							$overviewArray['date'] = $auxStartDayDate->toString("yyyy-MM-dd HH:mm:ss");
							$overviewArray['click_number'] = $overviewList[$j]->clickTotals;
							$overviewArray['impression_number'] = $overviewList[$j]->viewTotals;
							$overviewArray['website'] = $campaignValue;
							$transactionDateArray = Oara_Utilities::getDayFromArray($overviewArray['merchantId'],$transactionList, $auxStartDayDate);
							$overviewArray['transaction_number'] = 0;
							$overviewArray['transaction_confirmed_value'] = 0;
	                        $overviewArray['transaction_confirmed_commission']= 0;
	                        $overviewArray['transaction_pending_value']= 0;
	                        $overviewArray['transaction_pending_commission']= 0;
	                        $overviewArray['transaction_declined_value']= 0;
	                        $overviewArray['transaction_declined_commission']= 0;
							foreach ($transactionDateArray as $transaction){
								if (!isset($transaction['amount'])){
									$transaction['amount'] = 0;
								}
								if (!isset($transaction['commission'])){
									$transaction['commission'] = 0;
								}
		                        $overviewArray['transaction_number']++;
	                        	if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
	                            	$overviewArray['transaction_confirmed_value'] += $transaction['amount'];
	                            	$overviewArray['transaction_confirmed_commission'] += $transaction['commission'];
	                        	} else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
	                            	$overviewArray['transaction_pending_value'] += $transaction['amount'];
	                            	$overviewArray['transaction_pending_commission'] += $transaction['commission'];
	                        	} else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
	                            	$overviewArray['transaction_declined_value'] += $transaction['amount'];
	                            	$overviewArray['transaction_declined_commission'] += $transaction['commission'];
	                        	}
	                        }
	                        if (Oara_Utilities::checkRegister($overviewArray)){
	                        	$totalOverview[] = $overviewArray;
	                        }
						}
					}
	            }
			}
        }
	    return $totalOverview;
	}
	/**
	 * Get the campaings identifiers and returns it in an array.
	 * @return array
	 */
	private function getCampaignMap(){
		$campaingMap = array();
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://www.webgains.com/affiliates/report.html?f=0&action=sf', array());
	    $exportReport = $this->_webClient->get($urls);
	    
		$matches = array();
		
		
		$matches = array();
        if (preg_match("/<select name=\"campaignswitchid\" class=\"formelement\" style=\"width:134px\">([^\t]*)<\/select>/",
        			   $exportReport[0], $matches)){
        			   	
        	if (preg_match_all("/<option value=\"(.*)\" .*>(.*)<\/option>/", $matches[1], $matches)){
	            $campaingNumber = count($matches[1]);
	            $i = 0;
	            while ($i < $campaingNumber){
	            	$campaingMap[$matches[1][$i]] = $matches[2][$i];
	            	$i++;
	            }
	        } else{
	            throw new Exception('No campaigns found');
	        }  
            
        } else {
        	throw new Exception ("No campaigns found");
        }
		return $campaingMap;
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
    	$paymentHistory = array();
    	
    	$urls = array();
    	
        $urls[] = new Oara_Curl_Request('https://www.webgains.com/affiliates/payment.html', array());
        $exportReport = $this->_webClient->get($urls);
    	
		/*** load the html into the object ***/
	    $doc = new DOMDocument();
	    libxml_use_internal_errors(true);
	    $doc->validateOnParse = true;
	    $doc->loadHTML($exportReport[0]);
	    $tableList = $doc->getElementsByTagName('table');
	    $i = 0;
	    $enc = false;
	    while ($i < $tableList->length && !$enc) {
	    	
	    	$registerTable = $tableList->item($i);
	    	if ($registerTable->getAttribute('class') == 'withgrid'){
	    		$enc = true;
	    	}
	    	$i++;
	    }
		if (!$enc){
			throw new Exception ('Fail getting the payment History');
		}
		
		$registerLines = $registerTable->childNodes;
		for ($i = 2;$i < $registerLines->length ;$i++) {
			
			$obj = array();
			
			$linkList = $registerLines->item($i)->getElementsByTagName('a');
			$url = $linkList->item(1)->attributes->getNamedItem("href")->nodeValue;
			$parseUrl = parse_url(trim($url));
	        $parameters = explode('&', $parseUrl['query']);
	        foreach($parameters as $parameter){
	        	$parameterValue = explode('=', $parameter);
	            if ($parameterValue[0] == 'payment'){
	            	$obj['pid'] = $parameterValue[1];
	            }
	        }
	        
			$registerLine = $registerLines->item($i)->childNodes;
			$date = new Zend_Date($registerLine->item(0)->nodeValue, "dd/MM/yy");
			$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
			$value = $registerLine->item(2)->nodeValue;
			preg_match( '/[0-9]+(,[0-9]{3})*(\.[0-9]{2})?$/', $value, $matches);
			$obj['value'] = Oara_Utilities::parseDouble($matches[0]);
			$obj['method'] = $registerLine->item(6)->nodeValue;
			$paymentHistory[] = $obj;
		}
    	
    	return $paymentHistory;
    }
}