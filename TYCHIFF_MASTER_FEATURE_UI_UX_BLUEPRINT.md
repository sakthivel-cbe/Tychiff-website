# TYCHIFF Master Feature, UI/UX & Platform Blueprint

**Purpose:** Product discovery and architecture input before Phase 1 implementation  
**Scope:** ATS, recruitment management, contract staffing, client/candidate/recruiter/management experiences, AI and Agentic RAG  
**Reviewed:** Existing TYCHIFF files, Phase 0–4 requirements, approved Laravel architecture, and 21 supplied reference screenshots as summarized in `TYCHIFF_Reference_Website_360_Degree_Gap_Analysis.md`  
**Date:** 25 October 2026  
**Project application/source files modified:** No. This blueprint is the only file created for this task.

> This is an analysis and design document. It does not start implementation, create migrations, or alter the existing application.

## 1. Executive summary

TYCHIFF should build one coherent platform for recruitment and contract staffing, delivered in phases on the approved **Laravel/PHP + MySQL/MariaDB modular monolith** architecture. Keep the existing HTML/CSS/JavaScript as a practical presentation starting point and adapt it into Laravel views. Production identity, access checks, workflows, and records must live on the server. Keep the database as the source of truth for candidates, requirements, assignments, invoices, and KPI reporting.

The “mocktail” approach means selecting useful outcomes and interaction ideas across products, then designing TYCHIFF’s own workflows and interface around its users. It does not mean cloning screens or permanently rejecting a useful feature because a reference has it. Common ideas such as candidate pools, client visibility, work queues, alerts, pipeline views, skill matching, and analytics are appropriate when fitted to TYCHIFF’s business and privacy controls.

The feature model should keep distinct business records distinct: a requirement is not a JD; a JD is not a published job; a candidate is not a candidate’s application to a requirement; a placement is not a contract staffing assignment; an invoice is not a payment; a candidate’s master status is not their status for a specific requirement. This is essential to multi-requirement candidate histories, auditability, and reliable reporting.

The approved technology architecture can support the proposed roadmap. One domain addition should be explicit before Phase 1 data design: model contract staffing agreements and assignments separately from permanent placement, including assignment status, allocation, renewal, and billable terms. Payroll remains a future integration boundary rather than a Phase 1–4 implementation requirement.

## 2. Product vision

TYCHIFF is a professional **360° Recruitment + Staffing Management Platform** that helps its team move from client demand to candidate match, interview, placement or deployment, follow-up, and collection—with clear ownership and a useful experience for each authorized participant.

The platform should provide:

- A reliable shared record of clients, requirements, JDs, candidates, submissions, interviews, placements, assignments, documents, invoices, and collections.
- Recruiter workflows that show the next best human action and reduce repeated data entry.
- Candidate experiences that explain consent, status, interviews, requests, and communication preferences.
- Client visibility limited to records and actions the client is authorized to see.
- Management reporting derived from transactions and clearly defined measures.
- AI assistance that finds and explains relevant evidence; authorized people retain decision authority.
- A product structure that can add integrations, Tamil localization, SSO/passkeys, and future payroll connectivity without redesigning core business records.

## 3. “Mocktail” product philosophy

1. **Start from a user problem.** Adopt a pattern when it saves effort, improves clarity, reduces errors, or supports a business control.
2. **Combine complementary ideas.** For example, blend candidate search with consent-aware pools, availability dates, and explainable match evidence.
3. **Fit TYCHIFF’s scale.** Prefer an understandable modular monolith, useful queues, and a relational system of record before introducing separate platforms.
4. **Design independently.** Use TYCHIFF branding, vocabulary, content, visual hierarchy, and interaction design. Do not copy reference code, assets, text, or distinctive arrangements.
5. **Keep future capabilities visible.** Features outside the immediate phase are **NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY**, unless they genuinely do not fit TYCHIFF’s product.
6. **Do not turn marketing claims into requirements.** Screenshot-visible statements are not proof of backend capability or business value.
7. **Make human ownership clear.** Automation can remind, summarize, and prepare work; it must not silently make important recruitment decisions or communicate externally without an approved workflow.

## 4. Existing project assessment

### Project structure inspected

| Area | Existing files | Current state |
|---|---|---|
| Public site and content | `index.html`, `jobs.html`, `who-we-are.jpg`, `favicon.png`, `logo.png`, `logo-light.png` | Static site and sample public jobs. Keep the original TYCHIFF identity; verify factual claims and convert views gradually. |
| Shared visual system | `styles.css` | Existing CSS is reusable; it is not a production application framework. Adapt selectively into Laravel layouts/components. |
| General browser interaction | `script.js`, inline scripts in HTML | Client-side demo interactions and acknowledgements; no durable workflow backend. |
| Login and signup | `login.html`, four `login-*.html`, `login-ui.js`, four `signup-*.html`, `auth.js` | Role-specific demo screens. Seeded credentials, accounts, status, and sessions are in browser storage. Not secure authentication. |
| Portals | `portal-admin.html`, `portal-management.html`, `portal-recruiter.html`, `portal-candidate.html` | Static portal presentations with hard-coded people, pipeline records, KPI values, interviews, activity, and status claims. |
| Jobs | `jobs-data.js`, `jobs.html` | Sample job array; publication state in localStorage. Not a real jobs database or candidate application workflow. |
| JD workflow | `jd-builder.html`, `jd-builder.js`, `jd.js`, `jd-approval.html` | Browser-local JD draft/version/status and approval prototype. Link behavior is browser-specific, not durable client authorization. |
| AI | `api/generate-jd.js`, `README-AI.md` | Vercel-style AI JD endpoint. README warns that it lacks a real user-session check and rate limiting. Keep it out of production use until replaced by an authorized Laravel service. |
| SSO/passkeys | `webauthn.js`, `config.js`, `README-SSO.md` | Browser-level demonstration. README states server-side cryptographic verification is missing. Preserve only as UX reference until a production identity flow is implemented. |
| Project notes | `CHANGELOG.md`, `README-AI.md`, `README-SSO.md`, `TYCHIFF_Phase_0_to_4_Requirements.docx`, `TYCHIFF_Reference_Website_360_Degree_Gap_Analysis.md`, `.docx` | Requirements and analysis artifacts. Changelog identifies prototype features and user-facing changes. |
| Backend/runtime | `api/` contains `generate-jd.js`; no Laravel app, PHP source, migration set, database config, or production backend is present | Production ATS functionality is **NOT PRESENT**. The approved architecture is planned, not implemented. |

The browser demo’s execution is real in the narrow sense that front-end JavaScript updates local browser state. It does not provide server-side identity, shared durable records, authorization, audit, or multi-user workflow. Portal data and KPI figures are illustrative, not live transactional metrics.

### Keep / adapt / replace direction

