@echo off
set v="%1"
if "%1"=="" ( 
    echo "Usage: check_php_compatibility.bat <php version>"
) else (
    phpcs -p . --standard=PHPCompatibility --runtime-set testVersion %v%
)
