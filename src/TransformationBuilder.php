<?php

declare(strict_types=1);

namespace Dcd\Storage;

class TransformationBuilder
{
    protected string $endpoint;
    protected string $publicId;
    /** @var array<string, mixed> */
    protected array $options = [];
    protected string $deliveryPrefix = 'image/upload';

    protected const KNOWN_IMAGE_EXTS = '/\.(jpg|jpeg|png|webp|avif|gif|tiff|svg|bmp)$/i';

    public function __construct(
        string $endpoint,
        string $publicId,
        array $initialOptions = [],
        string $deliveryPrefix = 'image/upload'
    ) {
        $this->endpoint = rtrim($endpoint, '/');
        $this->publicId = ltrim($publicId, '/');
        $this->deliveryPrefix = trim($deliveryPrefix, '/');

        if (!empty($initialOptions)) {
            $opts = $initialOptions;
            if (isset($opts['w']) && !isset($opts['width'])) $opts['width'] = $opts['w'];
            if (isset($opts['h']) && !isset($opts['height'])) $opts['height'] = $opts['h'];
            if (isset($opts['c']) && !isset($opts['crop'])) $opts['crop'] = $opts['c'];
            if (isset($opts['f']) && !isset($opts['format'])) $opts['format'] = $opts['f'];
            if (isset($opts['q']) && !isset($opts['quality'])) $opts['quality'] = $opts['q'];
            $this->options = $opts;
        }
    }

    public function prefix(string $pathPrefix): self
    {
        $this->deliveryPrefix = trim($pathPrefix, '/');
        return $this;
    }

    public function cloudinaryMode(): self
    {
        $this->deliveryPrefix = 'image/upload';
        return $this;
    }

    public function width(int $w): self
    {
        $this->options['width'] = $w;
        return $this;
    }

    public function height(int $h): self
    {
        $this->options['height'] = $h;
        return $this;
    }

    public function resize(int $width, int $height, ?string $crop = null): self
    {
        $this->options['width'] = $width;
        $this->options['height'] = $height;
        if ($crop !== null) {
            $this->options['crop'] = $crop;
        }
        return $this;
    }

    public function crop(string $mode): self
    {
        $this->options['crop'] = $mode;
        return $this;
    }

    public function format(string $fmt): self
    {
        $this->options['format'] = $fmt;
        return $this;
    }

    public function quality(int|string $q): self
    {
        $this->options['quality'] = $q;
        return $this;
    }

    public function blur(int $sigma = 5): self
    {
        $this->options['blur'] = $sigma;
        return $this;
    }

    public function grayscale(bool $enable = true): self
    {
        $this->options['grayscale'] = $enable;
        return $this;
    }

    public function sharpen(bool $enable = true): self
    {
        $this->options['sharpen'] = $enable;
        return $this;
    }

    public function rotation(int|string $rot): self
    {
        $this->options['rotation'] = $rot;
        return $this;
    }

    public function toParamsString(): string
    {
        $parts = [];

        if (!empty($this->options['width'])) {
            $parts[] = 'w_' . (int)$this->options['width'];
        }
        if (!empty($this->options['height'])) {
            $parts[] = 'h_' . (int)$this->options['height'];
        }
        if (!empty($this->options['crop'])) {
            $parts[] = 'c_' . (string)$this->options['crop'];
        }
        if (!empty($this->options['format'])) {
            $parts[] = 'f_' . (string)$this->options['format'];
        }
        if (!empty($this->options['quality'])) {
            $parts[] = 'q_' . (string)$this->options['quality'];
        }
        if (!empty($this->options['blur'])) {
            $parts[] = 'e_blur_' . (int)$this->options['blur'];
        }
        if (!empty($this->options['grayscale'])) {
            $parts[] = 'e_gray';
        }
        if (!empty($this->options['sharpen'])) {
            $parts[] = 'e_sharp';
        }
        if (!empty($this->options['rotation'])) {
            $parts[] = 'r_' . (string)$this->options['rotation'];
        }

        return !empty($parts) ? implode(',', $parts) : 'original';
    }

    public function build(): string
    {
        $params = $this->toParamsString();
        $finalPublicId = $this->publicId;

        // Cloudinary standard: If a specific format was selected, ensure URL ends with that extension
        if (!empty($this->options['format']) && $this->options['format'] !== 'auto') {
            $targetExt = $this->options['format'] === 'jpeg' ? 'jpg' : $this->options['format'];
            if (preg_match(self::KNOWN_IMAGE_EXTS, $finalPublicId)) {
                $finalPublicId = preg_replace(self::KNOWN_IMAGE_EXTS, '.' . $targetExt, $finalPublicId);
            } else {
                $finalPublicId = $finalPublicId . '.' . $targetExt;
            }
        }

        if ($params === 'original') {
            return "{$this->endpoint}/{$this->deliveryPrefix}/{$finalPublicId}";
        }

        return "{$this->endpoint}/{$this->deliveryPrefix}/{$params}/{$finalPublicId}";
    }

    public function toString(): string
    {
        return $this->build();
    }

    public function __toString(): string
    {
        return $this->build();
    }
}

