# Slide sources kept in the repository

Most lecture sources live in the Overleaf project (synced to Dropbox). Sources
written outside Overleaf are kept here. They use the course preamble
(`preamble-pel.tex` and `pel-bear.tex` from the Overleaf project): copy the
`.tex` file next to them to compile.

- `lecture-budget-cycle.tex`: special session of 23 October 2026, "Where does
  evidence enter the budget?". Student slides: `pdflatex lecture-budget-cycle.tex`.
  Notes edition: `pdflatex -jobname=lecture-budget-cycle-notes "\def\pelShowNotes{}\input{lecture-budget-cycle.tex}"`.
  Sources: `materials/readings/special-session-budget/`.
- `lecture-06-did.tex`: Lecture 6, difference-in-differences (week 4, Oct 2026
  restructuring). Built from Overleaf "lecture 7.tex" and "lecture 8.tex" (the
  Migali DiD decks). Figures in `figures/lecture-06d/`; copy that folder next
  to the preamble too. Number sources are listed in the file header.
  Student slides: `pdflatex lecture-06-did.tex`.
- `lecture-07-iv-rdd.tex`: Lecture 7, instrumental variables, regression
  discontinuity and synthetic control (week 4). IV part from Overleaf "lecture 9.tex" (Migali IV
  deck); RDD and synthetic control parts new. Figures in `figures/lecture-07ivrd/`. Number sources in
  the file header. Student slides: `pdflatex lecture-07-iv-rdd.tex`.
- `lab-02-carta-docente.tex`: Lab 2 (Monday 2 November 2026), group case study
  on the Carta del docente (question 2 of the MEF written exam, 31 March 2025;
  data constructed for the exercise). The photos in `figures/lab-02/` are not
  kept in the repository.
- `lab-03-borse-dsu.tex` and `lab-04-parco-agrisolare.tex`: Labs 3 and 4
  (5 and 6 November 2026), case studies from the two envelopes not drawn at
  the same MEF exam (code STAT, envelopes 2 and 1), published by the MEF on
  4 June 2025. Lab 4 is released on the site at 17:00 on 6 November.
