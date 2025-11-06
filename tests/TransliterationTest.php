<?php

use PHPUnit\Framework\TestCase;

final class TransliterationTest extends TestCase {
    public function testTransliterateRomanianDiacritics(): void {
        $this->assertSame('sarmale si tuica', Util::transliterateRO('sarmale și țuică'));
        $this->assertSame('ASA E', Util::transliterateRO('AȘA E'));
        $this->assertSame('Targu Mures', Util::transliterateRO('Târgu Mureș'));
        $this->assertSame('Dacia 1310', Util::transliterateRO('Dacia 1310'));
    }
}