- **Keep as assets:** TYCHIFF logos, favicon, suitable public images, and reusable CSS patterns subject to the independent design review.
- **Adapt:** public site and job pages as Laravel views; login into one sign-in experience; candidate/recruiter/admin/management portal concepts into permission-scoped views; JD builder into a server-backed Phase 2 workflow.
- **Replace:** `auth.js` security behavior, browser-storage data models, public job sample array, local JD/approval persistence, unauthenticated AI endpoint, and client-side WebAuthn credential matching.
- **Remove after migration:** demo credentials, fake KPI values/activity, localStorage “session” guards, admin invite code, and any claim that demo SSO or browser biometric matching is production security.
- **Defer:** production SSO/WebAuthn, WhatsApp, external sourcing, advanced semantic retrieval, and native mobile until their roadmap phase.

## 5. Complete feature inventory and taxonomy

Classification terms for this blueprint:

- **USE:** valuable capability; include in TYCHIFF’s product design.
- **IMPROVE:** include, but make it more useful, secure, accessible, explainable, or workflow-aware for TYCHIFF.
- **COMBINE:** merge complementary ideas into one coherent workflow or work surface.
- **FUTURE:** useful, but implement in a later phase; preserve a clean boundary now where needed.
- **NOT RELEVANT:** does not materially serve the current TYCHIFF recruitment/staffing product; reconsider only if the business objective changes.
- **LEGAL/IP CAUTION:** the underlying capability may be valuable; implement independently and review rights/claims where needed. This is not a rejection of the functionality.

| Capability group | Recommended classification | TYCHIFF direction |
|---|---|---|
| Requirements/JD/client approval, candidate database, consent, duplicate checking, interview and selection | USE | Core Phase 2 recruitment loop from approved requirements. |
| Candidate pools, tags, availability, source, interest, follow-up dates | IMPROVE | Combine search, outreach history, consent, candidate preferences, and ownership into a recruiter-ready talent workspace. |
| Recruiter dashboard, pipeline, alerts and KPI cards | COMBINE | Provide role-specific work queues and real data, with concise KPIs and activity context. Independently design visual composition. |
| Client portal | IMPROVE | Read-only by default; narrowly scoped approval/feedback actions; never reveal internal notes, AI traces, cost/margin, or unrelated candidates. |
| Candidate comparison and AI match scoring | IMPROVE | Use requirement-specific evidence, unknown/missing fields, explanation and human review. Avoid generic employability scores. |
| Resume parsing and document search | USE | Private, asynchronous, reviewable extraction; preserve original resume versions and evidence. |
| Contract staffing assignment lifecycle | USE | Separate staffing domain with assignment lifecycle and commercial terms. Payroll is a later boundary. |
| Notifications and event-based reminders | COMBINE | Queue email first, with one activity/reminder model and future provider channels. No unapproved autonomous AI outreach. |
| Analytics and KPI | IMPROVE | Define formulas and source transactions before charts; include aging, conversion, placements, collections and staffing utilization. |
| External sourcing connections | FUTURE | Keep connector boundary; use authorized APIs/feeds and record source/provenance. |
| SaaS subscription pricing / feature tiers | NOT RELEVANT | Current TYCHIFF objective is recruitment/staffing management, not an approved SaaS subscription product. If business model changes, revisit independently. |
| Public career diagnostics, career simulation, general AI-proof score | NOT RELEVANT | Not part of the ATS/staffing workflow. Candidate-friendly status, feedback, and skills data remain useful in-scope functions. |
| Reference brand, copy, imagery, source code or distinctive layout | LEGAL/IP CAUTION | Do not reuse these materials. Implement useful workflow capability with TYCHIFF-owned design and copy. |
| Pricing/guarantee/security/performance claims | LEGAL/IP CAUTION | Only publish TYCHIFF terms and claims that are substantiated and approved. |

## 6. Reference feature analysis

The screenshots from the reference website show pages and promotional capability claims, not a verified backend. The report `TYCHIFF_Reference_Website_360_Degree_Gap_Analysis.md` inventories each of the 21 supplied JPEGs and provides the detailed per-capability gap table. This blueprint carries forward their product lessons without treating them as implementation proof.

| Observed idea | User/product value | TYCHIFF disposition |
|---|---|---|
| Unified recruitment operations story | Helps users understand an end-to-end process | Use a clear TYCHIFF workflow map, but organize it around TYCHIFF’s own lifecycle and terminology. |
| Sourcing across multiple channels | Expands candidate discovery and records candidate source | Future connectors; Phase 2 source attribution and manual/import sourcing. Respect provider authorization and consent. |
| Candidate skill assessment/report | Can reduce repetitive resume screening and structure comparison | Improve as evidence-backed matching for a specific approved requirement, not a generic candidate score. |
| Requirement-to-placement pipeline | Improves ownership, aging, and bottleneck visibility | Use server-side state history, queues and optional board/table views. |
| CRM/account history | Keeps client requirements and delivery context together | Use a client record and timeline linked to contacts, requirements, decisions, placements/assignments and invoices. |
| Read-only client visibility | Reduces status-chasing and email duplication | Use Phase 2 portal, client scope policies, explicit candidate share state, and private internal notes. |
| Recruiter productivity and management reporting | Helps prioritize work and inspect conversion | Use task-first recruiter queue plus management analytics defined from real transactions. |
| Automatic event follow-ups | Reduces missed interviews and feedback requests | Start with deterministic reminders; human approves external communications and any consequential change. |
| Sector/role capability catalog | Clarifies services and supports taxonomy/search | Build TYCHIFF-controlled industry, occupation, skill and location vocabularies, not a copied card set. |
| Public demo lead intake | Captures prospective client demand | Optional Phase 4 public inquiry form; validate server-side, rate-limit, consent and queue follow-up. |
| Tier pricing and subscription feature matrix | Useful for SaaS packaging | Not relevant to current product model; keep future extensibility if TYCHIFF later sells software subscriptions. |
| Candidate-facing career diagnostic | Could provide a separate career product | Not relevant to the recruitment/staffing core; do not let it expand candidate data collection without a business decision. |
| Login/signup with workspace selection | Helps different audiences find their path | Adopt the functional simplicity via one sign-in; server determines authorized roles. Do not copy exact screen composition. |

No screenshot establishes whether the reference supports RFP/RFQ/RFI, consent withdrawal, duplicate resolution, actual document protection, client-level isolation, interview scheduling, offer management, staffing assignments, invoices/payment, audit, backup, or a functioning AI. Each is **NOT DETERMINABLE FROM REFERENCE SCREENSHOTS**.

## 7. UI/UX inspiration and navigation model

### Product feel

Professional, simple, modern, friendly, interactive, responsive, and task-oriented. Keep forms understandable for non-technical users, show clear validation and next steps, support keyboard and screen-reader use, and keep mobile layouts useful rather than merely compressed.

### Suggested role-aware navigation

The information architecture should follow work objects, with navigation items shown only when permitted:

- **Recruiter:** My Work; Requirements; Talent; Submissions; Interviews; Placements/Assignments; Clients; Tasks; Reports.
- **Management:** Overview; Requirements; Delivery; Recruiters; Clients; Staffing; Finance; Reports; Administration (where permitted).
- **Client:** My Requirements; JD Approvals; Candidate Submissions; Interviews/Feedback; Status/Reports; Documents explicitly shared with the client.
- **Candidate:** Profile; Resume/Documents; Preferences/Consent; My Applications; Interviews; Messages/Notifications.
- **Admin:** Users/Invitations; Roles/Permissions; Client/workflow configuration; Taxonomies; Audit; Privacy/retention operations; System health.

