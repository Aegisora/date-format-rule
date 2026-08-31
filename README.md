# Aegisora Date Format Rule

[![Latest Version](https://img.shields.io/packagist/v/aegisora/date-format-rule?style=flat-square)](https://packagist.org/packages/aegisora/date-format-rule)
[![Total Downloads](https://img.shields.io/packagist/dt/aegisora/date-format-rule?style=flat-square)](https://packagist.org/packages/aegisora/date-format-rule)
![Code Coverage Badge](./badge.svg)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
![PHPStan Badge](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat)

Date Format Rule provides a simple, rule-based date/time format validation implementation for the Aegisora ecosystem.

It is built on top of [`aegisora/rule-contract`](https://github.com/Aegisora/rule-contract) and follows its strict validation architecture, ensuring consistent and predictable behavior across applications.

This rule is useful for validating user input, form fields, birth dates, appointment times, API request parameters, imported data, and any other string that must conform to a specific date/time format.

---

## 📑 Table of Contents
- [Features](#-features)
- [Installation](#-installation)
- [Core Concept](#-core-concept)
- [Basic Usage](#-basic-usage)
- [Valid vs Invalid](#-valid-vs-invalid)
- [Validation Result](#-validation-result)
- [Time Zones](#-time-zones)
- [Guardian Usage](#-guardian-usage)
- [Real-World Examples](#-real-world-examples)
- [Constructor & API](#-constructor--api)
- [Architecture](#-architecture)
- [License](#-license)
- [Contributing](#-contributing)
- [Support](#-support)

---

## ✨ Features
- 🔹 Lightweight and dependency-free except `aegisora/rule-contract`
- 🔹 Validates a string against any PHP date/time format (`Y-m-d`, `d/m/Y H:i`, `H:i:s`, ...)
- 🔹 Supports the full [PHP format syntax](https://www.php.net/manual/en/datetime.format.php), including escaped literals (`\T`)
- 🔹 Exhaustive check — rejects overflow dates (`2026-02-31`), out-of-range values, and non-canonical input the parser silently tolerates
- 🔹 Optional time zone support
- 🔹 Rejects non-string input as an invalid context
- 🔹 Fully compatible with Aegisora validation pipeline
- 🔹 Strict `Context` → `Result` validation flow
- 🔹 No raw booleans — only structured results
- 🔹 Safe execution via base `Rule` abstraction
- 🔹 Ready to use out of the box

---

## 📦 Installation

```bash
composer require aegisora/date-format-rule
```

---

## 🚀 Core Concept

This package implements a single validation rule:

- accepts a string value via `Context`
- checks whether the string is a valid date/time that **strictly** matches the configured format
- returns a standardized `Result`

Under the hood it wraps the common — and easy to get wrong — boilerplate:

```php
$date = DateTimeImmutable::createFromFormat('!' . $format, $value);
$errors = DateTimeImmutable::getLastErrors();
// $date !== false, no warnings/errors, and $date->format($format) === $value
```

into a reusable rule that reports its outcome through a `Result` object instead of a raw boolean.

---

## 🏗️ Basic Usage

```php
use Aegisora\RuleContract\Models\Context;
use Aegisora\Rules\DateFormatRule;

$result = (new DateFormatRule('Y-m-d'))->validate(Context::create('2026-08-31'));

if ($result->isValid()) {
    // value is a valid date in the given format
} else {
    // value does not match the format
}
```

---

## ✅ Valid vs Invalid

The rule passes when the string is a real date/time that renders back exactly as the input under the configured format, and fails otherwise.

### Dates

```php
(new DateFormatRule('Y-m-d'))->validate(Context::create('2026-08-31')); // valid   — a real calendar date
(new DateFormatRule('Y-m-d'))->validate(Context::create('2024-02-29')); // valid   — 2024 is a leap year

(new DateFormatRule('Y-m-d'))->validate(Context::create('2026-02-31')); // invalid — February has no 31st
(new DateFormatRule('Y-m-d'))->validate(Context::create('2026-02-29')); // invalid — 2026 is not a leap year
(new DateFormatRule('Y-m-d'))->validate(Context::create('2026-13-01')); // invalid — month out of range
```

### Strict formatting

```php
(new DateFormatRule('Y-m-d'))->validate(Context::create('2026-8-3'));         // invalid — missing leading zeros
(new DateFormatRule('Y-m-d'))->validate(Context::create('2026/08/31'));       // invalid — wrong separator
(new DateFormatRule('Y-m-d'))->validate(Context::create('2026-08-31 extra')); // invalid — trailing garbage

(new DateFormatRule('Y-n-j'))->validate(Context::create('2026-8-3'));         // valid   — the format allows no leading zeros
```

### Times and combined formats

```php
(new DateFormatRule('H:i'))->validate(Context::create('14:30'));      // valid
(new DateFormatRule('H:i:s'))->validate(Context::create('24:00:00')); // invalid — hour out of range

(new DateFormatRule('d F Y'))->validate(Context::create('31 August 2026'));             // valid — textual month
(new DateFormatRule('Y-m-d\TH:i:s'))->validate(Context::create('2026-08-31T10:20:30')); // valid — escaped literal T
```

---

## 🧪 Validation Result

If the string is a valid date/time matching the format, the rule returns a valid result.

`$result->isValid(); // true`

If the string does not match the format, the rule returns an invalid result.

```php
$result->isValid(); // false
$result->getFailedRuleCode(); // date_format_rule
```

If the context value is not a string, the rule throws:

`Aegisora\RuleContract\Exceptions\InvalidRuleContextException`

If the configured format is an empty string, the rule throws:

`Aegisora\RuleContract\Exceptions\InvalidRuleContextException`

---

## 🌍 Time Zones

An optional `DateTimeZone` can be passed as the second argument. It is used while parsing the value, which matters for formats that carry time information.

```php
use Aegisora\RuleContract\Models\Context;
use Aegisora\Rules\DateFormatRule;
use DateTimeZone;

$rule = new DateFormatRule('Y-m-d H:i:s', new DateTimeZone('Europe/Moscow'));

$rule->validate(Context::create('2026-08-31 12:00:00')); // valid
```

If the format itself carries a time zone (`e`, `T`, `P`, `O`), that value takes precedence and the argument is ignored.

---

## 🔗 Guardian Usage

This rule can be used together with `aegisora/guardian` to build fluent validation pipelines.

```php
use Aegisora\Guardian\Guardian;
use Aegisora\Rules\DateFormatRule;
use App\Exceptions\InvalidBirthDateException;

$guardian = new Guardian();

$guardian
    ->that($birthDate)
    ->must(new DateFormatRule('Y-m-d'), new InvalidBirthDateException())
    ->validate();
```

If the value does not match the format, `Guardian` throws the provided domain exception.

---

## 🧭 Real-World Examples

Date Format Rule is useful for enforcing date/time constraints before values are persisted or processed.

Examples

```text
User Registration:

require a birth date in ISO format (Y-m-d)
```
```text
Scheduling:

ensure an appointment time matches H:i and represents a real time of day
```
```text
Imports:

reject CSV rows whose date column is not a real calendar date
```
```text
API:

reject request parameters that do not match the expected date/time shape
```

---

## 🧩 Constructor & API
`new DateFormatRule($format);`
- creates a rule that passes when the value is a valid date/time strictly matching the PHP `$format`

`new DateFormatRule($format, $timeZone);`
- additionally applies the given `DateTimeZone` while parsing the value

`(new DateFormatRule($format))->validate($context);`
- `$context` — `Context` wrapping the string value to validate

---

## 🏛️ Architecture

This package relies on [`aegisora/rule-contract`](https://github.com/Aegisora/rule-contract).

Flow:
1. `validate()` is called
2. `Context` is passed in
3. The configured format is checked; an empty format raises `InvalidRuleContextException`
4. The string value is extracted from context (non-strings raise `InvalidRuleContextException`)
5. The value is parsed with `DateTimeImmutable::createFromFormat()`, checked against `getLastErrors()` for overflow/out-of-range warnings, and round-tripped back through the format to reject non-canonical input
6. `Result` is returned — valid on a strict match, invalid with the `date_format_rule` code otherwise

All logic is safely handled by Rule contract.

---

## ⚖️ License

This package is open-source and licensed under the MIT License. See the [LICENSE](LICENSE) for details.

---

## 🌱 Contributing

Contributions are welcome and greatly appreciated! See the [CONTRIBUTING](CONTRIBUTING.md) for details.

---

## 🌟 Support

If you find this project useful, please consider giving it a star on GitHub!

It helps the project grow and motivates further development.
