<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Base class for Barcode2D
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

abstract class BCGBarcode2D extends BCGBarcode
{
    protected $scaleX;
    protected $scaleY;            // ScaleX and Y multiplied by the scale

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->setScaleX(1);
        $this->setScaleY(1);
    }

    /**
     * Returns the maximal size of a barcode.
     *
     * @param int $w
     * @param int $h
     * @return int[]
     */
    public function getDimension($w, $h)
    {
        return parent::getDimension($w * $this->scaleX, $h * $this->scaleY);
    }

    /**
     * Sets the scale of the barcode in pixel for X.
     * If the scale is lower than 1, an exception is raised.
     *
     * @param int $scaleX
     */
    protected function setScaleX($scaleX)
    {
        $scaleX = intval($scaleX);
        if ($scaleX <= 0) {
            throw new ArgumentException('The scale must be larger than 0.', 'scaleX');
        }

        $this->scaleX = $scaleX;
    }

    /**
     * Sets the scale of the barcode in pixel for Y.
     * If the scale is lower than 1, an exception is raised.
     *
     * @param int $scaleY
     */
    protected function setScaleY($scaleY)
    {
        $scaleY = intval($scaleY);
        if ($scaleY <= 0) {
            throw new ArgumentException('The scale must be larger than 0.', 'scaleY');
        }

        $this->scaleY = $scaleY;
    }

    /**
     * Draws the text.
     * The coordinate passed are the positions of the barcode.
     * $x1 and $y1 represent the top left corner.
     * $x2 and $y2 represent the bottom right corner.
     *
     * @param resource $im
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     */
    protected function drawText($im, $x1, $y1, $x2, $y2)
    {
        foreach ($this->labels as $label) {
            $label->draw(
                $im,
                ($x1 + $this->offsetX) * $this->scale * $this->scaleX + $this->pushLabel[0],
                ($y1 + $this->offsetY) * $this->scale * $this->scaleY + $this->pushLabel[1],
                ($x2 + $this->offsetX) * $this->scale * $this->scaleX + $this->pushLabel[0],
                ($y2 + $this->offsetY) * $this->scale * $this->scaleY + $this->pushLabel[1]
            );
        }
    }

    /**
     * Draws 1 pixel on the resource at a specific position with a determined color.
     *
     * @param resource $im
     * @param int $x
     * @param int $y
     * @param int $color
     */
    protected function drawPixel($im, $x, $y, $color = self::COLOR_FG)
    {
        $scaleX = $this->scale * $this->scaleX;
        $scaleY = $this->scale * $this->scaleY;

        $xR = ($x + $this->offsetX) * $scaleX + $this->pushLabel[0];
        $yR = ($y + $this->offsetY) * $scaleY + $this->pushLabel[1];

        // We always draw a rectangle
        imagefilledrectangle(
            $im,
            $xR,
            $yR,
            $xR + $scaleX - 1,
            $yR + $scaleY - 1,
            $this->getColor($im, $color)
        );
    }

    /**
     * Draws an empty rectangle on the resource at a specific position with a determined color.
     *
     * @param resource $im
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @param int $color
     */
    protected function drawRectangle($im, $x1, $y1, $x2, $y2, $color = BCGBarcode::COLOR_FG)
    {
        $scaleX = $this->scale * $this->scaleX;
        $scaleY = $this->scale * $this->scaleY;

        if ($this->scale === 1) {
            imagefilledrectangle(
                $im,
                ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0],
                ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1],
                ($x2 + $this->offsetX) * $scaleX + $this->pushLabel[0],
                ($y2 + $this->offsetY) * $scaleY + $this->pushLabel[1],
                $this->getColor($im, $color)
            );
        } else {
            imagefilledrectangle($im, ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0], ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1], ($x2 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0], ($y1 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1], $this->getColor($im, $color));
            imagefilledrectangle($im, ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0], ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1], ($x1 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0], ($y2 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1], $this->getColor($im, $color));
            imagefilledrectangle($im, ($x2 + $this->offsetX) * $scaleX + $this->pushLabel[0], ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1], ($x2 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0], ($y2 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1], $this->getColor($im, $color));
            imagefilledrectangle($im, ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0], ($y2 + $this->offsetY) * $scaleY + $this->pushLabel[1], ($x2 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0], ($y2 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1], $this->getColor($im, $color));
        }
    }

    /**
     * Draws a filled rectangle on the resource at a specific position with a determined color.
     *
     * @param resource $im
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @param int $color
     */
    protected function drawFilledRectangle($im, $x1, $y1, $x2, $y2, $color = BCGBarcode::COLOR_FG)
    {
        if ($x1 > $x2) { // Swap
            $x1 ^= $x2 ^= $x1 ^= $x2;
        }

        if ($y1 > $y2) { // Swap
            $y1 ^= $y2 ^= $y1 ^= $y2;
        }

        $scaleX = $this->scale * $this->scaleX;
        $scaleY = $this->scale * $this->scaleY;

        imagefilledrectangle(
            $im,
            ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0],
            ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1],
            ($x2 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0],
            ($y2 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1],
            $this->getColor($im, $color)
        );
    }
}
