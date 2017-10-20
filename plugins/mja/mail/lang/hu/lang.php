<?php

return [
    'plugin_name' => 'Levelezés',
    'plugin_description' => 'E-mail statisztika és naplózás.',

    'formwidget' => [
        'title' => 'Levél rács',
        'description' => 'Rácsok létrehozás a levelekhez',
    ],

    'permission' => [
        'template' => 'Téma statisztika megtekintése',
        'mail' => 'Levél statisztika megtekintése',
    ],

    'controllers' => [
        'mail' => [
            'title' => 'Levél naplózás',
            'mails_sent' => 'Levelek',
            'preview' => 'Információk',
            'return' => 'Vissza a levél listához',

            'stats_sent' => 'Elküldött',
            'stats_bounced' => 'Visszapattanó',
            'stats_total_sent' => 'Összes elküldött',
            'stats_total_opens' => 'Összes megnyitott',
        ],
        'template' => [
            'title' => 'Témák',
            'stats_title' => 'Statisztika',
            'opens' => 'Olvasottság',
            'opens_desc' => 'Az elmúlt 7 napban megnyitott levelek száma.',
        ],
    ],

    'models' => [
        'email' => [
            'tab_emails' => 'Levelek',
            'tab_opens' => 'Megnyitott',

            'id' => 'Azonosító',
            'code' => 'Kód',
            'to_email' => 'Címzett',
            'cc_emails' => 'Másolat',
            'bcc_emails' => 'Titkos másolat',
            'subject' => 'Tárgy',
            'body' => 'Törzs',
            'sender' => 'Küldő',
            'reply_to' => 'Válasz neki',
            'date' => 'Elküldés dátuma',
            'sent' => 'Elküldve',
            'yes' => 'igen',
            'no' => 'nem',
            'times_opened' => 'Megnyitások száma',
            'times_opened_desc' => 'Az ügyfél által',
            'last_opened' => 'Utolsó megnyitás',
            'created_at' => 'Létrehozva',
            'updated_at' => 'Módosítva',

            'show_bounced' => 'Csak a visszapattanók mutatása',
        ],

        'emailopens' => [
            'id' => 'Azonosító',
            'ip' => 'IP cím',
            'created_at' => 'Dátum és idő',
        ],

        'template' => [
            'id' => 'Azonosító',
            'code' => 'Kód',
            'sent' => 'Elküldött levelek',
            'opens' => 'Megnyitások száma',
            'last_send' => 'Utoljára elküldve',
            'last_open' => 'Utoljára megnyitva',
        ],
    ],
];
