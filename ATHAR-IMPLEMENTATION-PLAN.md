# Athar / أثر — Contributor-Led Proof: Full Implementation Plan

> Prepared: 22 July 2026
> Delivery model: one complete, production-ready implementation session
> Scope: a private, bilingual, consent-led reflection flow that can produce one contributor-owned public proof card on one named page of ibrahimhasan.net.

## 1. Executive decision

Build Athar / أثر as a **contributor-led proof system**, not a testimonials wall and not an editorial pipeline that turns a private message into Ibrahim's marketing copy.

The defining principle is:

> Ibrahim may verify context and protect confidentiality. The contributor owns the words attributed to them.

Athar begins as a personal invitation and a private reflection, then makes publication consideration the clear next step. The contributor sends their own words for review, may still keep them private at the exact approval point, and must approve one exact card for one named page before anything becomes public.

This is a full feature boundary, not a temporary reduction:

- It ships as a complete text-only, website-only proof system.
- It does not include a QR pass, public Athar directory, media, logos, social reuse, proposals, talks, ratings, or multi-placement rights.
- Those exclusions protect the recipient experience and are not postponed obligations.

The result should feel creative because it is restrained: someone leaves knowing that Ibrahim protected their voice, not because they completed an elaborate testimonial process.

## 2. Product contract

### 2.1 Product purpose

Athar gives a former client, collaborator, or personal connection a calm place to describe a real moment of work or thought in their own words. It gives Ibrahim truthful, permissioned proof that appears only where it helps a qualified visitor understand a real project, method, or working relationship.

It must satisfy the product hierarchy in this order:

1. Truth, privacy, and data integrity.
2. Comprehension and a low-pressure recipient experience.
3. Accessibility, Arabic-first composition, and responsive completion.
4. Operational safety and maintainability.
5. Creative distinction.

### 2.2 Audience boundary

| Relationship | Private reflection | Public proof boundary |
|---|---|---|
| Former client | Supported | A precise Work, Service, or About context; never claims company authority without confirmation. |
| Independent collaborator | Supported | A precise collaboration context; no reciprocal-pressure language. |
| Personal connection | Supported | Private by default. A public note may appear only in an About context with a truthful personal relationship disclosure. |

Do not use Athar to collect proof from active clients, direct reports, or anyone whose commercial relationship makes a public response feel compulsory.

### 2.3 Jobs to be done

The contributor needs to:

- understand why Ibrahim invited them and how long the reflection takes;
- write privately without feeling they are completing a marketing form;
- decide after writing whether any exact words should appear on the website;
- see the exact public card, including context, language, name presentation, and page;
- keep the reflection private, edit it, decline public use, withdraw published proof, or request private-data deletion.

Ibrahim needs to:

- create a thoughtful, individualized invitation;
- receive a private reflection without invasive open or start tracking;
- review confidentiality and factual context without rewriting the contributor by default;
- prepare one exact, contextual website card only after the contributor requests public consideration;
- retain durable proof of source, consent, publication, and withdrawal;
- run the process from a focused Filament workspace with clear permissions.

A qualified website visitor needs to:

- see specific proof in the relevant context rather than a generic praise wall;
- distinguish the contributor's words from Ibrahim's factual context;
- understand that the note was published with the writer's explicit permission.

### 2.4 Success definition

Athar is successful when:

- a contributor can complete the private journey without JavaScript, an account, or a public decision;
- a public card contains only contributor-selected or contributor-edited words, plus one visually separate factual context line;
- every visible card is bound to an active consent event for its exact immutable snapshot;
- withdrawal removes the card from every Athar-controlled website location immediately;
- Arabic and English are both authored, readable, and accurate;
- the public proof improves project comprehension without making the site feel promotional.

## 3. Naming and Arabic language contract

### 3.1 Surface names

| Surface | Arabic | English |
|---|---|---|
| System | أثر | Athar |
| Meaning line | ما يبقى حين نعمل معاً | The trace of working together |
| Invitation | دعوة شخصية من إبراهيم | A personal invitation from Ibrahim |
| Private reflection | رسالة خاصة | A private note |
| Contributor-owned public text | النص الذي اخترته للنشر | The words you chose to share |
| Optional editorial-help route | اقتراح مختصر من إبراهيم | A shorter suggestion from Ibrahim |
| Factual line prepared by Ibrahim | سياق العمل | Work context |
| Public disclosure | نُشر بموافقة من كتبه | Shared with the writer's permission |
| Withdrawal action | إيقاف ظهور هذا النص على الموقع | Stop showing this text |
| Admin navigation | أثر | Athar |

Use مشاركة in neutral system and administrative language. On contributor-facing screens, use رسالة or كلمات because they are warmer and clearer than testimonial terminology.

### 3.2 Binding language rules

