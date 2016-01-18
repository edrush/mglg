<?php

/*****************************************************
 * mglg - The MultiGitLogGenerator
 *
 * Author Christopher Clark (@Frencil)
 * March 24, 2012
 *
 * Author Wolfram Eberius (@edrush)
 * in January, 2016
 *****************************************************/

$exportDivider = ';';
$exportDividerReplacement = ',';

$headers = array();
$headers[] = "Date";

ini_set('display_errors', false);

/**
 * ********************
 * Command options
 * *********************.
 */
$options = getopt("p::a::d::");
/*
 * Root path (mandatory)
 *
 * EXAMPLE: '/home/foo/repos';
 */
$reposPath = getcwd();
if (isset($options['p'])) {
    $reposPath = $options['p'];
}
if (!is_readable($reposPath)) {
    exit("Path not found.");
}
/*
 * Author (optional)
 *
 * EXAMPLE: 'foo bar';
 */
if (isset($options['a'])) {
    $authorFilter = $options['a'];
} else {
    $headers[] = "Author";
}
/*
 * Date from (optional)
 *
 * EXAMPLE: '2016-12-01';
 */
if (isset($options['d'])) {
    $dateFrom = $options['d'];
}

$headers[] = "Repository";
$headers[] = "Commit message";
/*
 * ********************
 * EXECUTABLE CODE
 * *********************
 */

$allCommits = array();

slurpLog($reposPath);

if (empty($allCommits)) {
    exit("No commits.");
}

ksort($allCommits);

$scalarLogs[] = implode($exportDivider, $headers);
foreach ($allCommits as $date => $commit) {
    $lastDate = null;
    foreach ($commit as $log) {
        if ($lastDate == $log[0]) {
            $log[0] = '';
        }
        $scalarLogs[] = implode($exportDivider, $log);
        $lastDate = $log[0];
    }
}
$scalarLog = implode("\n", $scalarLogs);

echo $scalarLog;

exit();

/**
 * ***********************
 * FUNCTIONS
 * **********************.
 */
function slurpLog($path = '.')
{
    global $authorFilter, $dateFrom;
    // Change the working directory
    chdir($path);

    // Scan the dir
    $dirContents = scandir($path);

    // If dir is a repo then slurp in the log
    if (in_array('.git', $dirContents)) {
        $gitLogCommand = 'git log --name-status --branches';
        if (!empty($authorFilter)) {
            $gitLogCommand .= ' --author="'.$authorFilter.'"';
        }
        if (!empty($dateFrom)) {
            $gitLogCommand .= ' --since="'.$dateFrom.'"';
        }
        $commits = explode("\n\ncommit ", `$gitLogCommand`);
        slurpCommits($path, $commits);
    } // Otherwise recurse over each subdir
    else {
        $dh = @opendir($path);
        while (false !== ($file = readdir($dh))) {
            if (!in_array($file, array(
                    '.',
                    '..',
                )) && is_dir("$path/$file")
            ) {
                slurpLog("$path/$file");
            }
        }
        closedir($dh);
    }
}

function slurpCommits($path = '.', $commits = array())
{
    global $allCommits, $reposPath, $exportDivider, $exportDividerReplacement, $authorFilter;

    foreach ($commits as $commit) {
        if (empty($commit)) {
            continue;
        }
        $commit = explode("\n", $commit);
        $files = array();
        $date = 0;
        $author = '';
        $subjectLines = array();
        $repository = array_pop(explode('/', $path));

        foreach ($commit as $key => $line) {
            // Skip blanks
            if (!trim($line)) {
                continue;
            }

            $line = str_replace($exportDivider, $exportDividerReplacement, $line);

            //Extract files
            if ((substr($line, 0, 2) == "M\t") || (substr($line, 0, 2) == "A\t") || (substr($line, 0, 2) == "D\t")) {
                $files[] = substr($line, 0, 1) . '|' . substr($path, strlen($reposPath) + 1) . '/' . substr($line, 2);
            } // Extract author
            elseif (substr($line, 0, 8) == 'Author: ') {
                $author = substr($line, strpos($line, ' ') + 1);
            } // Extract date
            elseif (substr($line, 0, 8) == 'Date:   ') {
                $date = strtotime(substr($line, 8));
            } elseif ($key != 0) {
                $subjectLines[] = trim($line);
            }
        }

        $logs = array();
        $logs[] = date('Y-m-d', $date);
        if (empty($authorFilter)) {
            $logs[] = $author;
        }
        $logs[] = $repository;
        $logs[] = implode(' ', $subjectLines);

        $allCommits[date('Ymd', $date)][] = $logs;
    }
}
