# Slide sources kept in the repository

Most lecture sources live in the Overleaf project (synced to Dropbox). Sources
written outside Overleaf are kept here. They use the course preamble
(`preamble-pel.tex` and `pel-bear.tex` from the Overleaf project): copy the
`.tex` file next to them to compile.

- `lecture-budget-cycle.tex`: special session of 23 October 2026, "Where does
  evidence enter the budget?". Student slides: `pdflatex lecture-budget-cycle.tex`.
  Notes edition: `pdflatex -jobname=lecture-budget-cycle-notes "\def\pelShowNotes{}\input{lecture-budget-cycle.tex}"`.
  Sources: `materials/readings/special-session-budget/`.
