<?php
/**
 * SECURE announcements/comments rendering — mitigation: content is
 * still stored exactly as submitted (it is treated as untrusted data,
 * not sanitized/rewritten at write time), but it is ALWAYS passed
 * through htmlspecialchars() at render time, so any HTML/JS in it is
 * displayed as inert text instead of being executed.
 */

function render_announcement_output(string $content): string
{
    return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
}
