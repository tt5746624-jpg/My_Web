import formidable from 'formidable';
import fs from 'fs';
import FormData from 'form-data';
import fetch from 'node-fetch';
import { createClient } from '@supabase/supabase-js';

export const config = {
  api: {
    bodyParser: false,
  },
};

export default async function handler(req, res) {
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
  const SUPABASE_URL = process.env.SUPABASE_URL;
  const SUPABASE_KEY = process.env.SUPABASE_SERVICE_ROLE_KEY;

  if (!BOT_TOKEN || !CHAT_ID || !SUPABASE_URL || !SUPABASE_KEY) {
    return res.status(500).json({ error: 'Server Environment Variables Missing' });
  }

  const supabase = createClient(SUPABASE_URL, SUPABASE_KEY);
  const form = formidable({ keepExtensions: true });

  form.parse(req, async (err, fields, files) => {
    if (err) {
      console.error("Form Parse Error:", err);
      return res.status(500).json({ error: 'Failed to process request' });
    }

    try {
      const type = Array.isArray(fields.type) ? fields.type[0] : fields.type;
      const tgUser = Array.isArray(fields.tgUser) ? fields.tgUser[0] : fields.tgUser;
      const deviceId = Array.isArray(fields.deviceId) ? fields.deviceId[0] : fields.deviceId;
      const plan = Array.isArray(fields.plan) ? fields.plan[0] : fields.plan;
      const payment = Array.isArray(fields.payment) ? fields.payment[0] : fields.payment;
      const payName = Array.isArray(fields.payName) ? fields.payName[0] : fields.payName;
      const photoFile = Array.isArray(files.photo) ? files.photo[0] : files.photo;

      const safeTgUser = (tgUser || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
      const safeDeviceId = (deviceId || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

      // 1. Custom Payment Request
      if (type === 'custom_request') {
        const safePayName = (payName || 'N/A').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

        // Supabase DB သို့ သိမ်းဆည်းခြင်း
        const { error: dbError } = await supabase
          .from('payment_requests')
          .upsert({ 
            device_id: safeDeviceId, 
            tg_user: safeTgUser, 
            pay_name: safePayName, 
            status: 'pending' 
          }, { onConflict: 'device_id' });

        if (dbError) {
          console.error("Supabase Detailed Error:", dbError);
          return res.status(500).json({ error: `Database save failed: ${dbError.message}` });
        }

        // Telegram သို့ အကြောင်းကြားစာ ပို့ခြင်း
        const caption = `⚠️ <b>CUSTOM PAYMENT REQUEST</b>\n\n` +
                        `💳 <b>Requested Payment:</b> ${safePayName}\n` +
                        `👤 <b>Telegram:</b> ${safeTgUser}\n` +
                        `📱 <b>Device ID:</b> <code>${safeDeviceId}</code>\n\n` +
                        `👉 <i>Admin Panel တွင် Device ID <code>${safeDeviceId}</code> အတွက် QR Link ထည့်သွင်းပေးပါ။</i>`;

        await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ chat_id: CHAT_ID, text: caption, parse_mode: 'HTML' }),
        });

        return res.status(200).json({ ok: true });
      }

      // 2. Normal Order Submit ( Receipt Photo )
      if (!photoFile) {
        return res.status(400).json({ error: 'Receipt photo is required' });
      }

      const safePlan = (plan || 'N/A').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
      const safePayment = (payment || 'N/A').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

      const caption = `🛒 <b>New Order Received!</b>\n\n` +
                      `💳 <b>Payment:</b> ${safePayment}\n` +
                      `💎 <b>Plan:</b> ${safePlan}\n` +
                      `👤 <b>Telegram:</b> ${safeTgUser}\n` +
                      `📱 <b>Device ID:</b> <code>${safeDeviceId}</code>`;

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
      return tgData.ok 
        ? res.status(200).json({ ok: true }) 
        : res.status(400).json({ ok: false, description: tgData.description });

    } catch (e) {
      console.error("Server Error:", e);
      return res.status(500).json({ error: e.message });
    }
  });
}
