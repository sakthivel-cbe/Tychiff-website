# TYCHIFF Final Product Gap Analysis

**Purpose:** Final product-discovery review before a Phase 1 approval decision  
**Project baseline:** `TYCHIFF_MASTER_FEATURE_UI_UX_BLUEPRINT.md`; `TYCHIFF_Phase_0_to_4_Requirements.docx`; current static TYCHIFF prototype; `TYCHIFF_Reference_Website_360_Degree_Gap_Analysis.md`  
**Review date:** 25 October 2026  
**Scope:** Analysis and recommendations only. No implementation, migrations, or application/source-file changes.

## Executive summary

The Master Blueprint already covers most of the strategic product foundation: a Laravel/MySQL modular monolith, server-side security, candidate consent, private documents, distinct candidate and submission states, JD approval, client portal, Agentic RAG, human review, audit, phase mapping, and a separate contract-staffing domain.

The remaining product gaps are mostly **operational detail** rather than a reason to change the architecture. The most important additions are:

1. Structured interview kits, interview scorecards, and a consistent debrief workflow.
2. Candidate self-service for interview availability/scheduling, job alerts, and clear status/closure messages.
3. Recruiter task queues with reusable task/SLA rules, assignment/escalation, and overdue-work visibility.
4. Contract worker onboarding, time/expense submission and approval, assignment extension/offboarding, and billing reconciliation.
5. Controlled bulk/import/export tools and a data-quality work queue.
6. Operational support for failed queue/notification/integration events, retention/anonymization jobs, and security incident response.
7. AI quality evaluation, fairness review, prompt/model change controls, and explicit limits on sensitive-data use.
8. Business metrics for source yield, stage aging, candidate experience, contract staffing utilization, billing accuracy, and cash collection.

These recommendations fit the approved architecture. Phase 1 should define the foundations and interfaces; it should not implement every workflow. The present Phase 1 architecture remains appropriate, with the previously identified approval still outstanding for contract staffing’s initial time/billing model and the other Phase 1 items in the Blueprint (role/permission matrix and verified hosting/staging capability).

## Scope and method

- Reviewed the full Master Blueprint and the prior 360° reference gap analysis, including its review of all 21 screenshots.
- Rechecked the Phase 0–4 requirements and current project structure. The current application remains a static/browser demo: seeded auth/session and JD data use browser storage, portals contain example data, and there is no production Laravel backend or durable ATS database.
- Compared blueprint coverage against the business lifecycle and operational needs in this task.
- Consulted official ATS and staffing product documentation for established workflow concepts (structured interview scorecards, candidate rediscovery, self-scheduling, pipeline action visibility, assignment/time/billing) and OWASP application-security references. These are examples of useful patterns, not proof that TYCHIFF needs identical products or interfaces.
- “Missing” means the Blueprint does not yet specify the capability to a product/operational level sufficient for design. It does not mean the feature is absent from every existing system; the current site has no production backend.

### Classification

- **MUST HAVE:** needed for a credible, safe core workflow in its target phase.
- **SHOULD HAVE:** materially improves productivity, experience, or control; schedule after the blocking core slice or within the target phase if capacity allows.
- **FUTURE:** useful but outside the present phase; retain an extension boundary.
- **NOT RELEVANT:** does not serve the current TYCHIFF recruitment/staffing objective. This is not a statement that another product’s feature is bad.

