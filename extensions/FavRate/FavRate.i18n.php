<?php
/**
 * Internationalisation file for extension FavRate.
 */

$messages = array();

/** English
 * @author Vitaliy Filippov
 */
$messages['en'] = array(
    'favrate'                   => 'Likes-based page rating',
    'favrate-desc'              => 'Likes-based page rating &ndash; yet another page rating system for MediaWiki.',
    'favratebar'                => 'Page rating',

    'favrate-hits'              => 'Views',
    'favrate-fav'               => 'Likes',
    'favrate-links'             => 'Links',
    'favrate-viewlogs'          => 'view logs',
    'favrate-favorites'         => 'my likes',
    'favrate-addfav'            => 'Like',
    'favrate-remfav'            => 'Unlike',
    'favrate-remfav-cmt'        => 'Unlike (current comment: $1)',
    'favrate-comment'           => 'comment here',
    'favrate-post-comment'      => 'Post',
    'favrate-added'             => 'Page added to [[$1|Favorites]].',
    'favrate-removed'           => 'Page removed from [[$1|Favorites]].',
    'favrate-unauthorized'      => '[[$1|Log in]] to add page to favorites.',
    'favrate-nonfavorable'      => 'Invalid page for favorites.',

    'favrate-page-log-title'    => 'Page view log',
    'favrate-page-log'          => '== [[:$1]] — Unique visit and like log ==

Total visitors: $2. Total likes: $3.',
    'favrate-log-view'          => '* $2: [[:$1]] <span style="color: gray">&mdash; last access time.</span>',
    'favrate-log-fav'           => '* $2: [[:$1]] &mdash; [[:$3|likes this page]].',
    'favrate-log-comment'       => '* $2: [[:$1]] &mdash; [[:$3|likes this page]] (comment: $4).',
    'favrate-invalid-title'     => 'Unknown or special page selected',
    'favrate-invalid-title-text' => 'The page "$1" either does not exist in this project or is a special page.',

    'favrate-favlist-title'     => 'Likes',
    'favrate-favlist-subtitle'  => '== Likes of user [[User:$1|$1]] ==',
    'favrate-list-fav'          => '* $2 &mdash; [[:$1]] {{#if:$3|(comment: $3)}}',
    'favrate-invalid-user'      => 'Unknown user',
    'favrate-invalid-user-text' => 'The user "$1" does not exist in this project.',

    'favrate-rating-title'      => 'Page rating',
    'favrate-rating-empty'      => 'No statistics to display.',
    'favrate-rating-item'       => '* [[:$1]] — $2 view{{PLURAL:$2|s}}, $3 link{{PLURAL:$3||s}}, $4 like{{PLURAL:$3||s}}.',
);

/** Russian
 * @author Vitaliy Filippov
 */
$messages['ru'] = array(
    'favrate'                   => 'Рейтинг отмеченных страниц',
    'favrate-desc'              => 'Рейтинг отмеченных страниц &mdash; ещё одна система рейтингов для MediaWiki.',
    'favratebar'                => 'Рейтинг страницы',

    'favrate-hits'              => 'Просмотры',
    'favrate-fav'               => 'Отмеченное',
    'favrate-links'             => 'Ссылки сюда',
    'favrate-viewlogs'          => 'журналы',
    'favrate-favorites'         => 'отмеченное',
    'favrate-addfav'            => 'Отметить',
    'favrate-remfav'            => 'Снять отметку',
    'favrate-remfav-cmt'        => 'Снять отметку (текущий комментарий: $1)',
    'favrate-comment'           => 'комментарий',
    'favrate-post-comment'      => 'ОК',
    'favrate-added'             => 'Страница добавлена к [[$1|Отмеченным]].',
    'favrate-removed'           => 'Страница удалена из [[$1|Отмеченных]].',
    'favrate-unauthorized'      => '[[$1|Авторизуйтесь]], чтобы отмечать страницы.',
    'favrate-nonfavorable'      => 'Выбрана несуществующая или некорректная страница.',

    'favrate-page-log-title'    => 'Журнал просмотров страницы',
    'favrate-page-log'          => '== [[:$1]] — Журнал уникальных просмотров и отметок ==

Всего посетителей: $2. Всего отметили: $3.',
    'favrate-log-view'          => '* $2: [[:$1]] <span style="color: gray">&mdash; последний просмотр.</span>',
    'favrate-log-fav'           => '* $2: [[:$1]] &mdash; [[:$3|отметил]].',
    'favrate-log-comment'       => '* $2: [[:$1]] &mdash; [[:$3|отметил]] (комментарий: $4).',
    'favrate-invalid-title'     => 'Страница не существует',
    'favrate-invalid-title-text' => 'Страница "$1" не существует или является служебной.',

    'favrate-favlist-title'     => 'Отмеченные страницы',
    'favrate-favlist-subtitle'  => '== Отмеченные пользователем [[User:$1|$1]] страницы ==',
    'favrate-list-fav'          => '* $2 &mdash; [[:$1]] {{#if:$3|(комментарий: $3)}}',
    'favrate-invalid-user'      => 'Неизвестный пользователь',
    'favrate-invalid-user-text' => 'Пользователь «$1» не зарегистрирован в данном вики-проекте.',

    'favrate-rating-title'      => 'Рейтинг страниц',
    'favrate-rating-empty'      => 'Нет статистики для отображения.',
    'favrate-rating-item'       => '* [[:$1]] — $2 просмотр{{PLURAL:$2||а|ов}}, $3 ссыл{{PLURAL:$3|ка|ки|ок}}, отмечено $4 пользовател{{PLURAL:$4|ем|ями}}.',
);
