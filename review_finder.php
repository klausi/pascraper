<?php

/**
 * @file
 * Returns some old "needs review" project applications that might had a review
 * bonus.
 */

require 'vendor/autoload.php';
require 'user_password.php';

use Goutte\Client;

$client = new Client();
// Get all "needs review" issues.
$crawler = $client->request('GET', 'http://drupal.org/project/issues/projectapplications?status=8');
// Oldest first.
$link = $crawler->selectLink('Last updated')->link();
$search_page = $client->click($link);

$issues = $search_page->filterXPath('//tbody/tr/td[1]/a');

if ($issues->count() == 0) {
  print "No issues found.\n";
  exit(2);
}

$links = $issues->links();

// Go to each issue.
foreach ($links as $link) {
  $issue_page = $client->click($link);
  $issue_summary = $issue_page->filter('.node-content');
  $review_links = $issue_summary->filterXPath("//@href[contains(., 'drupal.org/node/')]");
  if ($review_links->count() > 2) {
    print $link->getNode()->nodeValue . ' ' . $link->getUri() . "\n";
  }
}
