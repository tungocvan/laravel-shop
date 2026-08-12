#!/bin/bash
set -u

printf '%s\n' '=== System Demo: Application Info ==='
printf 'UTC time: '
date -u '+%Y-%m-%d %H:%M:%S UTC'
printf 'PHP CLI: '
php -r 'echo PHP_VERSION, PHP_EOL;'
printf 'Application directory: '
basename "$(pwd)"
