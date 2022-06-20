<html>
<head>
    <title>Anton Savchenko - Keywords</title>
</head>
<body>
    <?php
        $source = $_POST['source'] ?? null;

        if ($source) { 
            ?>
            <p><?= get_keywords($source); ?></p>
            <a href="index.php">Try again</a>
            <?php
        } else {
            ?>
            <form method="POST">
                <textarea name="source"></textarea>
                <input type="submit" text="Submit">
            </form>
            <?php
        }
    ?> 
</body>
</html>


<?php

define("LINES_SEPARATOR_REGEX", "/\r\n|\n|\r/");
define("LINE_ELEMENTS_SEPARATOR", ",");

/**
 * Подготавливает ключевые фразы для рекламного объявления
 * 
 * @param $source содержит исходный текст для генерации ключевых фраз.
 */
function get_keywords($source) {
    $source_lines = preg_split("/\r\n|\n|\r/", $source);

    $lines = [];
    foreach($source_lines as $source_line) {
        $lines[] = explode(",", $source_line);
    }

    return $lines[0][0];
}

?>
