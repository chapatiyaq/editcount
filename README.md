Login / password
================

Fill the file 'connection.php' with the adequate logins and passwords:
* For the wiki, $loginName and $loginPass the username and password of a bot account
* For the database, DBNAME, DBUSER and DBPASSWORD the database name, user name and password for access to the database (SELECT)

Images
======

Place game logos in a folder named 'logos', and SVG flags in a folder named 'svg-flags', with both folders in a folder named 'images' at the same level as the 'editcount' folder.
The images are available in the LiquipediaImageResources repository: https://github.com/chapatiyaq/LiquipediaImageResources

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
    /svg-flags
      /ad.svg
      ...
```

All game logos belong to their respective companies and owners.

The SVG flags were taken from https://github.com/sqlitebrowser/iso-country-flags-svg-collection
Some have been modified (color, shape) or added (Cascadia). Flags are in the Public Domain.