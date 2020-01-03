#!/bin/bash

echo "<INFO> Installing vendor libraries"
php composer.phar install --no-interaction --no-dev

echo "<INFO> Copying vendor libraries to $LBPDATA/$PDIR/$PSHNAME/vendor"
cp -r ./data/vendor $LBPDATA/$PDIR/$PSHNAME/vendor

# Exit with Status 0
exit 0