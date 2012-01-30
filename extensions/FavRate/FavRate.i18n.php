<?php
/**
 * Internationalisation file for extension FavRate.
 */

$messages = array();

/** English
 * @author Vitaliy Filippov
 */
$messages['en'] = array(
    'favrate'                   => 'Favorites-based page rating',
    'favrate-desc'              => 'Favorites-based page rating &ndash; yet another page rating system for MediaWiki.',
    'favratebar'                => 'Page rating',

    'favrate-hits'              => 'Views',
    'favrate-fav'               => 'Favorites',
    'favrate-links'             => 'Links',
    'favrate-viewlogs'          => 'view logs',
    'favrate-favorites'         => 'favorites',
    'favrate-addfav'            => 'Add to favorites',
    'favrate-remfav'            => 'Remove from favorites',
    'favrate-added'             => 'Page added to [[$1|Favorites]].',
    'favrate-removed'           => 'Page removed from [[$1|Favorites]].',
    'favrate-unauthorized'      => '[[$1|Log in]] to add page to favorites.',
    'favrate-nonfavorable'      => 'Invalid page for favorites.',

    'favrate-page-log-title'    => 'Page view log',
    'favrate-page-log'          => '== [[:$1]] — Unique visitors and favorites log ==

Total visitors: $2. Total favorites: $3.',
    'favrate-log-view'          => '* $2: [[:$1]] <span style="color: gray">&mdash; last access time.</span>',
    'favrate-log-fav'           => '* $2: [[:$1]] &mdash; added into [[:$3|Favorites]].',
    'favrate-invalid-title'     => 'Unknown or special page selected',
    'favrate-invalid-title-text' => 'The page "$1" either does not exist in this project or is a special page.',

    'favrate-favlist-title'     => 'Favorites',
    'favrate-favlist-subtitle'  => '== Favorites of user [[User:$1|$1]] ==',
    'favrate-list-fav'          => '* $2 &mdash; [[:$1]]',
    'favrate-invalid-user'      => 'Unknown user',
    'favrate-invalid-user-text' => 'The user "$1" does not exist in this project.',

    'favrate-rating-title'      => 'Page rating',
    'favrate-rating-empty'      => 'No statistics to display.',
    'favrate-rating-item'       => '* [[:$1]] — $2 view{{PLURAL:$2|s}}, $3 link{{PLURAL:$3||s}}, $4 user{{PLURAL:$3||s}} marked as favorite.',
);

/** Russian
 * @author Vitaliy Filippov
 */
$messages['ru'] = array(
    'favrate'                   => 'Рейтинг страниц на основе избранного',
    'favrate-desc'              => 'Рейтинг страниц на основе избранного &mdash; ещё одна система рейтингов для MediaWiki.',
    'favratebar'                => 'Рейтинг страницы',

    'favrate-hits'              => 'Просмотры',
    'favrate-fav'               => 'Избранное',
    'favrate-links'             => 'Ссылки сюда',
    'favrate-viewlogs'          => 'журналы',
    'favrate-favorites'         => 'избранное',
    'favrate-addfav'            => 'Добавить в избранное',
    'favrate-remfav'            => 'Удалить из избранного',
    'favrate-added'             => 'Страница добавлена в [[$1|Избранное]].',
    'favrate-removed'           => 'Страница удалена из [[$1|Избранного]].',
    'favrate-unauthorized'      => '[[$1|Авторизуйтесь]], чтобы добавлять страницы в избранное.',
    'favrate-nonfavorable'      => 'Выбрана несуществующая или некорректная страница.',

    'favrate-page-log-title'    => 'Журнал просмотров страницы',
    'favrate-page-log'          => '== [[:$1]] — Журнал уникальных просмотров и добавления в избранное ==

Всего посетителей: $2. Всего добавили в избранное: $3.',
    'favrate-log-view'          => '* $2: [[:$1]] <span style="color: gray">&mdash; последний просмотр.</span>',
    'favrate-log-fav'           => '* $2: [[:$1]] &mdash; добавил в [[:$3|Избранное]].',
    'favrate-invalid-title'     => 'Страница не существует',
    'favrate-invalid-title-text' => 'Страница "$1" не существует или является служебной.',

    'favrate-favlist-title'     => 'Избранные страницы',
    'favrate-favlist-subtitle'  => '== Избранные пользователем [[User:$1|$1]] страницы ==',
    'favrate-list-fav'          => '* $2 &mdash; [[:$1]]',
    'favrate-invalid-user'      => 'Неизвестный пользователь',
    'favrate-invalid-user-text' => 'Пользователь «$1» не зарегистрирован в данном вики-проекте.',

    'favrate-rating-title'      => 'Рейтинг страниц',
    'favrate-rating-empty'      => 'Нет статистики для отображения.',
    'favrate-rating-item'       => '* [[:$1]] — $2 просмотр{{PLURAL:$2||а|ов}}, $3 ссыл{{PLURAL:$3|ка|ки|ок}}, в избранном у $4 пользовател{{PLURAL:$4|я|ей}}.',
);
