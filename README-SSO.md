# SSO, biometric and card sign-in

## What works today (front-end demo)
- **Biometric** (fingerprint, face, Windows Hello, phone screen lock) and **Card / security key** (FIDO2 USB or NFC key, smart card) use the browser's WebAuthn API.
- Needs https or localhost. Does not work when you double-click the HTML file.
- Users first sign in with a password, then add a method under "Sign-in methods" in their portal.
- Demo limit: the browser matches the credential, but nothing verifies the cryptographic signature. Do not rely on it for real security.

## Needed for production
1. A backend (Vercel serverless functions + a database) that stores each user's credential public key.
2. Registration: the server creates a random challenge, the browser signs it, the server verifies and saves the public key. Use a library such as @simplewebauthn/server.
3. Sign-in: server issues a challenge, verifies the signature, then creates a session cookie (HttpOnly, Secure).
4. Keep a password or admin-reset fallback in case a device is lost.

## SSO
1. Choose the identity provider your client or staff use: Microsoft Entra ID, Google Workspace, Okta.
2. Register Tychiff as an application there (OpenID Connect). You get a client ID and secret, and you set a redirect address such as https://your-site/api/sso/callback.
3. Build two server routes: one that sends the user to the provider, and one callback that checks the signed ID token, maps the email to a Tychiff account and role, and starts a session.
4. In config.js set `window.TY_SSO={enabled:true,url:'https://your-site/api/sso/start'}`. The SSO buttons then redirect there.
5. Never grant a role from the IdP email alone. Check it against your approved-user list.

## Card access
- FIDO2 cards and keys work through the Card / key button.
- Plain RFID staff badges need a reader and a server-side lookup. They are easy to clone and should be a second factor only.
