<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Keywords\KeywordsGenerator as KG;

/**
 * Модульные тесты для разминусовывания
 */
class AddMinusesTest extends TestCase
{
    /**
     * Выполняет простой первый тест
     */
    public function testAddMinuses()
    {
        $this->assertEqualsCanonicalizing(
            KG::addMinuses([
                "Honda Fit",
                "Honda Fit Shuttle"
            ]), [
                "Honda Fit -Shuttle",
                "Honda Fit Shuttle"
            ]
        );
    }

    /**
     * Проверяет входные данные в виде пустой строки
     */
    public function testEmptyString()
    {
        $this->assertEqualsCanonicalizing(
            KG::addMinuses([]), []
        );
    }

    /**
     * Проверяет три повторяющихся слова
     */
    public function testThreeWords()
    {
        $this->assertEqualsCanonicalizing(
            KG::addMinuses([
                "Honda",
                "Honda Fit",
                "Honda Fit Shuttle"
            ]), [
                "Honda -Fit -Shuttle",
                "Honda Fit -Shuttle",
                "Honda Fit Shuttle"
            ]
        );
    }

    /**
     * Проверяет обратный порядок
     */
    public function testReverseOrder()
    {
        $this->assertEqualsCanonicalizing(
            KG::addMinuses([
                "Honda Fit Shuttle",
                "Honda Fit",
                "Honda"
            ]), [
                "Honda Fit Shuttle",
                "Honda Fit -Shuttle",
                "Honda -Fit -Shuttle"
            ]
        );
    }

    /**
     * Проверяет разный порядок слов во фразах
     */
    public function testWordsOtherOrder()
    {
        $this->assertEqualsCanonicalizing(
            KG::addMinuses([
                "Fit Honda Shuttle",
                "Honda Fit",
                "Honda"
            ]), [
                "Fit Honda Shuttle",
                "Honda Fit -Shuttle",
                "Honda -Fit -Shuttle"
            ]
        );
    }
}
