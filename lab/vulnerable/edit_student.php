<?php
/**
 * VULNERABLE edit-student flow.
 *
 * Vulnerability demonstrated: IDOR (missing authorization). Any logged
 * in user can load and submit edits for ANY student id, not just their
 * own profile or as an admin. (The SQL itself uses bound parameters
 * here so this file isolates the IDOR concern; SQL injection is
 * demonstrated separately via student.php / students.php.)
 */

function vulnerable_load_student_for_edit(int $id): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
    // No check that current user owns this record or is an admin.
}

function vulnerable_save_student(int $id, array $fields): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'UPDATE students SET full_name = ?, phone = ?, address = ?, major = ?, gpa = ? WHERE id = ?'
    );
    $stmt->execute([
        $fields['full_name'], $fields['phone'], $fields['address'],
        $fields['major'], $fields['gpa'], $id,
    ]);
    // Again: no check that the current session user is allowed to edit this id.
}
