<?php
/**
 * SECURE file upload handler — mitigations:
 *  - Strict extension ALLOWLIST (jpg, jpeg, png, gif only).
 *  - Server-side MIME-type verification using finfo (not the
 *    client-supplied Content-Type, which is trivially spoofable).
 *  - File size limit.
 *  - Randomly generated filename (no trust in client-supplied names).
 *  - Files stored in secure_uploads/, which is OUTSIDE the Apache
 *    document root (public/) and cannot be requested directly or
 *    executed as a script. A dedicated download endpoint serves them
 *    with the correct Content-Type after an auth/ownership check.
 */

const MAX_UPLOAD_BYTES = 2 * 1024 * 1024; // 2 MB
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];
const ALLOWED_MIME_TYPES = [
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/png' => ['png'],
    'image/gif' => ['gif'],
];

function secure_handle_upload(array $file, int $userId): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error.'];
    }

    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return ['success' => false, 'error' => 'File too large (max 2MB).'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return ['success' => false, 'error' => 'File extension not allowed.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset(ALLOWED_MIME_TYPES[$mime]) || !in_array($ext, ALLOWED_MIME_TYPES[$mime], true)) {
        return ['success' => false, 'error' => 'File content does not match an allowed image type.'];
    }

    $secureDir = __DIR__ . '/../../secure_uploads/';
    if (!is_dir($secureDir)) {
        mkdir($secureDir, 0775, true);
    }

    $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $secureDir . $randomName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Failed to save file.'];
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO uploads (user_id, original_filename, stored_filename, mime_type, size_bytes) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $file['name'], $randomName, $mime, $file['size']]);

    $pdo->prepare('UPDATE students SET profile_image = ? WHERE user_id = ?')
        ->execute([$randomName, $userId]);

    return ['success' => true, 'stored_filename' => $randomName];
}
