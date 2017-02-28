<?php

return array(
    'begin_html' => array(
        'title'        => 'Начало HTML-вставки',
        'description'  => 'Начало HTML-вставки',
        'value'        => '',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
    'each_row' => array(
        'title'        => 'HTML-шаблон для вывода каждой строчки',
        'description'  => '{$name} - имя скидки<br/>'
                        . '{$description} - описание скидки<br/>'
                        . '{$discount_percentage} - скидка в процентах<br/>'
                        . '{$discount} - скидка в денежных единицах<br/>'
                        . '{$discount_currency} - валюта скидки<br/>'
                        . '{$affiliate_percentage} - бонусы в процентах<br/>'
                        . '{$affiliate} - скидка в бонусах',
        'value'        => '',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
    'end_html' => array(
        'title'        => 'Конец HTML-вставки',
        'description'  => 'Конец HTML-вставки',
        'value'        => '',
        'control_type' => waHtmlControl::TEXTAREA,
    )   
);
