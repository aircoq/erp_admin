<?php
// $Id: EbatNs_Convert.php,v 1.2 2013-04-05 11:15:59 thomasbiniasch Exp $
// $Log: EbatNs_Convert.php,v $
// Revision 1.2  2013-04-05 11:15:59  thomasbiniasch
// bugfixes and template updates, first running version milestone!
//
// Revision 1.2  2008-05-02 15:04:05  carsten
// Initial, PHP5
//
//
require_once 'AmountType.php';
require_once 'CategoryType.php';

class EbatNs_Convert
{
	static function ToAmount($amountValue, $currency)
	{
		$a = new AmountType();
		$a->setTypeValue($amountValue);
		$a->setTypeAttribute('currencyID', $currency);
		
		return $a;
	}
	
	static function ToCategory($categoryId)
	{
		$category = new CategoryType();
		$category->CategoryID = $categoryId;
		return $category;
	}
}

?>
