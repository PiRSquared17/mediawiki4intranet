<?php

/**
 * UserPageByEmail.class.php -- Redirect to User:... page by his email
 * Copyright 2012+ Vitaliy Filippov <vitalif@mail.ru>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
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

class SpecialUserPageByEmail extends SpecialPage
{
    function __construct()
    {
        parent::__construct('UserPageByEmail');
    }

    function execute($par)
    {
        global $wgRequest, $wgOut;
        $email = $par;
        if (!$email)
            $email = $wgRequest->getVal('email');
        $wgOut->setPageTitle(wfMsg('upbyemail-title'));
        if (!$email)
            $this->form();
        else
            $this->locate($email);
    }

    function form($email = '')
    {
        global $wgTitle, $wgOut;
        $wgOut->addHTML(
            Xml::tags('form', array('method' => 'POST', 'action' => $wgTitle->getLocalUrl()),
                wfMsg('upbyemail-email') .
                Html::input('email', $email) .
                Html::input('submit', wfMsg('upbyemail-submit'), 'submit')
            )
        );
    }

    function locate($email)
    {
        global $wgOut;
        $dbr = wfGetDB(DB_SLAVE);
        // Maybe this isn't so efficient (uses substring LIKE matching)
        $res = $dbr->select('user', '*', array('user_email LIKE '.$dbr->addQuotes('%'.$email.'%')),
            __METHOD__, array('LIMIT' => 5));
        $n = $dbr->numRows($res);
        if ($n == 1)
        {
            $row = $dbr->fetchObject($res);
            $title = Title::makeTitle(NS_USER, $row->user_name);
            $wgOut->redirect($title->getLocalUrl());
        }
        else
        {
            if (!$n)
                $wgOut->addWikiMsg('upbyemail-notfound');
            else
            {
                $wgOut->addWikiMsg('upbyemail-notunique');
                $text = '';
                foreach ($res as $row)
                    $text .= "* [[".Title::makeTitle(NS_USER, $row->user_name)."]]\n";
                $wgOut->addWikiText($text);
            }
            $this->form($email);
        }
    }
}
