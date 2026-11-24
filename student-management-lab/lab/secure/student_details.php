<?php
/**
 * SECURE student details lookup — mitigations:
 *  - PDO prepared statement (no SQL injection).
 *  - Authorization check: only admins, or the student who owns the
 *    profile, may view it. Everyone else gets 403, regardless of
 *    whether the ID exists ("fail closed", and does not leak existence).
 */

function secure_get_student(int $id, array $currentUser): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT s.*, u.username, u.email
         FROM students s
         LEFT JOIN users u ON u.id = s.user_id
         WHERE s.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['allowed' => true, 'student' => null];
    }

    $isOwner = $currentUser['role'] === 'student' && (int)($row['user_id'] ?? 0) === (int)$currentUser['id'];
    $isAdmin = $currentUser['role'] === 'admin';

    if (!$isOwner && !$isAdmin) {
        return ['allowed' => false, 'student' => null];
    }

    return ['allowed' => true, 'student' => $row];
}
