# Keywords

**[Рабочая страница с реализацией, на которой можно проверить работу с решением](https://task-keywords.herokuapp.com)**

Рекламная кампания состоит из набора объявлений, каждое из объявлений имеет список ключевых слов, по которым оно будет показано на поиске.
С общими принципами можно ознакомиться где-то [здесь](https://yandex.ru/support/direct/keywords/keywords.html).

Необходимо разработать функционал подготовки ключевых фраз для рекламного объявления.


## Этап 1. Генерация

Исходный текст для генерации набора ключей может выглядеть так:

```
Honda, Honda CRF, Honda CRF-450X
Владивосток, Приморский край -Владивосток
продажа, покупка, цена, с пробегом
```

Исходные строки определяют элементы для составления перестановок в результирующих фразах. Первая строка задает множество для первого элемента фразы, вторая для второго и так далее.

```
1. Honda Владивосток продажа 
2. Honda Владивосток покупка
3. Honda Владивосток цена
4. Honda Владивосток с пробегом
5. Honda Приморский край -Владивосток продажа
6. Honda Приморский край -Владивосток покупка
...
11. Honda CRF Владивосток цена
...
24. Honda CRF-450X Приморский край -Владивосток с пробегом
```


## Этап 2. Корректировка

Ключевые фразы должны отвечать следующим требованиям:

- слова с минусами (минус-слова) должны располагаться в конце фразы

```
Honda CRF-450X Приморский край цена -Владивосток // правильно
Honda CRF-450X Приморский край -Владивосток цена // неправильно
```

- наборы слов и минус слов должны быть уникальны и не должны пересекаться

- порядок слов не важен (в рамках тестового задания)

- в исходных словах не должно присутствовать знаков препинания кроме: `!`, `+`, `-` один знак в начале слова. Невалидные символы нужно заменить пробелами.

`CRF-450X` - символ `-` в данном случае является невалидным

- короткие исходные слова (до 2х символов) должны начинаться с `+`

```
Honda Владивосток +с пробегом
```

- Фразы не должны конкурировать, т.е. пересекаться по ключам. Например:

```
Honda
Honda CRF
Honda CRF 450X
```

Эти фразы включают одна другую и должны быть "разминусованы" таким образом:

```
Honda -CRF -450X
Honda CRF -450X
Honda CRF 450X
```

## Требования

- пользоваться функционалом должно быть удобно: это может быть страничка, может быть команда

- очень пригодятся тесты, которые зафиксируют поведение системы
