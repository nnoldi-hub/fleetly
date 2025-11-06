<?php

class Util {
    public static function transliterateRO(?string $s): string {
        if ($s === null) return '';
        $map = [
            'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t',
            'Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ş'=>'S','Ț'=>'T','Ţ'=>'T'
        ];
        $s = strtr($s, $map);
        return preg_replace('/[^\x20-\x7E]/', '', $s);
    }
}
