// 1. COUNTDOWN TIMER PEMILU SERENTAK (Target: 27 November 2026)
const targetDate = new Date('November 27, 2026 07:00:00').getTime();

function updateCountdown() {
    const now = new Date().getTime();
    const gap = targetDate - now;

    const second = 1000;
    const minute = second * 60;
    const hour = minute * 60;
    const day = hour * 24;

    const d = Math.floor(gap / day);
    const h = Math.floor((gap % day) / hour);
    const m = Math.floor((gap % hour) / minute);
    const s = Math.floor((gap % minute) / second);

    const daysEl = document.getElementById('days');
    if (daysEl) {
        daysEl.innerText = d > 0 ? d : 0;
        document.getElementById('hours').innerText = h >= 10 ? h : '0' + h;
        document.getElementById('minutes').innerText = m >= 10 ? m : '0' + m;
        document.getElementById('seconds').innerText = s >= 10 ? s : '0' + s;
        document.getElementById('days-tag').innerText = 'D-' + (d > 0 ? d : 0);
    }
}
setInterval(updateCountdown, 1000);
updateCountdown();

// 2. INISIALISASI PETA LEAFLET
document.addEventListener("DOMContentLoaded", function() {
    const mapContainer = document.getElementById('map');
    
    if (mapContainer) {
        const map = L.map('map', { scrollWheelZoom: false }).setView([-6.92, 110.20], 10.3);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Pengaturan Warna berdasarkan Status
        const warnaStatus = {
            'masuk': '#EF4444',         // Merah
            'diklarifikasi': '#F59E0B',  // Kuning/Oranye
            'diteruskan': '#38BDF8',     // Biru
            'selesai': '#10B981'         // Hijau
        };
        
        // Looping data `titikLaporan` yang dilempar dari tag script di index.php
        if (typeof titikLaporan !== 'undefined') {
            titikLaporan.forEach(t => {
                if (t.latitude && t.longitude) {
                    let warna = warnaStatus[t.status] || '#0284C7';
                    L.circleMarker([parseFloat(t.latitude), parseFloat(t.longitude)], {
                        radius: 9, 
                        color: '#fff', 
                        fillColor: warna, 
                        fillOpacity: 0.9, 
                        weight: 2
                    }).addTo(map).bindPopup(`
                        <strong>${t.nama_kategori || 'Pelanggaran'}</strong><br>
                        <span style="font-size:12px; color:#64748B;">Kec. ${t.kecamatan || '-'} / Desa ${t.desa || '-'}</span><br>
                        <span style="font-size:11px; font-weight:700; color:${warna}; text-transform:uppercase;">Status: ${t.status}</span>
                    `);
                }
            });
        }
    }
});

// 3. FUNGSI LACAK LAPORAN (AJAX API Fetch)
function lacakLaporan() {
    const code = document.getElementById('track-code').value.trim();
    const resDiv = document.getElementById('track-result');
    
    if(!code) {
        resDiv.innerHTML = '<span style="color:#EF4444; margin-top:8px; display:block;">Masukkan kode pelacakan terlebih dahulu.</span>';
        return;
    }
    
    resDiv.innerHTML = '<span style="color:#F59E0B; margin-top:8px; display:block;">Mencari status laporan ' + code + '...</span>';

    // Request asinkron ke API di server (Pastikan file proses/lacak_laporan.php tersedia)
    fetch('proses/lacak_laporan.php?code=' + encodeURIComponent(code))
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                resDiv.innerHTML = `
                    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 10px 14px; border-radius: 8px; margin-top: 12px;">
                        <strong>✓ Laporan Ditemukan!</strong><br>
                        Kecamatan: ${data.kecamatan}<br>
                        Status: <span style="text-transform: uppercase; font-weight: 800;">${data.kategori}</span>
                    </div>`;
            } else {
                resDiv.innerHTML = `<span style="color:#EF4444; margin-top:8px; display:block;">${data.pesan}</span>`;
            }
        })
        .catch(error => {
            resDiv.innerHTML = '<span style="color:#EF4444; margin-top:8px; display:block;">Terjadi kesalahan koneksi sistem saat melacak.</span>';
        });
}