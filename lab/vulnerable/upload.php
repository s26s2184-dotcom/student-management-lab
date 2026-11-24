<?php
/**
 * VULNERABLE file upload handler.
 *
 * Weaknesses demonstrated (see docs/vulnerabilities.md):
 *  - Only a weak client-suggested-extension BLOCKLIST is checked
 *    (case-sensitive, easily bypassed with e.g. "shell.PHP" or
 *    "shell.phtml", double extensions like "image.php.jpg", etc.)
 *  - No server-side MIME-type verification via finfo.
 *  - No file size limit.
 *  - Original filename is kept (predictable, can collide/overwrite).
 *
 * IMPORTANT (safety net - see also the Apache config in Dockerfile):
 * even though validation here is intentionally weak, this lab does
 * NOT allow a real web shell: the uploads/ directory has the PHP
 * engine disabled via Apache config and any .php file is served as
 * text/plain, so no uploaded script can ever execute. Do not remove
 * that Apache safety net.
 */

function vulnerable_handle_upload(array $file, int $userId): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error.'];
    }

    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // --- VULNERABLE: weak blocklist instead of a strict allowlist ---
    $blocked = ['exe', 'sh', 'bat'];
    if (in_array($ext, $blocked, true)) {
        return ['success' => false, 'error' => 'File type not allowed.'];
    }
    // No finfo MIME check. No size limit. No allowlist of image types.

    $uploadDir = __DIR__ . '/../../uploads/profiles/';
    // basename() only, to avoid real path traversal outside uploads/ -
    // everything else about this validation is intentionally weak.
    $safeBaseName = basename($originalName);
    $destPath = $uploadDir . $safeBaseName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Failed to save file.'];
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO uploads (user_id, original_filename, stored_filename, mime_type, size_bytes) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $originalName, $safeBaseName, $file['type'] ?? null, $file['size'] ?? null]);

    $pdo->prepare('UPDATE students SET profile_image = ? WHERE user_id = ?')
        ->execute(['uploads/profiles/' . $safeBaseName, $userId]);

    return ['success' => true, 'path' => 'uploads/profiles/' . $safeBaseName];
}
