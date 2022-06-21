<?php

/** Разделитель для строк исходного текста */
const LINES_SEPARATOR_REGEX = "/\r\n|\n|\r/";

/** Разделитель для ключевых слов (выражений) в строке исходного текста */
const LINE_ELEMENTS_SEPARATOR = ",";

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
        form {
            display: flex;
            flex-direction: column;
        }
        input[type=submit] {
            width: 150px;
            margin: 20px auto 0;
        }
    </style>
</head>
<body>
    <h1>🗝️ Антон Савченко — Ключевые слова</h1>
    <?php
        $source = $_POST['source'] ?? null;
        if ($source) { 
            ?>
            <h2>Результат</h2>
            <ol>
                <?php
                    foreach(getPhrases($source) as $phrase) {
                        ?>
                        <li><?= $phrase ?></li>
                        <?php
                    };
                ?>
            </ol>
            <?php
        }
    ?>
    <h2>Исходный текст</h2>
    <form method="POST">
        <textarea name="source" rows="10"></textarea>
        <input type="submit" value="Обработать">
    </form>
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
    $keywords = explodeToKeywords($source);
    $phrases = combineKeywordsToPhrases($keywords);
    $phrases = shiftMinusWords($phrases);

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
    foreach($lines as $line) {
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
    foreach($keywords as $line) {
        $phrases = combinePhrasesWithLine($phrases, $line);
    }
    $words = [];
    foreach($phrases as $phrase) {
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
        foreach($line as $keyword) {
            $combination[] = [$keyword];
        }
    } else {
        foreach($phrases as $phrase) {
            foreach($line as $keyword) {
                $combination[] = [...$phrase, $keyword];
            }
        }
    }
    return $combination;
}

/**
 * Сдвигает минус-слова в конец фраз
 * 
 * @param $phrases Фразы. @see combineKeywordsToPhrases
 * @return array
 */
function shiftMinusWords(array $phrases): array
{
    $shifted = [];
    foreach($phrases as $phrase) {
        $noMinusWordsPart = [];
        $minusWordsPart = [];
        foreach(explode(" ", $phrase) as $keyword) {
            if (substr($keyword, 0, 1) === '-') {
                $minusWordsPart[] = $keyword;
            } else {
                $noMinusWordsPart[] = $keyword;
            }
        }
        $shifted[] = implode(" ", [...$noMinusWordsPart, ...$minusWordsPart]);
    }
    return $shifted;
}

?>
