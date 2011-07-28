<?php

$messages = array();

$messages['en'] = array(
'explainconflict'                  => '<span class="error">Warning: Someone else has changed this page since you started editing it.</span>

The upper text area contains \'\'\'your\'\'\' page version.
The lower text area contains the page text as it currently exists.
You have to merge changes from the lower text area into your version.
\'\'\'Only\'\'\' the text in the upper text area will be saved when you press "Save page".',
'explainconflictmerged'            => '<span class="error">Warning: Someone else has changed this page since you started editing it.</span>

The upper text area contains the \'\'\'merged\'\'\' version, containing your and their changes together.
Conflicting changes inside it are marked with <tt><<<<<<<, |||||||, =======, >>>>>>></tt>.
The lower text area contains the page text \'\'\'as it currently exists\'\'\'.
Resolve conflicts before saving.
\'\'\'Only\'\'\' the text in the upper text area will be saved when you press "Save page".',
'basetext'                         => '&larr; &nbsp; &nbsp; Base text &nbsp; &nbsp; &rarr;',
'merge-mine'                       => 'your version:',
'merge-old'                        => 'base text:',
'merge-their'                      => '(other version)',
);

$messages['ru'] = array(
'explainconflict'                  => '<span class="error">Внимание: Пока вы редактировали эту страницу, кто-то внёс в неё изменения.</span>

В верхнем окне для редактирования вы видите \'\'\'свой\'\'\' вариант страницы.
В нижнем окне находится сохранённая версия.
Перенесите изменения из нижнего окна в верхнее.
При нажатии на кнопку «{{int:savearticle}}» будет сохранён текст верхнего окна.',
'explainconflictmerged'            => '<span class="error">Внимание: Пока вы редактировали эту страницу, кто-то внёс в неё изменения.</span>

В верхнем окне показан текст, \'\'\'объединённый\'\'\' из ваших и чужих изменений.
Конфликтные изменения в нём помечены маркерами <tt><<<<<<<, |||||||, =======, >>>>>>></tt>.
В нижнем окне находится сохранённый \'\'\'в данный момент\'\'\' вариант.
Разрешите конфликты перед сохранением.
При нажатии на кнопку «{{int:savearticle}}» будет сохранён текст верхнего окна.',
'basetext'                         => '&larr; &nbsp; &nbsp; Старая версия &nbsp; &nbsp; &rarr;',
'merge-mine'                       => 'ваша версия:',
'merge-old'                        => 'старый текст:',
'merge-their'                      => '(чужая версия)',
);
