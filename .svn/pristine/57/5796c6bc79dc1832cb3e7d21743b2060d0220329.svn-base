<?php
$classFile = 'BCGcode39extended.php';
$className = 'BCGcode39extended';
$baseClassFile = 'BCGBarcode1D.php';
$codeVersion = '6.0.0';

function customSetup($barcode, $get)
{
    if (isset($get['checksum'])) {
        $barcode->setChecksum($get['checksum'] === '1' ? true : false);
    }
}
