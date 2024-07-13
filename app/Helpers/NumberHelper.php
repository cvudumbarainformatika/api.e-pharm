<?php

namespace App\Helpers;

class NumberHelper
{
    public static function setNumber($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 5 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $kode . $has . $n;
    }
}
