<?php

/*
|--------------------------------------------------------------------------
| Site forms
|--------------------------------------------------------------------------
|
| Strings a visitor sees. Everything the owner sets in the builder (field
| labels, the thank-you text, the button caption) lives in the form itself
| and is not listed here — that is content, not interface.
|
*/

return [
    'submit'     => 'Send',
    'sent'       => 'Thank you! We have received your message.',
    'check_form' => 'Please check the form:',
    'choose'     => 'Choose…',
    'too_often'  => 'Too many submissions. Please try again in :minutes minutes.',

    // Label of the honeypot field. A human never sees it, but a screen reader
    // does — so it should say something sensible rather than nothing.
    'trap_label' => 'Leave this field empty',

    'mail_subject' => 'New submission: :form',
    'mail_intro'   => 'Received on :date',
    'mail_footer'  => 'Sent from the site, IP :ip',
];
