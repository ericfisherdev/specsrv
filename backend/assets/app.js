/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Import CSS styles
import './styles/app.css';

// Import HTMX for dynamic HTML interactions
import htmx from 'htmx.org';
window.htmx = htmx;

// Import Alpine.js for reactive components
import Alpine from 'alpinejs';
window.Alpine = Alpine;

// Import GSAP for animations
import { gsap } from 'gsap';
import { Draggable } from 'gsap/Draggable';

// Register GSAP plugins
gsap.registerPlugin(Draggable);
window.gsap = gsap;

// Initialize Alpine.js
Alpine.start();

// Kanban board drag and drop functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('App initialized with HTMX, Alpine.js, and GSAP');
});
