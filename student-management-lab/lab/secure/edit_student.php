<?php
/**
 * SECURE edit-student flow — mitigation: explicit authorization check
 * on every load AND every save, never trusting the hidden/URL id alone.
 */

function secure_can_edit_student(array $student, array $currentUser): bool
{
    if ($currentUser['role'] === 'admin') {
        return true;
    }
    return $currentUser['role'] === 'student'
        && (int)($student['user_id'] ?? 0) === (int)$currentUser['id'];
}

function secure_load_student_for_edit(int $id, array $currentUser): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['allowed' => true, 'student' => null];
    }
    if (!secure_can_edit_student($row, $currentUser)) {
        return ['allowed' => false, 'student' => null];
    }
    return ['allowed' => true, 'student' => $row];
}

function secure_save_student(int $id, array $fields, array $currentUser): bool
{
    $pdo = get_pdo();
    // Re-check ownership right before writing - never trust earlier checks alone.
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || !secure_can_edit_student($row, $currentUser)) {
        return false;
    }

    $stmt = $pdo->prepare(
        'UPDATE students SET full_name = ?, phone = ?, address = ?, major = ?, gpa = ? WHERE id = ?'
    );
    $stmt->execute([
        $fields['full_name'], $fields['phone'], $fields['address'],
        $fields['major'], $fields['gpa'], $id,
    ]);
    return true;
}
