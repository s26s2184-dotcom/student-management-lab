<?php
/**
 * VULNERABLE announcements/comments rendering.
 *
 * Vulnerability: Stored XSS. Announcement and comment content is
 * saved to MySQL as submitted, then echoed back to every viewer
 * without any output encoding. A harmless payload submitted by one
 * user (e.g. in a comment) will execute in the browser of every
 * other user who later views that announcement.
 *
 * Stored XSS PoC (submit as a comment or announcement body):
 *   <script>alert('Stored XSS by ' + document.cookie)</script>
 *   <img src=x onerror="alert('stored-xss-poc')">
 */

function render_announcement_output(string $content): string
{
    // VULNERABLE: returned as-is, no htmlspecialchars().
    return $content;
}