Use a persistent, searchable global navigation only if it remains uncluttered at TYCHIFF’s scale. A global search box must return only authorized records and make result type clear.

### UX patterns to implement independently

| Pattern | TYCHIFF design direction |
|---|---|
| Dashboard | Prioritize overdue/next actions and bottlenecks; show a small number of contextual measures with definitions and drill-down. Recruiter and management views have different tasks. |
| Pipeline | Provide a fast table as the reliable default and optional board visualization; status changes use server validation, reason capture when needed, and history. |
| Filters | Searchable multi-select skills/location/status/owner/date; visible applied filters; clear-all; saved views later; accessible controls. |
| Candidate list | Provide privacy-safe summary, consent/share readiness, current availability, source, last contact, requirement-specific match context, and next action. Avoid exposing unnecessary PII in wide tables. |
| Detail workspace | Use a summary plus tabs/sections for profile, documents, activity, consent and requirement-specific submissions. Keep internal notes visually and permission-wise separate from client-visible data. |
| Timeline | Combine business events and assigned follow-ups with actor, date, and next owner. Avoid duplicating untrusted or sensitive AI output. |
| Quick actions | Allow safe actions such as log call, set follow-up, request availability, or schedule interview; confirm high-impact or bulk actions. |
| AI evidence panel | Explain criteria, matched evidence source/page/section, gaps, confidence/unknowns, and recruiter action. Do not use a decorative “fit score” without explanation. |
| Notifications | Show unread/due notifications and delivery state; preferences by channel/category; avoid placing sensitive candidate data in email subject/body. |
| Bulk actions | Add only with clear selection count, preview, permission checks, confirmation, atomic/partial result reporting, and audit. |
| Empty/error states | Explain what is missing and the next safe action; distinguish no matches from search failure or incomplete data. |
| Responsive behavior | Candidate self-service and recruiter follow-up/interview tasks work on phones. Large data grids may use card rows or controlled horizontal scrolling with labels. |

Visual inspiration is limited to functional patterns. TYCHIFF’s type, color, icons, spacing, illustrations, dashboard composition, copy, and section order should be independently designed.

## 8. Candidate module

**Goal:** a trustworthy, reusable candidate record that can participate in multiple requirements without one global pipeline status overwriting requirement-specific history.

### Candidate account and profile

- Self-registration with email verification and explicit privacy/consent acceptance; candidate can later update profile and communication preferences.
- Separate login account from candidate profile so staff-sourced candidates can exist before or without a portal login; link account when identity is verified.
- Master data: legal/preferred name as appropriate, contact channels, location, preferred roles/industries, work authorization/right-to-work fields only when needed, salary expectations/CTC, notice period, availability date, work mode, relocation/travel preference.
- Candidate source, source detail/referral, recruiter owner, created/updated timestamps, last verified date, and source provenance.
- Skills taxonomy plus candidate-stated and recruiter-verified indicators; experience entries with employer/title/date/context; education/certification entries and optional verification status.
- Candidate lifecycle status should describe the master relationship (e.g. active, dormant, do-not-contact, withdrawn/anonymized). Never use it as a substitute for a per-requirement submission status.

### Resume and document experience

- Multiple resume versions with upload date, source, file hash, scan status, parsed-data version, and “current” marker; older versions remain tied to historical matches/submissions.
- Private download only after server authorization; document activity audited.
- Candidate can see documents on their own profile and update/replace them, subject to retention/consent rules.
- Parsing is asynchronous; extracted fields are reviewable and traceable to page/section evidence. A parse failure does not block profile creation.

### Candidate portal

- Profile and preferences; resumes/documents; consent/privacy notice versions; client-sharing state where applicable; applications/submissions; interview schedules; status updates; feedback requests; contact preferences and notifications.
- Candidate sees only their own records and candidate-appropriate status language. Do not expose recruiter notes, client internal feedback, scoring traces, other candidates, or commercial data.
- Candidate communication should identify the responsible TYCHIFF contact and provide a clear action or expectation.

### Candidate pools and consent

Pools are saved search/assignment concepts or explicit membership records, not a global unreviewed list. Candidate should be searchable only when permitted by purpose/consent policy. Make consent version, capture date, source, purposes, sharing history, withdrawal, retention expiry, and anonymization/deletion status auditable.

## 9. Recruiter module

### Recruiter workbench

The landing page should answer: “What needs my attention now?” Show assigned requirements by aging/priority, candidates needing contact, pending availability/consent, client submissions awaiting review, interviews/feedback due, and offer/joining follow-ups. Use links to the underlying records and avoid fabricated “live” metrics.

### Requirement management

- Intake from client with request type (RFP/RFQ/RFI/standard requirement), role, headcount, location, experience, skills, compensation/billing context, target dates, urgency, owner, source and attachments.
- Validation checklist for missing fields and contradictory constraints; record client clarifications and owner/date.
- Requirement status/history and pause/close reason; SLA/target dates and aging report.
- Requirement can have one or more JDs/versions and one or more published job records; explicit parent relationships preserve history.

### JD and approval

- Create from structured requirement and approved client facts; AI may draft or check completeness, with provenance and human review.
- Version each draft; do not overwrite the version already sent for client approval.
- Client approval/changes link to exact JD version and identified approver; approval links are authenticated or signed, expiring and auditable.
- Published job is a separate public projection with approved fields only; expiry/unpublish is explicit.

### Search, shortlist, and follow-up

- Search candidate pool with structured filters; show consent/share eligibility before a candidate can be sent to a client.
- Candidate match is requirement-specific. Keep query, criteria, candidate/resume version, match evidence and recruiter decision.
- Candidate interest/availability/salary/notice/location confirmation is time-stamped and can expire or be reconfirmed.
- Recruiter review/shortlist is separate from AI recommendation. Candidate may simultaneously be in several requirement pipelines.
- Follow-up tasks have owner, due time, status, related entity, outcome, and reminders. Record contact consent/preferences and channel.

### Submissions and interviews

- Submission stores candidate, requirement, submitted JD/version, recruiter, consent/share basis, client-facing profile snapshot, submission date, status and history.
- Avoid resubmitting the same candidate to the same requirement accidentally; use an idempotent action and explain the prior submission state.
- Interview records include round, participants, time zone, mode/location, scheduling state, candidate/client confirmation, reminder status, reschedules/cancellation reason, feedback deadline and outcome.
- Selection decision belongs to an authorized human and is distinct from AI score and interview feedback.

### Recruiter KPIs

Recruiter ownership and attribution rules must be explicit: who gets credit when a requirement is reassigned or candidate sourced by one recruiter and submitted by another. Report activity as well as outcomes to discourage inappropriate volume-only incentives.

## 10. Client module and portal

### Client CRM and onboarding

- Client organization, status, sector, primary contacts, billing/legal contacts, delivery contacts, address/site hierarchy, account owner, client group/parent relationships if applicable.
- Onboarding/checklist for approved contacts, service type, agreement documents, communication preferences and authorized client users.
- Interaction/activity timeline links meetings, requirement intake, approvals, submissions, interviews, feedback, placements/assignments and commercial events.
- RFP/RFQ/RFI intake record and attached source documents; preserve original client files privately.

