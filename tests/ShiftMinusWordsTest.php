<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Keywords\KeywordsGenerator as KG;

/**
 * Модульные тесты для перемещение минус-слов в конец фразы
 */
class ShiftMinusWordsTest extends TestCase
{
    /**
     * Выполняет простой первый тест
     */
    public function testShiftMinusWords()
    {
        $this->assertEqualsCanonicalizing(
            KG::shiftMinusWords([
                "-Honda Fit",
                "Toyota -Axio"
            ]), [
                "Fit -Honda",
                "Toyota -Axio"
            ]
        );
    }

    /**
     * Проверяет входные данные в виде пустой строки
     */
    public function testEmptyString()
    {
        $this->assertEqualsCanonicalizing(
            KG::shiftMinusWords([]), []
        );
    }

    /**
     * Проверяет только минус-слова
     */
    public function testMinusWordsOnly()
    {
        $this->assertEqualsCanonicalizing(
            KG::shiftMinusWords([
                "-Honda -Fit",
                "Toyota Axio"
            ]), [
                "-Honda -Fit",
                "Toyota Axio"
            ]);
    }
}
