<?php

// Usage: php mass_updater.php <TFA code>
// TFA code is the code from your TFA phone app for drupal.org.
// Make sure to change the $url, $status and $comment variables below.

// "Needs work" issue status ID.
const PROJECTAPP_SCRAPER_NEEDS_WORK = 13;
const PROJECTAPP_SCRAPER_NEEDS_REVIEW = 8;
const PROJECTAPP_SCRAPER_DUPLICATE = 3;
const PROJECTAPP_SCRAPER_POSTPONED = 4;
const PROJECTAPP_SCRAPER_POSTPONED_INFO = 16;
const PROJECTAPP_SCRAPER_WONTFIX = 5;

// URL to a list of issues that should be updated.
$url = 'https://www.drupal.org/project/issues/coder?version=7.x-1.2';
// The new status the issue should be set to.
$status = PROJECTAPP_SCRAPER_WONTFIX;
$comment = 'Coder 7.x-1.x is frozen now and will not receive any updates. Coder 8.x-2.x can be used to check code for any Drupal version, Coder 8.x-2.x also supports the phpcbf command to automatically fix conding standard errors. Please check if this issue is still relevant and reopen against that version if necessary.';

require 'vendor/autoload.php';
require 'personal_password.php';

use Goutte\Client;

if (empty($argv[1])) {
  print "TFA token parameter missing.\n";
  exit(3);
}
$tfa_token = $argv[1];

$client = get_logged_in_client();
$crawler = $client->request('GET', $url);
$issues = $crawler->filterXPath('//tbody/tr/td[1]/a');

if ($issues->count() == 0) {
  print "No issues found.\n";
  exit(2);
}

$links = $issues->links();
$closed_issues = array();

// Go to each issue.
foreach ($links as $link) {
  print $link->getUri() . "\n";
  projectapp_scraper_post_comment($link->getUri(), $comment, $status);
}


/**
 * Performs a user login and returns the Goutte client object to perform request
 * as authenticated user.
 */
function get_logged_in_client() {
  static $client;
  if (!$client) {
    // Perform a user login.
    global $user, $password, $tfa_token;
    $client = new Client();
    $crawler = $client->request('GET', 'https://www.drupal.org/user');
    $form = $crawler->selectButton('Log in')->form();
    // $user and $password must be set in user_password.php.
    $crawler = $client->submit($form, array('name' => $user, 'pass' => $password));

    $login_errors = $crawler->filter('.messages-error');
    if ($login_errors->count() > 0) {
      print "Login failed.\n";
      exit(1);
    }

    // TFA page.
    $form = $crawler->selectButton('Verify')->form();
    $crawler = $client->submit($form, array('code' => $tfa_token));
    $login_errors = $crawler->filter('.messages-error');
    if ($login_errors->count() > 0) {
      print "TFA failed.\n";
      exit(1);
    }
  }
  return $client;
}

/**
 * Helper function to either output the issue comment on a dry-run or post a new
 * comment to the issue.
 */
function projectapp_scraper_post_comment($issue_uri, $post, $status = NULL, $issue_summary = NULL) {
  global $argv;
  if (!is_array($post)) {
    $post = array($post);
  }

  // Production run: post the comment to the drupal.org issue.
  $client = get_logged_in_client();

  $comment = implode("\n\n", $post);
  $issue_page = $client->request('GET', $issue_uri);
  $comment_form = $issue_page->selectButton('Save')->form();

  $form_values['nodechanges_comment[comment_body][und][0][value]'] = $comment;
  if ($status) {
    $form_values['field_issue_status[und]'] = $status;
  }
  if ($issue_summary) {
    $form_values['body[und][0][value]'] = $issue_summary;
  }
  else {
    // We need to HTML entity decode the issue summary here, otherwise we
    // would post back a double-encoded version, which would result in issue
    // summary changes that we don't want to touch.
    $form_values['body[und][0][value]'] = html_entity_decode($comment_form->get('body[und][0][value]')->getValue(), ENT_QUOTES, 'UTF-8');
  }

  do {
    // Repeat the form submission if there is a 502 gateway error.
    $client->submit($comment_form, $form_values);
    $response = $client->getResponse();
  } while ($response->getStatus() != 200);
}
