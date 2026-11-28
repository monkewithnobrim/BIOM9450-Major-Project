<?php
// Reusable server-side validation. Each function returns '' if valid, else an error message.

// Removes spaces from the start and end of a submitted value and makes sure it is text.
// Run every $_POST value through this before validating it.
function clean($value) {
    return trim((string)$value); // (string) turns the input into text
}

// Checks a name field (first name, last name): it must not be empty, must fit the
// database column length ($max, default 40) and may only contain letters, spaces,
// hyphens and apostrophes (so "O'Brien" and "Mary-Jane" pass, "123" does not).
// $value is what user typed, $label is the field's name used in messages (e.g. First Name)
function validate_name($value, $label, $max = 40) {
    if ($value === '')                  return "$label is required."; // catches empty field and returns message
    // mb_strlen() counts characters (mb for multibyte), normal strlen() will count non-English letters as 2 or more bytes each
    if (mb_strlen($value) > $max)       return "$label must be $max characters or fewer."; 
    // preg_match() tests the text againsts a pattern, this pattern /^[\p{L}][\p{L} '\-]*$/u 
        // first character must be a letter
        // only allows letters with at most one space, apostrophe or hypen
    // e.g. "Anna", "O'Brien", "Mary-Jane" pass while "@x" fail 
    if (!preg_match("/^[\p{L}][\p{L} '\-]*$/u", $value)) // if text does not meet the above pattern requirements, return error message
        return "$label can only contain letters, spaces, hyphens and apostrophes.";
    return '';
}

// Checks a date field (date of birth, admission date): it must be filled in, be a real
// calendar date in YYYY-MM-DD format, not be in the future (unless $allowFuture is true)
// and not be before 1900.
// The first return that runs ends the function. If it returns '', the value is fine.
// $allowFuture = false as default meaning future dates are rejected unless caller passes true
function validate_date($value, $label, $allowFuture = false) {
    if ($value === '') return "$label is required.";        // 1. empty?
    // createFromFormat() reads the text using the given pattern (Y - 4 digits year, m = month, d = day)
    // returns a date object or false if the text does not fit. The ! is needed to set everything not in the pattern to zero
    // Without the !, the date would carry the current time making today's date look later than "today at midnight"
    $d = DateTime::createFromFormat('!Y-m-d', $value);       // 2. try to read it as YYYY-MM-DD
    // !$d is true when reading failed, format('Y-m-d') turns the date object back to text.
    // the text is compared with what the user typed and it will be different when PHP rolls over impossible dates (e.g. 2026-02-30 becomes 2026-03-02)
    // which is different from what the user input hence is rejected
    if (!$d || $d->format('Y-m-d') !== $value)              // 3. unreadable, or something like 2026-02-30?
        return "$label must be a real date (YYYY-MM-DD).";
    // !$allowFuture is true when future dates are not allowed
    // new DateTime('today') is today's date at midnight and dates can be compared with > (later than)
    if (!$allowFuture && $d > new DateTime('today'))        // 4. in the future?
        return "$label cannot be in the future.";
    if ($d < new DateTime('1900-01-01'))                    // 5. absurdly old?
        return "$label is not a plausible date.";
    return '';                                              // all checks passed
}

// Checks that a value is one of an allowed list, such as gender (F, M, Other) or a
// dispensing status. This matches the ENUM columns in the database, and also stops
// anyone sending a made-up value instead of one of the dropdown options.
// array $allowed means this input must be an array
function validate_in_list($value, array $allowed, $label) {
    // in_array() asks if $value is one of the items in the array $allowed
    // true is added at the end so type and value must strictly match
    return in_array($value, $allowed, true) ? '' : "Please choose a valid $label.";
}

// Checks a room code: it must be filled in and look like A01, one capital letter
// followed by two digits.
function validate_room($value) {
    if ($value === '') return 'Room is required.';
    // pattern: '/^[A-Z][0-9]{2}$/' first character must be a capital letter followed by 2 digits exacylu
    if (!preg_match('/^[A-Z][0-9]{2}$/', $value)) return 'Room must look like A01 (a letter and two digits).';
    return '';
}

// Checks an optional photo upload (pass in $_FILES['photo']): no file is allowed, but
// if one is sent it must upload without error, be 2 MB or smaller and really be a JPG
// or PNG image. The type is read from the file's contents, not from its name.
// $file is the upload information PHP builds for each file: name, size, error code and temporary location
function validate_photo($file) {
    // Safety check on the input itself, if something was supplied ($file !== null), it must be an array
    // with a whole-number 'error' entry. If it is not an array, has no 'error' entry (!isset) or entry is not an integer, it is rejected
    if ($file !== null && (!is_array($file) || !isset($file['error']) || !is_int($file['error']))) return 'Invalid photo upload.';
    // isset($file) is false when nothing was passed in. UPLOAD_ERR_NO_FILE is PHP's code for "no file was chosen".
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return '';   // photo is optional so return ''
    // UPLOAD_ERR_OK (0) means the upload worked. Any other code (file too big for the server, cut off, etc.) is an error.
    if ($file['error'] !== UPLOAD_ERR_OK)  return 'The photo could not be uploaded.';
    // $file['size'] is the size in bytes. 2 * 1024 * 1024 = 2,097,152 bytes, which is 2 MB (1024 bytes = 1 KB, 1024 KB = 1 MB)
    if ($file['size'] > 2 * 1024 * 1024)   return 'The photo must be 2 MB or smaller.'; // file size too big
    // tmp_name is the temporary file where PHP stored the upload and is_uploaded_file is true only
    // if that file arrive through this form's upload not some other file on the server.
    if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return 'Invalid photo upload.';
    // new finfo(FILEINFO_MINE_TYPE) creates a file inspector that reports the file type (e.g. jpeg)
    // ->file() used to look at the real content of file instead of its name so it still works when renamed
    // the result is stored in $mime
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    // only allow JPEG and PNG images, return error message otherwise
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) return 'The photo must be a JPG or PNG image.';
    return '';
}