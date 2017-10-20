<?php

return [
    'plugin_name' => 'Mail',
    'plugin_description' => 'Statistics and logging of emails.',

    'formwidget' => [
        'title' => 'Email Grid',
        'description' => 'Renders a grid of emails',
    ],

    'permission' => [
        'template' => 'View template stats',
        'mail' => 'View mail stats',
    ],

    'controllers' => [
        'mail' => [
            'title' => 'Mail log',
            'mails_sent' => 'Mails sent',
            'preview' => 'View mail information',
            'return' => 'Return back to the mail list',

            'stats_sent' => 'Sent',
            'stats_bounced' => 'Bounced',
            'stats_total_sent' => 'Total emails sent',
            'stats_total_opens' => 'Total opens',
        ],
        'template' => [
            'title' => 'Templates',
            'stats_title' => 'Stats',
            'opens' => 'Email opens',
            'opens_desc' => 'Shows how many emails with the current template have been opened in the last 7 days.',
        ],
    ],

    'models' => [
        'email' => [
            'tab_emails' => 'Emails',
            'tab_opens' => 'Opens',

            'id' => 'ID',
            'code' => 'Code',
            'to_email' => 'To',
            'cc_emails' => 'CC',
            'bcc_emails' => 'BCC',
            'subject' => 'Subject',
            'body' => 'Body',
            'sender' => 'Sender',
            'reply_to' => 'Reply To',
            'date' => 'Date sent',
            'sent' => 'Sent',
            'yes' => 'Yes',
            'no' => 'No',
            'unknown' => 'Unknown',
            'times_opened' => 'Times Opened',
            'times_opened_desc' => 'By the client',
            'last_opened' => 'Last Opened',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',

            'show_bounced' => 'Show only bounced',
        ],

        'emailopens' => [
            'id' => 'ID',
            'ip' => 'IP',
            'created_at' => 'Date and time',
        ],

        'template' => [
            'id' => 'ID',
            'code' => 'Code',
            'sent' => 'Emails sent',
            'opens' => 'Times opened',
            'last_send' => 'Last time sent',
            'last_open' => 'Last time opened',
        ],
    ],
];
