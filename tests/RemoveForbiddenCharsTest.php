<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Keywords\KeywordsGenerator as KG;

/**
 * Модульные тесты для удаления невалидных символов
 */
class RemoveForbiddenCharsTest extends TestCase
{
    /**
     * Выполняет простой первый тест
     */
    public function testRemove()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeForbiddenChars([
                ".<*=Honda @#\$Fit",
                "Toyota()Axio"
            ]), [
                "Honda Fit",
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
            KG::removeForbiddenChars([]), []
        );
    }

    /**
     * Проверяет все операторы
     */
    public function testOperatorsOnlyString()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeForbiddenChars([
                "+ - - +???"
            ]), []);
    }

    /**
     * Проверяет наличие оператора, за которым следует последовательность нерелевантных символов
     */
    public function testForbiddenOnlyString()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeForbiddenChars([
                "-#*% !+- (['])"
            ]), []);
    }

    /**
     * Проверяет все операторы
     */
    public function testAllOperators()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeForbiddenChars([
                "%Honda% -Toyota%",
                "Fit +Axio",
                "!Test",
            ]), [
                "Honda -Toyota",
                "Fit +Axio",
                "!Test"
            ]);
    }

    /**
     * Проверяет наличие последовательности пробелов
     */
    public function testSpacesSequence()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeForbiddenChars([
                "    Honda     -Toyota     ",
                "    Fit     -Axio     "
            ]), [
                "Honda -Toyota",
                "Fit -Axio"
            ]
        );
    }

    /**
     * Проверяет наличие оператора в середине строки
     */
    public function testOperatorInTheMiddle()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeForbiddenChars([
                "%Honda%&%-Toyota%",
                "%Fit-Axio%",
            ]), [
                "Honda Toyota",
                "Fit Axio"
            ]);
    }

    /**
     * Проверяет наличие оператора, за которым следует последовательность нерелевантных символов
     */
    public function testOperatorBeforeForbiddenChars()
    {
        $this->assertEqualsCanonicalizing(
            KG::removeForbiddenChars([
                "-#*%Honda !+-Toyota",
                "Fit +#$@ Axio"
            ]), [
                "-Honda !Toyota",
                "Fit Axio"
            ]);
    }
}
