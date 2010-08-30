<?php

/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @author Stas Fomin <stas-fomin@yandex.ru>
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$messages = array();

$messages['en'] = array(
    'mediawikiquizzer'                  => 'MediaWiki Quizzer',
    'mwquizzer-actions'                 => '<p><a href="$2">Try the quiz "$1"</a> &nbsp; | &nbsp; <a href="$3">Printable version</a></p>',
    'mwquizzer-show-parselog'           => '[+] Show quiz parse log',
    'mwquizzer-hide-parselog'           => '[-] Hide quiz parse log',

    /* Errors */
    'mwquizzer-no-test-id-title'        => 'Quiz ID is undefined!',
    'mwquizzer-no-test-id-text'         => 'You opened a hyperlink not containing quiz ID.',
    'mwquizzer-test-not-found-title'    => 'Quiz not found',
    'mwquizzer-test-not-found-text'     => 'Quiz with this ID is not found in database!',
    'mwquizzer-check-no-ticket-title'   => 'Incorrect check link',
    'mwquizzer-check-no-ticket-text'    => 'You want to check the test, but no correct ticket ID is present in the request.<br />Try <a href="$2">the quiz «$1»</a> again.',
    'mwquizzer-review-denied-title'     => 'Access denied',
    'mwquizzer-review-denied-text'      => 'Quiz review is available only to MWQuizzer administrators.',

    'mwquizzer-pagetitle'               => '$1 — questions',
    'mwquizzer-print-pagetitle'         => '$1 — printable version',
    'mwquizzer-check-pagetitle'         => '$1 — results',
    'mwquizzer-review-pagetitle'        => 'MediaWiki Quizzer — review test results',

    'mwquizzer-question'                => 'Question',
    'mwquizzer-counter-format'          => '%%H%%:%%M%%:%%S%% elapsed.',
    'mwquizzer-prompt'                  => 'If you want to receive a test completion certificate, please, enter you name:',
    'mwquizzer-submit'                  => 'Submit answers',
    'mwquizzer-question-sheet'          => 'Question List',
    'mwquizzer-test-sheet'              => 'Questionnaire',
    'mwquizzer-answer-sheet'            => 'Control Sheet',
    'mwquizzer-table-number'            => '№',
    'mwquizzer-table-answer'            => 'Answer',
    'mwquizzer-table-stats'             => 'Statistics',
    'mwquizzer-table-label'             => 'Label',
    'mwquizzer-table-remark'            => 'Remarks',
    'mwquizzer-right-answer'            => 'Correct answer',
    'mwquizzer-your-answer'             => 'Selected answer',
    'mwquizzer-variant-already-seen'    => 'You have already tried this variant. Try <a href="$1">another one</a>.',
    'mwquizzer-results'                 => 'Results',
    'mwquizzer-variant-msg'             => '<p>Variant $1.</p>',
    'mwquizzer-right-answers'           => 'Correct answers',
    'mwquizzer-score'                   => 'Score',
    'mwquizzer-random-correct'          => '<i>Note that the expectation of  матожидание числа правильных ответов при случайном выборе ≈ <b>$1</b></i>',
    'mwquizzer-try-quiz'                => 'Try <a href="$2">the quiz «$1»</a>!',
    'mwquizzer-try'                     => 'try',
    'mwquizzer-congratulations'         => 'You passed the quiz! Insert the following HTML code into your blog or homepage:',
    'mwquizzer-explanation'             => 'Explanation',
    'mwquizzer-anonymous'               => 'Anonymous',
    'mwquizzer-select-tickets'          => 'Select',
    'mwquizzer-ticket-count'            => 'Found $1, showing $3 from $2.',
    'mwquizzer-no-tickets'              => 'No tickets found.',
    'mwquizzer-pages'                   => 'Pages: ',

    /* Имена разных полей */
    'mwquizzer-ticket-id'               => 'Ticket ID',
    'mwquizzer-quiz'                    => 'Quiz',
    'mwquizzer-variant'                 => 'Variant',
    'mwquizzer-who'                     => 'Display name',
    'mwquizzer-user'                    => 'User',
    'mwquizzer-start'                   => 'Start time',
    'mwquizzer-end'                     => 'End time',
    'mwquizzer-duration'                => 'Duration',
    'mwquizzer-ip'                      => 'IP address',
    'mwquizzer-perpage'                 => 'Count on one page',

    /* Regular expressions used to parse various quiz field names */
    'mwquizzer-parse-test_name'                         => 'Name|Title',
    'mwquizzer-parse-test_intro'                        => 'Intro|Short[\s_]*Desc(?:ription)?',
    'mwquizzer-parse-test_mode'                         => 'Mode',
    'mwquizzer-parse-test_shuffle_questions'            => 'Shuffle[\s_]*questions',
    'mwquizzer-parse-test_shuffle_choices'              => 'Shuffle[\s_]*answers|Shuffle[\s_]*choices',
    'mwquizzer-parse-test_limit_questions'              => 'Limit[\s_]*questions|Questions?[\s_]*limit',
    'mwquizzer-parse-test_ok_percent'                   => 'OK\s*%|Pass[\s_]*percent|OK[\s_]*percent|Completion\s*percent',
    'mwquizzer-parse-test_autofilter_min_tries'         => '(?:too[\s_]*simple|autofilter)[\s_]*min[\s_]*tries',
    'mwquizzer-parse-test_autofilter_success_percent'   => '(?:too[\s_]*simple|autofilter)[\s_]*(?:ok|success)[\s_]*percent',

    /* Regular expressions used to parse questions etc */
    'mwquizzer-parse-question'      => 'Question[:\s]*',
    'mwquizzer-parse-choice'        => '(?:Choice|Answer)(?!s)',
    'mwquizzer-parse-choices'       => 'Choices|Answers',
    'mwquizzer-parse-correct'       => '(?:Correct|Right)\s*(?:Choice|Answer)(?!s)[:\s]*',
    'mwquizzer-parse-corrects'      => '(?:Correct|Right)\s*(?:Choices|Answers)',
    'mwquizzer-parse-label'         => 'Label',
    'mwquizzer-parse-explanation'   => 'Explanation',
    'mwquizzer-parse-comments'      => 'Comments?',
    'mwquizzer-parse-true'          => 'Yes|True|1',
);

