OphLeIntravitrealinjection Module
=================================

This legacy module is designed to allow a simple import of previous injection data from legacy systems. It works in conjunction with OphTrIntravitrealInjection, which will pull in the legacy information through the UI.

The first release version of this module is 1.4

Dependencies
------------

1. Requires the following modules:
  1. OphTrIntravitrealinjection

Configuration
-------------

See config/common.php for details of configuration variables

Initialisation
--------------

./yiic importleintravitrealinjection

This will perform an import of data from protected/data/import/legacyinjections/

It requires the following files:

* legacyinjections.cpxmap 
* legacyinjections_episode.csv
* legacyinjections_event.csv
* legacyinjections_et_ophleinjection_injection.csv

This extends the relatedimportcomplex command, so check the help for details of how these files are related.

