#!/usr/bin/env bash
#
# This script injects a file into the current Apache2 environment temporarily
# in order to check its syntax.
#
# USAGE:
#
#     validate-htaccess <file>
#
# EXAMPLE:
#
#     validate-htaccess /path/to/some/config/file.conf
#     validate-htaccess /path/to/.htaccess
#
# EXIT CODES:
#
# 0 - The syntax is valid.
# 1 - The syntax is invalid, see STDERR for details.
# 2 - The Apache binary could not be found.
# 3 - The given file does not exist.
# 4 - The current Apache configuration (without including the file being tested)
#     appears to be invalid, so we cannot safely proceed.
#
# Author: Liquid Web
# License: MIT
# Source: https://github.com/liquidweb/htaccess-validator
# Modified by: Ytalo Colucci (drupaldemente)

declare -a variants

file=${1:?No configuration file specified.}

# First, figure out which Apache variant we're working with.
variants=( httpd apachectl apache2ctl )

for variant in "${variants[@]}"; do
    if command -v "$variant" &> /dev/null; then
        apache_binary="$variant"
        break
    fi
done

# Ensure that Apache is available.
if [ -z "$apache_binary" ]; then
    echo 'fail, unable to find the Apache binary, is Apache2 installed?' 1>&2
    exit 2
fi

# Verify the given file actually exists.
if [ ! -f "$file" ]; then
    echo "fail, file ${file} does not exist, aborting." 1>&2
    exit 3
fi

# Verify that the current configuration passes; if it doesn't, it won't
# magically be fixed by throwing more on top of it.
if ! "$apache_binary" -t -C "ServerName serverhtacces.com" 2> /dev/null; then
    echo "fail, the current Apache environment is misconfigured. Please resolve these issues before attempting to validate ${file}." 1>&2
    exit 4
fi

# Get the name of the current configuration file.
current_config=$("$apache_binary" -V | grep -E -o -e 'SERVER_CONFIG_FILE="(.+)"' | sed 's/SERVER_CONFIG_FILE=//; s/"//g')

# Ensure the file can be loaded.
tmpfile=$(mktemp)
cat "$file" > "$tmpfile"

# Now, use Apache to validate itself, injecting $file into the current.
# server environment.
results=$("$apache_binary" -t -C "Include ${current_config}" -f "$tmpfile" -C "ServerName serverhtacces.com" 2>&1)
exit_code=$?

# Remove the temp file.
rm "$tmpfile"

# If the check passed, there's nothing more to do.
if [ $exit_code -eq 0 ]; then
    # echo 'OK!' 1>&2
    exit
fi

# Something went wrong, so we need to parse the results.
tmpfilename=${tmpfile##*/}
echo "$results" | grep "Error\|error\|fail" | sed -E 's/^.+'"$tmpfilename"': //'
exit 1