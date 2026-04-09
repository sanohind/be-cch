<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Email Notifikasi CCH – SSH Sanoh Indonesia</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: #eaeef2;
  padding: 40px 16px;
  color: #1a2332;
}

/* ── Outer wrapper ── */
.email-wrapper {
  max-width: 600px;
  margin: 0 auto;
}

/* ── Email document ── */
.email-doc {
  background: #ffffff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,0.10);
}

/* ── Gold top bar ── */
.top-bar {
  height: 4px;
  background: linear-gradient(90deg, #c9a030 0%, #e8c060 100%);
}

/* ── Header / Letterhead ── */
.header {
  background: #0f1e35;
  padding: 20px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.header-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-logo {
  width: 44px;
  height: 44px;
  background: #1e3556;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 700;
  color: #c9a030;
  letter-spacing: 0.04em;
  border: 1px solid rgba(201,160,48,0.3);
}

.header-text {}
.header-company {
  font-size: 14px;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 0.01em;
  line-height: 1.2;
}
.header-system {
  font-size: 10px;
  font-weight: 500;
  color: rgba(255,255,255,0.45);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-top: 2px;
}

.header-meta {
  text-align: right;
  flex-shrink: 0;
}
.header-notif-id {
  font-size: 10px;
  font-family: 'IBM Plex Mono', monospace;
  color: rgba(255,255,255,0.4);
  margin-bottom: 3px;
}
.header-date {
  font-size: 11px;
  font-family: 'IBM Plex Mono', monospace;
  color: rgba(255,255,255,0.6);
  margin-bottom: 3px;
}
.header-priority {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  color: rgba(255,255,255,0.45);
  font-family: 'IBM Plex Mono', monospace;
}

/* ── Status badge row ── */
.status-bar {
  background: #f0f4f8;
  border-bottom: 1px solid #e2e8f0;
  padding: 10px 32px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  white-space: nowrap;
  flex-shrink: 0;
}

.badge-comment {
  background: #0f1e35;
  color: #c9a030;
  border: 1px solid rgba(201,160,48,0.25);
}

.status-desc {
  font-size: 12.5px;
  color: #6b7c93;
}

/* ── Body ── */
.body {
  padding: 32px 32px 28px;
}

.salutation {
  font-size: 13px;
  color: #6b7c93;
  margin-bottom: 16px;
}

.headline {
  font-size: 22px;
  font-weight: 700;
  color: #0f1e35;
  line-height: 1.3;
  letter-spacing: -0.02em;
  margin-bottom: 14px;
}

.body-para {
  font-size: 13.5px;
  line-height: 1.7;
  color: #4a5a6e;
  margin-bottom: 24px;
}

.body-para strong {
  color: #1a2332;
  font-weight: 600;
}

/* ── Detail table ── */
.detail-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 24px;
  font-size: 13px;
}

.detail-table thead tr {
  background: #0f1e35;
}

.detail-table thead th {
  padding: 10px 16px;
  text-align: left;
  font-size: 10.5px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.6);
}

.detail-table tbody tr {
  border-bottom: 1px solid #f0f4f8;
}

.detail-table tbody tr:last-child {
  border-bottom: none;
}

.detail-table tbody tr:nth-child(even) {
  background: #f8fafc;
}

.detail-table td {
  padding: 11px 16px;
  vertical-align: top;
}

.td-label {
  width: 36%;
  font-size: 12px;
  font-weight: 600;
  color: #6b7c93;
}

.td-value {
  font-size: 13px;
  font-weight: 500;
  color: #1a2332;
  font-family: 'IBM Plex Mono', monospace;
}

/* ── Comment card ── */
.comment-card {
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 24px;
}

.comment-header {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 10px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.comment-author {
  display: flex;
  align-items: center;
  gap: 8px;
}

.comment-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #0f1e35;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  color: #c9a030;
  flex-shrink: 0;
}

.comment-name {
  font-size: 13px;
  font-weight: 600;
  color: #1a2332;
}

.comment-time {
  font-size: 11px;
  color: #9aaabb;
  font-family: 'IBM Plex Mono', monospace;
  white-space: nowrap;
}

.comment-body {
  padding: 14px 16px;
  background: #ffffff;
}

.comment-label {
  font-size: 11px;
  font-weight: 600;
  color: #9aaabb;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: 6px;
}

.comment-text {
  font-size: 14px;
  font-weight: 600;
  color: #1a2332;
  line-height: 1.5;
  margin-bottom: 4px;
}

.comment-subtext {
  font-size: 13px;
  color: #4a5a6e;
  line-height: 1.6;
}

/* ── CTA ── */
.cta-section {
  margin-bottom: 28px;
}

