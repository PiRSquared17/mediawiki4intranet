<?php

require_once 'SpecialForm.i18n.php';

foreach ($messages as $k => $v)
    print '$aliases[\''.$k.'\'] = array(\'Form\' => \''.$v['form']."');\n";
