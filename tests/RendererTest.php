<?php
declare(strict_types=1);

namespace Pageveil\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Pageveil\Renderer;

final class RendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        unset($_SERVER['SCRIPT_NAME']);
        parent::tearDown();
    }

    public function testBypassesAdminUsers(): void
    {
        Functions\when('is_admin')->justReturn(false);
        Functions\when('is_user_logged_in')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('is_login')->justReturn(false);
        Functions\when('wp_doing_cron')->justReturn(false);
        Functions\when('wp_doing_ajax')->justReturn(false);

        self::assertTrue((new Renderer())->bypass());
    }

    public function testBypassesAdminScreen(): void
    {
        Functions\when('is_admin')->justReturn(true);
        Functions\when('wp_doing_cron')->justReturn(false);
        Functions\when('wp_doing_ajax')->justReturn(false);

        self::assertTrue((new Renderer())->bypass());
    }

    public function testBypassesWpLoginScript(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/wp-login.php';
        Functions\when('is_admin')->justReturn(false);
        Functions\when('is_user_logged_in')->justReturn(false);
        Functions\when('is_login')->justReturn(false);
        Functions\when('wp_doing_cron')->justReturn(false);
        Functions\when('wp_doing_ajax')->justReturn(false);

        self::assertTrue((new Renderer())->bypass());
    }

    public function testDoesNotBypassPublicVisitor(): void
    {
        Functions\when('is_admin')->justReturn(false);
        Functions\when('is_user_logged_in')->justReturn(false);
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('is_login')->justReturn(false);
        Functions\when('wp_doing_cron')->justReturn(false);
        Functions\when('wp_doing_ajax')->justReturn(false);

        self::assertFalse((new Renderer())->bypass());
    }

    public function testRenderEmitsFallbackWhenPageMissing(): void
    {
        Functions\when('get_post')->justReturn(null);
        Functions\when('status_header')->justReturn(null);
        Functions\when('nocache_headers')->justReturn(null);

        ob_start();
        (new Renderer())->render(99);
        $out = ob_get_clean();

        self::assertStringContainsString('Site is under construction.', $out);
    }

    public function testRenderEmitsPageContent(): void
    {
        $post = (object) ['ID' => 7, 'post_status' => 'publish', 'post_content' => 'Hello world'];

        Functions\when('get_post')->justReturn($post);
        Functions\when('status_header')->justReturn(null);
        Functions\when('nocache_headers')->justReturn(null);
        Functions\when('get_bloginfo')->alias(fn ($k) => $k === 'language' ? 'en-US' : 'UTF-8');
        Functions\when('get_the_title')->justReturn('Coming Soon');
        Functions\when('apply_filters')->alias(fn ($name, $value) => "<p>{$value}</p>");
        Functions\when('esc_html')->alias(fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'));
        Functions\when('esc_attr')->alias(fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'));
        Functions\when('wp_head')->justReturn(null);
        Functions\when('wp_footer')->justReturn(null);

        ob_start();
        @(new Renderer())->render(7);
        $out = ob_get_clean();

        self::assertStringContainsString('<title>Coming Soon</title>', $out);
        self::assertStringContainsString('<p>Hello world</p>', $out);
        self::assertStringContainsString('noindex,nofollow', $out);
        self::assertStringContainsString('class="pageveil"', $out);
    }
}