- Use على الموقع, not للعامة, when consent covers one named website page.
- Never call an Ibrahim-authored suggestion a better version.
- Never call a contributor-selected text صياغة مقترحة للنشر.
- Never claim that original words can never appear. The truthful promise is that nothing appears unless the contributor chooses the exact text and approves the exact card.
- Keep the four concepts distinct: a private note, a contributor-owned public note, an optional shorter suggestion, and a factual work-context line.
- The only text attributed to a contributor is text they selected or edited themselves.
- Ibrahim's work-context line is visually separate and included in the contributor's exact preview.

### 3.3 Recipient microcopy

#### Invitation welcome

Arabic:

> هذه مساحة قصيرة لتكتب ما تتذكّره من تجربة عملنا معاً بطريقتك. تصل رسالتك إلى إبراهيم بشكل خاص، ولا يظهر شيء منها على الموقع تلقائياً. بعد مراجعتها، يمكنك إبقاؤها خاصة أو اختيار كلمات محددة تودّ ظهورها.

English:

> This is a short space to write what you remember from working together, in your own words. Your note is sent privately to Ibrahim, and nothing appears on the site automatically. After reviewing it, you can keep it private or choose the exact words you would like to share.

Supporting labels:

| Arabic | English |
|---|---|
| تستغرق بضع دقائق | Takes a few minutes |
| اكتب ما تتذكّره بطريقتك | Write what you remember, in your own way |
| كلماتك، والقرار لك | Your words. Your decision. |
| ابدأ | Begin |

#### Public-choice moment

This appears only after private submission and its receipt. It is secondary to the complete private outcome.

Arabic:

> هل تودّ أن يظهر جزء من رسالتك على الموقع؟
>
> ليس عليك مشاركة أي شيء. وإذا اخترت ذلك، ستحدّد بنفسك الكلمات التي ستظهر، ثم تراجعها كما ستظهر على صفحة محددة في الموقع.

Choices:

| Arabic | Supporting copy | English |
|---|---|---|
| إبقاء الرسالة خاصة | لن يظهر أي جزء منها على الموقع. | Keep this note private |
| اختيار كلمات للنشر | حدّد جملة أو أكثر من رسالتك، أو عدّل النص بنفسك. | Choose words to share |
| طلب اقتراح مختصر من إبراهيم | اختره فقط إذا رغبت في اقتراح من إبراهيم. يمكنك تعديله أو رفضه، أو استخدام كلماتك كما هي، أو إبقاء رسالتك خاصة. | Ask Ibrahim for a shorter suggestion |

#### Exact preview and consent

Arabic heading:

> راجع النص كما سيظهر على الموقع

Required preview sections:

- كلماتك
- سياق العمل
- موضع الظهور
- كيف تودّ أن يظهر اسمك؟

Arabic scope disclosure:

> سيظهر هذا النص على صفحة «اسم الصفحة» في موقع إبراهيم فقط. لن يُستخدم في وسائل التواصل الاجتماعي أو العروض أو أي مكان آخر من دون طلب منفصل منك.

Arabic consent:

> أوافق على نشر النص الظاهر أعلاه على صفحة «اسم الصفحة» في موقع إبراهيم فقط.

Actions:

| Arabic | English |
|---|---|
| أوافق على نشر النص كما يظهر أعلاه | Publish as shown |
| تعديل النص | Edit my words |
| إبقاء الرسالة خاصة | Keep this private |

#### Publication and withdrawal

Arabic:

> نُشر هذا النص بعد أن راجعه من كتبه ووافق على ظهوره هنا.

> هل تريد إيقاف ظهور هذا النص على الموقع؟ سيُزال فوراً من صفحة «اسم الصفحة». ويعني ذلك سحب موافقتك على نشره. يمكنك طلب حذف رسالتك الخاصة بشكل منفصل.

Actions:

| Arabic | English |
|---|---|
| إيقاف ظهور هذا النص على الموقع | Stop showing this text |
| نعم، أوقف ظهور هذا النص على الموقع | Yes, stop showing it |
| توقّف ظهور النص على الموقع. | This text is no longer shown on the site. |

## 4. Complete experience design

### 4.1 The creative idea

The emotional entry is the Private Letter, not a scannable artifact. The page feels like a small, personal correspondence surface:

- a direct statement of who invited the person and why;
- a quiet trace line that connects private reflection, chosen words, and permission without making the process theatrical;
- a single primary action at every point;
- no countdown pressure, social proof, ratings, or testimonial language;
- no public navigation, cookie banner, analytics, social preview, or marketing footer.

The public object is a contextual proof note, not an Athar card collection. It appears inside a specific Work, Service, or About composition only when it makes the surrounding page more credible.

### 4.2 Screen inventory

| Screen | Primary task | One primary action |
|---|---|---|
| Invitation access | Open a private invitation | Continue with the private link |
| Link entry | Open the private invitation directly | Continue |
| Private reflection | Write and send words for review | Send my words for review |
| Private receipt | Confirm receipt when revisiting the link | Choose text to share |
| Public-choice moment | Decide whether to initiate public consideration | Choose words to share |
| Contributor editor | Select or write the exact public words | Send text for review |
| Optional suggestion review | Edit, accept, reject, or abandon an expressly requested suggestion | Choose my final words |
| Exact approval | Approve one rendered card for one page | Publish as shown |
| Published management | Review public status or withdraw | Stop showing this text |
| Unavailable state | Explain safely without revealing invitation details | Contact Ibrahim |

