# AI-assisted JD Builder: setup

1. Deploy this folder to Vercel (the `api/` folder becomes a serverless function at `/api/generate-jd`).
2. In Vercel > Project > Settings > Environment Variables add:
   - `ANTHROPIC_API_KEY`  your key from console.anthropic.com (server only, never in HTML/JS)
   - `ANTHROPIC_MODEL`    optional; check the current model name in the Anthropic docs
   - `ALLOWED_ORIGIN`     your site origin, e.g. https://tychiff-website.vercel.app
3. Redeploy. In the JD Builder, "Generate with AI" now works. If the AI is unreachable, the template draft is used.

Before going public:
- Replace the demo login with real authentication, and check the user's session in `api/generate-jd.js`. Today anyone who finds the URL could call it and use your credits.
- Add rate limiting (Vercel Firewall or a store such as Upstash).
- Tell clients that AI helps draft JDs and a recruiter reviews every JD.
- Do not send client confidential or candidate personal data in the brief.
