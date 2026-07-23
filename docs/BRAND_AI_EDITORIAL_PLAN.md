# Agricart — Brand Review AI Editorial Quality Plan

**Status:** Proposal only — no development until approved  
**Date:** 23 July 2026  
**Module:** Brand Review + About the Brand (AI writing)

---

## 1. Purpose

Brand Review Module میں AI سے جو متن بنتا ہے اس میں کئی editorial مسائل نظر آتے ہیں۔ زیادہ تر مسائل **code bugs** نہیں بلکہ **AI Prompt** کی وجہ سے ہیں۔

اس پلان کا مقصد:

1. مسائل کی صحیح قسم طے کرنا (Prompt / Backend / Rule-based)
2. بہترین architecture تجویز کرنا
3. مرحلہ وار حل — پہلے Prompt، بعد میں code (اگر ضرورت پڑے)

**اس مرحلے میں کوئی development شروع نہیں ہوگی۔** منظوری کے بعد کام شروع ہوگا۔

---

## 2. Observed Issues

| # | Issue | Example / Nature |
|---|--------|------------------|
| 1 | Marketing language | AI promotional / sales tone استعمال کرتا ہے؛ ہمیں editorial + neutral چاہیے |
| 2 | Unverified claims as facts | جیسے “Some users report…”, “Customers often face…” — غیر ثابت شدہ دعوے |
| 3 | Summary adds new points | Summary میں ایسی باتیں جو اوپر ratings/reasons میں نہیں |
| 4 | Reasons too long | Reasons لمبی ہو جاتی ہیں؛ چاہیے 1–2 لائن، وزنی، professional |
| 5 | Brand name repeated | بار بار brand name؛ editorial میں “the brand / its products” بہتر |
| 6 | About the Brand promotional | Brand introduction بھی marketing لگتی ہے؛ چاہیے neutral intro |

**Preference:** اگر یہ سب Prompt Engineering سے حل ہو سکیں تو پہلے Prompt بہتر بنائیں؛ code بعد میں۔

---

## 3. Current System (Technical Context)

دو الگ AI flows ہیں:

### A) Agricart Brand Review AI
- **File:** `app/Services/Catalog/BrandAgricartReviewAiService.php`
- **کام:** ہر review field کی reason (EN/UR) + overall review summary
- **موجودہ prompt مسئلہ:** “praise strengths… buying guidance” اور “1–3 sentences” — اسی سے marketing + لمبائی + loose summary آتی ہے

### B) About the Brand AI (Enrichment)
- **File:** `app/Services/Catalog/BrandEnrichmentService.php`
- **Default prompt:** `lang/en/agricart.php` → `brands.ai.default_prompt`
- **موجودہ مسئلہ:** “customer-facing”, “trust points” — promotional introduction کی طرف دھکیلتا ہے

### پہلے سے موجود Rule-based summary
- **File:** `app/Support/Catalog/BrandAgricartReviewCriteria.php` → `composeReview()`
- Ratings + reasons سے **deterministic** summary بنتی ہے
- AI “polish” اس template کو bypass کر کے نیا متن لکھ دیتی ہے — اسی سے Issue #3 بڑھتا ہے

---

## 4. Issue → Solution Type Matrix

| # | Issue | Primary fix (Phase 1) | If still needed later |
|---|--------|------------------------|------------------------|
| 1 | Marketing language | **Prompt** — editorial voice + banned phrases | Soft reject / regenerate |
| 2 | Unverified “facts” | **Prompt** — no invented market claims | Pattern/regex guard on output |
| 3 | Summary invents content | **Prompt** + prefer **Rule-based** summary | AI reasons-only; summary from `composeReview()` |
| 4 | Long reasons | **Prompt** — max 1–2 short sentences (~35–40 words) | Backend length reject/trim |
| 5 | Brand name spam | **Prompt** — name once, then “the brand / its products” | Optional light normalize |
| 6 | About the Brand promo | **Prompt** — neutral catalog intro only | Shorter max length + system role |

---

## 5. Recommended Architecture (3 Layers)

```
Admin ratings + suggested reasons
        │
        ▼
Strict editorial System Prompt + User Prompt
        │
        ▼
LLM returns JSON (reasons + optional summary)
        │
        ▼
Parse / normalize
        │
        ▼
Optional guards (length, banned claims) ──► optional 1 retry
        │
        ▼
Apply to brand form (human can still edit)

Parallel / fallback:
  ratings + reasons ──► composeReview() (rule-based summary)
```

### Layer A — Prompt (Phase 1 — Primary)
Fixed editorial policy + strict writing rules۔ سب سے سستا اور تیز۔

### Layer B — Rule-based (Phase 2 — Summary)
Overall summary ڈیفالٹ `composeReview()` سے؛ AI صرف field reasons لکھے  
(یا AI کو صرف template polish کرنے دیا جائے — نئی باتیں شامل نہ کرے)۔

