<?php

global $wpsf_settings;

// General Settings section
$wpsf_settings[] = array(
    'section_id' => 'general',
    'section_title' => 'API Credentials',
    'section_description' => '',
    'section_order' => 5,
    'fields' => array(
        array(
            'id' => 'api_key',
            'title' => 'API Key',
            'desc' => "You can find this on the <a href='https://bihang.com/apiKey/index.do'>API Keys</a> page.",
            'type' => 'text',
            'std' => ''
        ),
        array(
            'id' => 'api_secret',
            'title' => 'API Secret',
            'desc' => "You can find this on the <a href='https://bihang.com/apiKey/index.do'>API Keys</a> page.",
            'type' => 'password',
            'std' => ''
        ),
    )
);

?>
