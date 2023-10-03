<?
/*  FUNCTION Add2AHSTransactions
  Parameters:
	aTransactionType 	- int
	aSerialnumber 	- int
	aDateCreated	- timestamp
	aCreatedBy		- string
	aDebitCCID		- int
	aCreditCCID		- int
	aAmount     	- currency
	aDescription	- string
	aMarked		- bool
	aTag			- int
*/

require_once("../include/const.inc.php");

function Add2AHsTransactions(	$aTransactionType,
				$aSerialnumber,
				$aDateCreated,
				$aCreatedBy,
				$debitAHID,
				$creditAHID,
				$aAmount,
				$aDescription,
				$aMarked,
				$aTag) {

	$aDescription = strtoupper($aDescription);
	$aCreatedBy = strtoupper($aCreatedBy);
					

	$ahTransQuery = new Query("
			INSERT INTO AHsTransactions (
				TypeID,
				SerialNumber,
				DateCreated,
				AH1ID,
				CreatedBy,
				Debit,
				AH2ID,
				Description,
				Marked,
				Tag)
			VALUES (
				$aTransactionType,
				$aSerialnumber,
				'$aDateCreated',
				$debitAHID,
				'".addquotes($aCreatedBy)."',
				".(-$aAmount).",
				$creditAHID,
				'".addquotes($aDescription)."',
				$aMarked,
				$aTag
			)");
		
	$ahTransQuery->Query("
			INSERT INTO AHsTransactions (
				TypeID,
				SerialNumber,
				DateCreated,
				AH1ID,
				CreatedBy,
				Credit,
				AH2ID,
				Description,
				Marked,
				Tag)
			VALUES (
				$aTransactionType,
				$aSerialnumber,
				'$aDateCreated',
				$creditAHID,
				'".addquotes($aCreatedBy)."',
				".($aAmount).",
				$debitAHID,
				'".addquotes($aDescription)."',
				$aMarked,
				$aTag
			)");
		$ahTransQuery->Free();
		
	// update balances in the main account heads table
		
	$monthPrefix = getMonthName($_SESSION['monthLoaded']);
	$ahQuery = new QueryYear("select ".$monthPrefix."ClosingBalance, ".$monthPrefix."Debit from AHs where ID=$debitAHID"); // or die "Error in Add2CCsTransactions";
	$ahAmount = $ahQuery->FieldByName($monthPrefix."Debit")-$aAmount;
	$ahClosingBalance = $ahQuery->FieldByName($monthPrefix."ClosingBalance")-$aAmount;
	$ahQuery->ExecuteQuery("UPDATE AHs SET ".$monthPrefix."Debit=$ahAmount, ".$monthPrefix."ClosingBalance=$ahClosingBalance WHERE ID=$debitAHID");
	
		
	$ahQuery = new QueryYear("select ".$monthPrefix."ClosingBalance, ".$monthPrefix."Credit from AHs where ID=$creditAHID"); // or die "Error in Add2CCsTransactions";
	$ahAmount = $ahQuery->FieldByName($monthPrefix."Credit")+$aAmount;
	$ahClosingBalance = $ahQuery->FieldByName($monthPrefix."ClosingBalance")+$aAmount;
	$ahQuery->ExecuteQuery("UPDATE AHs SET ".$monthPrefix."Credit=$ahAmount, ".$monthPrefix."ClosingBalance=$ahClosingBalance WHERE ID=$creditAHID");
	
	$ahQuery->Free();
	
}		



/*  FUNCTION Delete2CCSTransactions
  Parameters:
	TransactionType	- int
	Serialnumber 	- int
	aDebitCCID,
	aCreditCCID	- int
	aAmount     	- currency
*/

function Delete2AHsTransactions($aTransactionType,
				$aSerialNumber,
				$debitAHID,
				$creditAHID,
				$aAmount) {


				
	$delQuery = new Query("DELETE FROM AHsTransactions where 
						TypeID=$aTransactionType AND
						SerialNumber=$aSerialNumber AND
						AH1ID=$debitAHID");
		
						
	$delQuery->Query("DELETE FROM AHsTransactions where 
						TypeID=$aTransactionType AND
						SerialNumber=$aSerialNumber AND
						AH1ID=$creditAHID");
	
	// update balances in the main account heads table
		
	$monthPrefix = getMonthName($_SESSION['monthLoaded']);
	$ahQuery = new QueryYear("select ".$monthPrefix."ClosingBalance, ".$monthPrefix."Debit from AHs where ID=$debitAHID"); // or die "Error in Add2CCsTransactions";
	$ahAmount = $ahQuery->FieldByName($monthPrefix."Debit")+$aAmount;
	$ahClosingBalance = $ahQuery->FieldByName($monthPrefix."ClosingBalance")+$aAmount;
	$ahQuery->ExecuteQuery("UPDATE AHs SET ".$monthPrefix."Debit=$ahAmount, ".$monthPrefix."ClosingBalance=$ahClosingBalance WHERE ID=$debitAHID");
	
		
	$ahQuery = new QueryYear("select ".$monthPrefix."ClosingBalance, ".$monthPrefix."Credit from AHs where ID=$creditAHID"); // or die "Error in Add2CCsTransactions";
	$ahAmount = $ahQuery->FieldByName($monthPrefix."Credit")-$aAmount;
	$ahClosingBalance = $ahQuery->FieldByName($monthPrefix."ClosingBalance")-$aAmount;
	$ahQuery->ExecuteQuery("UPDATE AHs SET ".$monthPrefix."Credit=$ahAmount, ".$monthPrefix."ClosingBalance=$ahClosingBalance WHERE ID=$creditAHID");
		
	$ahQuery->Free();						
								
	$delQuery->Free();
}		



?>