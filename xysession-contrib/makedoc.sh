#!/bin/sh
phpdoc -t xysession-docs/ -f session.class.php,sessionalreadyinuse.exception.php,sessionkeyempty.exception.php,sessionnotloadable.exception.php,sessiontimedout.exception.php,$1
