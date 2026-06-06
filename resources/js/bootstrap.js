import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Echo dan Pusher dihapus dari pemuatan global (bootstrap.js) untuk performa yang lebih ringan.
// Keduanya kini di-lazy load HANYA di halaman Dashboard (melalui helper di app.js)
// untuk mencegah error 'WebSocket connection failed' di seluruh halaman.
