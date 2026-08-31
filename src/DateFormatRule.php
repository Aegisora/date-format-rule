<?php

namespace Aegisora\Rules;

use Aegisora\RuleContract\Exceptions\InvalidRuleContextException;
use Aegisora\RuleContract\Models\Context;
use Aegisora\RuleContract\Models\Result;
use Aegisora\RuleContract\Rule;
use DateTimeImmutable;
use DateTimeZone;

class DateFormatRule extends Rule
{
    private string $format;
    private ?DateTimeZone $timeZone;

    public function __construct(
        string $format,
        ?DateTimeZone $timeZone = null
    ) {
        $this->format = $format;
        $this->timeZone = $timeZone;
    }

    protected function executeValidate(Context $context): Result
    {
        $this->validateFormat($this->format);

        $value = $context->getValue();

        if (!is_string($value)) {
            throw new InvalidRuleContextException();
        }

        return $this->matchesFormat($value)
            ? $this->getDefaultValidResult()
            : $this->getDefaultInvalidResult();
    }

    /**
     * @throws InvalidRuleContextException
     */
    private function validateFormat(string $format): void
    {
        if ($format === '') {
            throw new InvalidRuleContextException();
        }
    }

    private function matchesFormat(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!' . $this->format, $value, $this->timeZone);

        if ($date === false) {
            return false;
        }

        if ($this->hasParsingProblems()) {
            return false;
        }

        return $date->format($this->format) === $value;
    }

    private function hasParsingProblems(): bool
    {
        $errors = DateTimeImmutable::getLastErrors();

        return is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
    }
}
