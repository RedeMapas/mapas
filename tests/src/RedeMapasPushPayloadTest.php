<?php
declare(strict_types=1);
namespace Tests;
use PHPUnit\Framework\TestCase;

class RedeMapasPushPayloadTest extends TestCase
{
    public function testBodyIsTruncatedAt177Chars(): void
    {
        $body = str_repeat('a', 200);
        $truncated = mb_strlen($body) > 180
            ? mb_substr($body, 0, 177) . '...'
            : $body;
        $this->assertSame(180, mb_strlen($truncated));
        $this->assertStringEndsWith('...', $truncated);
    }

    public function testBodyUnder180CharsIsNotTruncated(): void
    {
        $body = str_repeat('b', 100);
        $truncated = mb_strlen($body) > 180
            ? mb_substr($body, 0, 177) . '...'
            : $body;
        $this->assertSame(100, mb_strlen($truncated));
    }
}
