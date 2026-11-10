<?php

declare(strict_types=1);

namespace Dcd\Storage\Tests;

use Dcd\Storage\TransformationBuilder;
use PHPUnit\Framework\TestCase;

class TransformationBuilderTest extends TestCase
{
    public function testFullTransformationPipeline(): void
    {
        $builder = new TransformationBuilder('https://storage.devclouddesk.com', 'avatars/user-1.jpg');
        $url = $builder
            ->width(400)
            ->height(300)
            ->crop('fill')
            ->format('webp')
            ->quality(85)
            ->blur(5)
            ->grayscale(true)
            ->sharpen(true)
            ->rotation(90)
            ->build();

        $this->assertEquals(
            'https://storage.devclouddesk.com/image/upload/w_400,h_300,c_fill,f_webp,q_85,e_blur_5,e_gray,e_sharp,r_90/avatars/user-1.webp',
            $url
        );
    }

    public function testOriginalWithoutTransformation(): void
    {
        $builder = new TransformationBuilder('http://localhost:7100', 'marketing/hero.png');
        $this->assertEquals(
            'http://localhost:7100/image/upload/marketing/hero.png',
            $builder->build()
        );
    }

    public function testCloudinaryMode(): void
    {
        $builder = new TransformationBuilder('https://res.cloudinary.com/demo', 'sample.jpg');
        $url = $builder->cloudinaryMode()->width(250)->crop('scale')->build();

        $this->assertEquals(
            'https://res.cloudinary.com/demo/image/upload/w_250,c_scale/sample.jpg',
            $url
        );
    }

    public function testCustomPrefix(): void
    {
        $builder = new TransformationBuilder('https://storage.example.com', 'photo.jpg');
        $url = $builder->prefix('assets/delivery')->width(500)->build();

        $this->assertEquals(
            'https://storage.example.com/assets/delivery/w_500/photo.jpg',
            $url
        );
    }

    public function testResizeShortcut(): void
    {
        $builder = new TransformationBuilder('https://storage.example.com', 'photo.jpg');
        $url = $builder->resize(1024, 768)->build();

        $this->assertEquals(
            'https://storage.example.com/image/upload/w_1024,h_768/photo.jpg',
            $url
        );
    }

    public function testEffectsIndividually(): void
    {
        $b1 = new TransformationBuilder('https://storage.example.com', 'photo.jpg');
        $this->assertStringContainsString('e_blur_10', $b1->blur(10)->build());

        $b2 = new TransformationBuilder('https://storage.example.com', 'photo.jpg');
        $this->assertStringContainsString('e_gray', $b2->grayscale()->build());

        $b3 = new TransformationBuilder('https://storage.example.com', 'photo.jpg');
        $this->assertStringContainsString('e_sharp', $b3->sharpen()->build());

        $b4 = new TransformationBuilder('https://storage.example.com', 'photo.jpg');
        $this->assertStringContainsString('r_180', $b4->rotation(180)->build());
    }

    public function testToParamsString(): void
    {
        $builder = new TransformationBuilder('https://storage.example.com', 'photo.jpg');
        $params = $builder->width(300)->height(200)->crop('crop')->format('avif')->toParamsString();

        $this->assertEquals('w_300,h_200,c_crop,f_avif', $params);
    }

    public function testToStringMatchesBuild(): void
    {
        $builder = new TransformationBuilder('https://storage.example.com', 'photo.jpg');
        $builder->width(200)->quality(90);

        $this->assertEquals($builder->build(), (string)$builder);
        $this->assertEquals($builder->build(), $builder->toString());
    }

    public function testFormatReplacesExtension(): void
    {
        $builder = new TransformationBuilder('https://storage.example.com', 'gallery/summer-sunset.jpeg');
        $url = $builder->format('webp')->build();

        $this->assertEquals(
            'https://storage.example.com/image/upload/f_webp/gallery/summer-sunset.webp',
            $url
        );
    }

    public function testLeadingSlashSanitization(): void
    {
        $builder = new TransformationBuilder('https://storage.example.com', '///photos/banner.png');
        $this->assertEquals(
            'https://storage.example.com/image/upload/photos/banner.png',
            $builder->build()
        );
    }
}

