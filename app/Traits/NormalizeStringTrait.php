<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait NormalizeStringTrait
{
    public function normalizeString(string $text): string
    {
        $text = trim($text); // quitar espacios al inicio y fin
        $text = mb_strtolower($text); // pasar a minúsculas
        $text = Str::ascii($text); // quitar acentos
        $text = preg_replace('/[\s\-]+/', ' ', $text); // reemplazar múltiples espacios o guiones por uno
        return trim($text); // recortar nuevamente
    }
}
