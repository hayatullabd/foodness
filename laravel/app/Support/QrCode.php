<?php

namespace App\Support;

class QrCode
{
    private static array $exp = [];

    private static array $log = [];

    private static function initGf(): void
    {
        if (! empty(self::$exp)) {
            return;
        }
        $val = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $val;
            self::$log[$val] = $i;
            $val <<= 1;
            if ($val & 0x100) {
                $val ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
    }

    private static function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }

        return self::$exp[self::$log[$x] + self::$log[$y]];
    }

    private static function rsPoly(int $degree): array
    {
        self::initGf();
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = [0];
            $factor = self::$exp[$i];
            foreach ($poly as $c) {
                $next[] = self::gfMul($c, $factor);
            }
            for ($j = 0; $j < count($poly); $j++) {
                $next[$j] ^= $poly[$j];
            }
            $poly = $next;
        }

        return $poly;
    }

    private static function rsEncode(array $data, int $ecCount): array
    {
        self::initGf();
        $generator = self::rsPoly($ecCount);
        $res = array_fill(0, $ecCount, 0);

        foreach ($data as $byte) {
            $factor = $byte ^ $res[0];
            array_shift($res);
            $res[] = 0;
            for ($i = 0; $i < $ecCount; $i++) {
                $res[$i] ^= self::gfMul($generator[$i + 1], $factor);
            }
        }

        return $res;
    }

    /**
     * Generate pure SVG QR Code (Version 1-3, Byte Mode, EC Level L).
     */
    public static function svg(string $text, int $size = 70): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $len = strlen($text);
        if ($len <= 17) {
            $version = 1;
            $dataCap = 19;
            $ecCount = 7;
            $side = 21;
        } elseif ($len <= 32) {
            $version = 2;
            $dataCap = 34;
            $ecCount = 10;
            $side = 25;
        } else {
            $version = 3;
            $dataCap = 55;
            $ecCount = 15;
            $side = 29;
        }

        $bits = '0100'.sprintf('%08b', $len);
        for ($i = 0; $i < $len; $i++) {
            $bits .= sprintf('%08b', ord($text[$i]));
        }

        $bits .= substr('0000', 0, max(0, min(4, $dataCap * 8 - strlen($bits))));
        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - (strlen($bits) % 8));
        }

        $pad = ['11101100', '00010001'];
        $pIdx = 0;
        while (strlen($bits) < $dataCap * 8) {
            $bits .= $pad[$pIdx % 2];
            $pIdx++;
        }

        $dataBytes = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $dataBytes[] = bindec(substr($bits, $i, 8));
        }

        $ecBytes = self::rsEncode($dataBytes, $ecCount);
        $allBytes = array_merge($dataBytes, $ecBytes);

        $matrix = array_fill(0, $side, array_fill(0, $side, null));
        $reserved = array_fill(0, $side, array_fill(0, $side, false));

        $setFinder = function (int $r0, int $c0) use (&$matrix, &$reserved): void {
            for ($r = -1; $r <= 7; $r++) {
                for ($c = -1; $c <= 7; $c++) {
                    $rr = $r0 + $r;
                    $cc = $c0 + $c;
                    if ($rr < 0 || $rr >= count($matrix) || $cc < 0 || $cc >= count($matrix)) {
                        continue;
                    }
                    $reserved[$rr][$cc] = true;
                    if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                        $matrix[$rr][$cc] = ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) ? 1 : 0;
                    } else {
                        $matrix[$rr][$cc] = 0;
                    }
                }
            }
        };

        $setFinder(0, 0);
        $setFinder(0, $side - 7);
        $setFinder($side - 7, 0);

        if ($version === 2) {
            $alignCoords = [[18, 18]];
        } elseif ($version === 3) {
            $alignCoords = [[22, 22]];
        } else {
            $alignCoords = [];
        }

        foreach ($alignCoords as [$ar, $ac]) {
            for ($r = -2; $r <= 2; $r++) {
                for ($c = -2; $c <= 2; $c++) {
                    $reserved[$ar + $r][$ac + $c] = true;
                    $matrix[$ar + $r][$ac + $c] = (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0)) ? 1 : 0;
                }
            }
        }

        for ($i = 8; $i < $side - 8; $i++) {
            $reserved[6][$i] = true;
            $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
            $reserved[$i][6] = true;
            $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
        }

        $reserved[4 * $version + 9][8] = true;
        $matrix[4 * $version + 9][8] = 1;

        for ($i = 0; $i <= 8; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $reserved[8][$side - 1 - $i] = true;
            $reserved[$side - 1 - $i][8] = true;
        }

        $allBits = '';
        foreach ($allBytes as $b) {
            $allBits .= sprintf('%08b', $b);
        }

        $bitIdx = 0;
        $bitLen = strlen($allBits);
        $up = true;
        for ($right = $side - 1; $right > 0; $right -= 2) {
            if ($right === 6) {
                $right--;
            }
            $rows = range(0, $side - 1);
            if ($up) {
                $rows = array_reverse($rows);
            }

            foreach ($rows as $r) {
                foreach ([$right, $right - 1] as $c) {
                    if (! $reserved[$r][$c]) {
                        $bit = ($bitIdx < $bitLen) ? (int) $allBits[$bitIdx] : 0;
                        $bitIdx++;
                        $mask = (($r + $c) % 2 === 0) ? 1 : 0;
                        $matrix[$r][$c] = $bit ^ $mask;
                    }
                }
            }
            $up = ! $up;
        }

        $formatBits = '111011111000100';
        for ($i = 0; $i < 6; $i++) {
            $matrix[8][$i] = (int) $formatBits[$i];
        }
        $matrix[8][7] = (int) $formatBits[6];
        $matrix[8][8] = (int) $formatBits[7];
        $matrix[7][8] = (int) $formatBits[8];
        for ($i = 9; $i < 15; $i++) {
            $matrix[14 - $i][8] = (int) $formatBits[$i];
        }

        for ($i = 0; $i < 8; $i++) {
            $matrix[$side - 1 - $i][8] = (int) $formatBits[$i];
        }
        for ($i = 8; $i < 15; $i++) {
            $matrix[8][$side - 15 + $i] = (int) $formatBits[$i];
        }

        $rects = '';
        for ($r = 0; $r < $side; $r++) {
            for ($c = 0; $c < $side; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $rects .= "<rect x='{$c}' y='{$r}' width='1' height='1' fill='#000'/>";
                }
            }
        }

        return "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$side} {$side}' width='{$size}' height='{$size}' shape-rendering='crispEdges' style='display:block;'>{$rects}</svg>";
    }
}
