const fs = require('fs');
const path = require('path');

const dir = 'd:/Telkom University/- Matkul sem 5 -/Magang/CCH/be-cch/resources/views/emails/cch';

const headStr = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Email Notifikasi CCH – SSH Sanoh Indonesia</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Plus Jakarta Sans', Arial, sans-serif;
  background-color: #eaeef2;
  padding: 40px 16px;
  color: #1a2332;
  margin: 0;
}

table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
td { vertical-align: top; }

/* ── Outer wrapper ── */
.email-wrapper {
  max-width: 600px;
  margin: 0 auto;
}

/* ── Email document ── */
.email-doc {
  background-color: #ffffff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,0.10);
}

/* ── Gold top bar ── */
.top-bar {
  height: 4px;
  background-color: #e8c060;
  background: linear-gradient(90deg, #c9a030 0%, #e8c060 100%);
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
  background-color: #0f1e35;
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
  background-color: #f8fafc;
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

/* ── CTA ── */
.cta-section {
  margin-bottom: 28px;
}

.btn-primary {
  display: inline-block;
  padding: 12px 28px;
  background-color: #0f1e35;
  color: #ffffff !important;
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
  background-color: #1a3356;
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
</style>
</head>`;

const footerStr = `    <!-- Bottom footer -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #0f1e35;">
      <tr>
        <td style="padding: 18px 32px;">
          <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td style="padding-bottom: 10px;">
                <a href="#" style="font-size: 11px; color: rgba(255,255,255,0.35); text-decoration: none; letter-spacing: 0.03em; margin-right: 20px;">Pusat Bantuan</a>
                <a href="#" style="font-size: 11px; color: rgba(255,255,255,0.35); text-decoration: none; letter-spacing: 0.03em; margin-right: 20px;">Kebijakan Privasi</a>
                <a href="#" style="font-size: 11px; color: rgba(255,255,255,0.35); text-decoration: none; letter-spacing: 0.03em;">Berhenti Berlangganan</a>
              </td>
            </tr>
            <tr>
              <td style="font-size: 10.5px; color: rgba(255,255,255,0.25); line-height: 1.6;">
                <span style="color: #c9a030; font-weight: 600;">SSH Sanoh Indonesia</span> · Sistem Notifikasi CCH<br>
                © {{ now()->year }} SSH Sanoh Indonesia. Seluruh hak dilindungi.
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

  </div>
</div>

</body>
</html>`;

function getWrapper(headerNotifId, badgeIcon, badgeText, badgeDesc, headline, bodyPara, tableRows, ctaText, ctaUrl, extraHtml = '') {
    return `${headStr}
<body style="font-family: 'Plus Jakarta Sans', Arial, sans-serif; background-color: #eaeef2; padding: 40px 16px; color: #1a2332; margin: 0;">

<div class="email-wrapper" style="max-width: 600px; margin: 0 auto;">
  <div class="email-doc" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.10);">

    <!-- Gold accent bar -->
    <div class="top-bar"></div>

    <!-- Header -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #0f1e35;">
      <tr>
        <td style="padding: 20px 32px;" valign="middle">
          
          <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <!-- Brand Left -->
              <td valign="middle" align="left">
                <table border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td valign="middle" style="padding-right: 12px;">
                      <div style="width: 44px; height: 44px; background-color: #1e3556; border-radius: 8px; text-align: center; line-height: 44px; font-size: 11px; font-weight: 700; color: #c9a030; letter-spacing: 0.04em; border: 1px solid rgba(201,160,48,0.3); box-sizing: border-box;">SSH</div>
                    </td>
                    <td valign="middle">
                      <div style="font-size: 14px; font-weight: 700; color: #ffffff; letter-spacing: 0.01em; line-height: 1.2;">SSH Sanoh Indonesia</div>
                      <div style="font-size: 10px; font-weight: 500; color: rgba(255,255,255,0.45); letter-spacing: 0.1em; text-transform: uppercase; margin-top: 2px;">Sistem Notifikasi CCH</div>
                    </td>
                  </tr>
                </table>
              </td>
              
              <!-- Meta Right -->
              <td valign="middle" align="right" style="text-align: right;">
                <div style="font-size: 10px; font-family: 'IBM Plex Mono', monospace; color: rgba(255,255,255,0.4); margin-bottom: 3px;">${headerNotifId}</div>
                <div style="font-size: 11px; font-family: 'IBM Plex Mono', monospace; color: rgba(255,255,255,0.6); margin-bottom: 3px;">{{ now()->format('d M Y') }}</div>
                <div style="font-size: 10px; color: rgba(255,255,255,0.45); font-family: 'IBM Plex Mono', monospace;">Prioritas: Normal</div>
              </td>
            </tr>
          </table>

        </td>
      </tr>
    </table>

    <!-- Status bar -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f0f4f8; border-bottom: 1px solid #e2e8f0;">
      <tr>
        <td style="padding: 10px 32px;" valign="middle">
          <table border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td valign="middle" style="padding-right: 10px;">
                <div style="background-color: #0f1e35; color: #c9a030; border: 1px solid rgba(201,160,48,0.25); padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; display: inline-block; white-space: nowrap;">${badgeIcon} ${badgeText}</div>
              </td>
              <td valign="middle">
                <div style="font-size: 12.5px; color: #6b7c93;">${badgeDesc}</div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <!-- Body -->
    <div class="body">

      <div class="salutation">Kepada Yth. <strong>Tim terkait</strong>,</div>

      <div class="headline">${headline}</div>

      <div class="body-para">
        ${bodyPara}
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
${tableRows}
        </tbody>
      </table>
${extraHtml}
      <!-- CTA -->
      <div class="cta-section">
        <a href="${ctaUrl}" class="btn-primary">${ctaText}</a>
      </div>

      <!-- Footer note -->
      <div class="footer-note">
        Email ini dikirim secara otomatis oleh sistem CCH SSH Sanoh Indonesia.<br>
        Jangan membalas email ini secara langsung.
      </div>

    </div>

${footerStr}`;
}

const templates = {
    'block_submitted.blade.php': getWrapper(
        'Notif. CCH-SUBMIT', '✅', 'Blok Disubmit', 'Blok pada CCH berhasil disubmit',
        'Blok CCH<br>Telah Disubmit',
        '<strong>{{ $blockName }}</strong> dari CCH <strong>{{ $cchNumber }}</strong> telah berhasil disubmit oleh <strong>{{ $submitterName }}</strong>. Berikut informasi selengkapnya.',
        `          <tr>
            <td class="td-label">Nomor CCH</td>
            <td class="td-value">{{ $cchNumber }}</td>
          </tr>
          <tr>
            <td class="td-label">Subject CCH</td>
            <td class="td-value">{{ $cchSubject }}</td>
          </tr>
          <tr>
            <td class="td-label">Rank</td>
            <td class="td-value">{{ $rank }}</td>
          </tr>
          <tr>
            <td class="td-label">Blok</td>
            <td class="td-value">{{ $blockName }}</td>
          </tr>
          <tr>
            <td class="td-label">Disubmit oleh</td>
            <td class="td-value">{{ $submitterName }}</td>
          </tr>`,
        'Lihat Detail CCH', '{{ $cchUrl }}'
    ),

    'closed.blade.php': getWrapper(
        'Notif. CCH-CLOSED', '🔒', 'CCH Di-close', 'CCH telah resmi di-close',
        'CCH Telah<br>Di-close',
        'CCH yang Anda buat telah resmi di-close oleh <strong>{{ $closedByName }}</strong>. Berikut informasi selengkapnya.',
        `          <tr>
            <td class="td-label">Nomor CCH</td>
            <td class="td-value">{{ $cchNumber }}</td>
          </tr>
          <tr>
            <td class="td-label">Subject CCH</td>
            <td class="td-value">{{ $cchSubject }}</td>
          </tr>
          <tr>
            <td class="td-label">Di-close oleh</td>
            <td class="td-value">{{ $closedByName }}</td>
          </tr>`,
        'Lihat Detail CCH', '{{ $cchUrl }}'
    ),

    'created.blade.php': getWrapper(
        'Notif. CCH-CREATED', '🆕', 'CCH Baru', 'CCH baru telah dibuat',
        'CCH Baru<br>Telah Dibuat',
        'CCH baru telah dibuat oleh <strong>{{ $creatorName }}</strong>. Berikut informasi selengkapnya.',
        `          <tr>
            <td class="td-label">Nomor CCH</td>
            <td class="td-value">{{ $cchNumber }}</td>
          </tr>
          <tr>
            <td class="td-label">Subject CCH</td>
            <td class="td-value">{{ $cchSubject }}</td>
          </tr>
          <tr>
            <td class="td-label">Rank</td>
            <td class="td-value">{{ $rank }}</td>
          </tr>
          <tr>
            <td class="td-label">Dibuat oleh</td>
            <td class="td-value">{{ $creatorName }}</td>
          </tr>`,
        'Lihat Detail CCH', '{{ $cchUrl }}',
        `
      <!-- Alert -->
      @if($rank === 'A')
      <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
        <tr>
          <td style="border-left: 4px solid #dc2626; padding: 12px 16px; font-size: 13px; color: #dc2626; font-weight: 600; background-color: transparent;">
            ⚠️ CCH ini memiliki Rank A dan memerlukan perhatian segera.
          </td>
        </tr>
      </table>
      @endif
`
    ),

    'ready_to_close.blade.php': getWrapper(
        'Notif. CCH-READY', '🔔', 'Siap Di-close', 'CCH siap untuk dilakukan Close Application',
        'CCH Siap<br>Untuk Di-close',
        'Semua blok pada CCH <strong>{{ $cchNumber }}</strong> telah diisi dan disubmit. CCH ini siap untuk dilakukan Close Application.',
        `          <tr>
            <td class="td-label">Nomor CCH</td>
            <td class="td-value">{{ $cchNumber }}</td>
          </tr>
          <tr>
            <td class="td-label">Subject CCH</td>
            <td class="td-value">{{ $cchSubject }}</td>
          </tr>
          <tr>
            <td class="td-label">Rank</td>
            <td class="td-value">{{ $rank }}</td>
          </tr>`,
        'Pergi ke Close Application', '{{ $cchUrl }}',
        `
      <!-- Alert -->
      <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
        <tr>
          <td style="border-left: 4px solid #c9a030; padding: 12px 16px; font-size: 13px; color: #c9a030; background-color: transparent;">
            🔖 Silahkan lakukan review dan Close Application pada CCH ini.
          </td>
        </tr>
      </table>
`
    ),

    'comment_added.blade.php': getWrapper(
        'Notif. CCH-COMMENT', '💬', 'Komentar Baru', 'Komentar baru telah ditambahkan pada CCH',
        'Komentar Baru<br>pada CCH Anda',
        '<strong>{{ $commenterName }}</strong> telah menambahkan komentar pada blok <strong>{{ $blockName }}</strong> di CCH <strong>{{ $cchNumber }}</strong>. Berikut informasi selengkapnya.',
        `          <tr>
            <td class="td-label">Nomor CCH</td>
            <td class="td-value">{{ $cchNumber }}</td>
          </tr>
          <tr>
            <td class="td-label">Subject CCH</td>
            <td class="td-value">{{ $cchSubject }}</td>
          </tr>
          <tr>
            <td class="td-label">Blok</td>
            <td class="td-value">{{ $blockName }}</td>
          </tr>`,
        'Lihat &amp; Balas Komentar', '{{ $cchUrl }}',
        `
      <!-- Comment card -->
      <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 24px;">
        <tr>
          <td style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 10px 16px;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td valign="middle">
                  <table border="0" cellpadding="0" cellspacing="0">
                    <tr>
                      <td valign="middle" style="padding-right: 8px;">
                        <div style="width: 28px; height: 28px; border-radius: 50%; background-color: #0f1e35; text-align: center; line-height: 28px; font-size: 11px; font-weight: 700; color: #c9a030;">
                          {{ strtoupper(substr($commenterName, 0, 1)) }}
                        </div>
                      </td>
                      <td valign="middle">
                        <span style="font-size: 13px; font-weight: 600; color: #1a2332;">{{ $commenterName }}</span>
                      </td>
                    </tr>
                  </table>
                </td>
                <td valign="middle" align="right" style="text-align: right;">
                  <span style="font-size: 11px; color: #9aaabb; font-family: 'IBM Plex Mono', monospace; white-space: nowrap;">{{ now()->format('d M Y, H:i') }} WIB</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding: 14px 16px; background-color: #ffffff;">
            <div style="font-size: 11px; font-weight: 600; color: #9aaabb; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 6px;">Subjek Komentar</div>
            <div style="font-size: 14px; font-weight: 600; color: #1a2332; line-height: 1.5; margin-bottom: 4px;">{{ $commentSubject }}</div>
            <div style="font-size: 13px; color: #4a5a6e; line-height: 1.6; white-space: pre-wrap; margin-top: 8px;">{{ $commentBody }}</div>
          </td>
        </tr>
      </table>
`
    )
};

for (const [filename, content] of Object.entries(templates)) {
    fs.writeFileSync(path.join(dir, filename), content);
    console.log('Wrote ' + filename);
}
