<?php

use League\CommonMark\Environment\Environment;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\TestCase;
use SimonVomEyser\CommonMarkExtension\LazyImageExtension;

class LazyImageExtensionTest extends TestCase
{
    protected function getConverter(array $config): MarkdownConverter
    {
        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension())->addExtension(new LazyImageExtension());

        return new MarkdownConverter($environment);
    }

    private function getImageRenderers(EnvironmentBuilderInterface $environment): array
    {
        return [...$this->getConverter([])->getEnvironment()->getRenderersForClass(Image::class)];
    }

    public function testLozadLibraryConfigurationAsExample(): void
    {
        $config = [
            'lazy_image' => [
                'strip_src' => true,
                'html_class' => 'lozad',
                'data_attribute' => 'src',
            ]
        ];
        $converter = $this->getConverter($config);

        $imageMarkdown = '![alt text](/path/to/image.jpg)';
        $html = $converter->convertToHtml($imageMarkdown);

        $this->assertStringContainsString('src="" alt="alt text" loading="lazy" data-src="/path/to/image.jpg" class="lozad"',
            $html);
    }

    public function testOnlyTheLazyAttributeIsAddedInDefaultConfig(): void
    {
        $converter = $this->getConverter([]);

        $html = $converter->convertToHtml('![alt text](/path/to/image.jpg)');
        $this->assertStringContainsString('<img src="/path/to/image.jpg" alt="alt text" loading="lazy" />', $html);
    }

    public function testTheClassCanBeAdded(): void
    {
        $config = [
            'lazy_image' => ['html_class' => 'lazy-loading-class']
        ];
        $converter = $this->getConverter($config);

        $imageMarkdown = '![alt text](/path/to/image.jpg)';
        $html = $converter->convertToHtml($imageMarkdown);
        $this->assertStringContainsString('class="lazy-loading-class"', $html);
    }

    public function testTheDataSrcBeDefined(): void
    {
        $config = [
            'lazy_image' => ['data_attribute' => 'src']
        ];
        $converter = $this->getConverter($config);

        $imageMarkdown = '![alt text](/path/to/image.jpg)';
        $html = $converter->convertToHtml($imageMarkdown);
        $this->assertStringContainsString('data-src="/path/to/image.jpg"', $html);
    }

    public function testTheRendererIsAdded(): void
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension())->addExtension(new LazyImageExtension());

        $this->assertCount(2, $this->getImageRenderers($environment));
    }

    public function testTheSrcCanBeStripped(): void
    {
        $config = [
            'lazy_image' => ['strip_src' => true]
        ];
        $converter = $this->getConverter($config);

        $html = $converter->convertToHtml('![alt text](/path/to/image.jpg)');
        $this->assertStringContainsString('<img src="" alt="alt text" loading="lazy" />', $html);
    }
}