### 4.3 Recipient flow

1. Ibrahim sends a personal message with a direct invitation link. The message makes clear that no response is expected.
2. The link opens a private reflection screen. The page does not expose recipient email or unrelated invitation metadata; the localized privacy notice carries the access and publication contract without adding a warning block to the writing surface.
3. Email is an optional delivery convenience. The same private link remains the access credential whether Ibrahim shares it manually or sends it by email.
4. The recipient sees the personal letter, privacy promise, estimated time, and a link to the localized privacy notice immediately; no email or code entry is required.
5. The recipient writes one free-form note. The default screen has no prompt block; the contributor's own words are the source of the reflection.
6. The recipient submits the note for review. Submission seals the original text and changes no public state.
7. The public-choice moment opens as the next step, while the contributor can still choose to keep the note private before exact approval.
8. If the contributor selects words to share, they see their private response in a read-only panel and create their own concise public note. They may use one answer unchanged or write a shorter version themselves.
9. If the contributor expressly requests a shorter suggestion, the public note waits for Ibrahim's suggestion. That route is labelled as a suggestion, never as an improvement.
10. Ibrahim checks factual context and confidentiality, then adds one separated work-context line and chooses one named website placement.
11. If a translation is included, it is human-reviewed and added to the exact card. No automatic translation is published.
12. The contributor receives an approval link, verifies access again, and sees one literal preview: contributor words, context line, display-name choice, approved language or languages, and one page placement.
13. Approving creates the consent event and publishes atomically. Declining or editing returns the public note to a non-public state.
14. The contributor receives a management link and can withdraw website publication or request deletion of their private note.

### 4.4 Prompt contract

The default is a single free-form field with no visible prompt block. The contributor's own words lead the reflection; the form does not ask them to praise Ibrahim, describe a benefit that may not exist, or represent their employer.

### 4.5 Optional shorter-suggestion route

This route exists to help a contributor who wants assistance, not to transfer authorship:

1. The contributor selects طلب اقتراح مختصر من إبراهيم.
2. Ibrahim may create one proposed shorter version from the private source.
3. The contributor sees their words and the suggestion side by side.
4. They can edit the suggestion, replace it with their own public note, or keep everything private.
5. A suggestion cannot be sent for publication until the contributor actively chooses a final public text.

The version records its origin as IbrahimSuggested. The public card still attributes only the final text the contributor chose.

## 5. Public presentation

### 5.1 One card, one placement

Every publication version has one placement only:

- a named Project page;
- a named Service page; or
- one About section for an explicitly personal context.

Home, generic services landing pages, a public Athar index, carousels, and duplication across pages are not part of Athar.

### 5.2 Proof-card anatomy

The public card contains only:

1. A quiet label: من واقع العمل / From the work, or عن قرب / Seen Up Close for a personal About context.
2. The contributor-owned text.
3. A clearly separated factual work-context line written by Ibrahim.
4. The contributor's approved display choice: full name, first name, or anonymous.
5. A source-language label when appropriate.
6. The disclosure: نُشر بموافقة من كتبه / Shared with the writer's permission.

It never contains:

- a portrait, company logo, title, organization name, rating, metric, social profile, or outbound link;
- a claim that the contributor represents a company;
- an implication that someone personal is a client;
- a share button or a public management URL.

### 5.3 Bilingual public behavior

The public interface is bilingual. A note appears in a locale only when the contributor approved the exact text in that locale.

- Arabic source plus approved English translation: render Arabic on Arabic pages and English translation on English pages.
- English source plus approved Arabic translation: render English on English pages and Arabic translation on Arabic pages.
- Source without an approved translation: render only in the source locale. The other locale intentionally has no Athar card rather than silently translating or showing an incomplete substitute.

## 6. Consent, provenance, and withdrawal contract

### 6.1 Core invariant

A public card may render only when all conditions are true:

1. The parent contribution has a published public version.
2. The public version snapshot hash exactly matches an active publication-approval consent event.
3. The active card is rendered only at the placement recorded in that snapshot.
4. No withdrawal event recorded after approval exists for that version.
5. The current locale has an approved text for that snapshot.

Any material change creates a new publication version and requires a new approval:

- contributor-owned public text;
- optional translation;
- factual work-context line;
- display-name choice;
- placement;
- authorship origin.

### 6.2 Distinct recipient decisions

