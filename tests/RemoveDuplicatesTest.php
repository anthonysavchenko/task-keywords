<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Keywords\KeywordsGenerator as KG;

/**
 * Модульные тесты для удаления повторов и пересечений
 */
class RemoveDuplicatesTest extends TestCase
{
    /**
     * Выполняет простой первый тест
     */
    public function testRemoveDuplicates()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeDuplicates([
                "Honda Honda Fit Fit",
                "Toyota -Axio -Axio"
            ]), [
                "Honda Fit",
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
            KG::removeDuplicates([]), []
        );
    }

    /**
     * Проверяет повторяющиеся слова и минус-слова
     */
    public function testWordsAndMinusWordsDuplicate()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeDuplicates([
                "Honda Fit -Honda"
            ]), []
        );
    }

    /**
     * Проверяет повторяющиеся фразы
     */
    public function testPhraseDuplicate()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeDuplicates([
                "Honda Fit",
                "Honda Fit",
                "Fit Honda",
                "-Toyota -Axio",
                "-Toyota -Axio",
                "-Axio -Toyota"
            ]), [
                "Honda Fit",
                "-Toyota -Axio"
            ]
        );
    }
}
