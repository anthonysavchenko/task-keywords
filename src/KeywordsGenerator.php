<?php

namespace Keywords;

class KeywordsGenerator
{
    /** Разделитель для строк исходного текста */
    const LINES_SEPARATOR_REGEX = "/\r\n|\n|\r/";

    /** Разделитель для ключевых слов в строке исходного текста */
    const LINE_ELEMENTS_SEPARATOR = ",";

    /** Символы, которых не должно быть в ключевом слове */
    const FORBIDDEN_CHARS = "/[^[:alnum:][:space:]]/u";

    /** Символ-оператор, который ставится перед минус-словом */
    const MINUS_WORD_OPERATOR = "-";

    /** Максимальное количество символов в слове,
     * при котором оно обязательно должно быть выделено оператором для коротких слов */
    const MAX_CHAR_COUNT_FOR_SHORT_WORD = 2;

    /** Символ-оператор, который ставится перед коротким словом */
    const PLUS_WORD_OPERATOR = "+";

    /** Символы-операторы, которые можно использовать в начале ключевого слова  */
    const ALLOWED_OPERATOR_CHARS = self::MINUS_WORD_OPERATOR . "+!";

    /** Последовательность символов пробела */
    const SPACES_SEQUENCE = "/[[:space:]]+/u";


    /**
     * Подготавливает ключевые фразы для рекламного объявления
     * 
     * @param $source содержит исходный текст для генерации ключевых фраз.
     * @return array 
     */
    static function getPhrases(string $source): array
    {
        /* Первый этап. Генерация */
        $keywords = self::explodeToKeywords($source);
        $phrases = self::combineKeywordsToPhrases($keywords);

        /* Второй этап. Корректировка */
        /* В исходных словах не должно присутствовать знаков препинания кроме: !,+,- один знак в начале слова.
        Невалидные символы нужно заменить пробелами */
        $phrases = self::removeForbiddenChars($phrases);

        /* Короткие исходные слова (до 2х символов) должны начинаться с + */
        $phrases = self::addPluses($phrases);

        /* Слова с минусами (минус-слова) должны располагаться в конце фразы */
        $phrases = self::shiftMinusWords($phrases);

        /* Наборы слов и минус-слов должны быть уникальны и не должны пересекаться. Это требование не совсем
        однозначно. Например, из него не явно поведение в случае, если в двух фразах совпадают наборы слов, но
        отличаются наборы минус-слов. Поэтому исходя из описания работы Яндекс поиска и того требования, что фразы
        не должны конкурировать, будем считать, что необходимо удалить повторяющиеся слова и минус-слова во фразе,
        а также отбрасывать фразы с таким набором слов, который полностью совпадает с набором слов в другой фразе
        (при этом минус-слова никак не учитываются). Также если во фразе пересекается набор слов и минус-слов,
        то отбрасываем ее */
        /* Порядок слов не важен (в рамках тестового задания). В рамках решения удалось сохранить порядок */
        $phrases = self::removeDuplicates($phrases);

        /* Фразы не должны конкурировать, т.е. пересекаться по ключам. Судя по всему, тут имеется ввиду не просто
        пересечение по словам, а именно полное включение набора слов одной из фраз (без учета минус-слов) в другую.
        Потому что, если фразы просто пересекаются, но имеют и некоторые различные слова, то они конкурировать
        не будут */
        $phrases = self::addMinuses($phrases);

        return $phrases;
    }

