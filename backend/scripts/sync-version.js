#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Read VERSION file from project root
const versionFile = path.join(__dirname, '../../VERSION');
const packageFile = path.join(__dirname, '../package.json');

try {
    // Read version from VERSION file
    const version = fs.readFileSync(versionFile, 'utf8').trim();
    
    // Read package.json
    const packageData = JSON.parse(fs.readFileSync(packageFile, 'utf8'));
    
    // Update version
    packageData.version = version;
    
    // Write back to package.json with proper formatting
    fs.writeFileSync(packageFile, JSON.stringify(packageData, null, 4) + '\n');
    
    console.log(`✅ Updated package.json version to ${version}`);
} catch (error) {
    console.error('❌ Error syncing version:', error.message);
    process.exit(1);
}