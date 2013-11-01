Drupal Project Applications Scraper (pascraper)
===============================================

A [Goutte](https://github.com/fabpot/Goutte) scraping script to automatically manage the [drupal.org project applications](http://drupal.org/project/projectapplications) issue queue.

Current features:
* Set a "needs review" application to "needs work" if a link to the sandbox or the Git repository could not be extracted from the issue summary.
* Post a hint to the review bonus program to "needs review" applications that did not receive such a hint yet.
* Set a "needs review" application to "needs work" if there has not been any automated review link to http://pareview.sh posted and the result of pareview.sh exceeds a threshold of 30 lines.
* Check if an applicant has multiple applications and close all but one as duplicates.
* Close old "needs work" project applications after 10 weeks if the applicant did not respond.


