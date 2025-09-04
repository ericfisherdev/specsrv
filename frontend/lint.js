#!/usr/bin/env node

/*jslint node */

import jslint from "./jslint.mjs";
import fs from "fs";
import { glob } from "glob";

async function lintFiles() {
  const jsFiles = await glob("src/**/*.js", { ignore: ["node_modules/**", "dist/**"] });
  let totalWarnings = 0;
  
  for (const file of jsFiles) {
    console.log(`\n=== Linting ${file} ===`);
    
    try {
      const source = fs.readFileSync(file, "utf8");
      const options = { browser: true, devel: true, maxlen: 120 };
      const result = jslint.jslint(source, options);
      
      if (result.warnings.length > 0) {
        console.log(`Found ${result.warnings.length} warnings:`);
        result.warnings.slice(0, 5).forEach(({ formatted_message }, index) => {
          console.error(`  ${index + 1}. ${formatted_message}`);
        });
        if (result.warnings.length > 5) {
          console.log(`  ... and ${result.warnings.length - 5} more warnings`);
        }
      } else {
        console.log("✅ No warnings!");
      }
      
      totalWarnings += result.warnings.length;
    } catch (error) {
      console.error(`Error linting ${file}:`, error.message);
      process.exitCode = 1;
    }
  }
  
  console.log(`\n=== SUMMARY ===`);
  console.log(`Total files checked: ${jsFiles.length}`);
  console.log(`Total warnings: ${totalWarnings}`);
  console.log(`Average warnings per file: ${jsFiles.length > 0 ? (totalWarnings / jsFiles.length).toFixed(1) : '0.0'}`);
  
  // Set exit code if there are warnings (for CI)
  if (totalWarnings > 0) {
    process.exitCode = 1;
  }
}

lintFiles().catch((error) => {
  console.error('Unexpected error during linting:', error);
  process.exitCode = 1;
});