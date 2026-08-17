#!/bin/bash
# Anti-hex check for Linux/CI
echo "Running anti-hex check..."

if grep -rnEi "#[0-9a-f]{3,6}\b" resources/views resources/css | grep -v "tokens.css"; then
    echo "ERROR: Hexadecimals found outside tokens.css!"
    exit 1
fi

echo "Anti-hex audit passed."
exit 0