### Client-facing views

Default to **read-only** visibility. Client users may access only their organization’s records and explicitly shared candidate submissions. Separate abilities for JD approval, candidate feedback, interview feedback, and request changes. Require server-side permission and record ownership checks for every page/API/document.

Client users must not automatically see:

- Internal recruiter notes, sourcing details, recruiter performance data, internal match reasoning not approved for sharing, or another client’s data.
- Candidate personal information before the required sharing/consent check.
- TYCHIFF margin, internal rates/costs, invoice notes, or internal commercial terms unless a specific client document/invoice is intentionally shared.
- Documents not explicitly shared with that client.

Client approval records should include the approver account, client organization, exact record and version, action, timestamp, and comment. Email links may notify but must not bypass authorization or approval record creation.

## 11. Management module

Management views should answer capacity, delivery, quality, client health, and cash-realization questions:

- Open demand by client/owner/age/priority; requirement coverage and due dates.
- Candidate funnel by source, skill group, recruiter and requirement; data-quality/consent readiness.
- Submission-to-interview, interview-to-selection, offer-to-join and join-to-payment conversion.
- Recruiter workload, follow-up aging, conversion and assignment utilization with attribution definitions.
- Placement and contract staffing count/revenue, replacement cases, assignment renewals/end dates, invoice aging and collection realization.
- Client performance and source effectiveness, with minimum cohort size and privacy controls where needed.
- Trend/drill-down to permitted records; exports permissioned and audited.

Avoid rankings based solely on raw sourced-candidate volume. Balance speed, quality, outcome, timely follow-up, client feedback and data completeness. Any productivity target needs management approval and should not incentivize inappropriate candidate handling.

## 12. Contract staffing module

Treat contract staffing as a separate business domain, not a flag on `placements` or a field on a candidate.

### Core staffing entities

- **Staffing requirement:** client/site/team need, headcount, role, location, expected dates, skills, billable terms and owner.
- **Staffing contract / work order:** client agreement or order, service scope, effective/expiry dates, renewal/termination notice dates, billing terms, currency/tax references as decided, replacement clauses and private document versions.
- **Staffing assignment:** one worker/candidate allocated to a client/site/team and work order; start/end dates, status, allocation percentage/hours where applicable, supervisor/contact, timesheet/billing mode, bill rate and frequency references, renewal state, replacement links.
- **Assignment status history:** proposed, confirmed, onboarding, active, on leave/on hold (if applicable), ending, ended, renewed, replaced, terminated; actor, timestamp, reason, effective date.
- **Availability/allocation:** candidate/worker availability windows and capacity allocation; prevent over-allocation where the business requires it.
- **Assignment documents:** offer/contract/work order/onboarding documents with private access and versions.
- **Billing records:** invoice lines reference assignment and period; retain billed quantity/rate and approved adjustment/credit history.
- **Cost/margin data:** only capture if the approved finance model requires it; restrict to finance/management roles. Do not leak to client portal or general recruiter views.

### Utilization and billing

Utilization needs an agreed denominator. Candidate/worker count active on assignment is simpler; hours/capacity utilization requires timesheets or an approved capacity source. Billing frequency (monthly, milestone, timesheet, other) and rate-effective dates must be modeled explicitly. Do not calculate margin from rates alone if actual internal costs are not captured reliably.

### Payroll boundary

Payroll is **NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY**. Keep assignment, approved time/quantity, and billing facts separable so a future payroll or finance system can consume an authorized export/API. Do not store payroll calculations, statutory deductions, bank details, or payroll ledgers in the ATS unless a separate product decision, controls review, and architecture assessment approve it.

### Boundary decision

Before Phase 1 data design, confirm contract staffing is in Phase 3 scope and whether initial assignment billing is invoice-per-period, timesheet-based, milestone-based, or mixed. Recommendation: model a contract/work order and separate assignment with effective-dated billing terms; begin with the billing method TYCHIFF actually uses, leaving other methods configurable later.

## 13. AI / Agentic RAG module

Keep **Agentic RAG** as the approved architecture. Use a bounded planner/orchestrator over permission-aware tools and retrieval methods. This is a recruitment workflow assistant, not an autonomous hiring agent.

### Agent tasks

- Parse a client brief and identify structured requirement fields, conflicts, and missing information.
- Draft a JD from approved facts and flag unsupported assertions or missing fields for recruiter review.
- Search structured candidate records, resume text, metadata and later vector retrieval as needed.
- Retrieve additional evidence where a criterion remains uncertain; identify unavailable rather than assume.
- Compare a candidate against requirement criteria, explain evidence and gaps, recommend a shortlist review order.
- Summarize recruiter pipeline/activity and explain aggregate KPI changes from authorized report data.
- Prepare (but not send) recruiter follow-up drafts or interview briefings, if data and privacy policy permit.

### Tool and retrieval controls

- Read-only, typed tools with caller-specific access scope and bounded result counts.
- No arbitrary SQL, unrestricted filesystem, arbitrary network access, or cross-client data access.
- Candidate-facing or client-facing output passes through application authorization and explicit human workflow.
- Structured search first; keyword/full-text and semantic retrieval may be combined inside the Agentic RAG workflow.
- Protect against prompt injection in resumes/attachments: treat document text as untrusted evidence, never as tool instructions.
- Filter/suppress attributes that are unnecessary or inappropriate for matching; do not infer sensitive traits.

### Human authority (mandatory)

AI must not hire, reject, make final employment decisions, change a critical workflow status without authorization, publish a JD, or contact candidates/clients without an approved human workflow. A recruiter can accept, edit, or override recommendations; retain an explanation/reason for meaningful overrides.

### AI traceability record

For every material AI recommendation, store:

- requirement and JD version;
- candidate and resume version(s);
- provider, model, model configuration, prompt and rubric version;
- retrieval strategy/tools used and bounded query criteria;
- evidence references and source excerpts/locations (not a copied full resume in logs);
- per-criterion scores, overall recommendation, uncertainty, missing information and timestamp;
- requesting user and authorization context;
- human reviewer, decision, override/action, reason and decision timestamp;
- usage, latency, cost estimate, failure/retry status.

Treat model output as untrusted. Validate schema, redact sensitive content where appropriate, set timeouts/retry limits, and preserve workflow records if AI is unavailable.

## 14. Document management

### Document classes

Candidate resumes and identity/qualification documents; client RFP/RFQ/RFI and agreements; requirements/JD versions and attachments; interview/offer/placement documents; staffing agreements and assignment documents; invoice/credit/payment evidence; and privacy/consent records.

### Storage and metadata

Keep bytes in private filesystem storage outside the public web root or a private object store. Persist metadata, owner/entity relation, purpose/category, storage key, original display filename, MIME and detected type, byte size, checksum, version, upload actor, created time, scan state/result, retention state, and access history. Never use a supplied filename as a storage path.

### Controls

