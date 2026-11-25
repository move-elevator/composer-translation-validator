<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use MoveElevator\ComposerTranslationValidator\Parser\{JsonParser, ParserInterface, PhpParser, YamlParser};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * DottedKeysTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class DottedKeysTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<ParserInterface>, string, string}>
     */
    public static function parserProvider(): iterable
    {
        yield 'yaml' => [YamlParser::class, 'messages.en.yaml', "app.title: Hello\napp:\n  nested: Nested\n  deep.key: Deep\n"];
        yield 'json' => [JsonParser::class, 'messages.en.json', '{"app.title": "Hello", "app": {"nested": "Nested", "deep.key": "Deep"}}'];
        yield 'php' => [PhpParser::class, 'messages.en.php', "<?php return ['app.title' => 'Hello', 'app' => ['nested' => 'Nested', 'deep.key' => 'Deep']];"];
    }

    /**
     * @param class-string<ParserInterface> $parserClass
     */
    #[DataProvider('parserProvider')]
    public function testFlatKeyWithDotsResolvesToItsValue(string $parserClass, string $fileName, string $content): void
    {
        $directory = sys_get_temp_dir().'/dotted_keys_'.uniqid('', true);
        mkdir($directory);
        $file = $directory.'/'.$fileName;

        try {
            file_put_contents($file, $content);
            $parser = new $parserClass($file);

            $this->assertSame('Hello', $parser->getContentByKey('app.title'));
            $this->assertSame('Nested', $parser->getContentByKey('app.nested'));
            $this->assertSame('Deep', $parser->getContentByKey('app.deep.key'));
            $this->assertNull($parser->getContentByKey('app'));
            $this->assertSame(['app.title', 'app.nested', 'app.deep.key'], $parser->extractKeys());
        } finally {
            @unlink($file);
            rmdir($directory);
        }
    }

    public function testFlatAndNestedKeyWithTheSameNameAreBothListedAndTheLastValueWins(): void
    {
        $directory = sys_get_temp_dir().'/dotted_keys_'.uniqid('', true);
        mkdir($directory);
        $file = $directory.'/messages.en.yaml';

        try {
            file_put_contents($file, "app.title: Flat\napp:\n  title: Nested\n");
            $parser = new YamlParser($file);

            $this->assertSame(['app.title', 'app.title'], $parser->extractKeys());
            $this->assertSame('Nested', $parser->getContentByKey('app.title'));
        } finally {
            @unlink($file);
            rmdir($directory);
        }
    }
}
