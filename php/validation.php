<?php
const QUESTIONS = [
    1 => 'What was the name of your first pet?',
    2 => 'Who was your best friend in elementary school?',
    3 => 'Who was your favorite teacher in high school?',
    4 => "What is your mother's maiden first name?",
    5 => 'What street did you live on in Grade 1?',
    6 => 'What was the first song you learned by heart?',
    7 => 'What was the name of your elementary school?',
    8 => 'What dish did you cook for the first time?',
];
const PROGRAMS = ['BSIT', 'BSCS', 'BSIS', 'BSCE', 'BSEE', 'BSBA', 'BEED', 'BSED'];
const ROMAN = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];

function title_error(string $label, string $v): ?string {
    if (strlen($v) > 1 && $v === strtoupper($v) && preg_match('/[A-Z]/', $v)) return 'Do not use all capital letters.';
    foreach (explode(' ', $v) as $w) {
        if ($w === '') continue;
        if (!preg_match('/^[A-Z][a-z]*$/', $w)) {
            if (ctype_lower($w[0])) return str_contains($label, 'Name') ? 'First letter of your name must be capital.' : "First letter of each word in $label must be capital.";
            return "Only the first letter of each word in $label may be capital.";
        }
    }
    return null;
}
function v_name(string $label, string $v, bool $required): ?string {
    if ($v === '') return $required ? "$label is required." : null;
    if (str_contains($v, '  ')) return 'Double space is not allowed.';
    if (preg_match('/\d/', $v)) return "Numbers are not allowed in $label.";
    if (preg_match('/[^A-Za-z ]/', $v)) return "Special characters are not allowed in $label.";
    if (strlen($v) < 2) return "$label must be at least 2 characters.";
    if (strlen($v) > NAME_MAX) return "$label must not be longer than " . NAME_MAX . " characters.";
    if (preg_match('/(.)\1\1/i', $v)) return 'Three consecutive identical letters are not allowed.';
    return title_error($label, $v);
}
function v_place(string $label, string $v): ?string {
    if ($v === '') return "$label is required.";
    if (str_contains($v, '  ')) return 'Double space is not allowed.';
    if (preg_match('/[^A-Za-z ]/', $v)) return "$label may contain letters and spaces only.";
    if (strlen($v) < 2 || strlen($v) > 50) return "$label must be 2 to 50 characters.";
    return title_error($label, $v);
}
function v_street(string $v): ?string {
    if ($v === '') return 'Purok / Street is required.';
    if (str_contains($v, '  ')) return 'Double space is not allowed.';
    if (preg_match('/[^A-Za-z0-9 ]/', $v)) return 'Purok / Street may contain letters, digits and spaces only.';
    if (strlen($v) < 3 || strlen($v) > 50) return 'Purok / Street must be 3 to 50 characters.';
    return null;
}
function v_answer(string $label, string $v): ?string {
    if ($v === '') return "$label is required.";
    if (str_contains($v, '  ')) return 'Double space is not allowed.';
    if (preg_match('/[^A-Za-z ]/', $v)) return "$label may contain letters and spaces only.";
    if (strlen($v) < 2 || strlen($v) > 50) return "$label must be 2 to 50 characters.";
    return null;
}
function age_from(string $d): int { return (new DateTime($d))->diff(new DateTime('today'))->y; }
function valid_date(string $v): ?DateTime {
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $d : null;
}
function v_birthdate(string $v): ?string {
    $d = valid_date($v);
    if (!$d) return 'Please enter a valid birthdate.';
    if ($d > new DateTime('today')) return 'Birthdate cannot be in the future.';
    if (age_from($v) < 18) return 'You must be at least 18 years old to register.';
    return null;
}
/* Password strength rules (my definition):
   Strong = 12+ characters with all four types; Medium = 8+ characters with at least three types; else Weak. */
