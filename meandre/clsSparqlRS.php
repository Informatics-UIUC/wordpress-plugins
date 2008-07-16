<?php

class SparqlRecordSet {

	var $arrResult;
	var $intCurrRecord;
	var $intRecordCount;
	
	function __construct($arrResult) {
		$this->arrResult = $arrResult;
		$this->intCurrRecord = 0;
		$this->intRecordCount = sizeof($arrResult);
	}
	
	function RowCount() {
		return $this->intRecordCount;
	}
	
	function Move($intInRecord) {
		$this->intCurrRecord = $intInRecord;
	}
	
	function MoveFirst() {
		$this->Move(0);
	}
	
	function getRow() {
		if ($this->intCurrRecord == $this->intRecordCount) {
			return false;
		}

		$arrThisRow = $this->arrResult[$this->intCurrRecord];
		$this->intCurrRecord++;
		
		if (!is_array($arrThisRow)) {
			return false;
		}
		
		$arrRow = array();
		
		foreach ($arrThisRow as $strThisKey => $objThisVal) {
			if (is_object($arrThisRow[$strThisKey])) {
				$arrRow[$strThisKey] = $objThisVal->getLabel();
			}
		}
		return $arrRow;
		
	}

}

?>