.btn-primary {
  display: inline-block;
  padding: 12px 28px;
  background: #0f1e35;
  color: #ffffff;
  border-radius: 5px;
  font-size: 13.5px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  letter-spacing: 0.02em;
  border: none;
  transition: background 0.15s;
}

.btn-primary:hover {
  background: #1a3356;
}

/* ── Footer note ── */
.footer-note {
  border-top: 1px solid #f0f4f8;
  padding-top: 20px;
  font-size: 12px;
  color: #9aaabb;
  line-height: 1.7;
}

.footer-note strong {
  color: #6b7c93;
}

/* ── Bottom footer ── */
.footer {
  background: #0f1e35;
  padding: 18px 32px;
}

.footer-links {
  display: flex;
  gap: 20px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.footer-link {
  font-size: 11px;
  color: rgba(255,255,255,0.35);
  text-decoration: none;
  cursor: pointer;
  letter-spacing: 0.03em;
  transition: color 0.15s;
}

.footer-link:hover {
  color: rgba(255,255,255,0.65);
}

.footer-copy {
  font-size: 10.5px;
  color: rgba(255,255,255,0.25);
  line-height: 1.6;
}

.footer-brand {
  color: #c9a030;
  font-weight: 600;
}
</style>
</head>
<body>

<div class="email-wrapper">
  <div class="email-doc">

    <!-- Gold accent bar -->
    <div class="top-bar"></div>

    <!-- Header -->
    <div class="header">
      <div class="header-brand">
        <div class="header-logo">SSH</div>
        <div class="header-text">
          <div class="header-company">SSH Sanoh Indonesia</div>
          <div class="header-system">Sistem Notifikasi CCH</div>
        </div>
      </div>
      <div class="header-meta">
        <div class="header-notif-id">Notif. CCH-COMMENT</div>
        <div class="header-date">18 Mar 2026</div>
        <div class="header-priority">Prioritas: Normal</div>
      </div>
    </div>

    <!-- Status bar -->
    <div class="status-bar">
      <span class="status-badge badge-comment">💬 Komentar Baru</span>
      <span class="status-desc">Komentar baru telah ditambahkan pada CCH</span>
    </div>

    <!-- Body -->
    <div class="body">

      <div class="salutation">Kepada Yth. <strong>Tim terkait</strong>,</div>

      <div class="headline">Komentar Baru<br>pada CCH Anda</div>

      <div class="body-para">
        <strong>zaki</strong> telah menambahkan komentar pada blok <strong>Closing (Block 10)</strong> di CCH <strong>CCH-2026-00024</strong>. Berikut informasi selengkapnya.
      </div>

      <!-- Detail table -->
      <table class="detail-table">
        <thead>
          <tr>
            <th>Keterangan</th>
            <th>Detail</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="td-label">Nomor CCH</td>
            <td class="td-value">CCH-2026-00024</td>
          </tr>
          <tr>
            <td class="td-label">Subject CCH</td>
            <td class="td-value">SUBJECT QC 3</td>
          </tr>
          <tr>
            <td class="td-label">Blok</td>
            <td class="td-value">Closing (Block 10)</td>
          </tr>
        </tbody>
      </table>

      <!-- Comment card -->
      <div class="comment-card">
        <div class="comment-header">
          <div class="comment-author">
            <div class="comment-avatar">Z</div>
            <span class="comment-name">zaki</span>
          </div>
          <span class="comment-time">18 Mar 2026, 03:00 WIB</span>
        </div>
        <div class="comment-body">
          <div class="comment-label">Isi Komentar</div>
          <div class="comment-text">Mantap</div>
          <div class="comment-subtext">mantap</div>
        </div>
      </div>

      <!-- CTA -->
      <!-- <div class="cta-section">
        <a class="btn-primary">Lihat &amp; Balas Komentar</a>
      </div> -->

      <!-- Footer note -->
      <div class="footer-note">
        Anda menerima email ini karena terdaftar sebagai pihak terkait pada CCH <strong>CCH-2026-00024</strong>.<br>
        Jangan membalas email ini secara langsung.
      </div>

    </div>

    <!-- Bottom footer -->
    <div class="footer">
      <div class="footer-links">
        <a class="footer-link">Pusat Bantuan</a>
        <a class="footer-link">Kebijakan Privasi</a>
        <a class="footer-link">Berhenti Berlangganan</a>
      </div>
      <div class="footer-copy">
        <span class="footer-brand">SSH Sanoh Indonesia</span> · Sistem Notifikasi CCH<br>
        © 2026 SSH Sanoh Indonesia. Seluruh hak dilindungi.
      </div>
    </div>

  </div>
</div>

</body>
</html>