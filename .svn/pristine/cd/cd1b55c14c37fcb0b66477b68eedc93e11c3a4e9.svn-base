<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Holds font family and size.
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

class BCGFontInfo
{
    private $box;

    public function __construct($box)
    {
        $this->box = $box;
    }

    public function getBox()
    {
        return $this->box;
    }

    public function getAscender()
    {
        return abs($this->box[7]);
    }

    public function getDescender()
    {
        return abs($this->box[1] > 0 ? $this->box[1] : 0);
    }

    public function getWidth()
    {
        // We drew at 0, so even if the box starts at 1, we need more space
        // So we don't do -box[0].
        return max($this->box[2], $this->box[4]);
    }

    public function getHeight()
    {
        $minY = min(array($this->box[1], $this->box[3], $this->box[5], $this->box[7]));
        $maxY = max(array($this->box[1], $this->box[3], $this->box[5], $this->box[7]));
        return $maxY - $minY;
    }
}

class BCGFontFile implements BCGFont
{
    private $path;
    private $size;
    private $text = '';
    private $foregroundColor;
    private $rotationAngle;
    private $fontInfo; // BCGFontInfo
    private $descenderSize;

    /**
     * Constructor.
     *
     * @param string $fontPath path to the file
     * @param int $size size in point
     */
    public function __construct($fontPath, $size)
    {
        if (!file_exists($fontPath)) {
            throw new BCGArgumentException('The font path is incorrect.', 'fontPath');
        }

        $this->path = $fontPath;
        $this->size = $size;
        $this->foregroundColor = new BCGColor('black');
        $this->setRotationAngle(0);
    }

    /**
     * Gets the text associated to the font.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets the text associated to the font.
     *
     * @param string text
     */
    public function setText($text)
    {
        $this->text = $text;
        $this->fontInfo = null;
    }

    /**
     * Gets the rotation in degree.
     *
     * @return int
     */
    public function getRotationAngle()
    {
        return (360 - $this->rotationAngle) % 360;
    }

    /**
     * Sets the rotation in degree.
     *
     * @param int
     */
    public function setRotationAngle($rotationAngle)
    {
        $this->rotationAngle = (int)$rotationAngle;
        if ($this->rotationAngle !== 90 && $this->rotationAngle !== 180 && $this->rotationAngle !== 270) {
            $this->rotationAngle = 0;
        }

        $this->rotationAngle = (360 - $this->rotationAngle) % 360;

        $this->fontInfo = null;
    }

    /**
     * Gets the background color.
     *
     * @return BCGColor
     */
    public function getBackgroundColor()
    {
    }

    /**
     * Sets the background color.
     *
     * @param BCGColor $backgroundColor
     */
    public function setBackgroundColor($backgroundColor)
    {
    }

    /**
     * Gets the foreground color.
     *
     * @return BCGColor
     */
    public function getForegroundColor()
    {
        return $this->foregroundColor;
    }

    /**
     * Sets the foreground color.
     *
     * @param BCGColor $foregroundColor
     */
    public function setForegroundColor($foregroundColor)
    {
        $this->foregroundColor = $foregroundColor;
    }

    /**
     * Returns the width and height that the text takes to be written.
     *
     * @return int[]
     */
    public function getDimension()
    {
        $fontInfo = $this->getFontInfo();
        $rotationAngle = $this->getRotationAngle();
        $w = $fontInfo->getWidth();
        $h = $fontInfo->getHeight();
        if ($rotationAngle === 90 || $rotationAngle === 270) {
            return array($h, $w);
        } else {
            return array($w, $h);
        }
    }

    /**
     * Draws the text on the image at a specific position.
     * $x and $y represent the left bottom corner.
     *
     * @param resource $im
     * @param int $x
     * @param int $y
     */
    public function draw($im, $x, $y)
    {
        $drawingPosition = $this->getDrawingPosition($x, $y);
        imagettftext($im, $this->size, $this->rotationAngle, $drawingPosition[0], $drawingPosition[1], $this->foregroundColor->allocate($im), $this->path, $this->text);
    }

    private function getDrawingPosition($x, $y)
    {
        $fontInfo = $this->getFontInfo();
        $dimension = $this->getDimension();
        $rotationAngle = $this->getRotationAngle();

        if ($rotationAngle === 0) {
            $y += $fontInfo->getAscender();
        } elseif ($rotationAngle === 90) {
            $x += $fontInfo->getDescender();
        } elseif ($rotationAngle === 180) {
            $x += $dimension[0];
            $y += $fontInfo->getDescender();
        } elseif ($rotationAngle === 270) {
            $x += $fontInfo->getAscender();
            $y += $dimension[1];
        }

        return array($x, $y);
    }

    private function getFontInfo()
    {
        if ($this->fontInfo === null) {
            $box = imagettfbbox($this->size, 0, $this->path, $this->text);
            $this->fontInfo = new BCGFontInfo($box);
        }

        return $this->fontInfo;
    }
}
