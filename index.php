<?php

require_once __DIR__ . '/vendor/autoload.php';

use Keywords\KeywordsGenerator as KG;

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
                    foreach (KG::getPhrases($source) as $phrase) {
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
