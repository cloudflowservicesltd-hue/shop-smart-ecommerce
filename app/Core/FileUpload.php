<?php

class FileUpload
{
    public static function handle(string $key, string $directory = 'products'): ?string
    {
        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$key];
        $config = require ROOT_PATH . '/config/app.php';
        $uploadPath = ROOT_PATH . '/public/uploads/' . $directory . '/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $config['upload']['allowed_types'])) {
            return null;
        }

        if ($file['size'] > $config['upload']['max_size']) {
            return null;
        }

        $filename = uniqid() . '_' . time() . '.' . $ext;
        $fullPath = $uploadPath . $filename;

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return '/uploads/' . $directory . '/' . $filename;
        }

        return null;
    }

    public static function delete(string $path): bool
    {
        $fullPath = ROOT_PATH . '/public' . $path;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}