<?php
// ─── nok-beeldmerk.php ────────────────────────────────────────────────────────

// 1) Load SVG variant
$variant  = $_GET['variant'] ?? 'plain';
$filename = $variant === 'diapositief'
    ? 'nok-beeldmerk-diapositief.svg'
    : 'nok-beeldmerk.svg';
$path = __DIR__ . "/{$filename}";
if (!is_file($path)) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
    exit("SVG not found.");
}
$svg = file_get_contents($path);

// 2) Parse original viewBox: x, y, width, height
if (!preg_match('/viewBox="([\d\.\s\-]+)"/', $svg, $m)) {
    header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
    exit("SVG has no viewBox.");
}
list(, $vb) = $m;
list($vbX, $vbY, $vbW, $vbH) = preg_split('/\s+/', trim($vb));

// ─── Helpers to parse params ──────────────────────────────────────────────────
/**
 * Parse a dimension param (width/height):
 *  - "" or "auto" → null
 *  - ends with "%"  → fraction * $base
 *  - else           → (float)px
 */
function parseDim(string $val, float $base): ?float {
    $v = trim($val);
    if ($v === '' || strtolower($v) === 'auto') {
        return null;
    }
    if (substr($v, -1) === '%') {
        $pct = floatval(rtrim($v, '%')) / 100;
        return max(0, min(1, $pct)) * $base;
    }
    return (float)$v;
}

/**
 * Parse an offset param (x/y):
 *  - ends with "%" → fraction * $maxOffset
 *  - else         → (float)px
 */
function parseOffset(string $val, float $maxOffset): float {
    $v = trim($val);
    if (substr($v, -1) === '%') {
        $pct = floatval(rtrim($v, '%')) / 100;
        return max(0, min(1, $pct)) * $maxOffset;
    }
    return (float)$v;
}

/**
 * Parse rotation offset (relative to the cropped box):
 *  - ends with "%" → fraction * $cropSize
 *  - else         → (float)px
 */
function parseRotOff(string $val, float $cropSize): float {
    $v = trim($val);
    if (substr($v, -1) === '%') {
        $pct = floatval(rtrim($v, '%')) / 100;
        return max(0, min(1, $pct)) * $cropSize;
    }
    return (float)$v;
}

/**
 * Trim trailing zeros & dots:  "500.00" → "500"
 */
function trimZeros(float $n): string {
    return rtrim(rtrim(sprintf('%.2f', $n), '0'), '.');
}

// ─── 3) width / height (pixels or %) ──────────────────────────────────────────
$wParam = $_GET['width']  ?? '';
$hParam = $_GET['height'] ?? '';
$newW = parseDim($wParam, $vbW);
$newH = parseDim($hParam, $vbH);

// Auto‐calculate missing side
if ($newW === null && $newH === null) {
    $newW = $vbW;
    $newH = $vbH;
} elseif ($newH === null) {
    $newH = $vbH;
} elseif ($newW === null) {
    // keep aspect ratio if only width passed
    $newW = $vbW / $vbH * $newH;
}

// ─── 4) x / y anchor (pixels or % of leftover) ───────────────────────────────
$maxXoff = $vbW - $newW;
$maxYoff = $vbH - $newH;
$xOff    = $vbX + parseOffset($_GET['x'] ?? '0', $maxXoff);
$yOff    = $vbY + parseOffset($_GET['y'] ?? '0', $maxYoff);

// ─── 5) opacity, rotation & rotationOffsets ─────────────────────────────────
$opacity = max(0, min(1, floatval($_GET['opacity']  ?? '1')));
$rotation    = floatval($_GET['rotation'] ?? '0');
$rotOffX     = parseRotOff($_GET['rotationOffsetX'] ?? '0', $newW);
$rotOffY     = parseRotOff($_GET['rotationOffsetY'] ?? '0', $newH);

// Rotation center = center of the cropped box + offset
$centerX = $xOff + $newW/2 + $rotOffX;
$centerY = $yOff + $newH/2 + $rotOffY;

// ─── 6) Build the new viewBox ────────────────────────────────────────────────
$newViewBox = sprintf(
    '%s %s %s %s',
    trimZeros($xOff),
    trimZeros($yOff),
    trimZeros($newW),
    trimZeros($newH)
);

// Prepare attribute strings
$wStr   = trimZeros($newW) . 'px';
$hStr   = trimZeros($newH) . 'px';
$opStr  = trimZeros($opacity);
$rotStr = trimZeros($rotation);
$cxStr  = trimZeros($centerX);
$cyStr  = trimZeros($centerY);

// ─── 7) Inject <svg> + wrap contents in <g> for rotation ────────────────────
$svg = preg_replace_callback(
    '/<svg\b([^>]*)viewBox="[^"]+"([^>]*)>/',
    function($m) use ($newViewBox, $wStr, $hStr, $opStr, $rotStr, $cxStr, $cyStr) {
        // rebuild opening tag
        $open  = "<svg{$m[1]} viewBox=\"{$newViewBox}\"{$m[2]} "
            ."width=\"{$wStr}\" height=\"{$hStr}\" opacity=\"{$opStr}\">";
        // immediately wrap in a <g> for rotation
        $open .= "<g transform=\"rotate({$rotStr} {$cxStr} {$cyStr})\">";
        return $open;
    },
    $svg,
    1
);
// close the <g> before </svg>
$svg = preg_replace('/<\/svg>/', '</g></svg>', $svg, 1);

// ─── 8) Emit ─────────────────────────────────────────────────────────────────
header('Content-Type: image/svg+xml');
echo $svg;
exit;