| Decision | What it means | What it does not mean |
|---|---|---|
| Submit private note | Ibrahim receives the sealed private reflection. | Permission to display any text. |
| Choose words to share | The contributor initiates an exact public note. | Publication permission. |
| Request a shorter suggestion | Ibrahim may offer a proposed shortening. | Permission to publish the proposal. |
| Approve and publish | The exact rendered card may appear on one named website page. | Permission for social, proposals, talks, or any other use. |
| Stop showing this text | Website rendering stops immediately. | Automatic deletion of the private note or historical consent evidence. |
| Request private-data deletion | Ibrahim processes the request under the approved retention policy. | A replacement for withdrawing public consent. |

### 6.3 Immutable source

The submitted private payload is sealed at submission:

- Store its encrypted source payload and SHA-256 hash.
- Do not mutate it through administrative forms, model fillable attributes, or a background job.
- A contributor may save and replace a draft before submission only.
- A public note is always stored separately from the sealed source.
- The admin can read the source only with the authorized Athar permission.

### 6.4 Consent evidence

Each approval or withdrawal creates an append-only event containing:

- contribution and publication-version identifiers;
- exact snapshot hash;
- event type;
- approved locales;
- one named website placement;
- display-name choice;
- privacy-notice version;
- verification method;
- timestamp;
- one-way IP and user-agent fingerprints used only for security/audit purposes.

The event record stores evidence, not a vague reusable marketing permission.

## 7. Domain model

### 7.1 Tables

| Table | Role | Key fields |
|---|---|---|
| athar_invitations | Private invitation, relationship context, access token, delivery choice, expiry, and administrator ownership. | public_id, invitation_token_hash, delivery_mode (`email` or private `link`), nullable recipient_email encrypted, nullable recipient_email_hash, recipient_name encrypted, locale, relationship, prompt snapshot, personal note encrypted, expires_at, revoked_at, sent_at, created_by. |
| athar_access_challenges | Legacy, short-lived code records retained only for compatibility with older challenge endpoints; not part of the normal link-first flow. | invitation_id, code_hash, requested_at, expires_at, verified_at, verification_token_hash, request_ip_hash, attempts. |
| athar_contributions | Server-side draft and sealed private source. | invitation_id unique, draft_payload encrypted, source_payload encrypted, source_hash, source_locale, response_mode, submitted_at, public_choice_at, deletion_requested_at, retention_due_at. |
| athar_publication_versions | Append-only exact public-card snapshots. | contribution_id, sequence, origin, content translations JSON, context translations JSON, identity_display, display_name encrypted, placement_type, project_id nullable, service_id nullable, snapshot JSON, snapshot_hash, status, created_by, sent_for_approval_at. |
| athar_publication_consent_events | Append-only publication approval and withdrawal evidence. | contribution_id, publication_version_id, event_type, snapshot_hash, approved_locales JSON, placement snapshot JSON, privacy_version, verification_method, occurred_at, ip_hash, user_agent_hash. |

Use application-level encrypted casts for recipient email, recipient name, personal note, private payload, and display name. Use deterministic HMAC hashes only where a safe lookup is necessary. Never store access tokens or one-time codes in plaintext.

### 7.2 Eloquent models and relations

- AtharInvitation has one AtharContribution, optional legacy AtharAccessChallenges, and belongs to the creating User.
- AtharContribution belongs to AtharInvitation and has many AtharPublicationVersions and AtharPublicationConsentEvents.
- AtharPublicationVersion belongs to AtharContribution, optionally belongs to Project or Service, and has many consent events.
- AtharPublicationConsentEvent belongs to AtharContribution and AtharPublicationVersion.

### 7.3 Enums

- AtharInvitationStatus: Draft, Active, Submitted, Declined, Revoked.
- AtharInvitationDeliveryMode: Email (the private link was also sent by email) or Link (the private link was shared manually).
- AtharPublicationStatus: Draft, AwaitingAdminReview, AwaitingContributorApproval, Published, Hidden, Withdrawn.
- AtharResponseMode: Freeform, Guided.
- AtharPublicationOrigin: ExactSource, ContributorEdited, IbrahimSuggested.
- AtharIdentityDisplay: FullName, FirstName, Anonymous.
- AtharPlacement: Project, Service, About.
- AtharConsentEventType: PublicationApproved, PublicationWithdrawn.
- AtharRelationship: FormerClient, Collaborator, PersonalConnection.

Expiry is derived from expires_at; it is not a scheduler-maintained state.

### 7.4 State rules

| Object | Allowed flow |
|---|---|
| Invitation | Draft → Active → Submitted or Declined; Active may become Revoked; expiry is computed. |
| Contribution | Draft → submitted private source → optional public choice. The submitted source never returns to a mutable state. |
| Publication version | Draft → AwaitingAdminReview → AwaitingContributorApproval → Published. It may return to Draft for contributor edits, become Hidden by an administrator, or become Withdrawn by the contributor. |
| Consent | Created only by atomic approval-and-publication. A withdrawal event recorded after approval blocks rendering immediately. |

An administrator may hide a version without claiming that the contributor withdrew consent. Re-showing a hidden version is allowed only when the matching consent remains active and the snapshot has not changed.

