<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Keywords\KeywordsGenerator as KG;

/**
 * Модульные тесты для этапа генерации
 */
class ExplodeAndCombineTest extends TestCase
{
    /**
     * Выполняет простой первый тест
     */
    public function testGeneration()
    {
        $this->assertEqualsCanonicalizing(
            KG::combineKeywordsToPhrases(
                KG::explodeToKeywords("Honda, Toyota\nFit, Axio")
            ), [
                "Honda Fit",
                "Honda Axio",
                "Toyota Fit",
                "Toyota Axio"
            ]
        );
    }

    /**
     * Проверяет входные данные в виде пустой строк
     */
    public function testEmptyString()
    {
        $this->assertEqualsCanonicalizing(
            KG::combineKeywordsToPhrases(
                KG::explodeToKeywords("")
            ), []
        );
    }

    /**
     * Проверяет входные данные в виде одной строки
     */
    public function testOneString()
    {
        $this->assertEqualsCanonicalizing(
            KG::combineKeywordsToPhrases(
                KG::explodeToKeywords("Honda, Toyota")
            ), [
                "Honda",
                "Toyota"
            ]);
    }

    /**
     * Проверяет различные символы переноса строк
     */
    public function testReturnChars()
    {
        $this->assertEqualsCanonicalizing(
            KG::combineKeywordsToPhrases(
                KG::explodeToKeywords("Honda\n\rToyota\nFit\rAxio")
            ), [
                "Honda Toyota Fit Axio"
            ]
        );
    }

    /**
     * Проверяет наличие последовательности пробелов
     */
    public function testSpacesSequence()
    {
        $this->assertEqualsCanonicalizing(
            KG::combineKeywordsToPhrases(
                KG::explodeToKeywords("    Honda    ,     Toyota     \n     Fit     ,    Axio     ")
            ), [
                "Honda Fit",
                "Honda Axio",
                "Toyota Fit",
                "Toyota Axio"
            ]
        );
    }

    /**
     * Проверяет наличие последовательности запятых
     */
    public function testCommaSequence()
    {
        $this->assertEqualsCanonicalizing(
            KG::combineKeywordsToPhrases(
                KG::explodeToKeywords(",,,Honda,,, ,,,Toyota,,,\n,,,Fit,,, ,,,Axio,,,")
            ), [
                "Honda Fit",
                "Honda Axio",
                "Toyota Fit",
                "Toyota Axio"
            ]
        );
    }
}
