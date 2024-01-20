<?php

declare(strict_types=1);

namespace Ccmiller2018\ImageEditor;

use Ccmiller2018\ImageEditor\Exceptions\ImageEditorException;
use GdImage;
use Throwable;

class ImageEditor
{
    const array EXTENSION_MAP = [
        'bmp',
        'gif',
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    private ?GdImage $image = null;
    private ?string $fileType = null;

    private ?string $targetType = null;

    private ?int $initialWidth = null;
    private ?int $initialHeight = null;
    private ?int $targetWidth = null;
    private ?int $targetHeight = null;

    public function setImage(GdImage $image): self
    {
        $this->image = $image;

        return $this;
    }

    private function setFileType(?string $fileType): void
    {
        $this->fileType = $fileType;
    }

    public function setInitialWidth(?int $width): void
    {
        $this->initialWidth = $width;
    }

    public function setInitialHeight(?int $height): void
    {
        $this->initialHeight = $height;
    }

    private function setTargetWidth(?int $width): void
    {
        $this->targetWidth = $width;
    }

    private function setTargetHeight(?int $height): void
    {
        $this->targetHeight = $height;
    }

    public function getImage(): ?GdImage
    {
        return $this->image;
    }

    private function getFileType(): ?string
    {
        return $this->fileType;
    }

    private function getTargetType(): ?string
    {
        return $this->targetType;
    }

    private function getTargetWidth(): ?int
    {
        return $this->targetWidth;
    }

    private function getTargetHeight(): ?int
    {
        return $this->targetHeight;
    }

    private function getInitialWidth(): ?int
    {
        return $this->initialWidth;
    }

    private function getInitialHeight(): ?int
    {
        return $this->initialHeight;
    }

    /**
     * @throws ImageEditorException
     */
    public function loadImage(string $filePath): self
    {
        $pathInfo = pathinfo($filePath);
        $fileExtension = $pathInfo['extension'] ?? null;

        if (!$fileExtension === null) {
            throw new ImageEditorException('Unable to read file extension');
        }

        if (!in_array($fileExtension, self::EXTENSION_MAP)) {
            throw new ImageEditorException("{$fileExtension} is not accepted");
        }

        if ($fileExtension === 'jpg') {
            $fileExtension = 'jpeg';
        }

        $functionName = "imagecreatefrom{$fileExtension}";

        try {
            $image = $this->openImageWithAlpha($functionName, $filePath);
        } catch (Throwable) {
            throw new ImageEditorException("{$filePath} could not be read safely");
        }

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("{$filePath} could not be read safely");
        }

        $this->setImage($image);
        $this->setFileType($fileExtension);

        list($width, $height) = getimagesize($filePath);

        $this->setInitialWidth($width);
        $this->setInitialHeight($height);
        $this->setTargetWidth($width);
        $this->setTargetHeight($height);

        return $this;
    }

