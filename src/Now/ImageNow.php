<?php

namespace Now;

class ImageNow
{
    const WATERMARK_POSITION_TOP_LEFT = 1;
    const WATERMARK_POSITION_TOP_CENTER = 2;
    const WATERMARK_POSITION_TOP_RIGHT = 3;
    const WATERMARK_POSITION_CENTER_LEFT = 4;
    const WATERMARK_POSITION_CENTER_CENTER = 5;
    const WATERMARK_POSITION_CENTER_RIGHT = 6;
    const WATERMARK_POSITION_BOTTOM_LEFT = 7;
    const WATERMARK_POSITION_BOTTOM_CENTER = 8;
    const WATERMARK_POSITION_BOTTOM_RIGHT = 9;

    /**
     * Path of the image.
     *
     * @var string
     */
    private $_imagePath;

    /**
     * Mime type of the image.
     *
     * @var string
     */
    private $_imageMimeType;

    /**
     * Resource of the image.
     *
     * @var resource
     */
    private $_imageResource;

    /**
     * Width of the origin image.
     *
     * @var int
     */
    private $_imageWidth;

    /**
     * Height of the image.
     *
     * @var int
     */
    private $_imageHeight;

    /**
     * Aspect ratio of the image.
     *
     * @var float|int
     */
    private $_imageAspectRatio;

    /**
     * Jpeg quality for destination image.
     *
     * @var int
     */
    private $_jpegQuality = 90;

    /**
     * Png quality for destination image.
     *
     * @var int
     */
    private $_pngQuality = 6;

    /**
     * ImageNow constructor.
     *
     * @param string $imagePath
     * @throws ImageNowException
     */
    public function __construct($imagePath = '')
    {
        if(empty($imagePath) || ! is_readable($imagePath))
        {
            throw new ImageNowException('File does not exist');
        }

        $imageInfo = getimagesize($imagePath);

        if($imageInfo === false)
        {
            throw new ImageNowException('Could not read file');
        }

        $this->_imageMimeType = image_type_to_mime_type($imageInfo[2]);

        switch($this->_imageMimeType)
        {
            case 'image/jpeg':
                $imageResource = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $imageResource = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $imageResource = imagecreatefromgif($imagePath);
                break;
            default:
                throw new ImageNowException('Unsupported image mime type');
                break;
        }

        $this->_imagePath = $imagePath;

        $this->_updateImageInfo($imageResource);
    }

    /**
     * Preserve transparency for png and gif images.
     *
     * @param resource $imageResource
     * @return resource
     */
    private function _preserveTransparency($imageResource)
    {
        if($this->_imageMimeType =='image/png')
        {
            imagealphablending($imageResource, false);

            imagesavealpha($imageResource, true);
        }

        if($this->_imageMimeType == 'image/gif')
        {
            $backgroundColor = imagecolorallocate($imageResource, 255, 255, 255);

            $backgroundColor = imagecolortransparent($imageResource, $backgroundColor);

            imagefill($imageResource, 0, 0, $backgroundColor);
        }

        return $imageResource;
    }

    /**
     * Update image info.
     *
     * @param resource $imageResource
     */
    private function _updateImageInfo($imageResource)
    {
        $this->_imageResource = $imageResource;

        $this->_imageWidth = imagesx($imageResource);

        $this->_imageHeight = imagesy($imageResource);

        $this->_imageAspectRatio = $this->_imageWidth / $this->_imageHeight;
    }

    /**
     * Set jpeg quality for destination image.
     *
     * @param int $quality
     * @return $this
     */
    public function setJpegQuality($quality = 90)
    {
        $this->_jpegQuality = $quality;

        return $this;
    }

    /**
     * Set png quality for destination image.
     *
     * @param int $quality
     * @return $this
     */
    public function setPngQuality($quality = 6)
    {
        $this->_pngQuality = $quality;

        return $this;
    }

    /**
     * Resize image.
     *
     * @param int|string $width
     * @param int|string $height
     * @return $this
     */
    public function resize($width, $height)
    {
        if($width / $height > $this->_imageAspectRatio)
        {
            $width = round($height * $this->_imageAspectRatio);
        }
        else
        {
            $height = round($width / $this->_imageAspectRatio);
        }

        $imageResource = imagecreatetruecolor($width, $height);

        $imageResource = $this->_preserveTransparency($imageResource);

        imagecopyresampled(
            $imageResource,
            $this->_imageResource,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $this->_imageWidth,
            $this->_imageHeight
        );

        $this->_updateImageInfo($imageResource);

        return $this;
    }

