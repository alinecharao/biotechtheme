<?php
/**
 * Placeholder Avatar Generator
 * Gera um avatar SVG padrão para uso quando não há imagem definida
 */

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=31536000');

$color = isset($_GET['color']) ? preg_replace('/[^a-fA-F0-9]/', '', $_GET['color']) : 'e5e7eb';
$size = isset($_GET['size']) ? intval($_GET['size']) : 100;

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" width="<?php echo $size; ?>" height="<?php echo $size; ?>" viewBox="0 0 100 100">
    <rect fill="#<?php echo $color; ?>" width="100" height="100"/>
    <circle fill="#9ca3af" cx="50" cy="38" r="18"/>
    <ellipse fill="#9ca3af" cx="50" cy="85" rx="28" ry="22"/>
</svg>
