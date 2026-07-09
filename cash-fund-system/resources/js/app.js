require('./bootstrap');

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Alpine.start() moved to layout after @stack('scripts')
// so functions like orderForm() are defined before Alpine scans the DOM
