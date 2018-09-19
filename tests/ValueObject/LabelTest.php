<?php

declare(strict_types=1);

namespace App\Tests\ValueObject;

use App\Entity\Label;
use PHPUnit\Framework\TestCase;

class LabelTest extends TestCase
{
    /**
     * @dataProvider provideLabelNames
     *
     * @param string $givenLabel
     * @param string $expectedNormalizedLabel
     * @param string $expectedWithoutEmoji
     */
    public function testLabelNormalization(
        string $givenLabel,
        string $expectedNormalizedLabel,
        string $expectedWithoutEmoji
    ): void {
        $label = new Label($givenLabel);
        $this->assertEquals($expectedNormalizedLabel, $label->getNormalizedName());
        $this->assertEquals($expectedWithoutEmoji, $label->withoutEmoji());
    }

    public function provideLabelNames(): array
    {
        return [
            ['feature', 'feature', 'feature'],
            ['doc', 'doc', 'doc'],
            ['help wanted', 'help wanted', 'help wanted'],
            ['help wanted :skier:', 'help wanted :skier:', 'help wanted'],
            [':skier: :skier: help wanted', ':skier: :skier: help wanted', 'help wanted'],
            ['help wanted :invalid_invalid_emoji:', 'help wanted :invalid_invalid_emoji:', 'help wanted'],
            ['help wanted :1 :2', 'help wanted :1 :2', 'help wanted :1 :2'],
            ['    BUG ', 'bug', 'BUG'],
            ['HELP wanted', 'help wanted', 'HELP wanted'],
        ];
    }

    public function testItIsNotPossibleToCreateEmptyLabel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Label('');
    }
}
