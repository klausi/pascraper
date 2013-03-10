<?php

require 'vendor/autoload.php';
require 'user_password.php';

use Goutte\Client;

// "Needs work" issue status ID.
const PROJECAPP_SCRAPER_NEEDS_WORK = 13;

// Perform a user login.
$client = new Client();
$crawler = $client->request('GET', 'http://drupal.org/user');
$form = $crawler->selectButton('Log in')->form();
// $user and $password must be set in user_password.php.
$crawler = $client->submit($form, array('name' => $user, 'pass' => $password));

$login_errors = $crawler->filter('.messages-error');
if ($login_errors->count() > 0) {
  print "Login failed.\n";
  exit(1);
}

// Get all "needs review" issues.
$crawler = $client->request('GET', 'http://drupal.org/project/issues/search/1339220?status[]=8');
$issues = $crawler->filterXPath('//tbody/tr/td[1]/a');

if ($issues->count() == 0) {
  print "No issues found.\n";
  exit(2);
}

$links = $issues->links();

// Go to each issue.
foreach ($links as $link) {
  $issue_page = $client->click($link);
  $issue_summary = $issue_page->filter('.node-content');
  $text = $issue_summary->text();

  // Search for the git repository link.
  $matches = array();
  preg_match('/http:\/\/git\.drupal\.org\/sandbox\/.+\.git/', $text, $matches);
  if (!empty($matches)) {
    $git_url = $matches[0];
  }
  else {
    // Try to find a user specific git URL.
    preg_match('/[^\s]+@git\.drupal\.org:sandbox\/.+\.git/', $text, $matches);
    if (!empty($matches)) {
      // Rewrite git URL to anonymous HTTP URL.
      $git_url = preg_replace('/^.+@git\.drupal\.org:sandbox/', 'http://git.drupal.org/sandbox', $matches[0]);
    }
    else {
      // Set the issue to "needs work" as the Git URL is missing.
      $comment_form = $issue_page->selectButton('Save')->form();
      $comment = 'Git repository URL is missing in the issue summary.';
      $client->submit($comment_form, array(
        'sid' => PROJECAPP_SCRAPER_NEEDS_WORK,
        'comment' => $comment,
      ));
      continue;
    }
  }


}