## 1. Missing ATS capabilities

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Structured interview plan and scorecards:** define competency/requirement criteria, interviewer assignments, role-specific questions, independent ratings, evidence/comments, and an overall recommendation. | Recruiters, interviewers, clients where authorized; improves consistent feedback and makes a decision reviewable. Official Greenhouse guidance describes job criteria, interviewer scorecards, and structured hiring as a connected workflow ([scorecards](https://support.greenhouse.io/hc/en-us/articles/4414777492891-Scorecard-overview), [structured hiring](https://support.greenhouse.io/hc/en-us/articles/360039539772-Structured-hiring-guide)). | MUST HAVE | P2 | Requirement/JD version, interview plan, interviewer permissions, submission and interview records. |
| **Interview debrief and feedback deadline:** gather submitted scorecards, show outstanding feedback, record the authorized decision and rationale, and prevent hidden/unsubmitted feedback from being mistaken for consensus. | Recruiter and hiring decision owner; reduces slow and inconsistent decisions. Scorecard visibility can be controlled by permissions in established ATS workflows ([Greenhouse scorecard visibility](https://support.greenhouse.io/hc/en-us/articles/4414777492891-Scorecard-overview)). | MUST HAVE | P2 | Interview scorecards, role policies, decision history, notifications. |
| **Candidate pipeline action queue and time-in-stage:** surface owner, next required action, elapsed stage time, overdue feedback, and whether the next move awaits TYCHIFF, candidate, or client. | Recruiters, coordinators, managers; prevents stalled submissions and clarifies responsibility. Pipeline interfaces commonly distinguish pending internal action, pending feedback, and waiting states ([Greenhouse pipeline](https://support.greenhouse.io/hc/en-us/articles/4874727408795-Visual-Candidate-Pipeline)). | MUST HAVE | P2 | Submission/interview status history, task ownership, due dates, policy-filtered candidate view. |
| **Talent rediscovery and prior outcome filters:** search past applicants/prospects by skills, source, prior stage, feedback, role, date and availability; avoid repeatedly sourcing from scratch. | Recruiters; increases reuse of existing, consent-eligible candidate data. ATS documentation demonstrates searching past applicants/prospects with application, profile and keyword filters ([Greenhouse talent rediscovery](https://support.greenhouse.io/hc/en-us/articles/30184390692379-Talent-Rediscovery)). | SHOULD HAVE | P2 | Candidate/submission history, searchable resume text, consent/retention state, access policies. |
| **Requirement approval and fulfillment plan:** record approved headcount, priority, budget/fee or rate context, target dates, client sponsor, delivery owner and approval before sourcing begins. | Client owner, recruiter, management; reduces work on incomplete or unauthorized demand and improves pipeline forecasts. | SHOULD HAVE | P2 | Client/requirement, approval policy, commercial agreement metadata, status history. |
| **Candidate submission packet and snapshot:** retain the candidate/profile fields, resume version, JD version, consent/share state, and submission text actually sent to client. | Recruiter, client and audit reviewer; prevents later profile edits from changing historical meaning and supports clean client review. | MUST HAVE | P2 | Candidate/resume versions, requirement/JD, consent ledger, client projection and document sharing. |
| **Candidate ownership/conflict management:** identify candidate source/owner, duplicate client introductions, prior submission history, recruiter handoffs, and client-specific representation rules. | Recruiters and managers; reduces duplicate outreach, conflicts, and disputes over candidate ownership. | SHOULD HAVE | P2 | Candidate source/contact history, submissions, client records, merge/audit policy. |
| **Requisition cancellation, hold, reopen and replacement reason codes:** distinguish canceled by client, paused, filled, withdrawn, duplicated, or reopened; preserve dates and reason. | Recruiter and management; yields accurate aging/conversion reports and prevents misleading funnel counts. | MUST HAVE | P2 | Requirement state machine and status history; approved KPI definitions. |
| **Job publishing lifecycle and application receipt:** create a public job from an approved JD version, manage publish/close/expiry, receive candidate applications, acknowledge receipt, and connect application to an existing candidate safely. | Candidate and recruiter; completes the public jobs path that is currently only a static sample page. | MUST HAVE | P2 | Approved JD, published-job projection, application intake, candidate consent, duplicate review, spam protection. |
| **Interview availability and self-scheduling option:** candidate proposes available windows or chooses from recruiter-approved slots; allow controlled reschedule/cancel requests and confirmations. | Candidate, recruiter, interviewer; reduces back-and-forth and makes scheduling clearer. Established ATS scheduling workflows support candidate self-scheduling, availability and confirmations ([Greenhouse self-scheduling](https://support.greenhouse.io/hc/en-us/articles/4409534677915-Send-a-self-schedule-request)). | SHOULD HAVE | P2/P4 integration | Interview stages, candidate communication preference, calendar adapter, time zones, reschedule policy. |
| **Recruiter/interviewer interview kit:** present role context, resume/JD snapshot, interview focus areas, schedule, questions, and private/visible note guidance in one place. | Interviewer and recruiter; improves preparation, structured feedback, and privacy separation. Interview kits are a documented recruiting workflow pattern ([Greenhouse interviewer guide](https://support.greenhouse.io/hc/en-us/articles/115002226826-Interviewer-guide-Using-interview-kits)). | SHOULD HAVE | P2 | JD version, resume version, interview plan, permissioned scorecards. |

## 2. Missing recruiter capabilities

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **My Work queue with prioritization:** one actionable list across requirements, candidate follow-ups, interviews, client responses, missing consent, and expiring assignments. | Recruiters/coordinators; cuts context switching and makes the next action obvious. | MUST HAVE | P2 | Task model, due dates, assignments, notifications, record-level access. |
| **Task templates and service-level rules:** reusable tasks and due offsets for intake, candidate screening, client submission, interview feedback, offer, joining and invoice follow-up. | Operations lead and recruiters; gives repeatability while allowing client/service-specific targets. | SHOULD HAVE | P2 | Workflow events, configurable rules, scheduler, escalation policy. |
| **Workload and capacity view:** open requirements and pending actions by owner, age, priority and estimated effort; support controlled reassignment. | Recruiter lead/management; helps balance workload and expose risks before a request is late. | SHOULD HAVE | P2/P3 | Assignment history, task queue, workload definitions, permissions. |
| **Communication history and templates:** log inbound/outbound email/call/WhatsApp references, outcome, next step and reusable approved templates; preserve provider delivery state. | Recruiter and coordinator; prevents “what did we tell the candidate/client?” gaps and reduces repetitive writing. | MUST HAVE for communication log; SHOULD HAVE for provider sync/templates | P2; sync later | Contact preferences/consent, notification abstraction, templates, audit/timeline. |
| **Searchable notes with visibility classes:** private recruiter note, team note, interviewer note, client-visible note, candidate-visible message—distinct labels and permissions. | Recruiters, interviewers, clients; reduces accidental disclosure while keeping context findable. | MUST HAVE | P2 | Note schema, client/candidate projections, access policy and audit. |
| **Recruiter sourcing and follow-up productivity aids:** saved searches, recent searches, reusable candidate lists, bulk follow-up task creation, and “last verified” profile prompts. | Recruiters; improves repeat work without storing unauthorized data or spamming candidates. | SHOULD HAVE | P2 | Consent-aware search, saved-view ownership, bulk-action controls. |
| **Candidate response/availability refresh:** ask a candidate to confirm interest, salary range, notice period, location and resume currency before submission; expire stale answers. | Recruiter and candidate; reduces avoidable client declines and stale candidate representations. | MUST HAVE | P2 | Candidate portal/secure links, timestamped preferences, consent, communication templates. |
| **Safe bulk operations:** batch assign, tag, request availability, close stale requirements, or schedule follow-ups, with preview, per-row results and undo where safe. | Recruiters/admin; saves time in high-volume work while avoiding accidental mass changes. | SHOULD HAVE | P2/P3 | Policies, idempotent actions, audit, selection preview, transactional handling. |

## 3. Missing candidate capabilities

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Candidate job alerts and saved preferences:** candidate selects role/skill/location/work-mode preferences and receives relevant new-job notifications they can control. | Candidate; supports return engagement and relevant applications. | SHOULD HAVE | P2/P4 | Published jobs, preferences, notification consent, suppression/unsubscribe. |
| **Application receipt and status explanations:** acknowledge each application/submission and show candidate-safe stages, expected next step, responsible contact and delay messaging. | Candidate; lowers uncertainty and inbound “any update?” queries. | MUST HAVE | P2 | Candidate-submission model, candidate-safe status mapping, notification templates. |
| **Self-service interview availability/reschedule:** candidate supplies availability or selects/reschedules approved slots, with clear timezone and confirmation. | Candidate; reduces scheduling friction and missed interviews. | SHOULD HAVE | P2/P4 | Interview scheduling, calendar integration, contact consent, policy limits. |
| **Consent and profile controls:** view accepted notice/purposes, update contact preferences, withdraw consent, request correction, and understand what is shared and with whom. | Candidate; gives understandable control over personal data and sharing. | MUST HAVE | P1 foundation; P2 user journey | Consent/notice versions, sharing ledger, retention/deletion process, authenticated portal. |
| **Document checklist and expiry reminders:** see requested/received/rejected documents and expiry dates for role/assignment requirements; upload replacements securely. | Candidate and staffing coordinator; reduces joining/onboarding delays. | SHOULD HAVE | P2/P3 | Private document store, document categories/expiry, assignment/onboarding workflow, reminders. |
| **Candidate communication preferences and contact history:** choose channels/language and quiet/suppression preferences; see important messages and response actions. | Candidate; makes contact more respectful and clear. | SHOULD HAVE | P2 | Notification abstraction, preference/consent model, candidate portal. |
| **Accessible profile completion and resume correction:** explain parsed fields, let candidate correct skills/history and indicate source/verification state. | Candidate/recruiter; improves data quality and reduces errors in AI matching. | SHOULD HAVE | P2 | Resume parsing, provenance, candidate profile edit policy and audit. |
| **Candidate feedback/experience pulse:** short optional survey at key stages (post-interview, offer, joining), with privacy-aware aggregation and response follow-up. | Candidate, management; detects candidate experience problems, not just placement throughput. | SHOULD HAVE | P2/P3 | Feedback schedule, candidate contact preferences, survey privacy and reporting rules. |

## 4. Missing client capabilities

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Client action center:** outstanding JD approval, candidate review, interview feedback and decision requests with due dates and clear authorized approver. | Client contact and recruiter; reduces status-chasing and approval delays. | MUST HAVE | P2 | Client memberships/permissions, approval records, due dates, reminders. |
| **Secure submission review and compare:** authorized client reviewers can compare submitted profiles against agreed role criteria, add comments, request interview, hold or decline with reason. | Client and recruiter; moves review from email attachments into a traceable, private workflow. | MUST HAVE | P2 | Candidate submission snapshots, client policy, candidate sharing consent, decision history. |
| **Delegated client roles and approver routing:** client admin assigns hiring manager, interviewer, approver and read-only roles; one approver cannot grant access outside that organization. | Client admin and TYCHIFF admin; supports real client organizations with multiple contacts. | SHOULD HAVE | P2 | Client organization, memberships, role policies, invite lifecycle and audit. |
| **Client-specific SLA and delivery dashboard:** agreed response/feedback targets and open actions by requirement, with transparent definitions. | Client owner and management; helps improve service and set mutual expectations. | SHOULD HAVE | P2/P3 | Agreement/SLA metadata, status timestamps, client-safe report projection. |
| **Client document exchange:** securely share/review JD, RFP, agreement and approved candidate documents with version, expiry and access history. | Client and account manager; reduces uncontrolled email attachments. | MUST HAVE for approved documents | P2/P3 | Private document storage, per-document share grants, versioning, download audit. |
| **Client notification controls and digest:** set recipients/frequency for review requests and reminders; send concise notifications that link to the portal. | Client and recruiter; helps adoption without over-notifying. | SHOULD HAVE | P2 | Client membership, notification preferences, queued mail, delivery logs. |
| **Client decision history and handoff:** see who made a decision and when, while hiding internal notes and candidate data outside client scope. | Client and account manager; supports transparent service handoffs and avoids lost approvals. | MUST HAVE | P2 | Audit events, decision records, access projection. |

## 5. Missing management capabilities

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Business development/client opportunity pipeline:** track prospect, lead source, service need, expected start/value, owner, next step, probability and conversion to client/requirement. | Management/client owners; helps forecast demand and avoid losing pre-requirement business. | SHOULD HAVE | P2/P3 | Client prospect/account lifecycle, activities/tasks, approved commercial data model. |
| **Demand-to-capacity forecast:** compare open/likely requirements with recruiter and candidate-pool capacity; highlight uncovered roles and sourcing gaps. | Management and recruiter leads; improves staffing plans and client commitments. | SHOULD HAVE | P3 | Requirement forecasts, ownership/workload, candidate availability/taxonomy. |
| **Margin and revenue forecast by service line/client/assignment:** distinguish invoiced, collected, expected, and earned amounts; permission cost data. | Management/finance; supports sustainable recruitment and staffing growth. | MUST HAVE for staffing billing/reporting; SHOULD HAVE for forecast | P3 | Contracts, placements/assignments, rate/cost policy, invoices, payment allocations. |
| **Client health and concentration:** active demand, conversion, overdue feedback, payment aging, repeat business, replacement and service issues. | Management/account owner; surfaces account risk and service improvement opportunities. | SHOULD HAVE | P3 | Client history, delivery outcomes, feedback, finance, consistent definitions. |
| **Source and channel yield:** compare candidate sources by screened, submitted, interviewed, joined and retained outcomes, plus cost if recorded. | Management/recruiter leads; helps prioritize lawful, productive sourcing channels. | SHOULD HAVE | P3 | Source attribution history, submissions, stage events, optional source cost. |
| **Forecast confidence and data-quality indicators:** show incomplete/stale candidate data, unconfirmed assignments, missing invoice fields, or unapproved requirements alongside forecast totals. | Management; prevents false precision and improves operational data quality. | SHOULD HAVE | P3 | Data validation rules, audit/status history, report definitions. |
| **Scheduled reports and governed exports:** define recipients, secure delivery, saved filters, expiration and audit; avoid emailing sensitive raw datasets by default. | Management/finance; automates routine reporting while protecting PII. | FUTURE | P3/P4 | Permissioned report service, export audit, notification security. |

## 6. Missing contract staffing capabilities

The Blueprint already correctly separates staffing contracts and assignments from placements. The gaps below fill out a usable contract-workforce lifecycle. Staffing platforms commonly connect placement/assignment data to time capture, approvals, billable charges and invoicing; payroll integration is a distinct downstream concern ([Bullhorn Middle Office guide](https://kb.bullhorn.com/bhone/Content/BH1/Topics/middle-office-field-guide.htm), [time-entry settings](https://kb.bullhorn.com/backoffice/Content/backoffice/Topics/timeEntryIntegrationSettings.htm)). This supports the domain distinction; it does not require TYCHIFF to build a payroll system.

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Worker onboarding checklist:** assignment-specific document collection, client/site orientation, start confirmation, equipment/access readiness and responsible owner. | Worker, recruiter, staffing coordinator and client contact; prevents “joined in system but not cleared to start” gaps. | MUST HAVE for contract deployment | P3 | Staffing contract/assignment, private documents, notification/task service. |
| **Assignment change lifecycle:** extension, rate change, site/team transfer, allocation change, leave/absence, early end, termination and offboarding, all effective-dated. | Staffing coordinator, worker, client and finance; maintains correct active roster and billing history. | MUST HAVE | P3 | Assignment status history, approvals, agreement/version and audit. |
| **Timesheet/time capture and approval:** submit hours/units against an assignment and period; route to client/manager approval; handle corrections, rejection and lock/close periods. | Worker, client approver, staffing coordinator, finance; supplies reliable billable quantities and operational attendance evidence. | MUST HAVE if billing depends on time; otherwise FUTURE | P3; boundary decision before P1 | Assignment, approved work calendar, period/billing terms, approver policy, audit. |
| **Expense submission and approval:** worker submits assignment-related expense with receipt; approver accepts/rejects and maps approved items to invoice lines. | Worker, client approver and finance; improves cost recovery and audit. | SHOULD HAVE | P3 | Assignment, document storage, expense policy, invoice lines and approvals. |
| **Billing reconciliation workbench:** compare approved time/units/rate/period against contract terms; identify exceptions, missing approvals, duplicate charge and bill-ready items before invoice. | Finance and staffing coordinator; reduces invoice disputes and revenue leakage. | MUST HAVE for time-based billing | P3 | Timesheets/approved units, effective rates, invoice line generation, exception queue. |
| **Availability, allocation and redeployment:** record worker capacity/availability between assignments, forecast end dates and suggest consent-appropriate future assignments. | Staffing coordinator and worker; improves utilization and redeployment. | SHOULD HAVE | P3 | Assignment dates/allocation, availability, candidate consent/preferences, search. |
| **Credential/certification and site-access expiry:** track document/credential type, verified status, expiry, renewal reminder and site/assignment requirement. | Staffing operations and worker; prevents assignment readiness gaps and expired evidence. | MUST HAVE when client/site requires it | P3 | Document metadata, assignment requirement checklist, private reminder workflow. |
| **Renewal and end-date dashboard:** notice dates, client decision, worker confirmation, rate review, renewal or planned end. | Account manager, staffing coordinator and finance; prevents avoidable assignment gaps and billing ambiguity. | MUST HAVE | P3 | Contract/assignment effective dates, agreement terms, tasks and notifications. |
| **Payroll integration contract:** export authorized assignment/time data to a payroll system and reconcile acknowledgements/errors without storing payroll calculations in ATS. | Finance/operations; preserves future payroll option without dragging payroll into core ATS. | FUTURE | P4 | Stable API/export, assignment IDs, approved time, integration audit and retry queue. |

### Staffing decision detail

The Phase 1 decision should establish whether TYCHIFF needs time-based billing at launch of its staffing module. If yes, include timesheet periods, submit/review/approve/correct states, and billable quantities in the Phase 3 plan. The database should not hard-code a single billing frequency. Payroll formulas, statutory deductions, bank details and payroll ledgers remain **NOT IN CURRENT SCOPE — KEEP FUTURE EXTENSIBILITY**.

## 7. Missing AI / Agentic RAG capabilities

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Requirement/JD quality gate:** check mandatory fields, contradiction, vague must-haves, unrealistic ranges, location/notice/salary mismatch, and unapproved discriminatory or unsupported criteria; return a checklist, not a final verdict. | Recruiter/client; reduces rework and improves retrieval quality. | MUST HAVE | P2 | Requirement/JD versions, approved rubric, reviewer action record. |
| **Candidate data confidence and freshness:** distinguish verified, candidate-stated, parser-extracted and inferred values; flag stale availability/compensation/experience. | Recruiter; avoids treating parsed or old data as fact. | MUST HAVE | P2 | Field provenance, candidate correction, timestamps, resume versions. |
| **Search-plan transparency:** show which structured filters, keywords, semantic retrieval and exclusions were used; let recruiter broaden/narrow search. | Recruiter; makes agentic retrieval understandable and controllable. | SHOULD HAVE | P2 | Search adapter, retrieval run trace, permission-aware filters. |
| **Agent run budget and stop conditions:** limit tool calls, retrieved records, time and cost; stop and ask for human input on ambiguous/high-risk criteria. | Admin/recruiter; prevents runaway cost and opaque loops. | MUST HAVE | P2 | AI orchestration, rate limit, usage ledger, failure state. |
| **AI evaluation and release gate:** use reviewed synthetic/anonymized cases to measure retrieval relevance, evidence accuracy, missing-data handling, false positives and regression when prompts/models change. | Product owner/admin; improves reliability before a model or prompt reaches recruiters. | MUST HAVE before production AI roll-out | P2 | Versioned prompts/rubrics, replayable trace, evaluation set and sign-off. |
| **Fairness and proxy review:** inspect criteria/outputs for sensitive-attribute use, proxy variables, disparate outcome patterns and unexplained ranking effects; record review/mitigation. | Management/AI owner; helps prevent harmful or unjustified decision support. | MUST HAVE governance; recurring analysis SHOULD HAVE | P2 onward | Data minimization, rubric governance, audit and human outcomes. |
| **Source-grounded summaries:** provide claim-level links to resume section/page, interview scorecard or structured record; label generated synthesis vs source text. | Recruiter/interviewer; enables rapid review without trusting fabricated summaries. | MUST HAVE | P2 | Versioned source documents, evidence storage, output schema. |
| **AI feedback loop:** recruiter can mark evidence wrong/missing and correct extraction; retain correction as data-quality feedback, not automatic model training. | Recruiter/admin; improves skills normalization and parsing quality safely. | SHOULD HAVE | P2/P3 | Provenance, human correction UI, controlled taxonomy. |
| **AI prompt/model change approval:** test, version, approve, roll back and log provider/model/prompt/rubric configuration changes. | Admin/AI owner; makes model changes accountable and reversible. | MUST HAVE | P1 prepare; P2 activate | AI provider abstraction, configuration audit, evaluation runs. |
| **AI redaction/retention settings:** control PII sent externally, allowed document classes, provider retention options, and per-task processing policy. | Privacy/security owner; reduces unnecessary data exposure and aligns actual provider setup to approved policy. | MUST HAVE before production AI | P2 | Provider contract/config, data classification, consent and logs. |
| **Agent permissions and human checkpoints:** each retrieval tool inherits caller scope; high-impact action requires an authorized user confirmation; logs tool calls and handoff. | All users; keeps Agentic RAG bounded and auditable. | ALREADY COVERED in architecture; implementation gate | P1 prepare/P2 build | Policies, tool registry, audit, workflow services. |

Do not add conversational autonomy as a product goal. Use agents to plan retrieval and prepare evidence; keep submissions, status changes, external contact, publication, hiring and rejection under authorized human workflows.

## 8. Missing UI/UX capabilities

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Keyboard-first recruiter navigation and shortcuts:** quick search, move through candidate list, open next task, save feedback. | High-volume recruiter/interviewer; reduces repetitive mouse navigation. | SHOULD HAVE | P2/P4 | Stable task/list views, accessibility review, shortcut discoverability. |
| **Saved filters/views and shareable team queues:** remember a private or team-approved search/worklist. | Recruiters/managers; shortens repeated searches and aligns team operations. | SHOULD HAVE | P2 | Search/filter system, ownership/permissions, view versioning. |
| **Global command/search entry point:** search candidates, requirements, clients, jobs, assignments and invoices with result-type filters and authorization. | All internal roles; faster navigation as records grow. | SHOULD HAVE | P2/P3 | Search service, access policies, indexing and audit rules. |
| **Visible record provenance and freshness badges:** show source, last verified date, consent/share readiness, and unverified parsed fields. | Recruiter/client where appropriate; prevents decisions on stale or uncertain records. | MUST HAVE for sensitive fields | P2 | Provenance, consent, parsing and share projection. |
| **Accessible status/validation/error system:** keyboard-accessible controls, text labels beyond color, clear field errors, save state, retry and support correlation ID. | Every role; reduces errors and improves usability for diverse users. | MUST HAVE | P1/P2 | Shared design system, validation and error-handling conventions. |
| **Draft autosave and recoverable workflows:** save long intake/JD/feedback form drafts and recover after timeout/network interruption. | Recruiters/client users; avoids lost work and abandoned forms. | SHOULD HAVE | P2 | Server draft lifecycle, session and record permissions. |
| **Privacy-aware notification center:** group by urgency/entity, mark read, snooze or assign where allowed, and link to the correct action. | Recruiter/client/candidate; keeps reminders useful rather than noisy. | SHOULD HAVE | P2 | Notification event model, preferences and task ownership. |
| **Bulk-action preview and undo/repair:** preview affected records, confirm consequence, report per-row success/failure, allow safe undo or compensating action. | Recruiter/admin; supports efficiency without silent mass mistakes. | SHOULD HAVE | P2/P3 | Idempotency, audit, transaction boundaries and permission checks. |
| **Role-specific onboarding/help:** explain terms, permission scope, setup checklist and first task for candidate/client/recruiter/admin. | New users; lowers support burden and speeds adoption. | SHOULD HAVE | P1/P2 | Role/permission matrix, product terminology, help content. |

## 9. Missing security and privacy capabilities

The Blueprint already covers sessions, server policies, consent, private files, rate limiting, audit, backups and production/staging separation. The following should turn those principles into verifiable controls. OWASP ASVS is a useful verification checklist across architecture, authentication, access control, validation, data protection, file handling, APIs and configuration; it should be tailored rather than treated as a substitute for threat analysis ([OWASP ASVS 5.0](https://owasp.org/www-project-application-security-verification-standard/)).

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Security requirements and acceptance checklist:** select a proportionate ASVS verification target; map requirements to evidence and release gates. | Developers/admin/management; makes “secure foundation” reviewable. | MUST HAVE | P1 | Approved hosting, auth, data classification, threat model. |
| **Privileged-user MFA and recovery policy:** require second factor for admin/management/finance or privileged access; define recovery and session revocation. | Admin/management/finance; reduces impact of stolen credentials. | SHOULD HAVE at P1 launch; SSO/passkeys remain Phase 4 options | P1 | Identity/session baseline, approved MFA mechanism, account recovery. |
| **Session/device management:** list/revoke active sessions, inactivity and absolute timeouts, re-authenticate before sensitive export/role changes. | Staff/client/candidate; limits exposure from lost devices and shared workstations. | SHOULD HAVE | P1 | Server sessions, account lifecycle, audit. |
| **Access review and separation of duties:** periodically review privileged/client accounts; separate invoice/payment entry and approval where practical; record review. | Admin/finance/management; catches stale access and reduces error/fraud risk. | SHOULD HAVE | P1/P3 | RBAC, account status history, finance workflow. |
| **Sensitive-field classification and masking:** mark PII, private notes, compensation and margin; mask by default in lists/logs and reveal only with reason/permission. | All internal/client users; reduces accidental exposure. | MUST HAVE | P1/P2 | Role policies, client/candidate projections, audit. |
| **Retention and deletion propagation:** apply retention schedules to candidate data, documents, parsed text, embeddings, exports and backups; record deletion/anonymization outcome and exceptions. | Privacy/admin; prevents indefinite retention and orphan copies. | MUST HAVE before live candidate data | P1 design; P2 implementation | Consent/retention policy, document/search/AI data lineage, backup lifecycle. |
| **Security event monitoring and incident runbook:** alert on repeated login failures, access denials, unusual exports/downloads, malware detections, queue failures and configuration changes; define escalation/response owner. | Admin/security/management; helps identify and contain incidents. | MUST HAVE | P1 basic; improve continuously | Sanitized logs, audit events, hosting monitoring, response contacts. |
| **Restore and recovery objective:** set practical RPO/RTO targets and prove a restore of database plus private documents; include key/secret recovery and audit retention. | Management/admin; ensures backups are usable, not merely present. | MUST HAVE | P1 | Backup architecture, storage credentials, runbook and staging. |
| **Public and internal API abuse controls:** origin/CSRF/CORS rules, scoped tokens, secret rotation, request size limits, replay/idempotency controls and integration revocation. | Admin/integration owner; protects future APIs and webhook endpoints. | SHOULD HAVE before external APIs | P1 prepare/P4 activate | API versioning, identity, audit and secrets management. |
| **Consent for interview recording/transcription:** explicit opt-in, notice, purpose, participant scope, retention and withdrawal before any recording/AI transcription. | Candidate/interviewer/client; protects trust and controls sensitive interview material. | FUTURE; MUST HAVE if capability ever activated | P4 or later | Consent versioning, meeting provider, storage and AI retention policy. |
| **Data subject/service request workflow:** route requests for access/correction/withdrawal/deletion to an owner; track identity verification, scope, due date and completion without asserting legal timelines here. | Candidate/client contact and privacy owner; provides a consistent operational response. | SHOULD HAVE before production P2 | P1 process design/P2 tooling | Identity verification, data lineage, retention/anonymization functions. |

Legal/privacy text, retention periods, lawful basis, and cross-border/provider requirements need separate qualified review. This table recommends controls and operational workflows, not legal conclusions.

## 10. Missing reporting/KPI capabilities

The Blueprint already defines core funnel, time-to-submit/fill, joining, replacement, invoice aging, payment realization and staffing utilization concepts. Add these measures only after the underlying events and denominators are captured. “Candidate response rate” and “time-to-feedback” are especially useful operational measures; all metrics should be filterable by cohort and should not reward unsafe volume behavior.

| KPI / definition | Who uses it and why | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Requirement fill/coverage rate:** requirements with at least one qualified/approved submission ÷ open approved requirements, by age band. | Management/recruiter lead; exposes uncovered demand early. | SHOULD HAVE | P3 | Approved requirement state, submissions, agreed “qualified” definition, as-of date. |
| **Time in stage / stage aging:** median and percentile time from entering to leaving each stage; show current aging separately. | Recruiters/managers; pinpoints bottlenecks instead of only overall fill time. | MUST HAVE operational view | P2/P3 | Timestamped status history, pause/reopen rules. |
| **Time to first candidate response:** time from candidate contact/request to response, segmented by channel and task type. | Recruiter lead; helps plan follow-up cadence and candidate contact experience. | SHOULD HAVE | P3 | Communication event timestamps, candidate preference/consent, no-response policy. |
| **Client decision/feedback SLA:** median and overdue share from submission/interview to client response. | Account owner/management; supports shared delivery expectations. | SHOULD HAVE | P2/P3 | Client share/decision events, agreed SLA due dates, pauses. |
| **Candidate response and withdrawal reasons:** response rate to availability/interview/offer requests and recorded withdrawal reasons. | Recruiter/candidate-experience owner; identifies friction and mismatched expectations. | SHOULD HAVE | P3 | Contact events, status reason codes, privacy-safe reporting. |
| **Source yield:** candidates from each source who are screened/submitted/interviewed/joined ÷ unique candidates sourced from that source. | Management/recruiters; compares source quality beyond raw volume. | SHOULD HAVE | P3 | Source provenance, duplicate resolution, submission and outcome events. |
| **Interview feedback completion:** completed scorecards by due time ÷ scorecards assigned; outstanding count by owner. | Recruiter/interview lead; supports faster, structured decisions. | MUST HAVE operational metric | P2 | Interview plan, scorecard assignment/submission, due dates. |
| **Offer acceptance and joining fallout:** accepted offers ÷ final offers; actual joiners ÷ accepted offers, with withdrawal/no-show reasons. | Management/recruiter; separates offer quality from joining friction. | SHOULD HAVE | P3 | Offer version/status, joining record, reason codes and cohort choice. |
| **Staffing active headcount and assignment continuity:** active workers/assignments as-of date; starts, ends, extensions, gaps and unfilled days. | Staffing operations; tracks workforce delivery and client coverage. | MUST HAVE if staffing is in scope | P3 | Assignment status history, effective dates, client/site. |
| **Timesheet submission/approval compliance:** approved time entries by cutoff ÷ expected entries; missing, rejected and late entries separately. | Staffing/finance; reduces billing/payroll handoff failures. | MUST HAVE if time-based billing is adopted | P3 | Assignment billing calendar, expected periods, time submission/approval states. |
| **Billable utilization:** approved billable hours/capacity ÷ available capacity for the period; define leave/bench treatment. | Staffing management; informs capacity and margin. | SHOULD HAVE | P3 | Timesheets or capacity source, assignment allocation, absence/leave policy. |
| **Invoice accuracy/dispute rate:** invoices with correction/dispute ÷ invoices issued; classify cause and value. | Finance/account manager; helps improve contract terms and billing process. | SHOULD HAVE | P3 | Invoice/credit/dispute history and billing source. |
| **DSO / collection duration:** average days from invoice date to allocated payment, reported with aging and partial payment handling. | Finance/management; clarifies cash conversion. | SHOULD HAVE | P3 | Invoice due/issue dates, payment allocation, credits/voids. |
| **Replacement and early attrition:** eligible placements/assignments replaced or ended within agreed period ÷ mature cohort. | Management/account owner; measures quality and contract outcomes. | SHOULD HAVE | P3 | Agreement windows, assignment/placement lifecycle, reason codes and mature cohorts. |
| **Client and candidate experience:** response/satisfaction by checkpoint with response count and bias caveat. | Service owner; surfaces friction not captured by conversion. | SHOULD HAVE | P3 | Feedback collection, opt-in/response denominator, privacy-safe minimum groups. |
| **AI evidence quality and reviewer override:** citation/evidence validity, missing-field rate, reviewer correction/override frequency by rubric/model version. | AI owner/recruiters; monitors whether AI assistance is useful and trustworthy. | MUST HAVE governance metric | P2 | AI trace, human review outcomes, versioned evaluation and bias review. |

For every metric, specify numerator, denominator, selected date basis, timezone, exclusions, reopened/paused/withdrawn handling, and source events. Show data freshness and sample size. KPI values must never be handwritten counters.

## 11. Missing integration capabilities

| Integration / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Calendar (Google/Microsoft) and .ics fallback:** availability, invites, RSVP, reschedule and reminders. | Recruiter/interviewer/candidate; reduces manual coordination. | SHOULD HAVE | P2/P4 | Interview records, OAuth/scopes, timezone policy, event sync/retry and audit. |
| **Video meeting provider:** create meeting links for online interviews and attach to authorized invite. | Candidate/client/interviewer; reduces manual setup. | FUTURE | P2/P4 | Calendar event adapter, provider consent and secure link handling. |
| **Zoho Mail integration with inbound thread association:** send transactional mail, track provider result, optionally associate replies with client/candidate/requirement. | Recruiter/client/candidate; preserves useful history across email. | MUST HAVE outbound notification adapter; SHOULD HAVE reply sync | P1/P2 | Zoho credentials, message correlation, privacy and duplicate/thread rules. |
| **E-signature service:** issue offer/contract documents for signature and record completed/declined/expired state. | Candidate/client/finance; avoids downloading/scanning signed PDFs manually. | FUTURE | P3/P4 | Document version, authorized signer, provider webhook, retention/audit. |
| **Job board/career page feed:** publish approved job fields and receive applications through permitted channel. | Candidate/recruiter; broadens sourcing and makes TYCHIFF job page transactional. | SHOULD HAVE feed; external boards FUTURE | P2/P4 | Published job projection, consent notice, application deduplication and provider terms. |
| **Accounting/invoicing export or API:** sync client, invoice, credit and payment references to accounting system. | Finance; reduces duplicate entry and reconciliation risk. | FUTURE | P3/P4 | Finance data model, external ID mapping, idempotency/reconciliation ledger. |
| **Background/credential verification provider:** request job/assignment-required checks with explicit candidate consent and access restrictions. | Staffing/recruiter/client; supports client requirements when legally and commercially approved. | FUTURE | P3/P4 | Consent, provider DPA/security review, result retention and restricted access. |
| **Payroll/HRIS/time system:** exchange approved assignment and timesheet data; no ATS payroll calculations. | Finance/operations; supports contractor administration in existing specialist systems. | FUTURE | P4 | Stable assignment/time model, scoped API/export, reconciliation/error queue. |
| **SSO/passkeys and identity provider:** enterprise login and phishing-resistant sign-in. | Staff/client; improves access management for enterprise clients. | FUTURE | P4 | Verified account mapping, role allow-list, recovery, audit and staging setup. |
| **WhatsApp Business:** approved template reminders and candidate/client communication, with delivery state and consent. | Recruiter/candidate/client; supports user-preferred communication. | FUTURE | P4 | Notification preferences, provider opt-in/template policy, suppression, message logs and controls. |
| **Public API/webhooks:** deliver approved requirement/submission/assignment/invoice events and accept scoped integrations. | Future partners/mobile/integration users; avoids one-off coupling. | FUTURE | P1 API conventions; P4 activation | API versioning, authentication, idempotency, signature validation, retries and audit. |

For each external integration: use least-privilege credentials, explicit consent/data scope, sandbox, idempotency, correlation IDs, retries with bounded backoff, dead-letter visibility, revocation and data deletion behavior. Do not connect systems directly to database tables.

## 12. Missing operational capabilities

| Gap / what it does | Users and TYCHIFF value | Classification | Phase | Depends on |
|---|---|---|---|---|
| **Assignment rules and escalations:** assign work by client/team/skill, support manual override, escalate unowned/overdue tasks to a named role. | Recruiter lead/operations; prevents orphan records and hidden SLA failures. | MUST HAVE basic ownership; SHOULD HAVE configurable rules | P1/P2 | User/role model, task/requirement ownership, scheduler, audit. |
| **Business calendar and holiday/timezone handling:** define business hours, holidays, SLA clocks and candidate/client local time zones. | Recruiter/coordinator/client; prevents incorrect due dates and interview confusion. | SHOULD HAVE | P2 | Config, timezone-aware events, scheduler and SLA definition. |
| **Template governance:** approved email, JD, interview kit, consent, document, invoice and reminder templates with owner/version/active date. | Admin/recruiters/finance; promotes consistency and controlled wording. | SHOULD HAVE | P2/P3 | Template service, versioning, approval and permission boundaries. |
| **Data import with dry run:** map fields, normalize values, detect duplicate candidates, preview conflicts, validate consent/source and import audit record. | Admin/operations; enables migration from spreadsheets without silent duplication. | MUST HAVE before first bulk migration | P1/P2 | Schema/taxonomies, dedup review, consent policy, audit. |
| **Controlled export:** permissioned CSV/PDF exports with selected fields, purpose, expiry, audit and PII warning; avoid blanket database dumps to users. | Recruiter/management/finance; enables work/reporting without uncontrolled copies. | MUST HAVE | P1/P2 | Field-level authorization, report service, audit and secure file expiry. |
| **Data-quality queue:** incomplete/stale candidate profiles, missing source/consent, invalid date ranges, duplicate signals, unresolved assignment/invoice data. | Admin/recruiter lead; turns quality into assigned work rather than invisible defects. | SHOULD HAVE | P2/P3 | Validation rules, provenance, task queue and ownership. |
| **Integration/queue operations console:** view failed mail, parsing, AI and external sync jobs; retry safely, inspect sanitized context and assign resolution. | Admin/operations; prevents silent record/notification loss. | MUST HAVE basic operational visibility | P1/P2 | Queue, correlation IDs, idempotency, retry/dead-letter policy, logs. |
| **Runbooks and environment checklist:** deployment/rollback, restore, key rotation, incident triage, scheduled job verification, staging refresh and access review. | Admin/maintainer; makes operations repeatable. | MUST HAVE | P1 | Hosting choice, backups, monitoring and role ownership. |
| **Search synonyms and taxonomy stewardship:** approve aliases/merges for skills, job titles and location names; show original terms. | Recruiter/admin; improves recall without erasing candidate wording. | SHOULD HAVE | P2 | Controlled taxonomies, audit and search indexing. |
| **Document expiry/review queue:** expired/expiring contract, credential, insurance or agreement documents linked to affected assignment/client. | Staffing coordinator/finance; prevents operational surprises. | SHOULD HAVE | P2/P3 | Document expiry metadata, relationship links, reminders and access control. |
| **Backup restore evidence and retention report:** record last successful backup, verification/restore date, scope, result and follow-up issue. | Admin/management; proves recovery works. | MUST HAVE | P1 | Provider backup jobs, restore runbook, document/database consistency. |
| **Service health and alerting:** application errors, queue age, scheduler heartbeat, disk/storage limits, database connectivity and mail/API availability. | Admin/maintainer; detects problems before users report them. | MUST HAVE basic | P1 | Hosting monitoring, structured logs, correlation IDs and escalation owner. |
| **Change/configuration audit:** history of workflow rules, permissions, templates, notification schedules, model settings and taxonomies. | Admin/security/AI owner; explains behavior changes and supports rollback. | MUST HAVE | P1/P2 | Configuration records, privileged audit and versioning. |

## 13. Capabilities already covered — do not duplicate

These are already established in the Blueprint at architecture or product-design level. They still need implementation in their phases; “covered” does not mean they work in the current static prototype.

| Existing Blueprint coverage | Current disposition |
|---|---|
| Laravel/PHP + MySQL/MariaDB modular monolith; current frontend adapted rather than replaced | Approved architecture; not implemented in current project. |
| Server-side sessions, one sign-in, roles/permissions, record-level authorization, invitations and account lifecycle | Foundation design; role matrix and hosting remain approval decisions. |
| Candidate profile, separate candidate account/profile linkage, source, skills, experience, education, preferences, availability, consent, communication preferences | Designed for Phase 2; no production candidate DB exists. |
| Candidate resume/document versions, private storage, scan/quarantine state, authorized download, access audit | Designed; implementation later. |
| Distinct requirements, JDs/JD versions, published jobs, submissions, interviews, decisions, offers, placements, joining, feedback, replacement, invoices and payments | Domain principle already explicit; add only the operational gaps above. |
| Candidate master status distinct from per-requirement submission status and multiple simultaneous applications | Explicitly covered; preserve as invariant. |
| Duplicate review/merge, candidate consent/share eligibility, client-scoped candidate views | Covered at design level; detailed workflow and UI remain to build. |
| Recruiter task-first workbench, due/aging actions, follow-ups, workload concepts | Core UX already covered; templates, escalation rules and queue operations are the gaps. |
| Client portal read-only by default with approved actions, client record isolation and internal-note/margin protection | Covered; client action center, delegated approvers and document exchange need detail. |
| Contract staffing separate from permanent placement; assignment, dates, status, renewal, allocation, billing and payroll boundary | Domain separation covered; timesheets/expenses/onboarding/change lifecycle need detail. |
| Agentic RAG, structured/keyword/semantic retrieval options, controlled tools, evidence, version traceability and human decision authority | Covered; AI quality evaluation, release gate, governance and privacy controls need detail. |
| Audit log, activity timeline, notification abstraction, queue/scheduler, backups, staging, API versioning and future adapters | Covered conceptually; operations console, incident runbooks and restore evidence need explicit treatment. |
| KPI definitions based on transactions and core recruitment/finance funnel measures | Covered; additional stage, client, staffing, candidate-experience and AI-quality measures listed above. |
| Search adapter and future vector/semantic retrieval; no separate vector store assumed initially | Covered; saved views, rediscovery workflow, aliases and search-plan transparency are gaps. |
| SSO/passkeys, WhatsApp, Tamil, external integrations, mobile, multi-tenancy and payroll are future extensions | Keep future extensibility; no early implementation required. |

## 14. Phase and dependency impact

| Phase | Add to product plan | Key prerequisites/dependencies |
|---|---|---|
| **Phase 0 — Decisions** | Confirm role/permission matrix; contract staffing time/billing scope; Hostinger/staging capability; data source/import assumptions; privacy notice/retention owner. | Business approval, verified company content, hosting account details, staffing workflow owner. |
| **Phase 1 — Foundation** | Build identity and authorization; consent/retention structure; audit; private storage abstraction; queue/notification/search/API boundaries; basic operational visibility and backup/restore checklist; import/export policy. | Approved roles; test/staging runtime; synthetic data; security acceptance checklist. |
| **Phase 2 — Core recruitment** | Structured interview plans/scorecards/debrief; candidate submission snapshots; application intake/receipt; client action center; recruiter queue/tasks/SLA; candidate status/availability; data quality; AI evaluation and governance. | Candidate/requirement/JD versions, client membership, activity/audit, notifications, AI provider abstraction, resume storage/search. |
| **Phase 3 — Staffing/placement/money/KPI** | Worker onboarding, assignment change/renewal/offboarding, approved time/expenses if selected, billable reconciliation, invoice/payment, staffing utilization, broader KPIs and client health. | Staffing contracts/assignment model, billing terms, approvers, time source, finance decision and assignment history. |
| **Phase 4 — Advanced/growth** | Calendar and video integrations if not earlier, e-signature, WhatsApp, SSO/passkeys, Tamil, external job boards, public API/webhooks, semantic/vector search, payroll/HRIS integration. | Provider contracts, privacy/security review, integration monitoring, data quality and stable API. |

## 15. Architecture impact

### No architecture change required

Most gaps fit the approved design through detail in existing modules: scorecards/debriefs under interviews, tasks under workflow/notifications, candidate job alerts under candidate/public jobs, client action center under client portal, data-quality queue under admin, expanded KPIs under reports, integration retries under queues, and AI evaluation under the AI service boundary.

### Small domain extensions to include in the master model

1. **Interview plan, scorecard template, assigned scorecard, scorecard response, debrief decision.** Keep scorecard feedback and final selection decision separate.
2. **Task/SLA rule, task assignment/escalation, communication log/template, client approval routing.** Preserve event history and role-specific visibility.
3. **Candidate application/public job record and candidate submission snapshot.** Do not conflate a public application with a client submission if their intake/consent paths differ.
4. **Staffing time/expense and billing reconciliation.** Add only if Phase 3’s approved operating model requires them; keep them separate from payroll records.
5. **AI evaluation/configuration and data lineage.** Model/prompt changes, evaluation result, source versions, redaction decisions and deletion state should be traceable.
6. **Operational job/integration delivery records.** Use correlation IDs, idempotency and bounded retry/dead-letter states rather than relying only on log text.

These extend modular monolith domains and tables; they do not justify microservices, a new frontend framework, or a separate vector database. The approved Laravel + MySQL/MariaDB + Agentic RAG direction remains suitable.

## 16. Architecture and implementation risks

- **Feature breadth:** implementing every SHOULD/FUTURE item at once could delay Phase 2’s real hiring loop. Build the smallest useful end-to-end path, then expand.
- **Permission complexity:** clients, internal notes, recruiter ownership, candidates and finance require explicit testable scopes; a portal is not safe merely because it is read-only.
- **Staffing operations:** time capture, expenses, approval and invoice reconciliation can become a back-office system. Decide minimum billing model and integration boundary before modeling it too broadly.
- **KPI ambiguity:** only count records whose eligibility, date basis, ownership, pause/reopen rules and denominator are defined; otherwise dashboards mislead.
- **AI quality:** traceability alone does not prove useful or fair recommendations. Add evaluation, reviewer correction, data freshness and version-change release gates.
- **Email/calendar synchronization:** external systems can be delayed or inconsistent. Preserve TYCHIFF’s workflow state, use idempotency and provide manual reconciliation.
- **Data retention/deletion:** search indexes, embeddings, exports, document versions and backups create copies. Deletion/anonymization must have a propagation and exception record.
- **Current prototype risk:** static portal metrics, browser-stored accounts and local JD approvals must not be carried into production as if they are real security/workflow controls.
- **Design similarity:** prior review found potential visual similarity between the recruiter portal mock and one reference screenshot. Retain the product insight (a recruiter overview), independently redesign the visual hierarchy.

## 17. Decisions requiring approval before Phase 1

These are the genuine Phase 1 decisions from the Blueprint, sharpened by the gap review:

1. **Roles and record scope:** approve Admin, Management, Recruiter/Staff, Client User and Candidate baseline roles plus who may see candidate records, private notes, financial data, exports and client-shared submissions.
2. **Contract staffing minimum model:** confirm Phase 3 includes contract staffing; decide whether TYCHIFF initially records approved timesheets/expenses or integrates with an external time/payroll system; choose initial billing basis (monthly, timesheet/unit, milestone or mixed). Keep payroll calculations out of scope.
3. **Deployment/staging capability:** verify actual Hostinger support for the Laravel runtime, MySQL/MariaDB, Composer deployment, private documents, queue execution, scheduler, backup/restore and private test environment.

Other items can be designed with owners during Phase 1 and approved before the phase that uses them: interview scorecard rubric, candidate notice/retention wording, detailed KPI formulas, client approver configuration, and external integration providers. Do not process real candidate data until the applicable privacy and retention wording has been approved.

## A. ALREADY COVERED

- Modular monolith, Laravel/MySQL, retain existing frontend where practical.
- One server-side sign-in, RBAC plus record-level authorization, role invitations and account lifecycle.
- Candidate and resume profiles, versions, consent, duplicate review, pools/search concepts, availability and submission separation.
- Requirement/JD/JD-version/published-job concepts; client approval and status history.
- Interviews, offers, placement, joining, feedback, replacement, invoice/payment concepts.
- Separate contract staffing contract/assignment boundary; payroll reserved for future integration.
- Private documents, audit/activity, notifications, queues/scheduler, backups, staging, API and provider abstractions.
- Agentic RAG with scoped tools, evidence, traceability, human review and no autonomous hiring/rejection.
- Core funnel, time-to-submit/fill, joining, replacement, invoice/payment and utilization KPI concepts.

Covered means included in the product/architecture blueprint, not implemented in the existing static prototype.

## B. MISSING — MUST HAVE

- Structured interview plans, assigned scorecards, feedback due tracking and explicit human debrief/decision.
- Candidate submission snapshot and requirement/job-specific status history.
- Application receipt and candidate-safe status/next-step communication.
- Recruiter task queue with ownership, due dates, time-in-stage, basic escalation and communication log.
- Candidate interest/availability refresh before client submission; accessible consent/profile correction journey.
- Client action center, submission review, decision history and explicit client document sharing.
- Contract worker onboarding, assignment changes/renewals/end dates, and billing reconciliation; timesheets/approval if billing depends on time.
- AI requirement-quality checks, source-grounded evidence, bounded run budgets, evaluation/release gate and sensitive-data controls before live candidate matching.
- Security verification checklist, session/access review, sensitive-data masking, retention/deletion propagation, incident runbook and tested restore evidence.
- Operational error visibility for queues, mail, parsing, AI and integration jobs.
- Stage aging, interview feedback completion and AI evidence-quality oversight metrics.

## C. MISSING — SHOULD HAVE

- Talent rediscovery, saved searches, candidate ownership/conflict checks and work capacity view.
- Candidate self-scheduling, job alerts, document checklist/expiry reminders and candidate experience feedback.
- Client delegated approvers, client SLA/action dashboard, notification preferences and secure document exchange.
- Management opportunity pipeline, capacity forecast, client health, source yield and forecast confidence.
- Staffing timesheets/expenses where operationally needed, availability/redeployment, credential expiry and utilization analytics.
- Bulk action preview, import dry-run, data-quality work queue, template governance, searchable notes and taxonomy stewardship.
- Calendar sync, interview kits, global search, saved views, draft recovery, keyboard workflows and accessibility refinement.
- DSO, invoice disputes, candidate response, client feedback SLA, source yield and staffing continuity KPIs.
- Privileged-user MFA, data subject request work queue, security event monitoring and access reviews.

## D. MISSING — FUTURE

- E-signature, video interview, SSO/passkeys, WhatsApp Business, Tamil, external job-board sourcing and public webhooks/API activation.
- Payroll/HRIS/time-system integrations; ATS should exchange approved assignment/time data rather than become payroll.
- Vector/semantic retrieval, advanced automation, mobile application, scheduled external reports and multi-tenancy if business model later requires it.
- Interview recording/transcription only after explicit consent, retention and provider controls are approved.

## E. NOT RELEVANT

- Payroll computation/ledger inside the ATS for the present roadmap. Maintain a clean future integration boundary.
- Generic “AI-proof” career scoring or a separate career simulation product as part of this recruitment/staffing platform.
- SaaS subscription packaging unless TYCHIFF changes from operating recruitment/staffing services to selling ATS software.

These scope choices do not reject capabilities merely because they appear in another product; they reflect TYCHIFF’s stated current business objective.

## F. ARCHITECTURE IMPACT

- **No platform architecture change:** retain Laravel + MySQL/MariaDB modular monolith + Agentic RAG.
- Add product-detail entities/boundaries for scorecards/debrief, task/SLA/escalation, communication logs/templates, public applications/submission snapshots, staffing time/expense reconciliation if selected, AI evaluation/configuration, and operational job delivery.
- Preserve separate entities for candidate vs submission, permanent placement vs staffing assignment, invoice vs payment, and approved time vs payroll.
- Keep integrations behind adapters; do not add microservices or vector DB for these gaps.

## G. PHASE IMPACT

- **Phase 0:** role/permission and contract-staffing/time-billing decisions; confirm host/staging capability.
- **Phase 1:** security/consent/audit/data model foundations, API/search/notification/queue boundaries, restore and operational runbooks, privacy/deletion design.
- **Phase 2:** structured recruitment, application intake, candidate/client action centers, scorecards/debrief, task/SLA queue, candidate self-service, AI evaluation and evidence.
- **Phase 3:** placement and staffing operations, onboarding, assignment lifecycle, time/expense approval if chosen, invoice reconciliation, collections, staffing and business KPI.
- **Phase 4:** integration expansion, SSO/passkeys, WhatsApp, Tamil, vector search, external sourcing and mobile/API consumers.

## H. FINAL RECOMMENDATION

The Master Blueprint is a strong foundation but should be updated with the operational gaps identified here before Phase 1 approval. The most important new product decisions are the Phase 3 staffing time/billing boundary and the Phase 2 interview scorecard/debrief workflow. The rest fit existing modules and phase boundaries.

The approved Laravel + MySQL/MariaDB + modular monolith + Agentic RAG architecture can support the complete proposed roadmap. No technology redesign is needed. Approve Phase 1 only after confirming the role/permission matrix, contract staffing minimum model, and actual staging/deployment capability. This review created only this analysis document; application/source files and migrations were not changed.

## Industry and security references

- Greenhouse, [Scorecard overview](https://support.greenhouse.io/hc/en-us/articles/4414777492891-Scorecard-overview) — structured criteria, assigned interviewer scorecards and visibility policies.
- Greenhouse, [Structured hiring guide](https://support.greenhouse.io/hc/en-us/articles/360039539772-Structured-hiring-guide) — role criteria and interview plan concepts.
- Greenhouse, [Talent Rediscovery](https://support.greenhouse.io/hc/en-us/articles/30184390692379-Talent-Rediscovery) — filtering past candidates/prospects and resume/notes search.
- Greenhouse, [Send a self-schedule request](https://support.greenhouse.io/hc/en-us/articles/4409534677915-Send-a-self-schedule-request) — candidate slot selection and interview confirmation states.
- Greenhouse, [Interviewer guide: interview kits](https://support.greenhouse.io/hc/en-us/articles/115002226826-Interviewer-guide-Using-interview-kits) — interview preparation, resume context and feedback.
- Greenhouse, [Visual candidate pipeline](https://support.greenhouse.io/hc/en-us/articles/4874727408795-Visual-Candidate-Pipeline) — pending action, stage age and task filtering concepts.
- Bullhorn, [Middle Office Field Guide](https://kb.bullhorn.com/bhone/Content/BH1/Topics/middle-office-field-guide.htm) and [Time Entry Integration Settings](https://kb.bullhorn.com/backoffice/Content/backoffice/Topics/timeEntryIntegrationSettings.htm) — assignment, time entry, billable charge and payroll/back-office boundary examples.
- OWASP, [Application Security Verification Standard](https://owasp.org/www-project-application-security-verification-standard/) — application security verification framework.
- OWASP, [Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html) — security-relevant logging and event handling guidance.

**FINAL PRODUCT GAP ANALYSIS COMPLETE — NO APPLICATION/SOURCE FILES MODIFIED.**