    private function openImageWithAlpha(string $functionName, string $filePath): GdImage
    {
        $image = call_user_func($functionName, $filePath);

        $trueColorImage = imagecreatetruecolor(imagesx($image), imagesy($image));

        imagealphablending($trueColorImage, false);

        $transparentColor = imagecolorallocatealpha($trueColorImage, 0, 0, 0, 127);

        imagefill($trueColorImage, 0, 0, $transparentColor);

        imagesavealpha($trueColorImage, true);

        imagecopy($trueColorImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        imagealphablending($trueColorImage, true);

        imagedestroy($image);

        return $trueColorImage;
    }

    /**
     * @throws ImageEditorException
     */
    public function as(string $type): self
    {
        if (!in_array($type, self::EXTENSION_MAP)) {
            throw new ImageEditorException("{$type} is not accepted");
        }

        if ($type === 'jpg') {
            $type = 'jpeg';
        }

        $this->targetType = $type;

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function saveTo(string $path)
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        $type = $this->getTargetType();

        if ($type === null) {
            $type = $this->getFileType();
        }

        if (!in_array($type, self::EXTENSION_MAP)) {
            throw new ImageEditorException("{$type} is not accepted");
        }

        if ($type === 'jpg') {
            $type = 'jpeg';
        }

        $functionName = "image{$type}";

        return call_user_func($functionName, $image, $path);
    }

    /**
     * @throws ImageEditorException
     */
    public function resize(int $width, int $height): self
    {
        $sourceImage = $this->getImage();

        if (!$sourceImage instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        $this->setTargetWidth($width);
        $this->setTargetHeight($height);

        $image = imagecreatetruecolor($this->getTargetWidth(), $this->getTargetHeight());

        imagecopyresampled(
            $image,
            $sourceImage,
            0,
            0,
            0,
            0,
            $this->getTargetWidth(),
            $this->getTargetHeight(),
            $this->getInitialWidth(),
            $this->getInitialHeight()
        );

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function sepia(): self
    {
        $source = $this->getImage();

        if (!$source instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        $width = $this->getTargetWidth();
        $height = $this->getTargetHeight();

        $finalImage = imagecreatetruecolor($width, $height);

        imagesavealpha($finalImage, true);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $pixelColor = imagecolorat($source, $x, $y);

                $alpha = ($pixelColor >> 24) & 0xFF;
                $red = ($pixelColor >> 16) & 0xFF;
                $green = ($pixelColor >> 8) & 0xFF;
                $blue = $pixelColor & 0xFF;

                $newRed = min(255, (int)round(0.393 * $red + 0.769 * $green + 0.189 * $blue));
                $newGreen = min(255, (int)round(0.349 * $red + 0.686 * $green + 0.168 * $blue));
                $newBlue = min(255, (int)round(0.272 * $red + 0.534 * $green + 0.131 * $blue));

                $sepiaColor = imagecolorallocatealpha($finalImage, $newRed, $newGreen, $newBlue, $alpha);

                imagesetpixel($finalImage, $x, $y, $sepiaColor);
            }
        }

        $this->setImage($finalImage);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function removeBackground(int $colorThreshold = 30): self
    {
        $source = $this->getImage();

        if (!$source instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        $width = $this->getTargetWidth();
        $height = $this->getTargetHeight();

        $this->setFileType('png');

        $finalImage = imagecreatetruecolor($width, $height);

        $transparentColor = imagecolorallocatealpha($finalImage, 0, 0, 0, 127);
        imagefill($finalImage, 0, 0, $transparentColor);

        imagesavealpha($finalImage, true);

        $topLeftColor = imagecolorat($source, 0, 0);
        $topRightColor = imagecolorat($source, $width - 1, 0);
        $bottomLeftColor = imagecolorat($source, 0, $height - 1);
        $bottomRightColor = imagecolorat($source, $width - 1, $height - 1);

        $averageRed = (($topLeftColor >> 16 & 0xFF) + ($topRightColor >> 16 & 0xFF) + ($bottomLeftColor >> 16 & 0xFF) + ($bottomRightColor >> 16 & 0xFF)) / 4;
        $averageGreen = (($topLeftColor >> 8 & 0xFF) + ($topRightColor >> 8 & 0xFF) + ($bottomLeftColor >> 8 & 0xFF) + ($bottomRightColor >> 8 & 0xFF)) / 4;
        $averageBlue = (($topLeftColor & 0xFF) + ($topRightColor & 0xFF) + ($bottomLeftColor & 0xFF) + ($bottomRightColor & 0xFF)) / 4;

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $pixelColor = imagecolorat($source, $x, $y);
                $red = $pixelColor >> 16 & 0xFF;
                $green = $pixelColor >> 8 & 0xFF;
                $blue = $pixelColor & 0xFF;

                if (abs($red - $averageRed) < $colorThreshold &&
                    abs($green - $averageGreen) < $colorThreshold &&
                    abs($blue - $averageBlue) < $colorThreshold) {

                    imagesetpixel($finalImage, $x, $y, $transparentColor);
                } else {
                    imagesetpixel($finalImage, $x, $y, $pixelColor);
                }
            }
        }

        $this->setImage($finalImage);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function negative(): self
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_NEGATE);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function grayscale(): self
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_GRAYSCALE);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function adjustBrightness(int $brightnessValue = 128): self
    {
        if ($brightnessValue < -255 || $brightnessValue > 255) {
            throw new ImageEditorException('Brightness Value must be between -255 and 255');
        }

        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_BRIGHTNESS, $brightnessValue);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function adjustContrast(int $contrastValue = 128): self
    {
        if ($contrastValue < -255 || $contrastValue > 255) {
            throw new ImageEditorException('Contrast Value must be between -255 and 255');
        }

        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_BRIGHTNESS, $contrastValue);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function colorOverlay(int $red, int $green, int $blue, int $alpha = 0): self
    {
        if ($red < 0 || $red > 255) {
            throw new ImageEditorException('Red value must be between 0 and 255');
        }

        if ($green < 0 || $green > 255) {
            throw new ImageEditorException('Green value must be between 0 and 255');
        }

        if ($blue < 0 || $blue > 255) {
            throw new ImageEditorException('Blue value must be between 0 and 255');
        }

        if ($alpha < 0 || $alpha > 255) {
            throw new ImageEditorException('Alpha value must be between 0 and 255');
        }

        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_COLORIZE, $red, $green, $blue, $alpha);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function edgeDetection(): self
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_EDGEDETECT);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function emboss(): self
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_EMBOSS);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function gaussianBlur(): self
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function selectiveBlur(): self
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_SELECTIVE_BLUR);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function sketch(): self
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_MEAN_REMOVAL);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function smooth(int $smoothValue = -6): self
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_SMOOTH, $smoothValue);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function pixelate(int $pixelSize = 16, bool $advanced = false): self
    {
        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_PIXELATE, $pixelSize, $advanced);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function scatter(int $subtractionLevel = 8, int $additionLevel = 10): self
    {
        if ($subtractionLevel >= $additionLevel) {
            throw new ImageEditorException('Subtraction Level can not be higher than or equal to Addition Level');
        }

        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        imagefilter($image, IMG_FILTER_SCATTER, $subtractionLevel, $additionLevel);

        $this->setImage($image);

        return $this;
    }

    /**
     * @throws ImageEditorException
     */
    public function customConvolution(array $matrix): self
    {
        if (!$this->is3x3Array($matrix)) {
            throw new ImageEditorException("The matrix must be 3 X 3");
        }

        $image = $this->getImage();

        if (!$image instanceof GdImage) {
            throw new ImageEditorException("There is no image.");
        }

        $flattenedMatrix = $this->flattenMatrix($matrix);
        $divisor = array_sum($flattenedMatrix);
        if ($divisor == 0) {
            $divisor = 1;
        }

        imageconvolution($image, $matrix, $divisor, 0);

        $this->setImage($image);

        return $this;
    }

    private function is3x3Array(array $array): bool
    {
        if (count($array) !== 3) {
            return false;
        }

        foreach ($array as $row) {
            if (!is_array($row) || count($row) !== 3) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws ImageEditorException
     */
    public function posterize(): self
    {
        $posterizationMatrix = [
            [1, 1, 1],
            [1, 1, 1],
            [1, 1, 1],
        ];

        return $this->customConvolution($posterizationMatrix);
    }

    /**
     * @throws ImageEditorException
     */
    public function sharpen(): self
    {
        $sharpenMatrix = [
            [0, -1, 0],
            [-1, 5, -1],
            [0, -1, 0],
        ];

        return $this->customConvolution($sharpenMatrix);
    }

    private function flattenMatrix(array $matrix): array
    {
        $flatMatrix = [];

        foreach ($matrix as $row) {
            $flatMatrix = array_merge($flatMatrix, $row);
        }

        return $flatMatrix;
    }
}