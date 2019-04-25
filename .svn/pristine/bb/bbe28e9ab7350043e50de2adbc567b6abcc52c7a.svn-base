<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Parse Exception
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

class BCGParseException extends \Exception
{
    protected $barcode;

    /**
     * Constructor with specific message for a parameter.
     *
     * @param string $barcode
     * @param string $message
     */
    public function __construct($barcode, $message)
    {
        $this->barcode = $barcode;
        parent::__construct($message, 10000);
    }
}
