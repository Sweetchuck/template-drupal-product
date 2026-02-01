#!/usr/bin/env bash

: "${sghHookName:?'argument is required'}"
: "${sghHasInput:?'argument is required'}"

echo 1>&2 "BEGIN Git hook: ${sghHookName}"

function sghExit ()
{
	if [[ "${2}" != '' ]]; then
		echo 1>&2 "${2}"
	fi

	echo 1>&2 "END   Git hook: ${sghHookName}"

	exit "${1}"
}

# @todo Docker container detection.
# @todo Better detection for executables: php, composer.phar and drush.
drush="$(composer config 'bin-dir')/drush"

test -x "${drush}" \
|| sghExit 0 'drush executable not found'

"${drush}" help "marvin:git-hook:${sghHookName}" 1> /dev/null 2>&1 \
|| sghExit 0 "Command does not exist: drush marvin:git-hook:${sghHookName}"

if [ "${sghHasInput}" = 'true' ]; then
	"${drush}" "marvin:git-hook:${sghHookName}" "${@}" <&0 || sghExit $?
else
	"${drush}" "marvin:git-hook:${sghHookName}" "${@}"     || sghExit $?
fi

sghExit 0
