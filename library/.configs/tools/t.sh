ver=$1
php phpdoc.php
cp /var/folders/_5/vqr623sx6nz3nt1y8vwfbhym0000gn/T/Tii-$ver.phpdoc /Users/alacner/git/tii3/build/Tii-$ver.phpdoc
php packer.php
cp /var/folders/_5/vqr623sx6nz3nt1y8vwfbhym0000gn/T/Tii-$ver.php /Users/alacner/git/tii3/build/Tii-$ver.php
php sync.php /var/folders/_5/vqr623sx6nz3nt1y8vwfbhym0000gn/T/Tii-$ver.php
