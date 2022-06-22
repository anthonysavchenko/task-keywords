<?php

/** Разделитель для строк исходного текста */
const LINES_SEPARATOR_REGEX = "/\r\n|\n|\r/";

/** Разделитель для ключевых слов в строке исходного текста */
const LINE_ELEMENTS_SEPARATOR = ",";

/** Символы, которых не должно быть в ключевом слове */
const FORBIDDEN_CHARS = "/[^[:alnum:][:space:]]/u";

/** Символ-оператор, который ставится перед минус-словом */
const MINUS_WORD_OPERATOR = "-";

/** Символы-операторы, которые можно использовать в начале ключевого слова  */
const ALLOWED_OPERATOR_CHARS = MINUS_WORD_OPERATOR . "+!";

/** Последовательность символов пробела */
const SPACES_SEQUENCE = "/[[:space:]]+/u";

?>


<html>
<head>
    <title>Антон Савченко — Ключевые слова</title>
    <style>
        body {
            width: 600px;
            margin: 0 auto;
        }
        h1, h2 {
            text-align: center;
        }
        a {
            text-decoration: none;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        nav {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        input[type=submit], button {
            width: 175px;
            height: 50px;
            padding: 5px 15px;
        }
    </style>
</head>
<body>
    <h1><a href="index.php">🗝️</a> Антон Савченко — Ключевые слова</h1>
    <h2>Исходный текст</h2>
    <form method="POST">
        <textarea id="source" name="source" rows="10"><?= $_POST['source'] ?? "" ?></textarea>
        <nav>
            <button
                onclick="event.preventDefault(); document.getElementById('source').value =
                    'Honda, Honda CRF, Honda CRF-450X\nВладивосток, Приморский край -Владивосток\nпродажа, покупка, цена, с пробегом';"
            >
                Вставить исходные данные примера
            </button>
            <button onclick="event.preventDefault(); document.getElementById('source').value = '';">
                Очистить
            </button>
            <input type="submit" value="Обработать">
        </nav>
    </form>
    <?php
        $source = $_POST['source'] ?? null;
        if ($source) { 
            ?>
            <h2>Результат</h2>
            <ol>
                <?php
                    foreach (getPhrases($source) as $phrase) {
                        ?>
                        <li><?= $phrase ?></li>
                        <?php
                    };
                ?>
            </ol>
            <?php
        }
    ?>
</body>
</html>


<?php

/**
 * Подготавливает ключевые фразы для рекламного объявления
 * 
 * @param $source содержит исходный текст для генерации ключевых фраз.
 * @return array 
 */
function getPhrases(string $source): array
{
    /* Первый этап. Генерация */
    $keywords = explodeToKeywords($source);
    $phrases = combineKeywordsToPhrases($keywords);

    /* Второй этап. Корректировка */
    /* В исходных словах не должно присутствовать знаков препинания кроме: !,+,- один знак в начале слова.
    Невалидные символы нужно заменить пробелами */
    $phrases = removeForbiddenChars($phrases);

    /* Слова с минусами (минус-слова) должны располагаться в конце фразы */
    $phrases = shiftMinusWords($phrases);

    /* Наборы слов и минус-слов должны быть уникальны и не должны пересекаться. Это требование не совсем однозначно.
    Например, из него не явно поведение в случае, если в двух фразах совпадают наборы слов, но отличаются наборы
    минус-слов. Поэтому исходя из описания работы Яндекс поиска и того требования, что фразы не должны конкурировать,
    будем считать, что необходимо удалить повторяющиеся слова и минус-слова во фразе, а также отбрасывать фразы с
    таким набором слов, который полностью совпадает с набором слов в другой фразе (при этом минус-слова никак не
    учитываются). Также если во фразе пересекается набор слов и минус-слов, то отбрасываем ее */
    /* Порядок слов не важен (в рамках тестового задания) */
    $phrases = removeDuplicates($phrases);

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
function explodeToKeywords(string $text): array
{
    $lines = preg_split("/\r\n|\n|\r/", $text);

    $keywords = [];
    foreach ($lines as $line) {
        $keywords[] = explode(LINE_ELEMENTS_SEPARATOR, $line);
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
function combineKeywordsToPhrases(array $keywords): array
{
    $phrases = [];
    foreach ($keywords as $line) {
        $phrases = combinePhrasesWithLine($phrases, $line);
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
function combinePhrasesWithLine(?array $phrases, array $line): array
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
function removeForbiddenChars(array $phrases): array
{
    $phrasesAfterRemove = [];
    foreach ($phrases as $phrase) {
        $phraseAfterRemove = "";
        $keywords = explode(" ", removeExtraSpaces($phrase));
        foreach ($keywords as $keyword) {

            /* Если первый символ является оператором, например, -, то запоминаем его и временно удаляем из слова */
            $first_char = substr($keyword, 0, 1);
            $operator = "";
            if (strpos(ALLOWED_OPERATOR_CHARS, $first_char) > -1) {
                $operator = $first_char;
                $keyword = substr($keyword, 1);
            }

            /* Удаляем лишние символы и лишние пробелы */
            $keyword = preg_replace(FORBIDDEN_CHARS, " ", $keyword);
            $keyword = removeExtraSpaces($keyword);

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
function removeExtraSpaces(string $target): string
{
    $result = preg_replace(SPACES_SEQUENCE, " ", $target);
    return trim($result);
}

/**
 * Сдвигает минус-слова в конец фраз
 * 
 * @param $phrases Фразы. @see combineKeywordsToPhrases
 * @return array Фразы
 */
function shiftMinusWords(array $phrases): array
{
    $shifted = [];
    foreach ($phrases as $phrase) {
        $noMinusWordsPhrase = getNoMinusWordsPhrase(explode(" ", $phrase));
        $minusWordsPhrase = getMinusWordsPhrase(explode(" ", $phrase));
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
function removeDuplicates(array $phrases): array
{
    $phrasesAfterRemove = [];
    foreach ($phrases as $phrase) {
        $phraseAfterRemove = [];
        $keywords = explode(" ", $phrase);
        foreach ($keywords as $keyword) {

            /* Отбрасывем фразу, если в ней есть одинаковые слово и минус-слово */
            if (!isMinusWord($keyword) &&
                array_search(MINUS_WORD_OPERATOR . $keyword, $keywords) !== false) {
                    $phraseAfterRemove = [];
                    break;
            }
            
            /* Отбрасываем повторяющиеся слова и минус-слова */
            if (array_search($keyword, $phraseAfterRemove) === false) {
                $phraseAfterRemove[] = $keyword;
            }
        }
        if (count($phraseAfterRemove) > 0) {

            /* Отбрасываем фразу, если набор ее слов совпадает с набором слов в одной из уже обработанных фраз */
            $noMinusWordsPhrase = getNoMinusWordsPhrase($phraseAfterRemove);
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
function getMinusWordsPhrase(array $phrase): array
{
    return array_filter($phrase, "isMinusWord");
}

/**
 * Оставляет во фразе только слова (удаляет минус-слова)
 * 
 * @param array $phrase Фраза
 * @return array Фраза
 */
function getNoMinusWordsPhrase(array $phrase): array
{
    return array_filter($phrase, function (string $keyword) {
        return !isMinusWord($keyword);
    });
}

/**
 * Проверяет, является строка минус-словом
 * 
 * @param string $target Исходная строка
 * @return string Итоговая строка
 */
function isMinusWord(string $target): bool
{
    return substr($target, 0, 1) === MINUS_WORD_OPERATOR;
}

?>
