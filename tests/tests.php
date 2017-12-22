<?php

use PHPUnit\Framework\TestCase;

class ImageNowTest extends TestCase
{
    /**
     * @expectedException \Now\ImageNowException
     * @expectedExceptionMessage File does not exist
     */
    public function testLoadNoFile()
    {
        new \Now\ImageNow(null);
    }

    /**
     * @expectedException \Now\ImageNowException
     * @expectedExceptionMessage Could not read file
     */
    public function testLoadUnsupportedFile()
    {
        new \Now\ImageNow(__FILE__);
    }

    public function testResizeImage()
    {
        $image = new \Now\ImageNow($this->_createImage(1024, 768, IMAGETYPE_JPEG));

        $image->resize(100, 100);

        $this->assertEquals(100, $this->_getPrivateProperty($image, '_imageWidth'));

        $this->assertEquals(75, $this->_getPrivateProperty($image, '_imageHeight'));
    }

    public function testCropImage()
    {
        $image = new \Now\ImageNow($this->_createImage(1024, 768, IMAGETYPE_JPEG));

        $image->crop(100, 100);

        $this->assertEquals(100, $this->_getPrivateProperty($image, '_imageWidth'));

        $this->assertEquals(100, $this->_getPrivateProperty($image, '_imageHeight'));
    }

    public function testRotateImage()
    {
        $image = new \Now\ImageNow($this->_createImage(1024, 768, IMAGETYPE_JPEG));

        $image->rotate(90);

        $this->assertEquals(768, $this->_getPrivateProperty($image, '_imageWidth'));

        $this->assertEquals(1024, $this->_getPrivateProperty($image, '_imageHeight'));
    }

    private function _getPrivateProperty($objectWithPrivateProperty, $propertyName)
    {
        $reflectionClass = new ReflectionClass('\Now\ImageNow');

        $property = $reflectionClass->getProperty($propertyName);

        $property->setAccessible(true);

        return $property->getValue($objectWithPrivateProperty);
    }

    private function _createImage($width, $height, $type)
    {
        $fileName = tempnam(sys_get_temp_dir(), 'test_image');

        $imageResource = imagecreatetruecolor($width, $height);

        switch($type)
        {
            case IMAGETYPE_PNG:
                imagepng($imageResource, $fileName);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($imageResource, $fileName);
                break;
            case IMAGETYPE_GIF:
                imagegif($imageResource, $fileName);
                break;
        }

        return $fileName;
    }
}