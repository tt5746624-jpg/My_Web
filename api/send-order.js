import formidable from 'formidable';
import fs from 'fs';
import FormData from 'form-data';
import fetch from 'node-fetch';

export const config = {
  api: {
    bodyParser: false,
  },
};

export default async function handler(req, res) {
  // CORS Headers (Cross-Origin Error မဖြစ်စေရန်)
  res.setHeader('Access-Control-Allow-Credentials', true);
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET,OPTIONS,PATCH,DELETE,POST,PUT');
  res.setHeader(
    'Access-Control-Allow-Headers',
    'X-CSRF-Token, X-Requested-With, Accept, Accept-Version, Content-Length, Content-MD5, Content-Type, Date, X-Api-Version'
  );

  if (req.method === 'OPTIONS') {
    res.status(200).end();
    return;
  }

  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  const BOT_TOKEN = process.env.BOT_TOKEN;
  const CHAT_ID = process.env.CHAT_ID;

  if (!BOT_TOKEN || !CHAT_ID) {
    return res.status(500).json({ error: 'Server Environment Variables (BOT_TOKEN / CHAT_ID) Missing' });
  }

  const form = formidable({ keepExtensions: true });

  form.parse(req, async (err, fields, files) => {
    if (err) {
      console.error("Form Parse Error:", err);
      return res.status(500).json({ error: 'Failed to process file upload' });
    }

    try {
      const tgUser = Array.isArray(fields.tgUser) ? fields.tgUser[0] : fields.tgUser;
      const deviceId = Array.isArray(fields.deviceId) ? fields.deviceId[0] : fields.deviceId;
      const plan = Array.isArray(fields.plan) ? fields.plan[0] : fields.plan;
      const photoFile = Array.isArray(files.photo) ? files.photo[0] : files.photo;

      if (!photoFile) {
        return res.status(400).json({ error: 'Photo is required' });
      }

      const safeTgUser = (tgUser || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
      const safeDeviceId = (deviceId || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
      const safePlan = (plan || 'N/A').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

      const caption = `🛒 <b>New Order Received!</b>\n\n` +
                      `👤 <b>Telegram:</b> ${safeTgUser}\n` +
                      `📱 <b>Device ID:</b> <code>${safeDeviceId}</code>\n` +
                      `💎 <b>Plan:</b> ${safePlan}`;

      const formData = new FormData();
      formData.append('chat_id', CHAT_ID);
      formData.append('caption', caption);
      formData.append('parse_mode', 'HTML');
      formData.append('photo', fs.createReadStream(photoFile.filepath));

      const tgRes = await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendPhoto`, {
        method: 'POST',
        headers: formData.getHeaders(),
        body: formData,
      });

      const tgData = await tgRes.json();

      if (tgData.ok) {
        return res.status(200).json({ ok: true });
      } else {
        return res.status(400).json({ ok: false, description: tgData.description });
      }
    } catch (e) {
      console.error("Server Error:", e);
      return res.status(500).json({ error: e.message });
    }
  });
}
