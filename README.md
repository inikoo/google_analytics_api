# google_api
Google Analytics api (PHP)


Google Search Console api (PHP)


To run Google Analytics:

pattern:  php ga.api.php --display-- --view-- --date-- --metrics--

eg.

php report.php l 000000000 today pageValue

php report.php l 000000000 yesterday:today pageValue

php report.php l 000000000 2019-01-01 pageValue

php report.php l 000000000 2019-01-01:2019-01-02 pageValue


--display--

l for list

t for table

--view--

view id from google

--date--

date can be range (separate by ":" without space) or one date

date should be in following format (YYYY-MM-DD) or date can be string (eg. today, yesterday)

--metrics--

at least one metrics is required (eg. pageviews, pageValue)

list of metrics can be found in following link:

https://developers.google.com/analytics/devguides/reporting/core/dimsmets


To run Google Search Console:

pattern:  php sc.api.php

*note:will* *update* *more* *features* _soon_