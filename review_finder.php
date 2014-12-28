<?php

/**
 * @file
 * Returns some old "needs review" project applications that might had a review
 * bonus.
 */

require 'vendor/autoload.php';

use Goutte\Client;

$client = new Client();
// Get all "needs review" and RTBC issues, oldest first.
$search_page = $client->request('GET', 'https://www.drupal.org/project/issues/search/projectapplications?status[0]=8&status[1]=14&order=created&sort=asc');

while ($search_page) {
  $issues = $search_page->filterXPath('//tbody/tr/td[1]/a');

  if ($issues->count() == 0) {
    print "No issues found.\n";
    exit(2);
  }

  $links = $issues->links();

  // Go to each issue.
  foreach ($links as $link) {
    $issue_page = $client->click($link);
    $issue_summary = $issue_page->filter('.field-name-body');
    $review_links = $issue_summary->filterXPath("//@href[contains(., 'drupal.org/node/') or contains(., 'drupal.org/comment/')]");
    if ($review_links->count() > 1) {
      print $link->getNode()->nodeValue . ' ' . $link->getUri() . "\n";
    }
  }

  // Go to the next page.
  $link = $search_page->selectLink('next ›')->link();
  $search_page = $client->click($link);
}
