import "./bootstrap";
import * as Turbo from "@hotwired/turbo";
import Alpine from "alpinejs";

window.Alpine = Alpine;
window.Turbo = Turbo;
window.chartInstances = window.chartInstances || [];

// ─── Konfigurasi Turbo Drive ───
// progressBarDelay: 300ms → loading bar TIDAK muncul untuk halaman cepat (<300ms),
// sehingga perpindahan halaman terasa instan. Loading bar hanya muncul
// jika server membutuhkan waktu lebih dari 300ms untuk merespons.
Turbo.config.drive.progressBarDelay = 300;

// ─── Mencegah bug Infinite Reload Loop saat Vite HMR (development only) ───
if (import.meta.hot) {
    import.meta.hot.on('vite:beforeFullReload', () => {
        Turbo.session.drive = false;
    });
}

// ─── Bersihkan chart instances sebelum halaman baru dirender ───
// Mencegah memory leak dan error ApexCharts/Chart.js saat navigasi antar halaman.
document.addEventListener("turbo:before-render", (event) => {
    if (Array.isArray(window.chartInstances)) {
        window.chartInstances.forEach(
            (c) => c && typeof c.destroy === "function" && c.destroy(),
        );
        window.chartInstances = [];
    }

    // ─── Animasi transisi halaman (fade smooth) ───
    // Saat halaman baru akan dirender, beri opacity 0 dulu,
    // lalu fade-in dengan halus menggunakan CSS transition.
    // Ini menghilangkan efek "blink/kedip" saat pindah halaman.
    const newBody = event.detail.newBody;
    newBody.style.opacity = "0";
});

// Expose dynamic loaders to window so inline Blade scripts can lazy load them 
// through Vite without bare specifier errors in the browser.

// ─── Fade-in halaman baru setelah Turbo selesai merender ───
// Setelah halaman baru masuk ke DOM (opacity masih 0 dari before-render),
// kita langsung jalankan transisi CSS fade-in agar halaman muncul dengan halus.
document.addEventListener("turbo:render", () => {
    document.body.style.transition = "opacity 0.15s ease-in";
    document.body.style.opacity = "1";
});

// ─── Reset opacity saat pertama kali load (bukan navigasi Turbo) ───
// Memastikan halaman pertama langsung terlihat tanpa efek fade.
document.addEventListener("turbo:load", () => {
    document.body.style.opacity = "1";
});
window.loadApexCharts = async () => {
    const ApexCharts = await import("apexcharts");
    return ApexCharts.default || ApexCharts;
};

window.loadEcho = async () => {
    const [echoModule, pusherModule] = await Promise.all([
        import("laravel-echo"),
        import("pusher-js")
    ]);
    return { Echo: echoModule.default, Pusher: pusherModule.default };
};

// Panggil Alpine.start() setelah semua helper di-load
Alpine.start();
