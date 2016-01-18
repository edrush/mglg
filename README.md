# mglg
MultiGitLogGenerator

mglg generates an aggregated git log for all git repositories located in a local folder, sorted by date, as csv.

Based on https://github.com/Frencil/MultiGource.

### Usage: 

`
php mglg.php -p="/home/foo" -a="Wolfram" -d="2016-01-01" > git_logs.csv
`

List of options:
- *p*: Path to repositories (string)
- *a*: Filter author  (string)
- *d*: Filter date from (string in date format YYYY-mm-dd) 