## 8. Security and privacy architecture

### 8.1 Recipient access

Access is explicit per invitation and never inferred from a missing email.

1. Every URL contains an opaque, random invitation token and no personal data.
2. Possession of the private link grants access to the invitation; the link remains short-lived, no-store, no-referrer, and revocable, while the localized privacy notice carries the durable bearer-link explanation.
3. Email is optional delivery only. The recipient does not need to type an email address or enter a code after opening the private link.
5. Approval, withdrawal, and deletion remain protected by the invitation access grant; any future capability split must use separate opaque capability tokens rather than reusing a public proof URL.
6. Token rotation invalidates active grants and all pending challenges for the invitation. A public proof link is always a contextual page URL, never an invitation or management token.

### 8.2 Route design

Register Athar inside the existing localized public-route groups so Arabic and English use stable equivalent routes while the opaque token remains unchanged.

| Method | Route purpose | Route name |
|---|---|---|
| GET | Invitation entry | athar.invitation.show |
| GET | Private reflection form | athar.contribution.create |
| POST | Save server-side draft | athar.contribution.draft |
| POST | Submit sealed private note | athar.contribution.store |
| POST | Decline invitation | athar.invitation.decline |
| GET and POST | Public-choice and contributor editor | athar.public-note.create and athar.public-note.store |
| GET and POST | Exact approval review | athar.approval.show and athar.approval.store |
| GET and POST | Contributor management and withdrawal | athar.manage.show and athar.withdraw.store |
| POST | Private-data deletion request | athar.deletion-request.store |

All public write routes use normal CSRF-protected forms and POST/redirect/GET. They are usable without JavaScript. Livewire is not the canonical public experience.

### 8.3 Route protection

- Apply dedicated rate limiters for drafts, submissions, approval, withdrawal, and deletion requests; keep the existing challenge limiter only for legacy compatibility endpoints.
- Rate-limit by invitation identifier and a keyed fingerprint of IP address.
- Add a honeypot to public write forms.
- Use generic unavailable responses for invalid, expired, revoked, rotated, or unauthorized access.
- Require idempotency keys for submission, approval, withdrawal, and deletion-request writes.
- Store no email, name, company, locale, or project detail in the URL.
- Set private responses to no-store and ensure forms are not browser-cacheable.

### 8.4 Private-surface isolation

Create a dedicated Athar Blade layout:

- no site navigation, public footer, cookie prompt, analytics, external embeds, social cards, or recipient-specific Open Graph data;
- semantic landmark, language, and direction set from the active locale;
- private support/contact path;
- direct link to the localized privacy notice;
- no essential state hidden behind animation or JavaScript.

Extend SetPrivacyHeaders so every athar.* route receives no-referrer behavior. Keep Athar routes out of sitemap generation, public analytics allowlists, robots-discoverable collections, and caching layers.

## 9. Bilingual, RTL, accessibility, and responsive requirements

### 9.1 Language behavior

- Arabic is the source composition; English is authored separately.
- The invitation stores a preferred locale but the contributor may switch locales without losing their verified access or draft.
- The language switch keeps the equivalent Athar route and opaque token.
- The form uses semantic document direction, logical CSS properties, and isolated bidi treatment for email addresses, URLs, project names, and codes.
- Every public version identifies source language and includes only contributor-approved translations.

### 9.2 Accessibility requirements

- All public Athar completion paths work with standard Blade forms and no JavaScript.
- Any retained legacy code endpoint uses a label, visible error, paste support, and clear resend timing; it is not shown in the normal invitation experience.
- Every primary action has at least a 44px target and visible focus state.
- Error, success, pending, unavailable, withdrawal, and deletion-request states use text rather than color alone.
- The contributor can complete every decision by keyboard.
- Private source and public preview use clear headings, not visual-only comparison treatment.
- Reduced-motion mode removes nonessential trace-line transition while preserving hierarchy.

### 9.3 Responsive content stress cases

Verify Arabic and English at 320–390px, 740–835px, laptop, and wide desktop with:

- long Arabic names and English organization names;
- long private notes;
- mixed Arabic, English, numerals, email addresses, and URLs;
- one-line, multi-line, and anonymous public attribution;
- validation errors, code expiry, repeated code request, saved draft, private receipt, returned-for-edit, published, hidden, withdrawn, and unavailable states.

## 10. Filament operations

### 10.1 Resource boundary

Create one AtharInvitationResource, visible only to administrators with Athar permissions. It is an operations surface, not a dashboard or a curation studio.

The resource has:

- list, create, and view pages;
- no unrestricted edit form for a sealed contribution or publication snapshot;
- focused actions that create a new version or event when change is required;
- a navigation badge for records awaiting an administrator safety review or contributor approval.

### 10.2 Invitation creation form

The form asks only for:

