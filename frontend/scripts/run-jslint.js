#!/usr/bin/env node

import { readFileSync, readdirSync, statSync } from 'fs';
import { join, extname } from 'path';
import jslint from '@jslint-org/jslint';

// Configuration
const config = {
  browser: true,
  devel: true,
  es6: true,
  module: true,
  fudge: true,
  indent: 2,
  maxlen: 120,
  predef: [
    'Alpine',
    'htmx',
    'gsap',
    'fetch',
    'URLSearchParams',
    'FormData',
    'localStorage',
    'sessionStorage',
    'setTimeout',
    'clearTimeout',
    'setInterval',
    'clearInterval'
  ]
};

// Get all JS files recursively
function getJSFiles(dir) {
  const files = [];
  const items = readdirSync(dir);
  const skipDirectories = new Set(['node_modules', 'dist', 'build', '.git', '.cache', 'coverage']);
  const jsExtensions = new Set(['.js', '.mjs', '.cjs']);
  
  for (const item of items) {
    const fullPath = join(dir, item);
    
    try {
      const stat = statSync(fullPath);
      
      if (stat.isDirectory()) {
        if (skipDirectories.has(item)) {
          continue;
        }
        files.push(...getJSFiles(fullPath));
      } else {
        const ext = extname(item);
        if (jsExtensions.has(ext)) {
          files.push(fullPath);
        }
      }
    } catch (error) {
      // Skip files/directories that can't be accessed
      continue;
    }
  }
  
  return files;
}

// Run JSLint on files
function runJSLint(files) {
  let totalFiles = 0;
  let totalWarnings = 0;
  let hasErrors = false;
  
  console.log('Running JSLint...\n');
  
  for (const file of files) {
    try {
      const source = readFileSync(file, 'utf8');
      const result = jslint(source, config);
      
      totalFiles++;
      
      if (result.warnings && result.warnings.length > 0) {
        console.log(`\x1b[33m${file}\x1b[0m`);
        for (const warning of result.warnings) {
          totalWarnings++;
          console.log(`  Line ${warning.line}, Column ${warning.column}: ${warning.message}`);
          if (warning.code === 'unexpected_a' || warning.code === 'expected_a_b') {
            hasErrors = true;
          }
        }
        console.log('');
      }
    } catch (error) {
      console.error(`Error processing ${file}: ${error.message}`);
      hasErrors = true;
    }
  }
  
  console.log(`\nJSLint completed:`);
  console.log(`  Files checked: ${totalFiles}`);
  console.log(`  Warnings: ${totalWarnings}`);
  
  if (hasErrors) {
    console.log('\n\x1b[31mJSLint found errors!\x1b[0m');
    process.exit(1);
  } else if (totalWarnings > 0) {
    console.log('\n\x1b[33mJSLint completed with warnings.\x1b[0m');
  } else {
    console.log('\n\x1b[32mJSLint passed!\x1b[0m');
  }
}

// Main execution
const args = process.argv.slice(2);
const dirs = args.length > 0 ? args : ['src'];

let allFiles = [];
for (const dir of dirs) {
  try {
    const stat = statSync(dir);
    if (stat.isDirectory()) {
      allFiles.push(...getJSFiles(dir));
    } else if (extname(dir) === '.js') {
      allFiles.push(dir);
    }
  } catch (error) {
    console.error(`Error accessing ${dir}: ${error.message}`);
    process.exit(1);
  }
}

if (allFiles.length === 0) {
  console.log('No JavaScript files found.');
  process.exit(0);
}

runJSLint(allFiles);