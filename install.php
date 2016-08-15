<?php
file_put_contents('composer-setup.php', file_get_contents('https://getcomposer.org/installer'));
if (hash('SHA384', file_get_contents('composer-setup.php')) === 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') {
    echo 'Installer verified';
} else {
    echo 'Installer corrupt';
    unlink('composer-setup.php');
}
exec('php composer-setup.php');
unlink('composer-setup.php');
exec('php composer.phar install');