- optional invited email address for delivery;
- an explicit “send by email” toggle, disabled until the entered address is valid;
- optional recipient name for the personal greeting;
- relationship category;
- preferred language;
- personal reason for the invitation;
- prompt snapshot;
- expiry;
- an intended single context: Project, Service, or About.

It does not ask for company authority, public identity, media, social rights, or multiple placements.

### 10.3 Record view

The record view contains:

1. invitation details and verification-safe lifecycle;
2. the opaque shareable invitation link with a copy action;
3. delivery mode and dispatch state;
4. sealed private source, visibly marked private;
5. contributor public-choice status;
6. publication-version history with origin and exact snapshot hash;
7. append-only consent and withdrawal history;
8. one named website placement;
9. privacy deletion-request status;
10. internal notes that never enter a public snapshot.

### 10.4 Admin actions

| Action | Rule |
|---|---|
| Create invitation | Always creates an opaque, expiring shareable link. |
| Send invitation by email | Requires the explicit toggle and a valid recipient email; the email contains the same private link. |
| Copy direct link | Copies only the opaque invitation link from the protected admin record view. |
| Revoke or rotate invitation | Invalidates access grants and pending challenges. |
| Review private source | Requires Athar private-source view permission. |
| Return public note for clarification | Sends a respectful contributor message; no public version is created. |
| Add factual context and placement | Creates or updates only a draft publication version. |
| Add human-reviewed translation | Adds text to a new draft snapshot. |
| Prepare an optional shorter suggestion | Available only when the contributor requested it. |
| Send exact approval | Sends a version-bound review link after admin review. |
| Hide or restore | Never alters the approved snapshot. |
| View withdrawal or deletion request | Routes to the appropriate protected operation. |

### 10.5 Permissions and policies

Define focused permissions:

- view_any athar_invitations
- view athar_invitations
- create athar_invitations
- send athar_invitations
- view_private athar_contributions
- review athar_publications
- publish athar_publications
- manage athar_retention

Super administrators retain full access. Administrators receive the full Athar set. Editors receive no Athar access by default because private reflections require a stricter boundary.

Create policies for AtharInvitation, AtharContribution, and AtharPublicationVersion. Add a dedicated idempotent permission-sync action and Artisan command because existing roles do not receive newly created permissions automatically.

## 11. Notifications and communication

All messages are localized, queued after the database transaction commits, and contain no private-note content in the email body.

| Notification | Recipient | Purpose |
|---|---|---|
| Athar invitation | Contributor | Personal invitation link and no-pressure expectation. |
| Private-note receipt | Contributor and authorized administrator | Confirms private receipt; no public permission implied. |
| Exact-card approval | Contributor | Version-bound approval link and one-page scope. |
| Publication or withdrawal confirmation | Contributor | Confirms what is live or removed and includes management link. |
| Deletion-request receipt | Contributor and authorized administrator | Confirms a distinct private-data request. |

There is no automated reminder sequence. The system does not report individual open or start tracking to Ibrahim.

## 12. Retention and legal implementation boundary

Athar must be represented accurately in the Arabic and English privacy notices before external invitations are sent:

- categories of private reflection, public card, security verification, and consent evidence;
- purpose of private collection and distinct purpose of public publication;
- website-only public scope;
- retention configuration;
- withdrawal and deletion-request paths;
- contact route for rights requests;
- processor, transfer, and jurisdiction disclosures if applicable.

The plan does not claim legal compliance by wording alone. Production configuration and public notice must be reviewed against the applicable law and actual processor setup. Saudi PDPL guidance recognizes information, access, deletion, and consent-withdrawal rights; this plan operationalizes those controls without substituting for legal advice.

Reuse the existing privacy retention preview/force pattern:

- add Athar retention configuration keys for sealed private contributions, expired challenges, withdrawn public snapshots, and resolved deletion requests;
- add indexed retention eligibility fields;
- extend the privacy purge action and command with preview-first output;
- never enable destructive retention in production until approved periods are configured and the existing retention gate is enabled;
- preserve minimum consent evidence only for the approved legal retention period.

## 13. Technical implementation blueprint

### 13.1 Models, factories, enums, and migrations

Create:

- AtharInvitation, AtharAccessChallenge, AtharContribution, AtharPublicationVersion, and AtharPublicationConsentEvent models;
- factories for all five models with explicit states for active, expired, submitted, awaiting approval, published, hidden, and withdrawn;
- enums from Section 7.3;
- migrations for the five tables and indexes required for token hash lookup, invitation state, expiry, version sequence, current publication lookup, consent lookup, and retention purge;
- model guards or service-level invariants that prevent modification of a sealed source or an approved snapshot.

### 13.2 Actions and services

Create single-purpose actions:

- CreateAtharInvitation
- IssueAtharAccessChallenge
- VerifyAtharAccessChallenge
- SaveAtharContributionDraft
- SealAtharContribution
- CreateContributorPublicNote
- CreateIbrahimSuggestedVersion
- PrepareAtharPublicationVersion
- SendAtharApproval
- ApproveAndPublishAtharVersion
- WithdrawAtharPublication
- RequestAtharPrivateDataDeletion
- PurgeExpiredAtharData
- SyncAtharPermissions

