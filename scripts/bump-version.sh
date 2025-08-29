#!/bin/bash
set -e

VERSION_FILE="VERSION"

if [ ! -f "$VERSION_FILE" ]; then
    echo "Error: VERSION file not found"
    exit 1
fi

current_version=$(cat "$VERSION_FILE")

# Parse semantic version
if [[ $current_version =~ ^([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]; then
    major=${BASH_REMATCH[1]}
    minor=${BASH_REMATCH[2]}
    patch=${BASH_REMATCH[3]}
else
    echo "Error: Invalid semantic version format in VERSION file: $current_version"
    exit 1
fi

# Bump patch version
new_patch=$((patch + 1))
new_version="${major}.${minor}.${new_patch}"

echo "$new_version" > "$VERSION_FILE"

# Stage the updated VERSION file
git add "$VERSION_FILE"

echo "Version bumped from $current_version to $new_version"