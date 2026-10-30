// Vercel serverless function: AI-assisted JD drafting for Tychiff.
// Setup: add ANTHROPIC_API_KEY (and optionally ANTHROPIC_MODEL, ALLOWED_ORIGIN) in Vercel > Settings > Environment Variables.
// The API key stays on the server. It must never be placed in any HTML or JS file.

const SECTIONS = ['Job snapshot','About the company','Role purpose','Key responsibilities','Must-have requirements','Good-to-have','Behavioural competencies','Work conditions','Compensation and benefits','Hiring process','Growth path','Equal opportunity','How to apply'];

const SYSTEM = `You are a senior recruitment consultant at Tychiff Solutions, an Indian recruitment and staffing company. You write clear, professional job descriptions from a client's high-level brief.

Rules:
1. Use ONLY facts in the brief. Never invent salary, benefits, company details, team size, targets or locations. If a fact is missing, write a short bracketed prompt such as [To confirm with client: notice period].
2. You may add widely standard duties and skills for the role. Do not add specific tools, numbers, targets or conditions the client did not state; use bracketed prompts for those.
3. Keep Must-have (five to eight items) separate from Good-to-have. Start each responsibility with an action verb. Use plain English and short sentences.
4. Never include discriminatory requirements (gender, age, marital status, religion, caste, appearance). If the brief contains one, leave it out and instead describe the genuine job need in neutral words, for example "willing to travel at night" only if the brief states a travel need.
5. Pay must state its unit (monthly or annual) only if the brief says so; otherwise add [confirm monthly or annual, fixed or CTC].
6. Output ONLY a JSON object. Keys must be exactly: ${SECTIONS.join(', ')}. Each value is plain text. Use "• " bullets separated by newlines for lists. No markdown, no code fences.
7. "Equal opportunity" must be: We welcome applications from all qualified candidates. Selection is based on skills, experience and fit for the role.
8. "How to apply" must be: Share your resume with Tychiff Solutions at clients@tychiff.com or call +91 80958 58644.
9. "Hiring process": 1. Application screening by Tychiff Solutions 2. Telephone discussion on interest, availability and expectations 3. Interview with the client [number of rounds to confirm] 4. Offer and joining confirmation.`;

const IMPROVE = `You edit one section of a job description. Follow the instruction, keep every fact, add no new facts, keep bracketed prompts that are still unresolved, and never add discriminatory wording. Return ONLY the revised section text, no heading, no markdown, no code fences.`;

function cors(req, res) {
  const allow = process.env.ALLOWED_ORIGIN || '';
  const origin = req.headers.origin || '';
  if (allow && origin === allow) res.setHeader('Access-Control-Allow-Origin', allow);
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  res.setHeader('Vary', 'Origin');
}

async function callModel(system, user, maxTokens) {
  const r = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: { 'content-type': 'application/json', 'x-api-key': process.env.ANTHROPIC_API_KEY, 'anthropic-version': '2023-06-01' },
    body: JSON.stringify({ model: process.env.ANTHROPIC_MODEL || 'claude-sonnet-5-5', max_tokens: maxTokens, system, messages: [{ role: 'user', content: user }] })
  });
  if (!r.ok) throw new Error('Model request failed: ' + r.status);
  const d = await r.json();
  return (d.content || []).filter(b => b.type === 'text').map(b => b.text).join('');
}

function parseSections(text) {
  const t = text.replace(/```json|```/g, '').trim();
  const o = JSON.parse(t.slice(t.indexOf('{'), t.lastIndexOf('}') + 1));
  const out = {};
  for (const k of SECTIONS) out[k] = typeof o[k] === 'string' ? o[k].trim() : '';
  return out;
}

module.exports = async (req, res) => {
  cors(req, res);
  if (req.method === 'OPTIONS') return res.status(204).end();
  if (req.method !== 'POST') return res.status(405).json({ error: 'POST only' });
  if (!process.env.ANTHROPIC_API_KEY) return res.status(500).json({ error: 'Server is not configured' });
  try {
    const body = typeof req.body === 'string' ? JSON.parse(req.body) : (req.body || {});
    if (JSON.stringify(body).length > 12000) return res.status(413).json({ error: 'Input too long' });
    if (body.action === 'improve') {
      const text = String(body.text || '').slice(0, 4000), instr = String(body.instruction || 'Make it clearer and more concise').slice(0, 300);
      const out = await callModel(IMPROVE, `Section: ${body.section}\nInstruction: ${instr}\n\nCurrent text:\n${text}`, 1200);
      return res.status(200).json({ text: out.trim() });
    }
    const b = body.brief || {};
    if (!String(b.title || '').trim() || !String(b.company || '').trim()) return res.status(400).json({ error: 'title and company are required' });
    const out = await callModel(SYSTEM, 'Client brief (JSON):\n' + JSON.stringify(b), 2500);
    return res.status(200).json({ sections: parseSections(out) });
  } catch (e) {
    return res.status(502).json({ error: 'AI drafting failed. Use the template draft.' });
  }
};
