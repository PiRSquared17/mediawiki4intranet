<?php
/**
 * Internationalisation file for extension PositivePageRate.
 */

$messages = array();

/** English
 * @author Vitaliy Filippov
 */
$messages['en'] = array(
    'positivepagerate'          => 'Page Rating',
    'pprate'                    => 'Page Rating',
    'pprate-desc'               => 'Yet another page rating system counting unique views and positive (and optionally negative) votes. It also enables a distinct user access log file.',
    'pprate-no-stats'           => 'Statistics empty.',
    'pprate-statistics'         => '+$3{{#ifeq:$4|0||/-$4}}, $1 {{PLURAL:$1|visitor|visitors}}',
    'pprate-log-stats'          => '$2 users voted for the page{{#ifeq:$4|0||&#32;(+$3, -$4)}}, $1 {{PLURAL:$1|visitor|visitors}} total.',
    'pprate-nplus'              => '+',
    'pprate-nview'              => 'visits',
    'pprate-nminus'             => '-',
    'pprate-unrate'             => 'recall your vote',
    'pprate-plus'               => 'like this page',
    'pprate-minus'              => 'don\'t like',
    'pprate-rated'              => '\'\'Your vote is accepted. Thank you!\'\'',
    'pprate-unrated'            => '\'\'Your vote is recalled.\'\'',
    'pprate-page-log-title'     => 'Log of voting and unique visits',
    'pprate-page-log'           => '== [[:$1]] — Log of voting and unique page views ==',
    'pprate-log-view'           => '* $2: [[:$1]]<span style="color: #aaa"> has viewed the page.</span>',
    'pprate-log-plus'           => '* $2: \'\'\'[[:$1]]\'\'\' has rated the page positive.',
    'pprate-log-minus'          => '* $2: \'\'\'[[:$1]]\'\'\' has rated the page negative.',
    'pprate-log-nu-view'        => '* $2: <span style="color: #aaa">unique page view.</span>',
    'pprate-log-nu-plus'        => '* $2: \'\'\'rating increased.\'\'\'',
    'pprate-log-nu-minus'       => '* $2: rating decreased.',
    'pprate-rating-title'       => 'Page Rating',
    'pprate-rating-text'        => 'List of pages{{#if:$1|&#32;in category [[Category:$1|$1]]|}}{{#if:$2$3|, last changed {{#if:$2|&#32;after $2{{#if:$3|&#32;and|}}|}}{{#if:$3|&#32;prior to $3|}}}}, sorted by rating:',
    'pprate-rating-empty'       => '* No statistics to display.',
    'pprate-rating-item'        => '* [[:$1]] — $3 {{PLURAL:$3|voter|voters}}{{#ifeq:$5|0||&#32;(+$4, -$5)}}, $2 {{PLURAL:$2|visitor|visitors}} total.',
    'pprate-rating-form-title'  => 'Page selection',
    'pprate-input-category'     => 'Category',
    'pprate-input-fromts'       => 'last&nbsp;changed&nbsp;between',
    'pprate-input-tots'         => 'and',
    'pprate-rating-submit'      => 'Display',
    'pprate-invalid-title'      => 'Unknown or special page selected: $1.',
    'pprate-invalid-category'   => 'Unknown category selected: $1.',
    'pprate-invalid-fromts'     => 'Invalid timestamp entered: $1.',
    'pprate-invalid-tots'       => 'Invalid timestamp entered: $1.',
);

/** Russian
 * @author Vitaliy Filippov
 */
$messages['ru'] = array(
    'positivepagerate'          => 'Рейтинг страниц',
    'pprate'                    => 'Рейтинг страницы',
    'pprate-desc'               => 'Очередная система рейтинга вики-статей, подсчитывающая уникальные просмотры и положительные (и, опционально, отрицательные) голоса, а также разрешающая ведение журнала доступа пользователей.',
    'pprate-no-stats'           => 'Нет статистики.',
    'pprate-statistics'         => '+$3{{#ifeq:$4|0||/-$4}}, $1 {{PLURAL:$1|посетитель|посетителя|посетителей}}',
    'pprate-log-stats'          => 'Проголосовал{{PLURAL:$2||о}} $2&nbsp;человек{{#ifeq:$4|0||&#32;(+$3, -$4)}}, всего $1 {{PLURAL:$1|посетитель|посетителя|посетителей}}.',
    'pprate-nview'              => 'просмотры',
    'pprate-unrate'             => 'отозвать свой голос',
    'pprate-plus'               => 'нравится',
    'pprate-minus'              => 'не нравится',
    'pprate-rated'              => '\'\'Ваш голос принят. Спасибо!\'\'',
    'pprate-unrated'            => '\'\'Ваш голос отозван.\'\'',
    'pprate-page-log-title'     => 'Журнал голосований и уникальных просмотров страниц',
    'pprate-page-log'           => '== [[:$1]] — Журнал голосований и уникальных просмотров ==',
    'pprate-log-view'           => '* $2: [[:$1]]<span style="color: #aaa"> просмотрел страницу.</span>',
    'pprate-log-plus'           => '* $2: \'\'\'[[:$1]]\'\'\' поддержал рейтинг страницы.',
    'pprate-log-minus'          => '* $2: \'\'\'[[:$1]]\'\'\' понизил рейтинг страницы.',
    'pprate-log-nu-view'        => '* $2: <span style="color: #aaa">уникальный просмотр.</span>',
    'pprate-log-nu-plus'        => '* $2: \'\'\'рейтинг увеличен.\'\'\'',
    'pprate-log-nu-minus'       => '* $2: рейтинг понижен.',
    'pprate-rating-title'       => 'Рейтинг страниц',
    'pprate-rating-text'        => 'Список страниц{{#if:$1|&#32;категории [[Категория:$1|$1]]|}}{{#if:$2$3|, последний раз изменённых{{#if:$2|&#32;после $2{{#if:$3|&#32;и|}}|}}{{#if:$3|&#32;до $3|}}}}, упорядоченный по убыванию рейтинга:',
    'pprate-rating-empty'       => '* Нет статистики для отображения.',
    'pprate-rating-item'        => '* [[:$1]] — проголосовал{{PLURAL:$3||о}} $3 {{#ifeq:$5|0||(+$4, -$5)}} из $2 {{PLURAL:$2|посетителя|посетителей}}.',
    'pprate-rating-form-title'  => 'Выбор страниц для рейтинга',
    'pprate-input-category'     => 'Категория',
    'pprate-input-fromts'       => 'последнее&nbsp;изменение&nbsp;от',
    'pprate-input-tots'         => 'и&nbsp;до',
    'pprate-rating-submit'      => 'Выбрать',
    'pprate-invalid-title'      => 'Выбрана неизвестная или специальная страница: $1.',
    'pprate-invalid-category'   => 'Выбрана неизвестная категория: $1.',
    'pprate-invalid-fromts'     => 'Введена некорректная дата: $1.',
    'pprate-invalid-tots'       => 'Введена некорректная дата: $1.',
);