- Upload through authenticated, authorized endpoints; enforce size/type limits and content inspection.
- Quarantine until malware scan completes; expose no download during quarantine or scan failure.
- Permit only approved formats; reject executable files and unexpected archives. Set operational size limits after hosting/storage capacity is confirmed.
- Stream download only after policy checks, or use short-lived signed links after an authorization check.
- Audit sensitive reads/downloads and client shares. Use separate access scopes by candidate, recruiter, client, finance and admin role.
- Version documents rather than overwriting signed/approved records. Retain prior versions per approved retention policy.
- Back up storage separately and test file/metadata recovery consistency.

## 15. Notification and communication architecture

Use a notification center and queued delivery service with replaceable channels. **Zoho Mail** is the initial email transport; hide provider-specific details behind the application notification abstraction.

Notification types: staff invitations, verification/password reset, requirement and JD approvals, candidate consent/action requests, candidate status where appropriate, interview scheduling/rescheduling/reminders, feedback requests, offer/joining reminders, assignment end/renewal reminders, invoice/payment follow-up, and system delivery failures.

Store recipient, related record, category, channel, notification intent, delivery status/attempts/provider reference, timestamps, and retry state. Keep email minimal; link to an authenticated portal for sensitive detail. Respect communication preferences, suppression/do-not-contact, consent, and role scope.

WhatsApp and other channels are **NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY**. Add a provider/channel adapter later; preserve a shared notification event model and consent/preferences now. AI can prepare a message but an authorized user or approved deterministic workflow sends it.

## 16. Security and authorization

### Identity lifecycle

- One sign-in experience; server maps the authenticated account to assigned roles. If multiple roles exist, user can choose an allowed workspace after authentication.
- Staff: admin invitation, verified identity, approved role assignment, activation/deactivation and revocation.
- Candidate: self-registration with email verification and consent; link to staff-created candidate profile via controlled verification.
- Client: invitation/onboarding by TYCHIFF staff; organization membership is explicit and revocable.
- Admin bootstrap is controlled and not an open signup path. Password reset and recovery are audited.

### Authorization

Use RBAC for broad abilities plus model policies/record-level checks for scope. Examples: recruiter assigned to requirement; candidate owns profile; client user belongs to client organization and sees only records explicitly shared with that organization; finance can see amounts/costs; management reports may aggregate restricted values. Validate every web route, API route, file download, export and background job with the relevant actor/resource scope. UI hiding is not authorization.

### Baseline protections

- Framework password hashing; secure, HttpOnly, SameSite session cookies; session rotation and revocation.
- CSRF protection, escaped HTML output, server-side validation and parameterized queries.
- Rate limits for login, registration, reset, upload/download, public intake, exports and AI calls.
- Secrets in deployment environment, never source or frontend files; separate keys by environment.
- Secure headers, HTTPS, least-privilege database/storage accounts, sanitized logs, dependency update process.
- Audited role/permission changes and privileged actions; generic login errors and rate-limited failed attempts.
- Import/export permission and audit; minimize PII in diagnostics and email.
- AI tool allow-list, scoped authorization, input/output limits, prompt-injection resistance, cost/rate limits.
- Backups encrypted/restricted; development uses synthetic/anonymized data, never casual production copies.

### Role baseline

Admin; management; recruiter/staff; client user; candidate. Fine-grained permission names and record scope require Phase 0 approval. Avoid a special public “admin signup” or a shared admin invitation code.

## 17. Audit and activity timeline

Audit important security and business events: authentication success/failure, account and role changes, profile updates, consent/share changes, duplicate merge, resume/document access, requirement/JD creation/version/approval/publication, candidate match and recruiter override, submissions, shortlist, client feedback, interview, selection, offer, joining, placement/assignment, replacement, invoice/payment/collection, exports, notification delivery, and AI actions.

Audit record fields: actor or system identity, action, entity type/ID, timestamp, outcome, request/correlation ID, changed fields or safe summary, source channel, and reason where required. Do not log passwords, bearer/session tokens, full resumes, or unnecessary sensitive information. Restrict audit browsing and protect records from ordinary edits.

The business activity timeline is a friendly projection of relevant audit/workflow events, not a separate competing source of truth. Tailor timeline content by role so internal-only notes and commercial information remain private.

## 18. KPI and reporting architecture

Create a metric dictionary before building charts. Every metric should define numerator, denominator, time window, source transaction, cohort/ownership rule, exclusions, and handling of reopened/paused/withdrawn cases. Store events/status history needed for historical calculations; do not rely on current status alone for time-based funnel reporting.

| KPI | Suggested definition | Source / policy notes |
|---|---|---|
| Requirements received | Count distinct requirements accepted/intaken in selected period | `requirements.created_at` or explicit received event; exclude drafts/test records; count reopened requirement once unless reporting reopen events separately. |
| Open requirements | Count requirements in active states at as-of date | Requirement state history; exclude paused/closed/cancelled; include paused in separate aging bucket. |
| Requirement aging | Days from accepted/intake date to as-of date or closure | Exclude time paused only if pause dates are recorded and policy defines net age; show gross age alongside if useful. |
| Candidates sourced | Distinct candidates with a valid source event in period | Candidate source history; de-duplicate candidate identity; do not equate imported rows with unique people. |
| Candidates screened | Distinct candidates with a recorded recruiter screening outcome | Screening event; include pass/hold/decline, exclude AI-only scores. |
| Submitted candidates | Distinct candidate-requirement submissions made in period | Submission event; withdrawn/resubmitted counts follow explicit event vs unique-pair reporting. |
| Shortlist rate | Submissions admitted to shortlist ÷ eligible submissions in same cohort/window | Define whether the cohort is submission-date or decision-date based; exclude duplicate/test submissions. |
| Interview rate | Submissions with at least one completed interview ÷ submissions in cohort | Cancelled/no-show interviews are reported separately; multiple rounds do not inflate unique candidates. |
| Selection rate | Submissions selected ÷ submissions with a final client decision | Pending/paused decisions excluded from denominator; rejected and selected decisions are distinct. |
| Offer acceptance | Offers accepted ÷ offers with final response | Expired/withdrawn/declined categories separately defined; count offer versions as one offer episode unless reissue policy says otherwise. |
| Joining ratio | Joined candidates ÷ accepted offers in cohort | Track no-show/withdrawal separately; cohort by accepted-offer date or planned start date must be selected. |
| Time-to-submit | Median days from accepted requirement date to first valid client submission | Use business/calendar days consistently; paused time handling documented; one first-submit per requirement. |
| Time-to-fill | Median days from accepted requirement to selected/filled event | Define filled as selection, accepted offer, or actual joining; recommended separate all three measures. |
| Recruiter conversion | Stage outcomes per eligible prior-stage cohort, attributed by approved ownership rule | Preserve ownership-at-event; distinguish source recruiter, submission recruiter and current owner. |
| Replacement rate | Placements/assignments replaced within agreed window ÷ eligible placements/assignments | Use actual agreement window and reason; only mature eligible cohorts in denominator. |
| Invoice aging | Outstanding balance grouped by days past due as of date | Invoice due date, credit notes, partial allocations and voids are reflected. |
| Payment realization | Amount collected ÷ amount due for a selected invoice/placement cohort | Define taxes/credits and whether denominator is issued or due amount; show aging and overdue separately. |
| Staffing utilization | Assigned capacity ÷ available capacity over period (if capacity/hours are captured) | Requires approved capacity/timesheet source; otherwise report active assignment count, not a misleading utilization percentage. |
| Client/source performance | Outcomes by client/source cohort | Minimum sample size, attribution, consent and source-provenance rules; avoid unsupported quality claims. |

