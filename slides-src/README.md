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