The approval action wraps snapshot validation, consent-event creation, publication state change, and notification dispatch in one database transaction. It refuses stale, withdrawn, hidden, mismatched, or unverified versions.

### 13.3 Controllers, requests, and middleware

Create normal HTTP controllers and Form Requests for each route in Section 8.2. Form Requests own localized validation and authorization. Controllers stay thin and delegate all mutation to actions.

Create:

- EnsureAtharVerifiedAccess middleware for invitation, contribution, approval, and management actions;
- a scoped Athar rate-limit configuration in AppServiceProvider;
- private-route handling in SetPrivacyHeaders;
- Athar-specific sitemap and analytics exclusions;
- a localized private-layout view composer only if the existing layout cannot provide the required isolation safely.

### 13.4 Views and translations

Create:

- a dedicated Athar layout;
- Blade views for all screens in Section 4.2;
- reusable components for private status, code entry, source panel, public-note editor, exact card preview, consent scope, and withdrawal confirmation;
- lang/ar/athar.php and lang/en/athar.php;
- focused additions to lang/ar/admin.php and lang/en/admin.php;
- focused additions to lang/ar/legal.php and lang/en/legal.php.

Use existing public typography, logical direction utilities, focus treatment, violet action language, and geometric pattern sparingly. Do not add a separate visual system or a generic card grid.

### 13.5 Filament files

Create:

- AtharInvitationResource;
- list, create, and view pages;
- invitation form schema;
- table definition;
- infolist and relation-style version/consent sections;
- resource actions for the operations in Section 10.4;
- policies, permission synchronization, and relevant localized labels.

### 13.6 Existing files to change

| Existing file or area | Required change |
|---|---|
| routes/web.php | Add localized Athar private routes with opaque-token handling. |
| app/Providers/AppServiceProvider.php | Register models, policies, and scoped Athar rate limiters. |
| app/Http/Middleware/SetPrivacyHeaders.php | Treat athar.* routes as sensitive. |
| app/Actions/Privacy/PurgeExpiredPersonalData.php | Add Athar preview and purge behavior. |
| app/Console/Commands/Privacy/PurgeExpiredPersonalData.php | Report Athar eligible records and retain preview-first safeguards. |
| config/legal.php | Add approved Athar retention configuration keys. |
| database/seeders/RoleSeeder.php | Register permissions without relying on newly created roles only. |
| resources/views/website work, service, and about surfaces | Render a current approved Athar card only at its single matching placement. |
| sitemap and analytics surfaces | Explicitly exclude private Athar routes and data. |
| PRODUCT.md | Record contributor-led proof as a durable product invariant. |

## 14. Single complete-release work breakdown

This is execution order inside one continuous implementation session. Completion means every item below is built, reviewed, and verified before the feature is considered ready.

1. Confirm legal copy, retention values, privacy-notice changes, and the exact website-only scope.
2. Create enums, migrations, models, factories, policies, permissions, and the permission-sync command.
3. Implement token issuance, expiring private-link access, rate limits, privacy headers, and private route isolation.
4. Build normal POST/redirect/GET private reflection screens, server-side drafts, sealing, decline, private receipt, and deletion request.
5. Build contributor-owned public-note selection and editing, including the explicitly requested shorter-suggestion route.
6. Build admin review, factual context, placement, translation, exact snapshot creation, approval email, atomic approval-and-publication, hide, and withdrawal.
7. Add contextual public-card rendering to Project, Service, and About surfaces with locale-aware consent checks.
8. Add all Arabic and English copy, privacy-policy content, accessible focus/error/success states, and responsive styling.
9. Implement retention preview/purge support and queued localized notifications.
10. Write and run the complete automated test contract, then perform browser and email QA in both locales.

## 15. Automated test contract

Create focused PHPUnit coverage:

| Test area | Required proof |
|---|---|
| Invitation access | Invalid, expired, revoked, rotated, and forwarded tokens never reveal private content. |
| Delivery | Every invitation opens through an expiring private link; email delivery is optional, warns about bearer access, and has expiry/revocation coverage. |
| Private-link access | Valid, unexpired, non-revoked opaque links open the private flow; invalid, expired, revoked, and rotated links reveal no private content. |
| Private reflection | Draft saves, normal no-JavaScript POST submission, receipt, decline, and idempotency work. |
| Source immutability | A sealed source cannot be modified through models, actions, or admin forms. |
| Public-note authorship | Exact-source and contributor-edited notes retain correct origin and provenance. |
| Suggested-shortening route | It exists only after explicit contributor request and can be rejected or replaced. |
| Version integrity | Material changes create a new snapshot hash and invalidate old consent. |
| Approval and publication | Verified approval atomically creates consent and renders exactly one page placement. |
| Withdrawal | Withdrawal hides every Athar-controlled rendering immediately and blocks republish without new approval. |
| Locale behavior | Arabic and English routes, copy, direction, long content, and approved translation behavior are correct. |
| Private-surface safety | No private route enters sitemap, analytics, cache, referrer, or public Open Graph output. |
| Filament authorization | Every resource and action obeys policy and existing-role permission sync. |
| Notifications | Locale, after-commit queueing, redacted email content, and management links are correct. |
| Retention | Preview is non-destructive; force path needs configured enablement and affects only eligible records. |
| Public rendering | A card never renders on the wrong page, wrong locale, withdrawn version, or unmatched snapshot. |