Charts must drill to authorized records and provide accessible table/export alternatives. Cache aggregates only when needed and retain a clear source-of-truth path.

## 19. Search architecture

### Phase 1 foundations

- Structured database indexes on candidate skills, location, experience, availability, consent/share state, client, requirement owner/status, submission status, and date fields.
- Taxonomy model for skills, job families, industries, locations and aliases; allow controlled administrative updates and keep original candidate wording.
- Search service boundary so retrieval can evolve without changing ATS records.

### Phase 2 retrieval

- Structured SQL filters and exact/normalized keyword search.
- Resume/JD text extraction into versioned text records with source document and page/section references.
- MySQL/MariaDB full-text search where selected hosting database supports appropriate functionality.
- Search results filtered by user permissions, consent, candidate status and client-share scope before display.

### Later retrieval

Semantic/vector retrieval and external search store are **NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY**. Add embeddings only after a measured retrieval-quality need, privacy/provider review, cost decision, and Hostinger/data-region compatibility review. Keep provider/model/version, source document version and deletion/synchronization state so changes/deletion propagate.

## 20. Data/domain model recommendations

### Identity, organization and authorization

`users`; `roles`; `permissions`; `user_roles`; `role_permissions`; `client_memberships`; `staff_profiles`; `recruiter_profiles`; invitations; account status history; sessions/credentials as appropriate.

### Client and demand

`clients`; `client_contacts`; `client_sites`; `client_activity`; `client_agreements`/agreement versions; `requirements`; `requirement_status_history`; `requirement_attachments`; `jobs`; `job_descriptions`; `jd_versions`; `jd_approvals`; `published_jobs` (or publication record tied to an approved JD version).

### Candidate and recruitment

`candidates`; `candidate_accounts` linkage or user relation; `candidate_contacts`; `candidate_consents`; `candidate_consent_events`; `candidate_skills`; `skills`; `candidate_experiences`; `candidate_education`; `candidate_preferences`; `candidate_availability`; `candidate_pools`; pool membership; `candidate_documents`; `resume_versions`; parsed resume fields/evidence.

`candidate_submissions` (candidate + requirement + recruiter + submitted version/snapshot); `submission_status_history`; `candidate_job_matches`; `ai_match_runs`; `ai_match_evidence`; `recruiter_reviews`; `shortlist_memberships`; `interviews`; `interview_participants`; `interview_feedback`; `selection_decisions`; `offers`; offer versions; `placements`; joining records; candidate/client feedback; replacement cases.

### Staffing and finance

`staffing_requirements`; `staffing_contracts` or work orders; contract versions; `staffing_assignments`; `assignment_status_history`; allocation/availability periods; assignment renewals/extensions; replacement links; approved billing terms/rates with effective dates; `invoices`; `invoice_lines`; `payments`; payment allocations; credit notes/adjustments as needed.

### Platform and AI

`documents`; document versions; `document_access_logs`; `notifications`; delivery attempts; `audit_logs`; `workflow_events`; `ai_usage_records`; AI run/prompt/rubric/retrieval metadata; later `resume_text_versions`, `resume_chunks`, and embeddings via adapter.

### Data rules

- Use explicit primary keys (ULID/UUID recommended for public-facing opaque identifiers) and foreign keys for core relations.
- Use unique normalized login email; phone is not assumed globally unique.
- Avoid hard deletes for records with business or audit history. Use account deactivation, retention expiry, anonymization or controlled deletion process.
- Candidate profile status is independent from requirement submission status; one candidate can hold multiple simultaneous submission records.
- JD version approved by client is immutable; edits create a new version.
- Candidate resume version is immutable; each match/submission references the resume version considered.
- Submission uniqueness/idempotency should prevent accidental duplicate submissions to a requirement while preserving withdrawn/reopened history.
- Keep timestamps in a consistent timezone (UTC storage recommended); retain effective dates separately from record creation times.
- Use transactions for state changes and status-history writes; preserve audit events for sensitive changes.
- Use JSON only for provider payload snapshots or evolving auxiliary metadata, not as a replacement for relational workflow entities.

## 21. Future extensibility

Preserve extension points without implementing every future system now:

| Capability | Current disposition | Foundation to preserve |
|---|---|---|
| Payroll | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Assignment/time/billing facts separable; stable integration API and export permissions. Do not build payroll ledger now. |
| SSO / passkeys | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Identity-provider mapping, verified-email policy, role mapping allow-list, credential revocation, callback route boundary. |
| WhatsApp | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Channel adapter, user preferences/consent, template/event model, delivery status. |
| Tamil/localization | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Translation-ready strings, locale preference and date/number formatting; activate in Phase 4. |
| External job boards/sourcing | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Connector interfaces, source provenance, candidate deduplication, authorized API/contract review. |
| Vector/semantic retrieval | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Search adapter, versioned source text, embedding provider/model metadata, deletion propagation. |
| Multi-tenancy | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Keep organization/client scoping explicit. Do not add tenant isolation complexity unless TYCHIFF becomes a multi-customer SaaS. |
| Advanced automation | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Event/queue/notification boundaries and explicit authorized workflow actions. |
| Mobile application | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Versioned JSON API, stable identifiers, API authorization, consistent domain services. |
| Finance/payroll integrations | NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY | Separate invoice/payment/assignment records and secure adapter credentials. |

## 22. Phase 0–4 mapping

| Phase | Build or decide | Prepare without overbuilding | Exit evidence |
|---|---|---|---|
| **Phase 0 — Decisions & content** | Approve role/permission list; verify company facts/public content; confirm initial admin and candidate/client onboarding policies; confirm contract staffing scope and minimum billing model; confirm hosting/staging capability. | Approve workflow definitions, privacy wording/retention policy owner, data dictionary, KPI definitions to implement later. | Approved role list and real verified facts; decisions for Phase 1 schema/deployment recorded. |
| **Phase 1 — Foundation** | Laravel app skeleton, MySQL/MariaDB schema foundation, one sign-in experience, admin invitations, server-side RBAC + record policies, consent/privacy pages and data structures, audit, backup, private staging, secure logs, CI/deployment process as available. | Private document storage abstraction, notification/queue/scheduler boundaries, API conventions, taxonomies, staffing domain boundary, AI provider interfaces; synthetic seed data only. | Foundation works securely on private test site; wrong-role/record requests denied; backup restore demonstrated. |
| **Phase 2 — Core recruitment** | Client/requirement/RFP intake; JD builder, immutable versions, AI draft/recruiter review/client approval; candidate database, resumes, consent, duplicate resolution, candidate search, Agentic RAG match evidence, recruiter review, shortlist, submissions, interview scheduling/feedback, selection, scoped read-only client portal. | Full-text retrieval adapter, resume parser, notifications and usage/cost tracing. | At least one real requirement proceeds from client request through hiring decision as required; human review remains mandatory. |
| **Phase 3 — Staffing/placement/billing/KPI** | Offers, joining, feedback, replacement, placement invoice/payment, follow-up, transaction-derived KPI; staffing contracts, assignment lifecycle, renewal, allocation/utilization as the approved data supports. | Payroll connector boundary; expanded finance reporting/automation if business need justifies. | Track a placement through payment; demonstrate staffing assignment/billing lifecycle if in approved scope. |
| **Phase 4 — Advanced/integrations/growth** | SSO and/or passkeys; WhatsApp and email reminders; Tamil; SEO and verified public proof; integration polish. | Vector search, external sourcing/job portal/mobile integrations, broader automation only after security and volume review. | Public website launch with verified content; integrations and security controls validated. |

