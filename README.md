## php-pack
This script takes a PHP application and attempts to compress it into a single file by creating a virtual filesystem.

Nowhere near stable, but it seems to work for quite a few things so far.

# Example

    % php pack-dir.php example/example.php > test.php
    [+] Packing ./image.inc.php
    [+] Packing ./example.png
    [+] Packing ./example.php
    [+] Generating example.php

Visit `test.php` in a web browser and it should display an image.

