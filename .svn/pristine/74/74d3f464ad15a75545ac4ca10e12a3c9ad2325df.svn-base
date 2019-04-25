<?php
$classFile = 'BCGs25.php';
$className = 'BCGs25';
$baseClassFile = 'BCGBarcode1D.php';
$codeVersion = '6.0.0';

function customSetup($barcode, $get)
{
    if (isset($get['checksum'])) {
        $barcode->setChecksum($get['checksum'] === '1' ? true : false);
    }
}
