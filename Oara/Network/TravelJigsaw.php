<?php
/**
 * Export Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Tj
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_TravelJigsaw extends Oara_Network{
    /**
     * Export client.
     * @var Oara_Curl_Access
     */
	private $_client = null;
	
	/**
	 * Transaction Export Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;

	/**
	 * Constructor and Login
	 * @param $traveljigsaw
	 * @return Oara_Network_Tj_Export
	 */
	public function __construct($credentials)
	{
		$user = $credentials['user'];
        $password = $credentials['password'];

		$loginUrl = 'http://www.traveljigsawgroup.com/affiliates/ProcessAffiliateLogin.do';
		
		$valuesLogin = array(new Oara_Curl_Parameter('affiliate.assignedCode', $user),
                             new Oara_Curl_Parameter('affiliate.password', $password)
                             );
		
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$today = new Zend_Date();
		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('orderBy', ''),
													new Oara_Curl_Parameter('orderAsc', 'true'),
													new Oara_Curl_Parameter('sessionStartDayFilter', $today->toString("d")),
													new Oara_Curl_Parameter('sessionStartMonthFilter', $today->toString("M")),
													new Oara_Curl_Parameter('sessionStartYearFilter', $today->toString("yyyy")),
													new Oara_Curl_Parameter('sessionEndDayFilter', $today->toString("d")),
													new Oara_Curl_Parameter('sessionEndMonthFilter', $today->toString("M")),
													new Oara_Curl_Parameter('sessionEndYearFilter', $today->toString("yyyy")),
													new Oara_Curl_Parameter('allDatesFilter', 'false'),
													new Oara_Curl_Parameter('origOrderBy', ''),
													new Oara_Curl_Parameter('origOrderAsc', 'true'),
													new Oara_Curl_Parameter('locationFilter', ''),
													new Oara_Curl_Parameter('campaignFilter', ''),
													new Oara_Curl_Parameter('statusFilter', '')
													);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;
		$urls = array();
		$today = new Zend_Date();
		$valuesFormExport = array();
		
		$valuesFormExport[] = new Oara_Curl_Parameter('sessionStartDayFilter', $today->toString("d"));
		$valuesFormExport[] = new Oara_Curl_Parameter('sessionStartMonthFilter', $today->toString("M"));
		$valuesFormExport[] = new Oara_Curl_Parameter('sessionStartYearFilter', $today->toString("yyyy"));
		$valuesFormExport[] = new Oara_Curl_Parameter('sessionEndDayFilter', $today->toString("d"));
		$valuesFormExport[] = new Oara_Curl_Parameter('sessionEndMonthFilter', $today->toString("M"));
		$valuesFormExport[] = new Oara_Curl_Parameter('sessionEndYearFilter', $today->toString("yyyy"));
		
        $valuesFormExport[] = new Oara_Curl_Parameter('origStartDayFilter', $today->toString("d"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origStartMonthFilter', $today->toString("M"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origStartYearFilter', $today->toString("yyyy"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origEndDayFilter', $today->toString("d"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origEndMonthFilter', $today->toString("M"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origEndYearFilter', $today->toString("yyyy"));
		
		$valuesFormExport[] = new Oara_Curl_Parameter('bookingRecord.idString', '');
		$valuesFormExport[] = new Oara_Curl_Parameter('sessionRecord.dateString', '');
		$valuesFormExport[] = new Oara_Curl_Parameter('allDatesFilter', 'false');
        $urls[] = new Oara_Curl_Request('http://www.traveljigsawgroup.com/affiliates/AffiliateSessionRecords.do', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
		if (!preg_match("/Password:/", $exportReport[0], $matches)){
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
        $merchants = Array();
        $obj = Array();
        $obj['cid'] = 1;
        $obj['name'] = 'Traveljigsaw';
        $obj['url'] = 'http://www.traveljigsawgroup.com';
        $merchants[] = $obj;
        
        return $merchants;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
     */
	public function getTransactionList($merchantList = null , Zend_Date $dStartDate = null , Zend_Date $dEndDate = null)
	{
		$totalTransactions = Array();

        $valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
													
        $valuesFormExport[] = new Oara_Curl_Parameter('startDayFilter', $dStartDate->toString("d"));
        $valuesFormExport[] = new Oara_Curl_Parameter('startMonthFilter', $dStartDate->toString("M"));
        $valuesFormExport[] = new Oara_Curl_Parameter('startYearFilter', $dStartDate->toString("yyyy"));
        $valuesFormExport[] = new Oara_Curl_Parameter('endDayFilter', $dEndDate->toString("d"));
        $valuesFormExport[] = new Oara_Curl_Parameter('endMonthFilter', $dEndDate->toString("M"));
        $valuesFormExport[] = new Oara_Curl_Parameter('endYearFilter', $dEndDate->toString("yyyy"));
        
        $valuesFormExport[] = new Oara_Curl_Parameter('origStartDayFilter', $dStartDate->toString("d"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origStartMonthFilter', $dStartDate->toString("M"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origStartYearFilter', $dStartDate->toString("yyyy"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origEndDayFilter', $dEndDate->toString("d"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origEndMonthFilter', $dEndDate->toString("M"));
		$valuesFormExport[] = new Oara_Curl_Parameter('origEndYearFilter', $dEndDate->toString("yyyy"));
		
		$valuesFormExport[] = new Oara_Curl_Parameter('bookingRecord.idString', '0');
        
        
       	$urls = array();
        $urls[] = new Oara_Curl_Request('http://www.traveljigsawgroup.com/affiliates/AffiliateBookingRecordsDownload.do', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
	    
	    $exportTransactionList = self::readTransactionRef($exportReport[0], $dStartDate,$dEndDate);
		foreach ($exportTransactionList as $exportTransaction){
			$transactionDate = new Zend_Date($exportTransaction[0], "d MMM yyyy HH:mm:ss",'en_US');
			if ($transactionDate->compare($dStartDate) >= 0 && $transactionDate->compare($dEndDate)<=0
				&& $exportTransaction[10] != 'Quote'){
				
				$transaction = array();
				
				$transaction['merchantId'] = 1;
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				$transaction['amount'] = (double) $exportTransaction[7];
				$transaction['commission'] = (double) $exportTransaction[8];
				if ($exportTransaction[10] == 'Completed'){
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else if ($exportTransaction[10] == 'Cancelled'){
					$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
				} else if ($exportTransaction[10] == 'Booked' || $exportTransaction[10] == 'Amended'){
					$transaction['status'] = Oara_Utilities::STATUS_PENDING;
				} else{
					throw new Exception('new state '. $exportTransaction[10]);
				}
				
				$totalTransactions[] = $transaction;
			}
		}
		
        return $totalTransactions;
        
	}
	
	/**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
     */
    public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
        $totalOverviews = Array();
        $transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
        foreach ($transactionArray as $merchantId => $merchantTransaction){
        	foreach ($merchantTransaction as $date => $transactionList){
        		
        		$overview = Array();
                                    
                $overview['merchantId'] = $merchantId;
                $overviewDate = new Zend_Date($date, "yyyy-MM-dd");
                $overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
                $overview['click_number'] = 0;
                $overview['impression_number'] = 0;
                $overview['transaction_number'] = 0;
                $overview['transaction_confirmed_value'] = 0;
                $overview['transaction_confirmed_commission']= 0;
                $overview['transaction_pending_value']= 0;
                $overview['transaction_pending_commission']= 0;
                $overview['transaction_declined_value']= 0;
                $overview['transaction_declined_commission']= 0;
                foreach ($transactionList as $transaction){
                	$overview['transaction_number'] ++;
                    if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
                    	$overview['transaction_confirmed_value'] += $transaction['amount'];
                    	$overview['transaction_confirmed_commission'] += $transaction['commission'];
                    } else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
                    	$overview['transaction_pending_value'] += $transaction['amount'];
                    	$overview['transaction_pending_commission'] += $transaction['commission'];
                    } else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
                    	$overview['transaction_declined_value'] += $transaction['amount'];
                    	$overview['transaction_declined_commission'] += $transaction['commission'];
                	}
        		}
                $totalOverviews[] = $overview;
        	}
        }
        
        return $totalOverviews;                                 	
    }
	
    private function readTransactionRef($csvExport, $dStartDate,$dEndDate){
    	$transactions = array();
    	$transactionRef = array();
    	$exportData = str_getcsv($csvExport,"\n");
        $num = count($exportData);
        for ($i = 3; $i < $num-2; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i],",");
       		if (count($transactionExportArray) <= 1){
            	throw new Exception ('Fail getting the transactions');
            }
            //to avoid the repeated transaction reference
            $transactionRef[$transactionExportArray[2]] = '';
        }
        
        $urls = array();
        foreach ($transactionRef as $ref => $nothing) {
        	$valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
													
	        $valuesFormExport[] = new Oara_Curl_Parameter('startDayFilter', $dStartDate->toString("d"));
	        $valuesFormExport[] = new Oara_Curl_Parameter('startMonthFilter', $dStartDate->toString("M"));
	        $valuesFormExport[] = new Oara_Curl_Parameter('startYearFilter', $dStartDate->toString("yyyy"));
	        $valuesFormExport[] = new Oara_Curl_Parameter('endDayFilter', $dEndDate->toString("d"));
	        $valuesFormExport[] = new Oara_Curl_Parameter('endMonthFilter', $dEndDate->toString("M"));
	        $valuesFormExport[] = new Oara_Curl_Parameter('endYearFilter', $dEndDate->toString("yyyy"));
	        
	        $valuesFormExport[] = new Oara_Curl_Parameter('origStartDayFilter', $dStartDate->toString("d"));
			$valuesFormExport[] = new Oara_Curl_Parameter('origStartMonthFilter', $dStartDate->toString("M"));
			$valuesFormExport[] = new Oara_Curl_Parameter('origStartYearFilter', $dStartDate->toString("yyyy"));
			$valuesFormExport[] = new Oara_Curl_Parameter('origEndDayFilter', $dEndDate->toString("d"));
			$valuesFormExport[] = new Oara_Curl_Parameter('origEndMonthFilter', $dEndDate->toString("M"));
			$valuesFormExport[] = new Oara_Curl_Parameter('origEndYearFilter', $dEndDate->toString("yyyy"));
			
			$valuesFormExport[] = new Oara_Curl_Parameter('bookingRecord.idString', $ref);
	        $urls[] = new Oara_Curl_Request('http://www.traveljigsawgroup.com/affiliates/AffiliateBookingRecord.do', $valuesFormExport);
        
        }
        if (count($urls) > 0) {
	        $exportReport = $this->_client->post($urls);
	        $num = count($exportReport);
	        for ($z = 0; $z < $num; $z++) {
	        	$doc = new DOMDocument();
			    libxml_use_internal_errors(true);
			    $doc->validateOnParse = true;
			    $doc->loadHTML($exportReport[$z]);
			    $tableList = $doc->getElementsByTagName('table');
			    
			    $exportData1 = self::htmlToCsv(self::DOMinnerHTML($tableList->item(6)));
			    $exportData3 = self::htmlToCsv(self::DOMinnerHTML($tableList->item(8)));
			    $exportStatus = self::htmlToCsv(self::DOMinnerHTML($tableList->item(9)));
			    
			    
				$obj = array();
				
		        $rowNum = count($exportData1);
		        for ($i = 1; $i < $rowNum -1; $i++) {
		        	$exportColumnData = str_getcsv($exportData1[$i], ";");
		        	$columnNum = count($exportColumnData);
		        	for ($j = 0; $j < $columnNum; $j++) {
		        		$obj[] = $exportColumnData[$j];
		        	}
		        	
		        }
		        
		        $rowNum = count($exportData3);
		        for ($i = 1; $i < $rowNum - 1; $i++) {
		        	$exportColumnData = str_getcsv($exportData3[$i], ";");
		        	$columnNum = count($exportColumnData);
		        	for ($j = 0; $j < $columnNum; $j++) {
		        		$obj[] = $exportColumnData[$j];
		        	}
		        	
		        }
				
		        $rowNum = count($exportStatus);
		        for ($i = $rowNum-2; $i < $rowNum-1; $i++) {
		        	$exportColumnData = str_getcsv($exportStatus[$i], ";");
		        	$columnNum = count($exportColumnData);
		        	for ($j = 0; $j < $columnNum; $j++) {
		        		$obj[] = $exportColumnData[$j];
		        	}
		        	
		        }
				
				
		    	$transactions[] = $obj;
	        }
        }
        
        return $transactions;
    }
	/**
     * 
     * Function that Convert from a table to Csv
     * @param unknown_type $html
     */
    private function htmlToCsv($html){
    	$html = str_replace(array("\t","\r","\n"), "", $html);
    	$csv = "";
    	$dom = new Zend_Dom_Query($html);
      	$resultsRow = $dom->query('tr');
      	$resultsRowCount = count($resultsRow);
      	foreach ($resultsRow as $result){
      		$dom = new Zend_Dom_Query(self::DOMinnerHTML($result));
      		$resultsColumn = $dom->query('td');
      		$resultsColumnCount = count($resultsColumn);
      		$i = 0;
			foreach ($resultsColumn as $resultColumn) {
				$value = $resultColumn->nodeValue;
				if ($i != $resultsColumnCount -1){
					$csv .= trim($value).";";
				} else {
					$csv .= trim($value);
				}
				$i++;
			}
			$csv .= "\n";
      	}
    	$exportData = str_getcsv($csv,"\n");
    	return $exportData;
    }
    /**
     * 
     * Function that returns the innet HTML code 
     * @param unknown_type $element
     */
	private function DOMinnerHTML($element)
	{
	    $innerHTML = "";
	    $children = $element->childNodes;
	    foreach ($children as $child)
	    {
	        $tmp_dom = new DOMDocument();
	        $tmp_dom->appendChild($tmp_dom->importNode($child, true));
	        $innerHTML.=trim($tmp_dom->saveHTML());
	    }
	    return $innerHTML;
	} 
}