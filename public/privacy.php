<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

$c       = course()['course'];
$contact = config('contact_email') ?? $c['email'];

page_header('Privacy', 'privacy.php');
page_head('Privacy notice', 'How this website uses your data (EU Regulation 2016/679, GDPR).');
?>

      <div class="course-item">
        <p class="course-title">Who is responsible</p>
        <p class="course-desc">
          This website is run personally by the lecturer of the course, <?= e($c['lecturer']) ?>
          (<a href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a>), to share teaching material.
          It is not an official website of <?= e($c['university']) ?>; official communications are
          published on the university's platforms.
        </p>
      </div>

      <div class="course-item">
        <p class="course-title">What is collected and why</p>
        <p class="course-desc">
          When you register: first and last name, university e-mail, student ID (<em>matricola</em>),
          degree programme and whether you plan to attend. Each time you log in, the date is recorded.
          These data are used only to give you access to the course material, to know how many
          students follow the course, and to contact you about the course. Your password is stored
          only in encrypted (hashed) form and cannot be read by anyone.
        </p>
        <p class="course-desc">
          The site uses a single technical cookie to keep you logged in. There are no analytics,
          advertising or tracking cookies, and data are never shared with third parties.
        </p>
      </div>

      <div class="course-item">
        <p class="course-title">How long data are kept</p>
        <p class="course-desc">
          Until the end of the last exam session of the <?= e($c['year']) ?> academic year, after
          which all accounts are deleted.
        </p>
      </div>

      <div class="course-item">
        <p class="course-title">Your rights</p>
        <p class="course-desc">
          You can see your data under <em>Account</em>, delete your account at any time from the same
          page, or write to the address above to access, correct or erase your data.
        </p>
      </div>

<?php page_footer();
