<?php
/* Copy this file to app/config.php and edit it. config.php is git-ignored:
   it holds the course access code, so it never goes to GitHub. On the server
   create it once by hand (Plesk File Manager) — deployments will not touch it. */

return [
    // Given out in class. Students need it once, to register. It can also be
    // changed later from the admin page (the admin value overrides this one).
    'access_code' => 'change-me',

    // These accounts get the admin page. They register like everyone else
    // (with the access code) and are exempt from the e-mail domain rule.
    'admin_emails' => ['carusomatteo17@gmail.com'],

    // Only university addresses may register. Empty array = any address.
    'allowed_email_domains' => ['stud.uniroma3.it', 'uniroma3.it'],

    // How long a student stays logged in without coming back.
    'session_days' => 14,

    // Shown in the privacy notice and the footer.
    'contact_email' => 'carusomatteo17@gmail.com',
];
