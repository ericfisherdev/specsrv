#!/bin/bash

# Custom webpack watch script with quieter output
echo "🔥 Starting webpack in watch mode (quiet)..."
echo "📁 Watching assets/ directory for changes..."
echo "⏱️  Initial compilation..."

# Run webpack watch and filter out verbose output
npm run watch 2>&1 | stdbuf -oL grep -E "(ERROR|WARNING|ERROR in|WARNING in|webpack compiled|DONE|FAILED|files written to public/build|WAIT.*Compiling)" | while read line; do
    if echo "$line" | grep -qE "(ERROR|WARNING|ERROR in|WARNING in|webpack compiled|DONE|FAILED)"; then
        echo "$line"
    elif echo "$line" | grep -qE "files written to public/build"; then
        # Show a simplified version of the build success message
        echo "✅ Assets compiled successfully"
        # Add a small delay to prevent rapid recompilation
        sleep 0.5
    elif echo "$line" | grep -qE "WAIT.*Compiling"; then
        echo "🔄 Recompiling assets..."
    fi
done