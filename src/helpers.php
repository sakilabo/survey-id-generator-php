<?php

/**
 * Return the expiration date ($created_at + $retention_days) formatted with the given date() format.
 *
 * @param string $created_at_iso ISO 8601 datetime string
 * @param int    $retention_days retention period in days
 * @param string $format         date() format string (e.g. 'Y年n月j日' or 'F j, Y')
 * @return string formatted expiration date
 */
function format_expiration_date(string $created_at_iso, int $retention_days, string $format): string
{
    return date($format, strtotime($created_at_iso . ' +' . $retention_days . ' days'));
}