### Layer C — Backend guards (Phase 2/3 — Safety net)
Word/sentence limits، banned claim phrases، optional one regenerate۔

**نتیجہ:** AI style بہتر، summary قابلِ اعتبار، guards آخری حفاظتی جال۔

---

## 6. What Should Be Rule-Based vs AI?

| Output | Recommendation |
|--------|----------------|
| Overall score (1–10 average) | **Rule-based** — AI نہ چھوائے |
| Suggested default messages (Settings) | **Rule-based / DB** — AI ratings نہ بدلے |
| Per-field reasons | **AI OK** — سخت prompt + length rules کے ساتھ |
| Full review summary | **Rule-based default بہتر**؛ AI صرف اختیاری polish |
| About the Brand text | **Short AI OK** اگر neutral prompt؛ ورنہ admin دستی |

---

## 7. Phase 1 — Prompt Only (First Delivery)

کم سے کم code change۔ زیادہ تر prompt text۔

### 7.1 Agricart Review Prompt Rewrite

Target: `BrandAgricartReviewAiService::buildPrompt()`  
Better: **System message** (editorial policy) + **User message** (brand + checklist data)

**Voice**
- Independent retailer assessment — neutral, measured, editorial
- No marketing / sales tone
- No superlatives: best, unmatched, premium, must-buy, trusted by thousands, etc.
- No call-to-action / buying pitch

**Evidence rules**
- Use ONLY given rating bands + existing reason notes
- Do NOT invent surveys, “customers often…”, “dealers say…”, certifications, awards, or numbers unless already in admin notes
- If detail unknown: cautious, band-aligned sentence — no fabricated specificity

**Length**
- Each reason: **1–2 short sentences**, ~35–40 words max (EN); Urdu equally concise
- Summary: **2–4 short paragraphs**, checklist summary only

**Naming**
- Brand name **once** (intro); then “the brand / its products / after-sales support”

**Summary rule (strict)**
- “Summarize ONLY the ratings and reasons above. Do not introduce any criterion outcome or claim not present in the checklist.”

**Few-shot examples**
- One good Positive / Neutral / Needs Improvement reason
- One good vs bad summary — reduces hallucination

### 7.2 About the Brand Prompt Rewrite

Targets:
- `lang/en/agricart.php` → `brands.ai.default_prompt`
- Hardcoded suffix in `BrandEnrichmentService` (“customer-facing…”)

**Required tone**
- Neutral catalog introduction: what the brand sells, category, market context
- No trust slogans, no “leading / trusted / premium”
- 2–4 short sentences EN + UR; factual only

---

## 8. Phase 2 — Backend / Rule-based (Only If Phase 1 Leaves Gaps)

1. **Summary strategy (most important logic change)**  
   - Preferred: AI writes only `criteria.*.reason_en/ur`  
   - `review_en/ur` = `composeReview()` (or tightly constrained template polish)  
   - This largely eliminates Issue #3

2. **Post-parse guards**  
   - Reason length cap (reject or trim at sentence boundary)  
   - Banned phrase list (EN + UR) → fail + one stricter retry  
   - Optional: brand-name occurrence limit

3. **System role message** if OpenRouter client supports it cleanly

4. **Tests**  
   - Prompt contains required editorial rules  
   - Guards reject bad fixture JSON  
   - `composeReview` regression

---

## 9. Phase 3 — Only If Drift Continues

- Manageable “AI editorial policy” text in Settings (usually unnecessary early)
- Second-pass “editor” AI call (cost/latency — avoid first)
- Lower temperature / max_tokens if client exposes them

---

## 10. Success Criteria (Acceptance)

After Phase 1 (and Phase 2 if needed):

- [ ] Reasons short, band-matched, no fabricated market claims  
- [ ] Summary does not invent strengths/weaknesses outside checklist  
- [ ] Tone is editorial — not marketing / CTA  
- [ ] Brand name not repeated excessively  
- [ ] About the Brand is a neutral intro  
- [ ] Admin can still edit AI draft on the form before save  

---

## 11. Implementation Order (After Approval)

1. Phase 1: Rewrite review + About-the-Brand prompts (system message if easy)  
2. Manual QA on several brands (strong / mixed / weak scores)  
3. If Issue #3 remains → Phase 2 rule-based summary  
4. If Issues #2 / #4 remain → light guards + tests  
5. Commit / deploy only after QA  

---

## 12. Decision Requested

Please review and reply with one of:

- **APPROVED** — proceed with Phase 1 (prompt-first) as written  
- **APPROVED WITH CHANGES** — list changes  
- **NOT APPROVED** — state preferred direction  

No code will be written until this plan is approved.