$messages['ru'] = array(
    'mediawikiquizzer'                  => 'Опросы MediaWiki',
    'mwquizzer-actions'                 => '<p><a href="$2">Пройти тест «$1»</a> &nbsp; | &nbsp; <a href="$3">Версия для печати</a></p>',
    'mwquizzer-show-parselog'           => '[+] Показать лог разбора страницы теста',
    'mwquizzer-hide-parselog'           => '[-] Скрыть лог разбора страницы теста',

    /* Ошибки */
    'mwquizzer-no-test-id-title'        => 'Не задан идентификатор теста!',
    'mwquizzer-no-test-id-text'         => 'Вы перешли по ссылке, не содержащей идентификатор теста.',
    'mwquizzer-test-not-found-title'    => 'Тест не найден',
    'mwquizzer-test-not-found-text'     => 'Тест с этим номером не определен!',
    'mwquizzer-check-no-ticket-title'   => 'Неверная ссылка',
    'mwquizzer-check-no-ticket-text'    => 'Запрошен режим проверки, но идентификатор вашей попытки прохождения теста не задан или неверен.<br />Попробуйте <a href="$2">пройти тест «$1»</a> заново.',
    'mwquizzer-review-denied-title'     => 'Доступ запрещён',
    'mwquizzer-review-denied-text'      => 'Просмотр результатов доступен только администраторам системы тестирования.',

    'mwquizzer-pagetitle'               => '$1 — вопросы',
    'mwquizzer-print-pagetitle'         => '$1 — версия для печати',
    'mwquizzer-check-pagetitle'         => '$1 — результаты',
    'mwquizzer-review-pagetitle'        => 'Опросы MediaWiki — просмотр результатов',

    'mwquizzer-question'                => 'Вопрос $1',
    'mwquizzer-counter-format'          => 'Прошло %%H%%:%%M%%:%%S%%.',
    'mwquizzer-prompt'                  => 'Если хотите получить сертификат прохождения теста, пожалуйста, введите свое имя:',
    'mwquizzer-submit'                  => 'Отправить ответы',
    'mwquizzer-question-sheet'          => 'Лист вопросов',
    'mwquizzer-test-sheet'              => 'Форма для тестирования',
    'mwquizzer-answer-sheet'            => 'Проверочный лист',
    'mwquizzer-table-number'            => '№',
    'mwquizzer-table-answer'            => 'Ответ',
    'mwquizzer-table-stats'             => 'Статистика',
    'mwquizzer-table-label'             => 'Метка',
    'mwquizzer-table-remark'            => 'Примечание',
    'mwquizzer-right-answer'            => 'Правильный ответ',
    'mwquizzer-your-answer'             => 'Выбранный ответ',
    'mwquizzer-variant-already-seen'    => 'На этот вариант вы уже отвечали. Попробуйте <a href="$1">другой вариант</a>.',
    'mwquizzer-results'                 => 'Итог',
    'mwquizzer-variant-msg'             => '<p>Вариант $1.</p>',
    'mwquizzer-right-answers'           => 'Число правильных ответов',
    'mwquizzer-score'                   => 'Набрано очков',
    'mwquizzer-random-correct'          => '<i>Кстати, матожидание числа правильных ответов при случайном выборе ≈ <b>$1</b></i>',
    'mwquizzer-try-quiz'                => 'Попробуй <a href="$2">пройти тест «$1»</a>!',
    'mwquizzer-try'                     => 'пройти',
    'mwquizzer-congratulations'         => 'Вы успешно прошли тест! Можете вставить следующий HTML-код в ваш блог или сайт:',
    'mwquizzer-explanation'             => 'Пояснение',
    'mwquizzer-anonymous'               => 'Анонимный',
    'mwquizzer-select-tickets'          => 'Выбрать',
    'mwquizzer-ticket-count'            => 'Найдено $1, показано $3, начиная с №$2.',
    'mwquizzer-no-tickets'              => 'Не найдено ни одной попытки прохождения.',
    'mwquizzer-pages'                   => 'Страницы: ',

    /* Имена разных полей */
    'mwquizzer-ticket-id'               => 'ID попытки',
    'mwquizzer-quiz'                    => 'Тест',
    'mwquizzer-variant'                 => 'Вариант',
    'mwquizzer-who'                     => 'Имя',
    'mwquizzer-user'                    => 'Пользователь',
    'mwquizzer-start'                   => 'Время начала',
    'mwquizzer-end'                     => 'Время окончания',
    'mwquizzer-to'                      => ' до',
    'mwquizzer-duration'                => 'Длительность',
    'mwquizzer-ip'                      => 'IP-адрес',
    'mwquizzer-perpage'                 => 'На странице',

    /* Регулярные выражения для разбора названий различных полей теста */
    'mwquizzer-parse-test_name'                         => 'Название|Name|Title',
    'mwquizzer-parse-test_intro'                        => 'Введение|Описание|Intro|Short[\s_]*Desc(?:ription)?',
    'mwquizzer-parse-test_mode'                         => 'Режим|Mode',
    'mwquizzer-parse-test_shuffle_questions'            => 'Переставлять\s*вопросы|Перемешать\s*вопросы|Перемешивать\s*вопросы|Shuffle[\s_]*questions',
    'mwquizzer-parse-test_shuffle_choices'              => 'Переставлять\s*ответы|Перемешать\s*ответы|Перемешивать\s*ответы|Shuffle[\s_]*answers|Shuffle[\s_]*choices',
    'mwquizzer-parse-test_limit_questions'              => 'Количество\s*вопросов|Число\s*вопросов|Ограничить\s*число\s*вопросов|Limit[\s_]*questions|Questions?[\s_]*limit',
    'mwquizzer-parse-test_ok_percent'                   => 'Процент\s*завершения|%\s*завершения|ОК\s*%|OK\s*%|Pass[\s_]*percent|OK[\s_]*percent|Completion\s*percent',
    'mwquizzer-parse-test_autofilter_min_tries'         => 'Мин[\s\.]*попыток\s*слишком\s*простых\s*вопросов|(?:too[\s_]*simple|autofilter)[\s_]*min[\s_]*tries',
    'mwquizzer-parse-test_autofilter_success_percent'   => '%\s*успехов\s*слишком\s*простых\s*вопросов|(?:too[\s_]*simple|autofilter)[\s_]*(?:ok|success)[\s_]*percent',

    /* Регулярные выражения для разбора названий вопросов и т.п. */
    'mwquizzer-parse-question'      => '(?:Вопрос|Question)[:\s]*',
    'mwquizzer-parse-choice'        => 'Ответ(?!ы)|(?:Choice|Answer)(?!s)',
    'mwquizzer-parse-choices'       => 'Ответы|Варианты\s*ответа|Choices|Answers',
    'mwquizzer-parse-correct'       => '(?:Правильный\s*ответ(?!ы)|(?:Correct|Right)\s*(?:Choice|Answer)(?!s))[:\s]*',
    'mwquizzer-parse-corrects'      => 'Правильные\s*ответы|Правильные\s*варианты\s*ответа|(?:Correct|Right)\s*(?:Choices|Answers)',
    'mwquizzer-parse-label'         => 'Метка|Label',
    'mwquizzer-parse-explanation'   => '(?:Об|Раз)[ъь]яснение|Explanation',
    'mwquizzer-parse-comments'      => 'Примечани[ея]|Комментари[ий]|Comments?',
    'mwquizzer-parse-true'          => 'Да|Yes|True|1',
);
