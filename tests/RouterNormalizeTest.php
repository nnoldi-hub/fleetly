<?php

use PHPUnit\Framework\TestCase;

final class RouterNormalizeTest extends TestCase {
    public function testNormalizePathVariants(): void {
        $router = new Router();
        $ref = new ReflectionClass($router);
        $m = $ref->getMethod('normalizePath');
        $m->setAccessible(true);

        $this->assertSame('/vehicles', $m->invoke($router, '/vehicles/'));
        $this->assertSame('/vehicles', $m->invoke($router, 'vehicles'));
        $this->assertSame('/vehicles', $m->invoke($router, '/vehicles'));
        $this->assertSame('/', $m->invoke($router, '/'));
        $this->assertSame('/vehicles', $m->invoke($router, '/vehicles?foo=bar'));
    }
}
