<?php

function extraer_id_participante_qr(?string $raw): int
{
    $raw = trim((string)$raw);

    if ($raw === '') {
        return 0;
    }

    $raw = preg_replace('/^\xEF\xBB\xBF/u', '', $raw);
    $raw = str_replace(["\xC2\xA0", "\xE2\x80\xAF"], ' ', $raw);

    if (ctype_digit($raw)) {
        return (int)$raw;
    }

    if (preg_match('/(?i)\bID\b\h*[:\-NÑ]?\h*(\d{1,10})(?=\D|$)/u', $raw, $m)) {
        return (int)$m[1];
    }

    if (preg_match('/(?i)ID\h*[:\-NÑ]?\h*(\d{1,10})(?=\D|$)/u', $raw, $m)) {
        return (int)$m[1];
    }

    if (preg_match('/(?:^|\D)(\d{1,10})(?=\D|$)/', $raw, $m)) {
        return (int)$m[1];
    }

    return 0;
}
