// api/send-order.js
import formidable from 'formidable';
import fs from 'fs';
import FormData from 'form-data';
import fetch from 'node-fetch';

export const config = {
  api: {
    bodyParser: false, // File Upload လက်ခံနိုင်ရန်
  },
};

export default async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  // Server Environment Variables ထဲမှ Token ကို ယူသုံးမည်
  const BOT_TOKEN = process.env.BOT_TOKEN; 
  const CHAT_ID = process.env.CHAT_ID;

  if (!BOT_TOKEN || !CHAT_ID) {
    return res.status(500).json({ error: 'Server Configuration Error' });
  }

  const form = formidable({});

  form.parse(req, async (err, fields, files) => {
    if (err) {
      return res.status(500).json({ error: 'Form parsing failed' });
    }

    try {
      const tgUser = Array.isArray(fields.tgUser) ? fields.tgUser[0] : fields.tgUser;
      const deviceId = Array.isArray(fields.deviceId) ? fields.deviceId[0] : fields.deviceId;
      const plan = Array.isArray(fields.plan) ? fields.plan[0] : fields.plan;
      const photoFile = Array.isArray(files.photo) ? files.photo[0] : files.photo;

      if (!photoFile) {
        return res.status(400).json({ error: 'Photo is required' });
      }

      // Safe HTML String Escaping
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
      return res.status(500).json({ error: e.message });
    }
  });
}
