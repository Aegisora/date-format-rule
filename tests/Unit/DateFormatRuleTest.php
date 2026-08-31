<?php

namespace Aegisora\Rules\Tests\Unit;

use Aegisora\RuleContract\Exceptions\InvalidRuleContextException;
use Aegisora\RuleContract\Models\Context;
use Aegisora\RuleContract\Models\Result;
use Aegisora\RuleContract\RuleInterface;
use Aegisora\Rules\DateFormatRule;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use stdClass;

class DateFormatRuleTest extends TestCase
{
    /**
     * @dataProvider getConstructorProvidedData
     */
    public function testConstructorCreatesRule(DateFormatRule $rule): void
    {
        self::assertInstanceOf(RuleInterface::class, $rule);
    }

    public static function getConstructorProvidedData(): array
    {
        return [
            'date format' => [
                'rule' => new DateFormatRule('Y-m-d'),
            ],
            'datetime format' => [
                'rule' => new DateFormatRule('Y-m-d H:i:s'),
            ],
            'time format' => [
                'rule' => new DateFormatRule('H:i'),
            ],
            'format with time zone' => [
                'rule' => new DateFormatRule('Y-m-d H:i:s', new DateTimeZone('Europe/Moscow')),
            ],
        ];
    }

    /**
     * @dataProvider getValidateProvidedData
     */
    public function testValidate(
        Context $context,
        string $format,
        array $expectedResult
    ): void {
        self::assertActualResultEqualsExpected(
            (new DateFormatRule($format))->validate($context),
            $expectedResult
        );
    }

    public static function getValidateProvidedData(): array
    {
        return [
            'iso date matches' => [
                'context' => Context::create('2026-08-31'),
                'format' => 'Y-m-d',
                'expectedResult' => self::validResult(),
            ],
            'iso datetime matches' => [
                'context' => Context::create('2026-08-31 23:59:59'),
                'format' => 'Y-m-d H:i:s',
                'expectedResult' => self::validResult(),
            ],
            'leap day matches' => [
                'context' => Context::create('2024-02-29'),
                'format' => 'Y-m-d',
                'expectedResult' => self::validResult(),
            ],
            'time only matches' => [
                'context' => Context::create('14:30'),
                'format' => 'H:i',
                'expectedResult' => self::validResult(),
            ],
            'day only matches' => [
                'context' => Context::create('31'),
                'format' => 'd',
                'expectedResult' => self::validResult(),
            ],
            'no leading zeros matches its format' => [
                'context' => Context::create('2026-8-3'),
                'format' => 'Y-n-j',
                'expectedResult' => self::validResult(),
            ],
            'textual month matches' => [
                'context' => Context::create('31 August 2026'),
                'format' => 'd F Y',
                'expectedResult' => self::validResult(),
            ],
            'consistent weekday matches' => [
                'context' => Context::create('Monday, 31 August 2026'),
                'format' => 'l, d F Y',
                'expectedResult' => self::validResult(),
            ],
            'ordinal suffix matches' => [
                'context' => Context::create('31st August 2026'),
                'format' => 'jS F Y',
                'expectedResult' => self::validResult(),
            ],
            'am pm matches' => [
                'context' => Context::create('2:30 PM'),
                'format' => 'g:i A',
                'expectedResult' => self::validResult(),
            ],
            'escaped literal matches' => [
                'context' => Context::create('2026-08-31T10:20:30'),
                'format' => 'Y-m-d\TH:i:s',
                'expectedResult' => self::validResult(),
            ],
            'unix timestamp matches' => [
                'context' => Context::create('1756645200'),
                'format' => 'U',
                'expectedResult' => self::validResult(),
            ],
            'empty string does not match' => [
                'context' => Context::create(''),
                'format' => 'Y-m-d',
                'expectedResult' => self::invalidResult(),
            ],
            'non date garbage does not match' => [
                'context' => Context::create('not-a-date'),
                'format' => 'Y-m-d',
                'expectedResult' => self::invalidResult(),
            ],
            'overflow february does not match' => [
                'context' => Context::create('2026-02-31'),
                'format' => 'Y-m-d',
                'expectedResult' => self::invalidResult(),
            ],
            'february 29th on non leap year does not match' => [
                'context' => Context::create('2026-02-29'),
                'format' => 'Y-m-d',
                'expectedResult' => self::invalidResult(),
            ],
            'month out of range does not match' => [
                'context' => Context::create('2026-13-01'),
                'format' => 'Y-m-d',
                'expectedResult' => self::invalidResult(),
            ],
            'hour out of range does not match' => [
                'context' => Context::create('2026-08-31 24:00:00'),
                'format' => 'Y-m-d H:i:s',
                'expectedResult' => self::invalidResult(),
            ],
            'missing leading zero does not match' => [
                'context' => Context::create('2026-8-3'),
                'format' => 'Y-m-d',
                'expectedResult' => self::invalidResult(),
            ],
            'wrong separator does not match' => [
                'context' => Context::create('2026/08/31'),
                'format' => 'Y-m-d',
                'expectedResult' => self::invalidResult(),
            ],
            'trailing garbage does not match' => [
                'context' => Context::create('2026-08-31 extra'),
                'format' => 'Y-m-d',
                'expectedResult' => self::invalidResult(),
            ],
            'leading garbage does not match' => [
                'context' => Context::create('x2026-08-31'),
                'format' => 'Y-m-d',
                'expectedResult' => self::invalidResult(),
            ],
            'partial input does not match' => [
                'context' => Context::create('2026-08-31'),
                'format' => 'Y-m-d H:i:s',
                'expectedResult' => self::invalidResult(),
            ],
            'inconsistent weekday does not match' => [
                'context' => Context::create('Sunday, 31 August 2026'),
                'format' => 'l, d F Y',
                'expectedResult' => self::invalidResult(),
            ],
        ];
    }

