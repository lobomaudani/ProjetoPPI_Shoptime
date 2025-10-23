<?php
// Funções utilitárias para usuários: validação de CPF e upload/thumbnail de foto de perfil

function validate_cpf(string $cpf): bool
{
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11)
        return false;
    // sequências repetidas inválidas
    if (preg_match('/^(\d)\1{10}$/', $cpf))
        return false;

    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d)
            return false;
    }
    return true;
}

function save_profile_photo(array $file, int $maxBytes = 2097152): array
{
    // retorna ['url' => string, 'thumb' => string]
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['url' => null, 'thumb' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erro no upload do arquivo.');
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Arquivo excede tamanho máximo de ' . ($maxBytes / 1024 / 1024) . 'MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!array_key_exists($mime, $allowed)) {
        throw new RuntimeException('Formato de imagem não suportado.');
    }

    $ext = $allowed[$mime];
    $uploadsDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadsDir))
        mkdir($uploadsDir, 0777, true);
    $base = 'profile_' . bin2hex(random_bytes(6)) . '_' . time();
    $filename = $base . '.' . $ext;
    $dest = $uploadsDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Falha ao mover o arquivo enviado.');
    }

    // criar thumbnail
    $thumbName = $base . '_thumb.' . $ext;
    $thumbPath = $uploadsDir . $thumbName;
    create_thumbnail($dest, $thumbPath, 150, 150, $mime);

    return ['url' => 'uploads/' . $filename, 'thumb' => 'uploads/' . $thumbName];
}

function create_thumbnail(string $srcPath, string $destPath, int $w, int $h, string $mime)
{
    // suporta jpeg, png, gif, webp quando suportado
    switch ($mime) {
        case 'image/jpeg':
            $srcImg = imagecreatefromjpeg($srcPath);
            break;
        case 'image/png':
            $srcImg = imagecreatefrompng($srcPath);
            break;
        case 'image/gif':
            $srcImg = imagecreatefromgif($srcPath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp'))
                $srcImg = imagecreatefromwebp($srcPath);
            else
                $srcImg = imagecreatefromjpeg($srcPath); // fallback
            break;
        default:
            return;
    }
    if (!$srcImg)
        return;
    $origW = imagesx($srcImg);
    $origH = imagesy($srcImg);
    $dstImg = imagecreatetruecolor($w, $h);
    // manter transparência para PNG/GIF
    if (in_array($mime, ['image/png', 'image/gif'])) {
        imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
    }
    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $w, $h, $origW, $origH);
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($dstImg, $destPath, 85);
            break;
        case 'image/png':
            imagepng($dstImg, $destPath);
            break;
        case 'image/gif':
            imagegif($dstImg, $destPath);
            break;
        case 'image/webp':
            if (function_exists('imagewebp'))
                imagewebp($dstImg, $destPath);
            else
                imagejpeg($dstImg, $destPath, 85);
            break;
    }
    imagedestroy($srcImg);
    imagedestroy($dstImg);
}

// CSRF helpers
function generate_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    if (empty($token) || empty($_SESSION['csrf_token']))
        return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

?>