Run the focused test files as implementation completes. Run formatter on modified PHP. Run the frontend build after public or private view styling changes.

## 16. Visual and interaction QA

Verify all private screens and public proof cards in Arabic and English:

- 320–390px mobile;
- 740–835px intermediate width;
- laptop;
- wide desktop;
- keyboard-only navigation;
- 200% zoom;
- reduced motion;
- no-JavaScript form completion;
- long Arabic and mixed-direction content;
- valid and invalid code, error, save, submitted, private-only, public-choice, editor, suggested-edit, awaiting approval, published, hidden, withdrawn, deletion-request, and unavailable states.

The review must confirm that the exact public card never makes the contributor feel like they are approving a marketing rights matrix. It should read as one clear decision: publish this text, on this page, under this name choice.

## 17. Risks and owning controls

| Risk | Owning control |
|---|---|
| A contributor feels pressured to help with marketing. | The writing remains contributor-owned, publication requires an exact preview and explicit approval, and private use remains available at the approval point. |
| Ibrahim's wording is mistaken for the contributor's voice. | Contributor-owned text is separate from a labeled factual context line; suggestion route is explicit opt-in. |
| Forwarded link exposes private content. | Expiry, revocation, no-store, no-referrer, and generic unavailable responses limit exposure; the localized privacy notice carries the bearer-link explanation. |
| Consent is too broad. | One exact text card, named page, named locales, and no external use. |
| Withdrawal promise is misleading. | Scope is website-only; withdrawal removes Athar-controlled website rendering immediately. |
| Translation changes meaning. | Human review, exact preview, and locale-specific contributor approval. |
| Confidential information reaches public copy. | Administrator safety review occurs before the exact approval request. |
| Private pages leak into analytics or discovery. | Dedicated layout, sensitive headers, sitemap and analytics exclusions. |
| The site gains a generic testimonial wall. | Cards exist only in contextual Project, Service, or About placements. |
| Retention is ambiguous. | Reviewed configuration, preview-first purge, distinct withdrawal and deletion-request paths. |

## 18. Definition of done

Athar is complete only when:

- the contributor-led product contract and one-session scope are reflected in this plan and PRODUCT.md;
- private reflection, contributor-owned public note, optional requested suggestion, exact approval, contextual publication, withdrawal, and deletion request are all implemented;
- all recipient-sensitive actions require possession of the expiring private invitation link; the link is clearly disclosed as bearer access and never becomes a public proof URL;
- public publication is website-only, one named placement, text-only, and attributable only to contributor-selected text;
- every public card has a matching active snapshot-hash consent event;
- changes, hidden state, withdrawal, and deletion requests preserve the correct integrity boundary;
- Arabic and English copy is authored, natural, and complete;
- private routes are no-store, no-referrer, unindexed, analytics-free, and free of public social metadata;
- normal forms complete all essential public tasks without JavaScript;
- policies, existing-role permissions, notifications, retention preview/purge, and admin operations are covered;
- focused PHPUnit tests pass, modified PHP is formatted, assets build, and browser QA passes at the required widths and states;
- the legal notice and production retention configuration have been reviewed for the actual deployment and processor setup.

## 19. Explicit non-goals

The completed Athar feature does not include:

- QR codes, downloadable passes, print artifacts, or third-party shorteners;
- a testimonial wall, public Athar directory, ratings, counters, filters, or SEO collection page;
- media, voice, video, portraits, logos, organization endorsements, or social profiles;
- automatic AI rewriting, automatic translation, or silent edits;
- social posts, proposals, talks, sales materials, or any non-website distribution;
- multiple simultaneous placements;
- individual open/start surveillance, automated reminder sequences, or invasive contribution analytics;
- public proof from active clients, direct reports, or personal relationships presented as professional evidence;
- a promise that withdrawal deletes private source material automatically.

## 20. Legal reference note

Athar's privacy and consent controls are designed around explicit, scoped publication permission, a visible withdrawal path, and a separate deletion-request path. They must be validated against the actual applicable law, processors, retention periods, and deployment context before external use.

Useful primary references for that review:

- Saudi Data and AI Authority: Personal Data Protection Law knowledge center, including information, access, deletion, and consent-withdrawal rights: https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/details/GPDPL
- Saudi Data and AI Authority: guidance stating that withdrawal procedures must exist before requesting consent: https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/details/PDPL2/
