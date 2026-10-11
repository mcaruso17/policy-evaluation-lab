## ===========================================================================
##  Policy Evaluation Lab -- Lab 1: solutions to the exercises
## ===========================================================================

firms <- read.csv("firms_grant.csv")
firms$sales_growth <- 100 * (firms$sales_2019 / firms$sales_2015 - 1)
firms$south <- as.numeric(firms$region == "South")

## (a) Employment growth
firms$empl_growth <- 100 * (firms$employees_2019 / firms$employees_2015 - 1)
tapply(firms$empl_growth, firms$grant, mean)
t.test(empl_growth ~ grant, data = firms)
# Subsidised firms: +4.5%, others: +1.7%. Difference 2.9 points, |t| = 6.5:
# significant at 1%. Same caveat as sales: this is not (only) the grant.

## (b) Balance check
t.test(sales_2015 ~ grant, data = firms)     # |t| = 7.3  -> significant at 1%
t.test(exporter ~ grant, data = firms)       # |t| = 8.5  -> significant at 1%
t.test(family_owned ~ grant, data = firms)   # |t| = 2.0  -> significant at 5%, not at 1%
t.test(south ~ grant, data = firms)          # |t| = 5.3  -> significant at 1%
# (R reports group 0 minus group 1, hence the negative t-statistics.)
# Subsidised firms were larger, sold more, exported more and were more often
# in the South already in 2015: the groups are not balanced.

## (c) Exporters only
exporters <- firms[firms$exporter == 1, ]
tapply(exporters$sales_growth, exporters$grant, mean)
t.test(sales_growth ~ grant, data = exporters)
# Difference 6.3 points, almost the same as in the full sample (6.4).
# Holding export status fixed does not remove the gap: subsidised firms
# differ in something else we do not observe (Lecture 2: management quality).

## (d) Share of subsidised firms by sector
sort(tapply(firms$grant, firms$sector, mean), decreasing = TRUE)
# Furniture (29.5%), but the shares range only from 25.6% to 29.5%: sector
# did not drive who got the grant. Not every characteristic is unbalanced.
