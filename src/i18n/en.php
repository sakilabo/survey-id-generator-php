<?php

return [
    'html_lang'                   => 'en',

    'page_title'                  => 'Anonymous IDs in Place of Login — Survey ID Generator',
    'h1'                          => 'Survey ID Generator',

    'overview_image'              => 'overview.en.jpg',

    'whats_this_heading'          => 'What is this?',
    'whats_this_body'             => 'A tool that generates "Survey IDs". Survey IDs let you run surveys that require both anonymity and reliability at once — sensitive research, internal surveys at companies or schools, and consultations conducted by local governments or religious congregations.',
    'feature_typo_prevention'     => 'Generates ID patterns that resist typos',
    'feature_anonymous'           => 'No email address required — fully anonymous surveys',
    'feature_no_login'            => 'No login or user authentication — accessible even to non-technical respondents',
    'feature_fraud_detection'     => 'Detects duplicates and fraudulent responses via ID cross-check',

    'readme_link'                 => 'For technical details, see the <a href="https://github.com/sakilabo/survey-id-generator-php/blob/main/README.md">README on GitHub</a>.',

    'usage_heading'               => 'How to use',
    'usage_intro'                 => 'Choose an "ID length" and press "Generate". This produces an "ID Recognition Pattern" and a "Distribution ID list".',
    'usage_step_pattern'          => 'Set the "ID Recognition Pattern" as the validation on your survey\'s input field. In Google Forms: open the "⋮" menu at the bottom-right of the question, enable "Response validation", then paste the "ID Recognition Pattern" into the "Pattern" field under "Regular expression".',
    'usage_step_ids'              => 'The "Distribution ID list" is the set of IDs you hand out to respondents. Cross-check submitted responses against this list to detect fraudulent answers.',
    'usage_tip_sheet_link'        => 'For Google Forms, we recommend <a href="https://support.google.com/docs/answer/2917686">linking responses to a spreadsheet</a> for easier handling.',

    'generate_heading'            => 'Generate IDs',
    'id_length_label'             => 'ID length (number of Distribution IDs):',
    'complexity_label_format'     => '%d chars (%s IDs)',
    'generate_button'             => 'Generate',

    'bookmark_url_heading_format' => 'Bookmark URL (valid until %s)',
    'delete_confirm'              => "Server data will be deleted.\nAfter deletion, this URL will no longer work.\nProceed?",
    'delete_button'               => 'Delete server data',
    'pattern_heading'             => 'ID Recognition Pattern (regex for forms)',
    'distribution_heading_format' => 'Distribution IDs (%s)',
    'download_button'             => 'Download',
    'download_filename_prefix'    => 'Survey IDs ',

    'validation_heading'          => 'Response ID Verification',
    'validation_button'           => 'Verify',
    'validation_paste_error'      => 'No IDs were recognized.',
    'validation_result_label'     => 'Result',
    'validation_count_total'      => 'Responses: %s',
    'validation_count_unique'     => 'Unique IDs: %s',
    'validation_count_duplicates' => 'Duplicates: %s',
    'validation_count_invalid'    => 'Invalid: %s',

    'date_format'                 => 'F j, Y',

    'language_switch_label'       => '日本語',

    'copyright'                   => '© 2026 Sakilabo Corporation Ltd. All rights reserved.',
];