    /**
     * Разбивает исходный текст на строки и ключевые слова в этих строках.
     * 
     * @param string $text Исходный текст
     * @return array Например:
     * ```
     * [
     *      ["Honda", "Honda CRF", "Honda CRF-450X"],
     *      ["Владивосток", "Приморский край -Владивосток"]
     * ]
     * ```
     */
    static function explodeToKeywords(string $text): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $text);

        $keywords = [];
        foreach ($lines as $line) {
            $words = explode(self::LINE_ELEMENTS_SEPARATOR, $line);
            $words = array_filter($words, function (string $word) {
                return strlen(trim($word)) !== 0;
            });
            if ($words) {
                $keywords[] = $words;
            }
        }

        return $keywords;
    }

    /**
     * Комбинирует фразы из ключевых слов
     * 
     * @param array $keywords Набор строк с ключевыми словами. @see explodeToKeywords
     * @return array Например:
     * ```
     * [
     *      "Honda Владивосток",
     *      "Honda Приморский край -Владивосток",
     *      "Honda CRF Владивосток",
     *      "Honda CRF Приморский край -Владивосток",
     *      "Honda CRF-450X Владивосток",
     *      "Honda CRF-450X Приморский край -Владивосток"
     * ]
     * ```
     */
    static function combineKeywordsToPhrases(array $keywords): array
    {
        $phrases = [];
        foreach ($keywords as $line) {
            $phrases = self::combinePhrasesWithLine($phrases, $line);
        }
        $words = [];
        foreach ($phrases as $phrase) {
            $words[] = implode(" ", $phrase);
        }
        return $words;
    }

    /**
     * Комбинирует фразы с одним набором ключевых слов (очередным)
     * 
     * @param array $phrases Набор уже составленных фраз. @see combineKeywordsToPhrases. Или null,
     * если фраз еще не было составлено
     * @param array $line Набор ключевых слов
     * @return array Например:
     * ```
     * [
     *      ["Honda", "Владивосток"],
     *      ["Honda CRF", "Владивосток"],
     *      ["Honda CRF-450X", "Владивосток"]
     * ]
     * ```
     */
    private static function combinePhrasesWithLine(?array $phrases, array $line): array
    {
        $combination = [];
        if (count($phrases) === 0) {
            foreach ($line as $keyword) {
                $combination[] = [trim($keyword)];
            }
        } else {
            foreach ($phrases as $phrase) {
                foreach ($line as $keyword) {
                    $combination[] = [...$phrase, trim($keyword)];
                }
            }
        }
        return $combination;
    }

    /**
     * Удаляет лишнее символы
     * 
     * @param $phrases array Фразы. @see combineKeywordsToPhrases
     * @return array Фразы
     */
    static function removeForbiddenChars(array $phrases): array
    {
        $phrasesAfterRemove = [];
        foreach ($phrases as $phrase) {
            $phraseAfterRemove = "";
            $keywords = explode(" ", self::removeExtraSpaces($phrase));
            foreach ($keywords as $keyword) {

                /* Если первый символ является оператором, например, -, то запоминаем его и временно удаляем из слова */
                $first_char = substr($keyword, 0, 1);
                $operator = "";
                if (strpos(self::ALLOWED_OPERATOR_CHARS, $first_char) > -1) {
                    $operator = $first_char;
                    $keyword = substr($keyword, 1);
                }

                /* Удаляем лишние символы и лишние пробелы */
                $keyword = preg_replace(self::FORBIDDEN_CHARS, " ", $keyword);
                $keyword = self::removeExtraSpaces($keyword);

                /* Если в итоге от слова ничего не осталось, то пропускаем его и переходим к следующему */
                $keywordLength = strlen($keyword);
                if ($keywordLength === 0) { 
                    continue;
                }

                /* Сохраняем слово во фразу */
                $phraseAfterRemove =
                    $phraseAfterRemove .
                    (strlen($phraseAfterRemove) > 0 ? " " : "") .
                    $operator .
                    $keyword;
            }
            if (strlen($phraseAfterRemove) > 0) {
                $phrasesAfterRemove[] = $phraseAfterRemove;
            }
        }
        return $phrasesAfterRemove;
    }

    /**
     * Заменяет последовательность пробелов в строке на один пробел и удаляет пробелы в начале и в конце строки
     * 
     * @param string $target Исходная строка
     * @param string Итоговая строка
     */
    private static function removeExtraSpaces(string $target): string
    {
        $result = preg_replace(self::SPACES_SEQUENCE, " ", $target);
        return trim($result);
    }

    /**
     * Выделяет короткие слова специальным символом-оператором, если они еще не выделены
     * 
     * @param array $phrases Фразы. @see combineKeywordsToPhrases
     * @return array Фразы
     */
    static function addPluses(array $phrases): array
    {
        $phrasesAfterAdd = [];
        foreach ($phrases as $phrase) {
            $phraseAfterAdd = [];
            $keywords = explode(" ", $phrase);
            foreach ($keywords as $keyword) {
                $phraseAfterAdd[] = (
                    strlen($keyword) <= self::MAX_CHAR_COUNT_FOR_SHORT_WORD && !self::isPlusWord($keyword) ? "+" : ""
                ) . $keyword;
            }
            $phrasesAfterAdd[] = implode(" ", $phraseAfterAdd);
        }
        return $phrasesAfterAdd;
    }

    /**
     * Сдвигает минус-слова в конец фраз
     * 
     * @param $phrases Фразы. @see combineKeywordsToPhrases
     * @return array Фразы
     */
    static function shiftMinusWords(array $phrases): array
    {
        $shifted = [];
        foreach ($phrases as $phrase) {
            $noMinusWordsPhrase = self::getNoMinusWordsPhrase(explode(" ", $phrase));
            $minusWordsPhrase = self::getMinusWordsPhrase(explode(" ", $phrase));
            $shifted[] = implode(" ", [...$noMinusWordsPhrase, ...$minusWordsPhrase]);
        }
        return $shifted;
    }

    /**
     * Удаляет одинаковые слова и минус-слова, отбрасывает фразы с пересекающимися наборами слов и минус-слов,
     * а также с совпадающими наборами слов
     * 
     * @param array $phrases Фразы. @see combineKeywordsToPhrases
     * @return array Фразы
     */
    static function removeDuplicates(array $phrases): array
    {
        $phrasesAfterRemove = [];
        foreach ($phrases as $phrase) {
            $phraseAfterRemove = [];
            $keywords = explode(" ", $phrase);
            foreach ($keywords as $keyword) {

                /* Отбрасывем фразу, если в ней есть одинаковые слово и минус-слово */
                if (!self::isMinusWord($keyword) &&
                    array_search(self::MINUS_WORD_OPERATOR . $keyword, $keywords) !== false) {
                        $phraseAfterRemove = [];
                        break;
                }
                
                /* Отбрасываем повторяющиеся слова и минус-слова */
                if (array_search($keyword, $phraseAfterRemove) === false) {
                    $phraseAfterRemove[] = $keyword;
                }
            }
            if ($phraseAfterRemove) {

                /* Отбрасываем фразу, если набор ее слов совпадает с набором слов в одной из уже обработанных фраз */
                $noMinusWordsPhrase = self::getNoMinusWordsPhrase($phraseAfterRemove);
                sort($noMinusWordsPhrase);
                $noMinusWordsPhrase = implode(" ", $noMinusWordsPhrase);
                if (empty($phrasesAfterRemove[$noMinusWordsPhrase])) {
                    $phrasesAfterRemove[$noMinusWordsPhrase] = implode(" ", $phraseAfterRemove);
                }
            }
        }
        return array_values($phrasesAfterRemove);
    }

    /**
     * Оставляет во фразе только минус-слова
     * 
     * @param array $phrase Фраза
     * @return array Фраза
     */
    private static function getMinusWordsPhrase(array $phrase): array
    {
        return array_filter($phrase, function (string $keyword) {
            return self::isMinusWord($keyword);
        });
    }

    /**
     * Оставляет во фразе только слова (удаляет минус-слова)
     * 
     * @param array $phrase Фраза
     * @return array Фраза
     */
    private static function getNoMinusWordsPhrase(array $phrase): array
    {
        return array_filter($phrase, function (string $keyword) {
            return !self::isMinusWord($keyword);
        });
    }

    /**
     * Проверяет, является строка минус-словом
     * 
     * @param string $target Исходная строка
     * @return string Итоговая строка
     */
    private static function isMinusWord(string $target): bool
    {
        return substr($target, 0, 1) === self::MINUS_WORD_OPERATOR;
    }

    /**
     * Проверяет, находится ли в начале строки плюс-оператор
     * 
     * @param string $target Исходная строка
     * @return string Итоговая строка
     */
    private static function isPlusWord(string $target): bool
    {
        return substr($target, 0, 1) === self::PLUS_WORD_OPERATOR;
    }

    /**
     * Разминусовывает фразы, если одна из них включает другую
     * 
     * @param $phrases Фразы. @see combineKeywordsToPhrases
     * @return array Фразы
     */
    static function addMinuses(array $phrases): array
    {
        $phrasesAfterAdd = [];
        foreach ($phrases as $phrase) {
            $phraseAfterAdd = $phrase;
            $keywords = explode(" ", $phrase);
            $noMinusWordsPhrase = self::getNoMinusWordsPhrase($keywords);
            foreach (array_keys($phrasesAfterAdd) as $key) {
                $keyAsArray = explode(" ", $key);

                /* Текущая фраза включает в себя одну из уже обработанных,
                поэтому добавляем в обработанную фразу минус-слова */
                if (!array_diff($keyAsArray, $noMinusWordsPhrase)) {
                    $phrasesAfterAdd[$key] = implode(" ", self::addMinusWords(
                        $noMinusWordsPhrase,
                        $keyAsArray,
                        explode(" ", $phrasesAfterAdd[$key])
                    ));
                }
                
                /* Одна из уже обработанных фраз включает в себя текущую,
                поэтому добавляем в текущую фразу минус-слова */
                else if (!array_diff($noMinusWordsPhrase, $keyAsArray)) {
                    $phraseAfterAdd = implode(" ", self::addMinusWords(
                        $keyAsArray,
                        $noMinusWordsPhrase,
                        explode(" ", $phraseAfterAdd)
                    ));
                }
            }
            $phrasesAfterAdd[implode(" ", $noMinusWordsPhrase)] = $phraseAfterAdd;
        }
        return array_values($phrasesAfterAdd);
    }

    /**
     * Добовляет во фразу минус-слова для разминусовки с другой фразой
     * 
     * @param array $noMinusWordsSourceArray Слова без минус-слов фразы, из которой будут формироваться минус-слова
     * @param array $noMinusWordsTargetArray Слова без минус-слов фразы, в которую будут добавляться минус-слова
     * @param array $allWordsTargetArray Всее слова фразы, в которую будут добавляться минус-слова
     * @return array Фраза с нужными минус-словами
     */
    private static function addMinusWords(
        array $noMinusWordsSourceArray,
        array $noMinusWordsTargetArray,
        array $allWordsTargetArray
    ): array {
        $diff = array_diff($noMinusWordsSourceArray, $noMinusWordsTargetArray);
        $diff = self::transformWordsToMinusWords($diff);
        $noMinusWords = self::getNoMinusWordsPhrase($allWordsTargetArray);
        $minusWords = self::getMinusWordsPhrase($allWordsTargetArray);
        return [...$noMinusWords, ...$minusWords, ...array_diff($diff, $minusWords)];
    }

    /**
     * Преобразует набор слов в набор минус-слов
     * 
     * @param array $words Исходный набор слов
     * @return array Итоговый набор слов
     */
    private static function transformWordsToMinusWords(array $words): array
    {
        return array_map(function (string $word) {
            $first_char = substr($word, 0, 1);
            if (strpos(self::ALLOWED_OPERATOR_CHARS, $first_char) > -1) {
                return self::MINUS_WORD_OPERATOR . substr($word, 1);
            }
            return "-" . $word;
        }, $words);
    }
}
