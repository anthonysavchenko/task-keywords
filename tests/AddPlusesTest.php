<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Keywords\KeywordsGenerator as KG;

/**
 * Модульные тесты для добавления оператора коротким словам
 */
class AddPlusesTest extends TestCase
{
    /**
     * Выполняет простой первый тест
     */
    public function testAddPlises()
    {
        $this->assertEqualsCanonicalizing(
            KG::addPluses([
                "Honda F",
                "T To Toy"
            ]), [
                "Honda +F",
                "+T +To Toy"
            ]
        );
    }

    /**
     * Проверяет входные данные в виде пустой строки
     */
    public function testEmptyString()
    {
        $this->assertEqualsCanonicalizing(
            KG::addPluses([]), []
        );
    }

    /**
     * Проверяет при наличии оператора
     */
    public function testPlusExists()
    {
        $this->assertEqualsCanonicalizing(
            KG::addPluses([
                "+Honda +T"
            ]), [
                "+Honda +T"
            ]);
    }
}
