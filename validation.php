<?php
// Reusable server-side validation. Each function returns '' if valid, else an error message.

// Removes spaces from the start and end of a submitted value and makes sure it is text.
// Run every $_POST value through this before validating it.
function clean($value) {
    return trim((string)$value);
}

// Checks a name field (first name, last name): it must not be empty, must fit the
// database column length ($max, default 40) and may only contain letters, spaces,
// hyphens and apostrophes (so "O'Brien" and "Mary-Jane" pass, "123" does not).
function validate_name($value, $label, $max = 40) {
    if ($value === '')                  return "$label is required.";
    if (mb_strlen($value) > $max)       return "$label must be $max characters or fewer.";
    if (!preg_match("/^[\p{L}][\p{L} '\-]*$/u", $value))
        return "$label can only contain letters, spaces, hyphens and apostrophes.";
    return '';
}

// Checks a date field (date of birth, admission date): it must be filled in, be a real
// calendar date in YYYY-MM-DD format, not be in the future (unless $allowFuture is true)
// and not be before 1900.
// The first return that runs ends the function. If it returns '', the value is fine.
function validate_date($value, $label, $allowFuture = false) {
    if ($value === '') return "$label is required.";        // 1. empty?
    $d = DateTime::createFromFormat('Y-m-d', $value);       // 2. try to read it as YYYY-MM-DD
    if (!$d || $d->format('Y-m-d') !== $value)              // 3. unreadable, or something like 2026-02-30?
        return "$label must be a real date (YYYY-MM-DD).";
    if (!$allowFuture && $d > new DateTime('today'))        // 4. in the future?
        return "$label cannot be in the future.";
    if ($d < new DateTime('1900-01-01'))                    // 5. absurdly old?
        return "$label is not a plausible date.";
    return '';                                              // all checks passed
}

// Checks that a value is one of an allowed list, such as gender (F, M, Other) or a
// dispensing status. This matches the ENUM columns in the database, and also stops
// anyone sending a made-up value instead of one of the dropdown options.
function validate_in_list($value, array $allowed, $label) {
    return in_array($value, $allowed, true) ? '' : "Please choose a valid $label.";
}

// Checks a room code: it must be filled in and look like A01, one capital letter
// followed by two digits.
function validate_room($value) {
    if ($value === '') return 'Room is required.';
    if (!preg_match('/^[A-Z][0-9]{2}$/', $value)) return 'Room must look like A01 (a letter and two digits).';
    return '';
}

// Checks an optional photo upload (pass in $_FILES['photo']): no file is allowed, but
// if one is sent it must upload without error, be 2 MB or smaller and really be a JPG
// or PNG image. The type is read from the file's contents, not from its name.
function validate_photo($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return '';   // photo is optional
    if ($file['error'] !== UPLOAD_ERR_OK)  return 'The photo could not be uploaded.';
    if ($file['size'] > 2 * 1024 * 1024)   return 'The photo must be 2 MB or smaller.';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) return 'The photo must be a JPG or PNG image.';
    return '';
}