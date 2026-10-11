## ===========================================================================
##  Policy Evaluation Lab -- Lab 1: first steps in R
##  Roma Tre, a.y. 2026/27 -- Dr. Matteo Caruso
##
##  Before you start:
##   1. Create a folder for the course, e.g. Documents/PEL
##   2. Put this script and firms_grant.csv (course website, week 2) in it
##   3. In RStudio: Session > Set Working Directory > To Source File Location
##
##  Run a line with Ctrl+Enter (Cmd+Enter on a Mac).
## ===========================================================================


## ---- 1. R as a calculator ------------------------------------------------

2 + 3
10 / 4
2^10
sqrt(144)
log(100)          # natural logarithm
log10(100)


## ---- 2. Objects: give a name to a result ---------------------------------

grant_share <- 0.30                  # "<-" assigns a value to a name
machinery   <- 250000                # cost of new machinery, euro
grant_share * machinery              # how much the agency pays

# Vectors: several values in one object
growth <- c(11, 3, 8, 6)             # c() = combine
growth
mean(growth)
length(growth)
growth[1]                            # first element
growth > 5                           # TRUE / FALSE, element by element

# Text and logical values
firm   <- c("Alfa", "Beta")
funded <- c(TRUE, FALSE)

# Help on any function
?mean


## ---- 3. Load the data -----------------------------------------------------

firms <- read.csv("firms_grant.csv")

# A first look
head(firms)          # first 6 rows
nrow(firms)          # number of firms
ncol(firms)          # number of variables
names(firms)         # variable names
str(firms)           # type of each variable
summary(firms)       # min, mean, max... of each variable
View(firms)          # spreadsheet view (RStudio)

# One variable: data$variable
firms$employees_2015
mean(firms$employees_2015)
median(firms$employees_2015)
sd(firms$employees_2015)

# Categorical variables: counts
table(firms$region)
table(firms$sector)
table(firms$grant)                   # 1 = received the grant
prop.table(table(firms$grant))       # shares


## ---- 4. Create new variables ----------------------------------------------

# Sales growth 2015-2019, in percent
firms$sales_growth <- 100 * (firms$sales_2019 / firms$sales_2015 - 1)
summary(firms$sales_growth)

# A dummy for firms in the South
firms$south <- as.numeric(firms$region == "South")
mean(firms$south)                    # share of southern firms


## ---- 5. Lecture 2, Table 1: the naive comparison --------------------------

# Average growth of subsidised and non-subsidised firms
mean(firms$sales_growth[firms$grant == 1])
mean(firms$sales_growth[firms$grant == 0])

# All groups at once
tapply(firms$sales_growth, firms$grant, mean)
tapply(firms$sales_growth, firms$grant, sd)

# The difference in means and its standard error: t-test
t.test(sales_growth ~ grant, data = firms)
# Note: R computes mean(group 0) - mean(group 1), so the sign is reversed


## ---- 6. Lecture 2, Table 2: balance check ---------------------------------

# Were subsidised firms larger BEFORE the grant?
tapply(firms$employees_2015, firms$grant, mean)
t.test(employees_2015 ~ grant, data = firms)

# Were they older?
tapply(firms$age, firms$grant, mean)
t.test(age ~ grant, data = firms)

# Save a result and pick its pieces
tt <- t.test(age ~ grant, data = firms)
tt$statistic                         # t-statistic
tt$p.value                           # p-value


## ---- 7. A picture ---------------------------------------------------------

hist(firms$sales_growth, breaks = 40,
     main = "Sales growth 2015-2019", xlab = "%")

boxplot(sales_growth ~ grant, data = firms,
        names = c("No grant", "Grant"),
        ylab = "Sales growth 2015-2019 (%)")


## ---- 8. Exercises ---------------------------------------------------------
## Write your code below each question.

## (a) Create employment growth 2015-2019 (%) and compare its mean between
##     subsidised and non-subsidised firms. Is the difference significant?


## (b) Complete the balance check of Table 2: sales_2015, exporter,
##     family_owned, south. Which differences are significant at 1%?


## (c) Repeat the naive comparison of sales growth for exporters only.
##     Hint: exporters <- firms[firms$exporter == 1, ]
##     Does the difference change much? What does this tell you?


## (d) Which sector has the highest share of subsidised firms?
##     Hint: tapply(firms$grant, firms$sector, mean)


## (e) Save this script (Ctrl+S / Cmd+S). Next lab starts from here.
