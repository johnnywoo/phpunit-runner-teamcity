phpunit-runner-teamcity
=======================

This is a PHPUnit runner for TeamCity. It makes use of TeamCity service messages to report unit test results directly to TeamCity.

Advantages over [official meta-runners](https://github.com/JetBrains/meta-runner-power-pack/tree/master/php):

 1. It's a shell script, which you can easily use in your build scripts, unlike a meta-runner;
 2. It reports test results live, as opposed to waiting for the whole suite to finish.

### Usage

You need to have `php` and `phpunit` in your PATH.

    cd /path/with/project
    phpunit-tc

All `phpunit` command line arguments are supported, except `--configuration` (`-c`), which will disable TeamCity output.

You can symlink `phpunit-tc` into your PATH, for example `sudo ln -s .../phpunit-tc /usr/bin/phpunit-tc`.
Do not make a hard link!