function pw_strength(string $p): string {
    $t = (int)preg_match('/[a-z]/', $p) + (int)preg_match('/[A-Z]/', $p) + (int)preg_match('/\d/', $p) + (int)preg_match('/[^A-Za-z0-9]/', $p);
    if (strlen($p) >= 12 && $t === 4) return 'Strong';
    if (strlen($p) >= PASS_MIN && $t >= 3) return 'Medium';
    return 'Weak';
}
function v_password(string $p): ?string {
    if (strlen($p) < PASS_MIN) return 'Password must be at least ' . PASS_MIN . ' characters.';
    if (strlen($p) > PASS_MAX) return 'Password must not be longer than ' . PASS_MAX . ' characters.';
    if (pw_strength($p) === 'Weak') return 'Password is too weak. Use upper and lower case letters, numbers and symbols.';
    return null;
}

/* Server-side validation for every registration field (core + system fields) */
function validate_registration(array $in): array {
    $e = [];
    if (!preg_match('/^\d{4}-\d{4}$/', $in['id_number'])) $e['id_number'] = 'ID Number must follow the format xxxx-xxxx.';
    if ($x = v_name('First Name', $in['first_name'], true)) $e['first_name'] = $x;
    if ($x = v_name('Middle Name', $in['middle_name'], false)) $e['middle_name'] = $x;
    if ($x = v_name('Family Name', $in['family_name'], true)) $e['family_name'] = $x;
    if (!in_array($in['name_ext'], ['', 'Jr.', 'Sr.', 'Other'], true)) $e['name_ext'] = 'Please select a valid Name Extension.';
    elseif ($in['name_ext'] === 'Other' && !in_array($in['name_ext_other'], ROMAN, true)) $e['name_ext_other'] = 'Name Extension must be a Roman numeral such as I, II, III, IV, IX or X.';
    if ($x = v_birthdate($in['birthdate'])) $e['birthdate'] = $x;
    if ($x = v_street($in['street'])) $e['street'] = $x;
    foreach (['barangay' => 'Barangay', 'municipality' => 'Municipality / City', 'province' => 'Province', 'country' => 'Country'] as $k => $l)
        if ($x = v_place($l, $in[$k])) $e[$k] = $x;
    if (!preg_match('/^\d{4}$/', $in['zip'])) $e['zip'] = 'ZIP Code must be exactly 4 digits.';
    if ($in['email'] === '' || strlen($in['email']) > 100 || preg_match('/\s/', $in['email']) || !filter_var($in['email'], FILTER_VALIDATE_EMAIL))
        $e['email'] = 'Please enter a valid email address.';
    $u = $in['username'];
    if (!preg_match('/^[A-Za-z]/', $u)) $e['username'] = 'Username must start with a letter.';
    elseif (preg_match('/[^A-Za-z0-9]/', $u)) $e['username'] = 'Username may contain letters and digits only.';
    elseif (strlen($u) < USER_MIN || strlen($u) > USER_MAX) $e['username'] = 'Username must be ' . USER_MIN . ' to ' . USER_MAX . ' characters.';
    if ($x = v_password($in['password'])) $e['password'] = $x;
    if ($in['password'] !== $in['confirm']) $e['confirm'] = 'Passwords do not match.';
    foreach ([1, 2, 3] as $i) {
        if (!isset(QUESTIONS[(int)$in["q$i"]])) $e["q$i"] = 'Please choose a valid question.';
        if ($x = v_answer("Answer $i", $in["a$i"])) $e["a$i"] = $x;
    }
    if (!isset($e['q1']) && !isset($e['q2']) && !isset($e['q3']) && count(array_unique([$in['q1'], $in['q2'], $in['q3']])) < 3)
        $e['q2'] = 'Please choose three different questions.';
    if (!preg_match('/^09\d{9}$/', $in['emergency'])) $e['emergency'] = 'Contact number must be 11 digits and start with 09.';
    if (!in_array($in['program'], PROGRAMS, true)) $e['program'] = 'Please select a valid program.';
    $de = valid_date($in['date_enrolled']);
    if (!$de || $de > new DateTime('today')) $e['date_enrolled'] = 'Date Enrolled must be a valid date that is not in the future.';
    if ($in['remarks'] !== '' && (!preg_match('/^[A-Za-z, ]+$/', $in['remarks']) || strlen($in['remarks']) > 100))
        $e['remarks'] = 'Remarks may contain letters, commas and spaces only (maximum 100 characters).';
    if ($in['consent'] !== '1') $e['consent'] = 'You must agree to the Privacy Notice to register.';
    return $e;
}