    /**
     * Crop image.
     *
     * @param $width
     * @param $height
     * @return $this
     */
    public function crop($width, $height)
    {
        $xPosition = 0;

        $yPosition = 0;

        if($width / $height > $this->_imageAspectRatio)
        {
            $tmpWidth = $width;

            $tmpHeight = round($width / $this->_imageAspectRatio);

            $yPosition = round(($tmpHeight - $height) / 2);
        }
        else
        {
            $tmpWidth = round($height * $this->_imageAspectRatio);

            $tmpHeight = $height;

            $xPosition = round(($tmpWidth - $width) / 2);
        }

        $tmpImageResource = imagecreatetruecolor($tmpWidth, $tmpHeight);

        $tmpImageResource = $this->_preserveTransparency($tmpImageResource);

        imagecopyresampled(
            $tmpImageResource,
            $this->_imageResource,
            0,
            0,
            0,
            0,
            $tmpWidth,
            $tmpHeight,
            $this->_imageWidth,
            $this->_imageHeight
        );

        $imageResource = imagecreatetruecolor($width, $height);

        $imageResource = $this->_preserveTransparency($imageResource);

        imagecopy(
            $imageResource,
            $tmpImageResource,
            0,
            0,
            $xPosition,
            $yPosition,
            $width,
            $height
        );

        $this->_updateImageInfo($imageResource);

        return $this;
    }

    /**
     * Rotate image.
     *
     * @param int $degrees
     * @return $this
     */
    public function rotate($degrees = 0)
    {
        $backgroundColor = imagecolorallocatealpha($this->_imageResource, 255, 255, 255, 127);

        $imageResource = imagerotate($this->_imageResource, $degrees, $backgroundColor);

        $this->_updateImageInfo($imageResource);

        return $this;
    }

    /**
     * Blur image.
     *
     * @param int $blurFactor
     * @return $this
     */
    public function blur($blurFactor = 3)
    {
        for($i = 0; $i < $blurFactor; $i++)
        {
            imagefilter($this->_imageResource, IMG_FILTER_GAUSSIAN_BLUR);
        }

        return $this;
    }

    /**
     * Watermark for destination image.
     *
     * @param string $imagePath
     * @param $watermarkPosition
     * @return $this
     * @throws ImageNowException
     */
    public function watermark($imagePath = '', $watermarkPosition)
    {
        if(empty($imagePath) || ! is_readable($imagePath))
        {
            throw new ImageNowException('File does not exist');
        }

        $imageInfo = getimagesize($imagePath);

        if($imageInfo === false)
        {
            throw new ImageNowException('Could not read file');
        }

        $imageMimeType = image_type_to_mime_type($imageInfo[2]);

        switch($imageMimeType)
        {
            case 'image/jpeg':
                $imageResource = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $imageResource = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $imageResource = imagecreatefromgif($imagePath);
                break;
            default:
                throw new ImageNowException('Unsupported image mime type');
                break;
        }

        $imageWidth = imagesx($imageResource);

        $imageHeight = imagesy($imageResource);

        switch($watermarkPosition)
        {
            case self::WATERMARK_POSITION_TOP_LEFT:
                $destinationX = 0;
                $destinationY = 0;
                break;
            case self::WATERMARK_POSITION_TOP_CENTER:
                $destinationX = round(($this->_imageWidth - $imageWidth) / 2);
                $destinationY = 0;
                break;
            case self::WATERMARK_POSITION_CENTER_LEFT:
                $destinationX = 0;
                $destinationY = round(($this->_imageHeight - $imageHeight) / 2);
                break;
            case self::WATERMARK_POSITION_CENTER_CENTER:
                $destinationX = round(($this->_imageWidth - $imageWidth) / 2);
                $destinationY = round(($this->_imageHeight - $imageHeight) / 2);
                break;
            case self::WATERMARK_POSITION_CENTER_RIGHT:
                $destinationX = $this->_imageWidth - $imageWidth;
                $destinationY = round(($this->_imageHeight - $imageHeight) / 2);
                break;
            case self::WATERMARK_POSITION_BOTTOM_LEFT:
                $destinationX = 0;
                $destinationY = $this->_imageHeight - $imageHeight;
                break;
            case self::WATERMARK_POSITION_BOTTOM_CENTER:
                $destinationX = round(($this->_imageWidth - $imageWidth) / 2);
                $destinationY = $this->_imageHeight - $imageHeight;
                break;
            case self::WATERMARK_POSITION_BOTTOM_RIGHT:
                $destinationX = $this->_imageWidth - $imageWidth;
                $destinationY = $this->_imageHeight - $imageHeight;
                break;
        }

        imagecopy($this->_imageResource, $imageResource, $destinationX, $destinationY, 0, 0, $imageWidth, $imageHeight);

        return $this;
    }

    /**
     * Save image.
     *
     * @param string $destinationImagePath
     * @throws ImageNowException
     */
    public function save($destinationImagePath = '')
    {
        if(empty($destinationImagePath))
        {
            $destinationImagePath = $this->_imagePath;
        }

        switch($this->_imageMimeType)
        {
            case 'image/jpeg':
                imagejpeg($this->_imageResource, $destinationImagePath, $this->_jpegQuality);
                break;
            case 'image/png':
                imagepng($this->_imageResource, $destinationImagePath, $this->_pngQuality);
                break;
            case 'image/gif':
                imagegif($this->_imageResource, $destinationImagePath);
                break;
        }

        imagedestroy($this->_imageResource);
    }
}