## 23. Dependencies between features

| Capability | Depends on |
|---|---|
| Recruiter/client/candidate portals | Accounts, role list, record-level policies, relationship/ownership model, audit |
| Candidate database and talent pools | Consent/retention model, controlled taxonomy, source provenance, private document store |
| Resume parsing and AI matching | Private document upload/scanning, resume versions, text extraction/evidence references, permission-aware search, provider abstraction |
| JD generation and approval | Requirement master, structured brief, immutable JD versions, authenticated client membership, approval history, AI provider abstraction |
| Candidate submission | Candidate consent/share eligibility, requirement/JD version, candidate-requirement uniqueness, recruiter ownership, client data projection |
| Interview and feedback | Submission, client/candidate access, notification/scheduler, time zone and status history |
| Offer/joining/replacement | Selection decision, placement record, agreement/replacement terms, audit history |
| Staffing utilization and billing | Contract/work order, staffing assignment dates/status, allocation/time or capacity source, effective billing terms |
| Invoice/payment KPIs | Invoice/payment transactions, credit/partial allocation logic, due dates, collection attribution |
| Recruiter KPIs | Timestamped event/status history, ownership attribution rules, stable metric dictionary |
| Client lead intake | Public form validation/rate limiting, privacy notice, lead follow-up owner and notification |
| SSO/passkeys and external APIs | Stable account identity, role mapping, secure sessions/API policy, environment-specific secrets and audit |
| Vector search | Parsed/versioned resume text, consent-aware retrieval, embedding provider decision, synchronization/deletion process |

## 24. Risks and architecture concerns

| Risk | Impact | Mitigation/design response |
|---|---|---|
| Demo authentication mistaken for security | Unauthorized access and false production confidence | Replace browser storage with Laravel sessions, authorization policies, and controlled account lifecycle. |
| Candidate master status used for all jobs | Loses concurrent/recent application context | Separate candidate master lifecycle from per-requirement submission state and history. |
| JD or resume edited in place | Makes client approval and AI evidence irreproducible | Immutable versions; each approval/match/submission stores version references. |
| Client sees internal notes or margins | Confidentiality and commercial harm | Separate client projection/resource policy; test every route, export, file and API scope. |
| AI score treated as decision | Unfair/opaque employment outcomes | Evidence, uncertainty, human review, override reason, no autonomous decisions. |
| Resume content contains prompt injection | Agent could be misdirected | Treat documents as untrusted data; tool allow-list, context isolation, no arbitrary action tools. |
| Resume/document leakage via public storage/logs | Personal-data exposure | Private storage, quarantine, server authorization, sanitized logs and download audit. |
| Weak duplicate merge | Lost candidate histories or consent | Reviewable match signals, controlled merge, preserve source IDs/submissions/consents. |
| Unclear contract staffing billing model | Phase 3 schema retrofit and incorrect invoices | Decide minimum model before Phase 1; distinct contracts/assignments/invoice lines. |
| Misleading KPIs | Bad management decisions and incentives | Metric dictionary and transaction-level lineage; publish denominator, cohort and exclusions. |
| Queue/mail/AI outages | Workflow delays or dropped reminders | Persist business action first, retry integration jobs, inspect failures, idempotent notifications. |
| Hostinger plan constraints | Queue, cron, storage, Composer or staging mismatch | Verify actual PHP/database/cron/storage/runtime capabilities before choosing deployment details. |
| Future expansion overbuilt too early | Cost and delay | Keep adapters and data boundaries, implement only justified phase features. |
| Reference design resemblance | Avoidable design/IP dispute risk | Independent TYCHIFF visual system; redesign recruiter dashboard and retain asset provenance. |
| Unverified marketing/security claims | Trust, commercial, privacy or legal exposure | Publish factual, approved and substantiated claims only. |
| Production data in development | Privacy/security exposure | Synthetic/anonymized fixtures; separate credentials, stores and backups. |

The previous reference analysis identified a **high potential visual-similarity concern**: the current recruiter dashboard and a reference screenshot share a notable combined arrangement of KPI cards, compact operational metrics, funnel, live activity, and AI insight. This is not a legal conclusion. Redesign around TYCHIFF’s task-first work queue and independently chosen visual hierarchy before public release.

## 25. Features missing from the current plan

The existing Phase 0–4 requirements cover the foundation and core hiring loop but leave some useful product details implicit. Add the following to planning/design; these do not require a change from Laravel/MySQL/modular monolith:

1. Candidate resume versioning and explicit matching/submission references to the considered version.
2. Candidate availability, interest, communication preferences, source provenance, tags and talent pools.
3. Candidate-client sharing ledger and explicit consent withdrawal/retention states.
4. Candidate/requirement duplicate submission prevention, candidate merge history and multi-requirement concurrency.
5. Client organization hierarchy, multiple contacts/sites, authorized portal membership and account history.
6. Requirement intake type and document capture for RFP/RFQ/RFI; agreement/SLA metadata with private versioned files.
7. Detailed follow-up/task queue with owner, due date, outcome, and event-based reminders.
8. Interview round participants, time zones, confirmations, reschedules, feedback and cancellation reasons.
9. A distinct selection decision record, not an AI score or mere interview status.
10. Distinct placement vs contract staffing assignment lifecycle; renewal, allocation and effective billing terms.
11. Invoice lines, partial payment allocation, credit/void history and payment realization rules.
12. KPI definitions/ownership attribution and event history sufficient for trend analysis.
13. Controlled taxonomies and aliases for skills, jobs, industries, locations and sectors.
14. Document scan state, private access, version lineage, access audit and restore requirements.
15. Export/deletion/anonymization operations and permissioned audit/retention tooling.
16. AI run/retrieval traceability fields and human decision/override record.
17. Accessibility, mobile-first key tasks, explicit empty/error/loading states, and tabular chart alternatives.
18. Integration/API idempotency, webhook/event delivery records, notification preferences and failure review.

## 26. Recommended implementation priority

Priority labels: **P0** decisions/foundation blockers; **P1** core product; **P2** quality and productivity; **P3** future expansion. Build order remains phase-based.

