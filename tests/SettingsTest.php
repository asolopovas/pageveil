<?php
declare(strict_types=1);

namespace Pageveil\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Pageveil\Settings;

final class SettingsTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); Monkey\setUp(); }
    protected function tearDown(): void { Monkey\tearDown(); parent::tearDown(); }

    public function testDefaultsWhenOptionMissing(): void
    {
        Functions\when('get_option')->justReturn([]);
        $s = new Settings();
        self::assertSame(['enabled' => false, 'page_id' => 0], $s->get());
        self::assertFalse($s->active());
    }

    public function testReadsAndCoercesStoredOption(): void
    {
        Functions\when('get_option')->justReturn(['enabled' => 1, 'page_id' => '42']);
        $s = new Settings();
        self::assertSame(['enabled' => true, 'page_id' => 42], $s->get());
        self::assertTrue($s->active());
        self::assertSame(42, $s->pageId());
    }

    public function testActiveFalseWhenPageIdZero(): void
    {
        Functions\when('get_option')->justReturn(['enabled' => true, 'page_id' => 0]);
        self::assertFalse((new Settings())->active());
    }

    public function testSaveSanitizesAndPersists(): void
    {
        $captured = null;
        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->alias(function ($key, $value) use (&$captured) {
            $captured = [$key, $value];
            return true;
        });

        $clean = (new Settings())->save(['enabled' => 'on', 'page_id' => '13']);

        self::assertSame(['enabled' => true, 'page_id' => 13], $clean);
        self::assertSame(['pageveil', ['enabled' => true, 'page_id' => 13]], $captured);
    }

    public function testSaveRejectsNegativeIds(): void
    {
        Functions\when('update_option')->justReturn(true);
        $clean = (new Settings())->save(['enabled' => false, 'page_id' => -5]);
        self::assertSame(['enabled' => false, 'page_id' => 0], $clean);
    }
}
