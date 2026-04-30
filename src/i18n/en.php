<?php

return [
    'html_lang'                   => 'en',

    'page_title'                  => 'Survey ID Generator',
    'h1'                          => 'Survey ID Generator',

    'whats_this_heading'          => 'What is this?',
    'whats_this_body'             => 'A tool that generates "Survey IDs".',
    'whats_this_value'            => 'Combine Survey IDs with Google Forms or any other online form to run surveys that are easy to answer and produce trustworthy results.',

    'typo_prevention_body'        => 'Generated IDs are built to resist typos. Set the ID Recognition Pattern on your form, and input mistakes get caught by the form\'s validation.',

    'fraud_detection_body'        => 'Only about <strong>1%</strong> of strings that match the pattern are actually distributed as IDs. Cross-check submitted answers against your distribution list, and you can flag fraudulent responses (= those sent with non-distributed IDs).',

    'readme_link'                 => 'For technical details, see the <a href="https://github.com/sakilabo/survey-id-generator-php/blob/main/README.md">README on GitHub</a>.',

    'usage_heading'               => 'How to use',
    'usage_intro'                 => 'Pressing "Generate" produces an "ID Recognition Pattern" and a "Distribution ID list".',
    'usage_step_pattern'          => 'Set the "ID Recognition Pattern" as the validation on your survey\'s input field. In Google Forms: enable "Response validation" on the question, choose "Regular expression" and "Matches", and paste the pattern into the "Pattern" field.',
    'usage_step_ids'              => 'The "Distribution ID list" is the set of IDs you hand out to respondents. You\'ll need it later to spot fraudulent answers, so save it somewhere — or bookmark this page\'s URL after generating.',

    'generate_heading'            => 'Generate IDs',
    'id_length_label'             => 'ID length (number of Distribution IDs):',
    'complexity_label_format'     => '%d chars (%s IDs)',
    'generate_button'             => 'Generate',

    'bookmark_url_heading_format' => 'Bookmark URL (valid until %s)',
    'delete_confirm'              => "Server data will be deleted.\nAfter deletion, this URL will no longer work.\nProceed?",
    'delete_button'               => 'Delete server data',
    'pattern_heading'             => 'ID Recognition Pattern (regex for forms)',
    'distribution_heading_format' => 'Distribution IDs (%s)',

    'date_format'                 => 'F j, Y',

    'copyright'                   => '© 2026 Sakilabo Corporation Ltd. All rights reserved.',
];
