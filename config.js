/* Set TY_API to your deployed site origin, e.g. 'https://tychiff-website.vercel.app'.
   Leave '' when the site and the /api folder are deployed together on Vercel. */
window.TY_API='';
/* SSO card: when enabled, the SSO card button sends users to your identity provider (which does the card/certificate check) instead of the in-browser card check.
    set enabled:true and url to your identity provider's sign-in start address (Microsoft Entra ID, Google Workspace, Okta...). See README-SSO.md */
window.TY_SSO={enabled:false,url:''};
