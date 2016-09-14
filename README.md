User database
===============
Use the code in the 'databases.sql' and execute it to create the database required to store user information (flags).

Login / password
================

Fill the file 'connection.php' with the adequate logins and passwords:
* For the wiki, $loginName and $loginPass the username and password of a bot account
* For the database, DBNAME, DBUSER and DBPASSWORD the database name, user name and password for access to the database (SELECT)

Images
======

The images use in this project are available in the LiquipediaImageResources repository: https://github.com/chapatiyaq/LiquipediaImageResources
Change the name of the folder to 'images' and put it at the same level as the 'editcount' folder.

Example of folder structure:

```
/liquipedia
  /editcount
    /index.php
    /table.php
    /flag-icon.min.css
    /style.css
    ...
  /images
    /logos
      /starcraft.png
      ...
    /misc
      /icon\_22\_computer.png
    /svg-flags
      /ad.svg
      ...
```

All game logos belong to their respective companies and owners.

The SVG flags were taken from https://github.com/sqlitebrowser/iso-country-flags-svg-collection
Some have been modified (color, shape) or added (Cascadia). Flags are in the Public Domain.

User agent
==========
You can change the user agent in 'table.php' (search for "CURLOPT_USERAGENT"), especially to change the contact e-mail to your e-mail address.
