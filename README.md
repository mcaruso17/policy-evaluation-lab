# Policy Evaluation Lab — course website

Private course site for *Policy Evaluation Lab* (Roma Tre, Department of Business
Economics, a.y. 2026/27), served at **https://pel.carusomatteo.it**.

Students register with their university e-mail, student ID, degree programme,
a password of their own and the **course access code** given in class. After
that they log in with e-mail + password. The site looks exactly like
carusomatteo.it: `public/assets/style.css`, `js/main.js` and `js/bear.js` are
verbatim copies from the personal-site repo.

PHP 8 + SQLite, no build step, no external services.

## Layout

```
public/            ← Plesk DOCUMENT ROOT (the only folder reachable from the web)
  index.php          home: announcements + course info
  materials.php      lectures, week by week
  exercises.php      problem sets, EconVis practice
  exam.php           assessment, sessions, past exams
  resources.php      textbooks, software, data
  login.php / register.php / logout.php / account.php / privacy.php
  admin.php          student list, headcount, CSV export, access code (admin only)
  file.php           serves files from materials/ to logged-in users only
  assets/            style.css (copy of the personal site) + pel.css (forms, table)
app/
  bootstrap.php      session, database, auth, helpers
  layout.php         nav + footer
  config.sample.php  → copy to config.php (git-ignored: holds the access code)
content/
  course.json        ALL the text and links on the site — edit this
materials/           PDFs, datasets, do-files (not public; served via file.php)
  slides/ datasets/ exercises/ solutions/ exams/ readings/
data/                SQLite database + sessions (git-ignored: personal data)
```

## Weekly routine: adding material

1. Drop the file in `materials/…`, e.g. `materials/slides/pel-01-causality.pdf`
   (compile it in Overleaf, then download the PDF).
2. Add an item to the right week in `content/course.json`:
   ```json
   { "type": "slides", "label": "Lecture 1 — Causality", "file": "slides/pel-01-causality.pdf" }
   ```
   Types: `slides`, `dataset`, `code`, `exercise`, `solution`, `reading`, `exam`,
   `link`, `video`. Use `"url": "https://…"` instead of `"file"` for external links.
3. To release something later (e.g. solutions), add
   `"available_from": "2026-11-20"`: students do not see it and cannot download it
   before that date; you see it with a dashed "from …" badge.
4. `git add -A && git commit -m "Week 1 material" && git push` → live in ~30 s.

Only files listed in `course.json` can be downloaded, so an unlisted file in
`materials/` stays private. Announcements go at the top of `"announcements"`.
Text fields (`body`, `summary`, `description`) may contain simple HTML.

Check the JSON before pushing: `python3 -m json.tool content/course.json > /dev/null`.

## One-time setup on Plesk

1. **GitHub**: create a **private** repository (the materials are behind a login,
   so the repo must not be public), then from this folder:
   ```bash
   git add -A && git commit -m "Course website" 
   git remote add origin git@github.com:mcaruso17/policy-evaluation-lab.git
   git push -u origin main
   ```
2. **Subdomain**: Plesk → *Websites & Domains* → *Add Subdomain* → `pel` →
   document root **`pel.carusomatteo.it/public`**. DNS for subdomains of
   carusomatteo.it is normally created automatically if Plesk manages the DNS;
   otherwise add an A record `pel` pointing to the same IP as `carusomatteo.it`.
3. **HTTPS**: *SSL/TLS Certificates* → Let's Encrypt for `pel.carusomatteo.it`.
4. **PHP**: *PHP Settings* → PHP 8.1 or newer (8.3 recommended). The `pdo_sqlite`
   extension is on by default in Plesk.
5. **Git**: *Git* → add the private repository (Plesk shows an SSH public key: add
   it to GitHub → repo → Settings → *Deploy keys*, read-only) → deployment path
   `/pel.carusomatteo.it` (the subdomain folder, **not** `…/public`) →
   *Automatic deployment*. Add the webhook URL Plesk shows under GitHub →
   Settings → Webhooks, as for the personal site.
6. **Config**: in the Plesk *File Manager*, copy `app/config.sample.php` to
   `app/config.php` and set the access code. Deployments never overwrite it.
7. **Admin account**: open the site, register with `carusomatteo17@gmail.com`
   (it is in `admin_emails`, so it is exempt from the university-domain rule).
   The *Students* tab appears in the menu.

## Admin page (`Students` tab)

- Number of registered / attending / non-attending students, and who was active
  in the last 7 days; breakdown by degree programme.
- **Download CSV** (semicolon-separated, opens in Excel with accents intact).
- Change the access code, or close registration once the class list is complete.
- Per student: *Reset pw* (shows a temporary password to send them), *Disable*,
  *Delete*. There is no e-mail sending, so forgotten passwords go through you.

## Privacy

`privacy.php` is a short GDPR notice: data are used only for course access and
headcount, one technical session cookie, no analytics, accounts deleted after the
last exam session. To clean up at the end of the year, delete `data/pel.sqlite`
on the server. The database is never committed (see `.gitignore`).

## Run locally

Needs PHP ≥ 8.1 (`brew install php`):

```bash
cp app/config.sample.php app/config.php
php -S 127.0.0.1:8000 -t public
```
