<?php
/**
 * BatchEditor.i18n.php -- Localisation files for BatchEditor
 * Copyright 2009 Vitaliy Filippov <vitalif@mail.ru>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @file
 * @ingroup Extensions
 * @author Vitaliy Filippov <vitalif@mail.ru>
 */

$messages = array();
$specialPageAliases = array();

/* English
 * @author Vitaliy Filippov <vitalif@mail.ru>
 */
$messages['en'] = array(
    'batcheditor'               => 'Batch editing of pages',
    'batcheditor-title'         => 'Batch Editor',
    'batcheditor-warning'       => 'This page is intended to batch (mass) editing of [[{{SITENAME}}]] articles. Please, use this promptly!',
    'batcheditor-list-title'    => '<b>Articles:</b><br /><small style="line-height: normal">Article titles,<br />one per line</small>',
    'batcheditor-comment-title' => '<b>Comment:</b>',
    'batcheditor-find'          => 'Find',
    'batcheditor-replace'       => 'Replace',
    'batcheditor-addlines'      => 'Add lines',
    'batcheditor-deletelines'   => 'Delete lines',
    'batcheditor-pcre'          => 'Use [http://en.wikipedia.org/wiki/PCRE Perl-compatible regular expressions] for replacement',
    'batcheditor-oneline'       => 'Treat this as <b>one</b> multi-line replacement',
    'batcheditor-create'        => 'Create non-existing pages if adding something',
    'batcheditor-preview'       => 'Preview',
    'batcheditor-run'           => 'Run',
    'batcheditor-preview-page'  => '= Preview =',
    'batcheditor-results-page'  => "= '''Results''' =",
    'batcheditor-not-found'     => 'Article [[$1]] not found!',
    'batcheditor-edit-error'    => 'Editing failed',
    'batcheditor-edit-denied'   => 'You cannot edit this article.',
);
$specialPageAliases['en'] = array(
    'BatchEditor' => array('BatchEditor'),
);

/* Русский
 * @author Vitaliy Filippov <vitalif@mail.ru>
 */
$messages['ru'] = array(
    'batcheditor'               => 'Массовая правка страниц',
    'batcheditor-title'         => 'Массовый редактор',
    'batcheditor-warning'       => 'Данная страница служит для массового редактирования статей [[{{SITENAME}}]]. Пожалуйста, используйте её с осторожностью!',
    'batcheditor-list-title'    => 'Статьи:<br /><small style="line-height: normal">Названия статей,<br />одно на строку</small>',
    'batcheditor-comment-title' => 'Комментарий:',
    'batcheditor-find'          => 'Найти',
    'batcheditor-replace'       => 'Заменить',
    'batcheditor-pcre'          => 'Использовать [http://en.wikipedia.org/wiki/PCRE Perl-совместимые регулярные выражения] для замены',
    'batcheditor-oneline'       => 'Трактовать поле не как список замен, а как \'\'\'одну\'\'\' многострочную',
    'batcheditor-create'        => 'Создавать несуществующие статьи при добавлении строк',
    'batcheditor-addlines'      => 'Добавить строки',
    'batcheditor-deletelines'   => 'Удалить строки',
    'batcheditor-preview'       => 'Просмотреть',
    'batcheditor-run'           => 'Выполнить',
    'batcheditor-preview-page'  => '= Предварительный просмотр =',
    'batcheditor-results-page'  => "= '''Результаты''' =",
    'batcheditor-not-found'     => 'Статья [[$1]] не существует!',
    'batcheditor-edit-error'    => 'Ошибка редактирования',
    'batcheditor-edit-denied'   => 'Вам запрещено редактировать эту статью.'
);
$specialPageAliases['ru'] = array(
    'BatchEditor' => array('BatchEditor', 'МассоваяПравка'),
);
