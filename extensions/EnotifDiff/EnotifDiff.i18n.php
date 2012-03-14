<?php

$messages = array();

$messages['en'] = array(
    'tog-enotifsenddiffs'    => 'Include page differences in notification e-mails',
    'tog-enotifsendmultiple' => 'Send multiple notifications instead of one after each visit',
    'enotif-empty-summary'   => 'empty summary.',
    'enotif_lastvisited'     => 'See $1 for all changes since your last visit.',
    'enotif_lastdiff'        => 'See $1 to view this change.',

    // Page change notification email body
    'enotif_body'            =>
'<body>
<p>Dear <b>$WATCHINGUSERNAME</b>,</p>

<p>The {{SITENAME}} page $PAGETITLE has been $CHANGEDORCREATED on $PAGEEDITDATE by $PAGEEDITOR,
see <a href="$PAGETITLE_URL">$PAGETITLE_URL_NOENC</a> for the current version.</p>

<p>$NEWPAGE</p>

<p>Editor\'s summary: <b>$PAGESUMMARY $PAGEMINOREDIT</b><br>
Contact the editor: <a href="$PAGEEDITOR_EMAIL">mail</a>, <a href="$PAGEEDITOR_WIKI">wiki</a>.</p>

$REALDIFF

<p style="font-size: 12px"><i>
-- <br>
<b>Your friendly {{SITENAME}} notification system</b><br>
To change your watchlist settings, visit
<a href="{{fullurl:{{ns:special}}:Watchlist/edit}}">Special:Watchlist/edit</a><br>
Feedback and further assistance: <a href="{{fullurl:{{MediaWiki:Helppage}}}}">{{MediaWiki:Helppage}}</a>
</i></p>
</body>$DIFF',

    // Email address confirmation body (for new accounts and re-submit from Special:ConfirmEmail)
    'confirmemail_body'      =>
'<body>
<p>Someone, probably you, from IP address $1,
has registered an account "<b>$2</b>" with this e-mail address on {{SITENAME}}.</p>

<p>To confirm that this account really does belong to you and activate
e-mail features on {{SITENAME}}, open this link in your browser:</p>

<p><a href="$3">$3</a></p>

<p>If you did *not* register the account, follow this link
to cancel the e-mail address confirmation:</p>

<p><a href="$5">$5</a></p>

<p>This confirmation code will expire at $4.</p>
</body>',

    // Email address confirmation body (for the case when address was changed in preferences)
    'confirmemail_body_changed' =>
'<body>
<p>Someone, probably you, from IP address $1,
has changed the e-mail address of the account "$2" to this address on {{SITENAME}}.</p>

<p>To confirm that this account really does belong to you and reactivate
e-mail features on {{SITENAME}}, open this link in your browser:</p>

<p><a href="$3">$3</a></p>

<p>If the account does *not* belong to you, follow this link
to cancel the e-mail address confirmation:</p>

<p><a href="$5">$5</a></p>

<p>This confirmation code will expire at $4.</p>
</body>',

    // Email address confirmation body (for the case when address was added in preferences)
    'confirmemail_body_set'     =>
'<body>
<p>Someone, probably you, from IP address $1,
has set the e-mail address of the account "$2" to this address on {{SITENAME}}.</p>

<p>To confirm that this account really does belong to you and reactivate
e-mail features on {{SITENAME}}, open this link in your browser:</p>

<p><a href="$3">$3</a></p>

<p>If the account does *not* belong to you, follow this link
to cancel the e-mail address confirmation:</p>

<p><a href="$5">$5</a></p>

<p>This confirmation code will expire at $4.</p>
</body>',
);

