## php-pack
This script takes a PHP web application and attempts to compress it into a single file by creating a virtual filesystem.

# Example

    % php pack-dir.php example/example.php > test.php
    [+] Packing ./image.inc.php
    [+] Packing ./example.png
    [+] Packing ./example.php
    [+] Generating example.php

Visit `test.php` in a web browser and it should display an image.

