# Agricart — Product Rating & Customer Reviews  
## مختصر مشورہ نوٹ (Discussion Only — کوئی Development نہیں)

**تاریخ:** 24 July 2026  
**حیثیت:** صرف مشورہ / منظوری سے پہلے  
**اہم:** موجودہ **Brand Review سسٹم بہترین ہے — اسے تبدیل نہیں کریں گے۔**

---

## 1. دو الگ چیزیں (گڈھ ملاوٹ نہ کریں)

| قسم | کون دیتا ہے؟ | کیا ہے؟ | مثال |
|-----|--------------|---------|------|
| **A) Agricart Product Rating** | آپ / Admin (اندرونی جائزہ) | برانڈ ریویو جیسا editorial score | “Agricart نے اس product کو 8/10 دیا” |
| **B) Customer Product Reviews** | خریدار (آن لائن سٹور) | لوگوں کے ستارے + متن (+ بعد میں تصاویر) | “علی ★★★★☆ — پارٹس اچھے ہیں” |

برانڈ ریٹنگ **وہی رہے گی**۔  
پروڈکٹ کی اپنی ریٹنگ **الگ** ہو گی — کیونکہ ایک ہی برانڈ کی ہر پروڈکٹ ایک جیسی نہیں ہوتی۔

---

## 2. Agricart Product Rating (اندرونی) — برانڈ والا ماڈل

برانڈ ریویو کا **اسی انداز** کو پروڈکٹ پر لاگو کیا جا سکتا ہے:

- Admin product میں **1–10 rating** (یا چند مختصر criteria)
- Suggested messages / short reason (EN + UR)
- Score سے **ستارے + %** PDP اور لسٹنگ پر (جیسے اب برانڈ پر)
- ضرورت ہو تو **اس ایک پروڈکٹ** کی ریٹنگ بدل سکتے ہیں — برانڈ سکور تبدیل نہیں ہوتا

**اصول:**  
Brand score = برانڈ کی مجموعی ساکھ  
Product score = اس مخصوص شے کا Agricart جائزہ  

اگر product کی الگ ریٹنگ نہ دی ہو تو UI میں عارضی طور پر **برانڈ سکور دکھانا** ممکن ہے، مگر بہتر ہے ہر اہم پروڈکٹ کی اپنی ریٹنگ ہو۔

---

## 3. Customer Reviews (آن لائن سٹور) — Industry Standard Flow

```
خریدار آرڈر کرتا ہے
        ↓
آرڈر Deliver ہوتا ہے  (Verified purchase)
        ↓
Customer login سے Review لکھتا ہے
  (1–5 ستارے + مختصر متن؛ تصاویر بعد میں)
        ↓
Status = Pending  (سب کو فوراً نہیں دکھاتے)
        ↓
Admin چیک کرتا ہے
   ├─ Approve  → سٹور پر نظر آتا ہے
   ├─ Hide     → محفوظ مگر عوامی نہیں
   └─ Delete   → ختم / رد
```

### کون لکھے؟
- **تجویز:** صرف وہی جس نے وہ **پروڈکٹ خرید کر deliver** کروائی ہو (Verified)
- ایک customer + ایک product = عام طور پر **ایک فعال review** (edit ممکن)

### سٹور پر کیا دکھے؟
- صرف **Approved** reviews
- اوسط ستارے (مثلاً 4.2 / 5) + کل تعداد
- “Verified purchase” بیج
- پرانی سے نئی / مفید ترتیب

### Admin کیا کرے؟
| Status | مطلب |
|--------|------|
| **Pending** | نیا؛ ابھی عوامی نہیں — صف میں انتظار |
| **Approved** | سٹور پر نظر |
| **Hidden** | عارضی چھپا (غلط / متنازع / پرانا) |
| **Rejected / Deleted** | نامناسب، سپیم، غلط پروڈکٹ — ختم |

Admin کر سکتا ہے: منظوری، چھپانا، مٹانا، (بعد میں) جواب لکھنا۔

### تصاویر؟
- **پہلی phase:** صرف ستارے + متن + moderation  
- **دوسری phase:** اختیاری تصاویر (پہلے Pending، پھر Approve)

---

## 4. سٹور پر دکھاوٹ (صارف کو کیا نظر آئے)

**Product page (PDP):**
1. **Agricart product rating** (ستارے / % / مختصر) — آپ کا editorial score  
2. **Customer rating summary** — اوسط + “۱۲ reviews”  
3. **Customer review list** — Approved والے  

برانڈ والا Agricart review / % **برانڈ قطار میں** ویسا ہی رہ سکتا ہے۔

**Category listing:**  
چھوٹا chip — Agricart % اور/یا customer ★ اوسط (جب ڈیٹا ہو)۔

---

## 5. مرحلہ وار (جب کام شروع ہو — ابھی نہیں)

| مرحلہ | کام |
|-------|-----|
| **Phase 1** | Customer reviews: ستارے + متن، Verified purchase، Pending → Approve/Hide/Delete، PDP پر فہرست |
| **Phase 2** | Agricart Product Rating (برانڈ ماڈل جیسا، per-product override) + لسٹنگ/PDP chips |
| **Phase 3** | Review photos، admin reply، رپورٹ سپیم |

ترتیب بدل بھی سکتے ہیں — پہلے مشورہ طاین کریں۔

---

## 6. مختصر خلاصہ (باس / ٹیم کے لیے)

- Brand Review **مت بدلیں**۔  
- Product کی **اپنی** Agricart ریٹنگ ہو سکتی ہے (برانڈ سے الگ، تبدیل بھی ہو سکتی ہے)۔  
- لوگوں کے reviews **الگ سسٹم**: خرید → deliver → لکھیں → **Pending** → Admin **Approve / Hide / Delete** → صرف Approved سٹور پر۔  
- پہلے phase میں تصاویر ضروری نہیں۔  
- **ابھی development نہیں** — پہلے یہ نوٹ منظور / ترمیم۔

---

## 7. فیصلے جو بعد میں چاہییں (جب آپ تیار ہوں)

1. پہلے **Customer reviews** شروع کریں یا پہلے **Agricart product rating**؟  
2. Review صرف **delivered order** کے بعد؟ (تجویز: ہاں)  
3. پہلی phase میں **تصاویر**؟ (تجویز: نہیں)

جواب آنے تک کوئی کوڈ نہیں لکھا جائے گا۔
