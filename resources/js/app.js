import "./bootstrap";
import Alpine from "alpinejs";

window.Alpine = Alpine;
window.chartInstances = window.chartInstances || [];

if (!window.__shiroAlpineStarted) {
    Alpine.start();
    window.__shiroAlpineStarted = true;
}