$messages['ru'] = array(
    'tog-enotifsenddiffs'    => 'Включать в оповещения различия (diff\'ы) страниц',
    'tog-enotifsendmultiple' => 'Отправлять новое оповещение при каждом изменении, а не одно для всех с момента посещения',
    'enotif-empty-summary'   => 'нет описания.',
    'enotif_lastvisited'     => '<a href="$1">Перейдите по ссылке</a> для просмотра всех изменений, произошедших с вашего последнего посещения.',
    'enotif_lastdiff'        => '<a href="$1">Перейдите по ссылке</a> для ознакомления с изменением.',

    // Тело email-оповещения об изменении страницы
    'enotif_body'            =>
'<body>
<p><b>$WATCHINGUSERNAME,</b></p>
<p>
$PAGEEDITDATE страница проекта «{{SITENAME}}» $PAGETITLE была $CHANGEDORCREATED участником
$PAGEEDITOR, см. <a href="$PAGETITLE_URL">$PAGETITLE_URL_NOENC</a> для просмотра текущей версии.
</p>

<p>$NEWPAGE</p>

<p>Краткое описание изменения: <b>$PAGESUMMARY $PAGEMINOREDIT</b><br>
Обратиться к изменившему: <a href="$PAGEEDITOR_EMAIL">эл.почта</a>, <a href="$PAGEEDITOR_WIKI">вики</a>.</p>

$REALDIFF

<p style="font-size: 12px"><i>
-- <br>
<b>Система оповещения {{grammar:genitive|{{SITENAME}}}}</b><br>
Чтобы изменить настройки вашего списка наблюдения, обратитесь к странице
<a href="{{fullurl:{{ns:special}}:Watchlist/edit}}">Special:Watchlist/edit</a><br>
Обратная связь и помощь: <a href="{{fullurl:{{MediaWiki:Helppage}}}}">{{MediaWiki:Helppage}}</a>
</i></p>
</body>$DIFF',

    // Тело письма подтверждения адреса (на случай регистрации и перезапроса со страницы Special:ConfirmEmail)
    'confirmemail_body'      =>
'<body>
<p>Кто-то (возможно вы) с IP-адресом $1 зарегистрировал
на сервере проекта {{SITENAME}} учётную запись «<b>$2</b>»,
указав этот адрес электронной почты.</p>

<p>Чтобы подтвердить, что эта учётная запись действительно
принадлежит вам и включить возможность отправки электронной почты
с сайта {{SITENAME}}, откройте приведённую ниже ссылку в браузере.</p>

<p><a href="$3">$3</a></p>

<p>Если вы <b>*не* регистрировали</b> подобной учётной записи, то перейдите
по следующей ссылке, чтобы отменить подтверждение адреса:</p>

<p><a href="$5">$5</a></p>

<p>Код подтверждения действителен до $4.</p>
</body>',

    // Тело письма подтверждения адреса (на случай его изменения со страницы настроек)
    'confirmemail_body_changed' =>
'<body>
<p>Кто-то (возможно вы) с IP-адресом $1
указал данный адрес электронной почты в качестве нового для учётной записи «<b>$2</b>» в проекте {{SITENAME}}.</p>

<p>Чтобы подтвердить, что эта учётная запись действительно принадлежит вам,
и включить возможность отправки писем с сайта {{SITENAME}}, откройте приведённую ниже ссылку в браузере.</p>

<p><a href="$3">$3</a></p>

<p>Если данная учётная запись *не* относится к вам, то перейдите по следующей ссылке,
чтобы отменить подтверждение адреса</p>

<p><a href="$5">$5</a></p>

<p>Код подтверждения действителен до $4.</p>
</body>',

    // Тело письма подтверждения адреса (на случай его добавления со страницы настроек)
    'confirmemail_body_set'     =>
'<body>
<p>Кто-то (возможно вы) с IP-адресом $1
указал данный адрес электронной почты для учётной записи «$2» в проекте {{SITENAME}}.</p>

<p>Чтобы подтвердить, что эта учётная запись действительно принадлежит вам,
и включить возможность отправки писем с сайта {{SITENAME}}, откройте приведённую ниже ссылку в браузере.</p>

<p><a href="$3">$3</a></p>

<p>Если данная учётная запись *не* относится к вам, то перейдите по следующей ссылке,
чтобы отменить подтверждение адреса</p>

<p><a href="$5">$5</a></p>

<p>Код подтверждения действителен до $4.</p>
</body>',
);