| Priority | Capability | Why now/later |
|---|---|---|
| P0 | Role/permission matrix, initial admin, verified company facts, deployment/staging capability, contract staffing scope/billing boundary | Phase 0 explicitly requires role approval and real facts; these decisions shape Phase 1 access/schema/deployment. |
| P0 | Server-side auth, record-level authorization, audit, consent/privacy, backups and private staging | Phase 1 completion criteria and sensitive-data foundation. |
| P1 | Client, requirement, JD/JD version/approval, candidate/resume/consent/duplicate, submission, interview and selection | Makes the Phase 2 end-to-end hiring loop operational. |
| P1 | Candidate search, availability, source/talent pool, evidence-based Agentic RAG and recruiter review | Core productivity and matching value, with required human authority. |
| P1 | Client portal with approved visibility; candidate portal for profile/status/interviews/consent | Improves client/candidate service while reducing status-chasing. |
| P1 | Offer, joining, placement, feedback, replacement, invoice/payment and real KPI | Phase 3 outcome tracking and money realization. |
| P2 | Follow-up task queue, reminders, client timeline, skill normalization, document parsing/review, responsive/accessibility refinements | Improves operational quality and convenience; depends on core records. |
| P2 | Contract assignment utilization and profitability reporting | Requires business definitions and capacity/cost sources; only report measures supported by data. |
| P3 | External sourcing, WhatsApp, SSO/passkeys, Tamil, semantic/vector search, mobile app, advanced automation, payroll integration | Preserve extension points; implement after phase priorities and provider/operational review. |

## 27. Final architecture validation

**The current Laravel + MySQL/MariaDB + modular monolith + Agentic RAG architecture can support the complete proposed roadmap.** No framework, database family, frontend framework, or service architecture change is justified by this review.

Required architecture refinements are domain-level, not platform-level:

- Model `Candidate` separately from `CandidateSubmission` and all requirement-specific states.
- Model `Requirement`, `JD`, `JDVersion`, `PublishedJob`, `Interview`, `SelectionDecision`, `Placement`, `StaffingAssignment`, `Invoice`, `Payment/Collection`, and `ReplacementCase` as distinct records with explicit relationships and histories.
- Add contract staffing as a proper module/domain with separate contract/work-order and assignment records. Preserve a future payroll integration boundary without implementing payroll.
- Keep AI, document parsing, notifications, search, and external integrations behind application interfaces, with explicit queues, logs, audit and permission checks.
- Keep client and candidate record projections separate from internal recruiter/finance data.

The Phase 1 foundation should implement only identity, permissions, database foundation, privacy/consent structure, audit, backups, secure private staging, and public site adaptation. Build the actual recruitment loop in Phase 2 and placement/staffing money flows in Phase 3. Phase 4 handles integrations and public growth. This gives the system a future-ready shape without implementing all future capabilities early.

## 28. Questions/decisions requiring approval before Phase 1

Only the following decisions materially affect Phase 1 schema, permissions, or deployment. Recommended choices are given to make approval straightforward.

### Decision 1 — Initial role and permission matrix

- **Recommendation:** approve Admin, Management, Recruiter/Staff, Client User and Candidate roles, then approve least-privilege permission sets and record scopes (recruiter assignment scope; client organization + explicitly shared submissions; candidate self-only).
- **Alternative:** begin with fewer roles, but still define permission-level boundaries and client/candidate scope before development.
- **Why before Phase 1:** Phase 0 explicitly requires role/permission approval and Phase 1 must enforce it server-side.

### Decision 2 — Contract staffing and initial billing model

- **Recommendation:** confirm contract staffing in Phase 3 with distinct `StaffingContract/WorkOrder` and `StaffingAssignment` records; define whether first billing is fixed monthly, timesheet-based, milestone-based, or mixed. Keep payroll outside the ATS.
- **Alternative:** scope Phase 3 to permanent recruitment placement only and defer contract staffing as a separate approved extension.
- **Why before Phase 1:** the domain boundary and core assignment relations should be reflected in the foundation model; otherwise Phase 3 may retrofit assignment concepts into placement records.

### Decision 3 — Hostinger deployment capability

- **Recommendation:** use the intended Hostinger plan only if it supports the required Laravel/PHP runtime, Composer deployment, MySQL/MariaDB, private storage, scheduled tasks, queue execution strategy, backups, and a private staging environment. Verify the actual plan before fixing deployment details.
- **Alternative:** keep Hostinger for the public site and select a compatible private application host if the current plan cannot meet the requirements.
- **Why before Phase 1:** private staging is a Phase 1 completion condition, and document/queue/scheduler design depends on host capabilities.

Other policies—candidate retention duration, consent wording, public content, and detailed billing rules—must be approved before the relevant production workflow or publication. They can be designed with responsible owners during Phase 1; real candidate data should not be used before the applicable privacy wording and retention policy are approved.

## A. MUST HAVE

- Secure server-side identity, role/permission and record-level authorization.
- Client, requirement, JD/JD-version/approval, candidate/resume/consent, candidate-submission, interview/feedback, selection, offer/placement, staffing assignment, invoice/payment and replacement records as distinct concepts.
- Candidate consent, sharing controls, duplicate detection/review, private document storage, audit and retention handling.
- Recruiter task queue, candidate search, evidence-backed Agentic RAG assistance, human review, client-scoped portal, candidate self-service.
- Operationally defined KPIs based on transactional records; secure backups, private staging and production/dev separation.
- Contract staffing as a separate Phase 3 domain if confirmed, with explicit assignment history and billing terms.

## B. SHOULD HAVE

- Resume versions and parse/evidence lineage; candidate pools, tags, availability and source attribution.
- RFP/RFQ/RFI intake, client/site/contact hierarchy, agreement metadata and activity timelines.
- Follow-up tasks, SLA aging, notifications, accessible mobile interactions, search aliases and reviewer correction workflows.
- Client feedback/approval actions constrained by permissions; management reports with KPI definitions and drill-through.

## C. FUTURE

- SSO/passkeys; WhatsApp; Tamil/localization; approved job-board/source integrations; mobile app; vector/semantic retrieval; advanced automation; multi-tenancy; payroll/finance integration.
- Preserve extension points, but implement only after business value, consent, provider access, security and operational readiness are clear.

## D. NOT RELEVANT

- A separate public career diagnostic/simulation product and generic candidate “AI-proof” score do not materially serve the current ATS/staffing objective. If TYCHIFF later adopts a distinct career-services product, assess it separately with its own consent, data and AI review.
- SaaS subscription pricing tiers are not relevant to the current recruitment/staffing-service platform model. Revisit if TYCHIFF decides to sell the software as a SaaS product.

## E. ARCHITECTURE DECISIONS REQUIRED BEFORE PHASE 1

1. Approve the initial roles, permission matrix and record scopes.
2. Confirm whether contract staffing is in Phase 3 and the minimum initial billing/assignment model.
3. Verify Hostinger supports private Laravel staging and the required database, storage, scheduler, queue and backup approach.

## F. FINAL RECOMMENDATION

Proceed with the approved **Laravel + MySQL/MariaDB + modular monolith + Agentic RAG** architecture after the three decisions above are resolved. The architecture supports the proposed recruitment, staffing, client, candidate, management, audit, reporting, document, AI and future integration roadmap. Add staffing contracts and assignments as an explicit domain and preserve payroll as an integration boundary; do not turn Phase 1 into a premature implementation of every later feature.

**ANALYSIS ONLY — NO APPLICATION OR SOURCE FILES MODIFIED.**
