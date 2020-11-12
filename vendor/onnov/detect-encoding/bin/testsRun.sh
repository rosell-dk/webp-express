#!/usr/bin/env bash

vendor/bin/phpcs
vendor/bin/phpmd src text cleancode,codesize,controversial,design,naming,unusedcode
vendor/bin/phpstan analyse src/ -c phpstan.neon --level=7 --no-progress -vvv --memory-limit=-1
vendor/bin/phpbench run benchmarks --report=default
vendor/bin/infection --min-msi=50 --min-covered-msi=70 --log-verbosity=all
vendor/bin/phpunit --coverage-text --coverage-html ./build/coverage/html --coverage-clover ./build/coverage/clover.xml
