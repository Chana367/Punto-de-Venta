<?php
/**
 * Servir imágenes desde APPDATA
 * Uso: get_image.php?file=logo.jpg
 */

$allowed_files = ['logo.jpg', 'logo.png', 'logo.gif', 'logo.jpeg', 'background.jpg', 'background.png', 'background.gif', 'background.jpeg'];
$file = $_GET['file'] ?? '';

// Validar que el archivo esté en la lista permitida
$is_allowed = false;
foreach ($allowed_files as $pattern) {
    if (strpos($file, basename($pattern, '.' . pathinfo($pattern, PATHINFO_EXTENSION))) === 0) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Buscar imagen en APPDATA
$appDataDir = getenv('APPDATA') . '\\PuntoVenta\\imagenes';
$filePath = $appDataDir . '\\' . basename($file);

if (file_exists($filePath)) {
    // Determinar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    // Enviar headers
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: max-age=3600');
    
    // Enviar archivo
    readfile($filePath);
    exit;
} else {
    // Si no existe en APPDATA, intentar desde assets (para imágenes por defecto)
    $defaultPath = __DIR__ . '/../assets/img/' . basename($file);
    if (file_exists($defaultPath)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $defaultPath);
        finfo_close($finfo);
        
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($defaultPath));
        header('Cache-Control: max-age=3600');
        
        readfile($defaultPath);
        exit;
    }
    
    // No se encontró la imagen
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>