    public function testValidateHonoursTheProvidedTimeZone(): void
    {
        $rule = (new DateFormatRule('Y-m-d H:i:s', new DateTimeZone('Europe/Moscow')));

        self::assertActualResultEqualsExpected(
            $rule->validate(Context::create('2026-08-31 12:00:00')),
            self::validResult()
        );
    }

    /**
     * @dataProvider getInvalidContextProvidedData
     */
    public function testThrowsInvalidRuleContextException(Context $context): void
    {
        $this->expectException(InvalidRuleContextException::class);

        (new DateFormatRule('Y-m-d'))->validate($context);
    }

    public static function getInvalidContextProvidedData(): array
    {
        return [
            'context value - true' => [
                'context' => Context::create(true),
            ],
            'context value - false' => [
                'context' => Context::create(false),
            ],
            'context value - zero integer' => [
                'context' => Context::create(0),
            ],
            'context value - positive integer' => [
                'context' => Context::create(20260831),
            ],
            'context value - negative integer' => [
                'context' => Context::create(-1),
            ],
            'context value - zero float' => [
                'context' => Context::create(0.0),
            ],
            'context value - positive float' => [
                'context' => Context::create(2026.0831),
            ],
            'context value - negative float' => [
                'context' => Context::create(-0.01),
            ],
            'context value - null' => [
                'context' => Context::create(null),
            ],
            'context value - not empty array' => [
                'context' => Context::create(['2026-08-31',]),
            ],
            'context value - empty array' => [
                'context' => Context::create([]),
            ],
            'context value - object' => [
                'context' => Context::create(new stdClass()),
            ],
            'context value - callable' => [
                'context' => Context::create(
                    static function () {
                    }
                ),
            ],
            'context value - resource' => [
                'context' => Context::create(tmpfile()),
            ],
        ];
    }

    public function testThrowsInvalidRuleContextExceptionOnEmptyFormat(): void
    {
        $this->expectException(InvalidRuleContextException::class);

        (new DateFormatRule(''))->validate(Context::create('2026-08-31'));
    }

    private static function validResult(): array
    {
        return [
            'isValid' => true,
            'failedRuleCode' => null,
        ];
    }

    private static function invalidResult(): array
    {
        return [
            'isValid' => false,
            'failedRuleCode' => 'date_format_rule',
        ];
    }

    private static function assertActualResultEqualsExpected(
        Result $result,
        array $expectedResult
    ): void {
        self::assertEquals($expectedResult['isValid'], $result->isValid());
        self::assertEquals($expectedResult['failedRuleCode'], $result->getFailedRuleCode());
    }
}
