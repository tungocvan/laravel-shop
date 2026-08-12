#!/bin/bash
set -u

printf '%s\n' '=== System Demo: Filesystem Usage ==='
df -h "$(pwd)"
