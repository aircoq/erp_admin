<?php
namespace app\common\service;

use  Picqer\Barcode\BarcodeGeneratorPNG;

class Barcode
{
    private $generator = null;
    
    public function __construct($picType)
    {
        switch (strtolower($picType)) {
            case 'png':
                $this->generator = new BarcodeGeneratorPNG;
            break;
        }
    }
    
    /**
     * Return a PNG image representation of barcode (requires GD or Imagick library).
     *
     * @param string $code code to print
     * @param string $type type of barcode:
     * @param int $widthFactor Width of a single bar element in pixels.
     * @param int $totalHeight Height of a single bar element in pixels.
     * @param array $color RGB (0-255) foreground color for bar elements (background is transparent).
     * @return string image data or false in case of error.
     * @public
     */
    public function create($code,  $widthFactor = 2, $totalHeight = 30, $color = [0, 0, 0])
    {
       return $this->generator->getBarcode($code, BarcodeGeneratorPNG::TYPE_CODE_128, $widthFactor, $totalHeight, $color);
    }
}
