<?php

/**
 * آسان بریف — Contacts
 * نمونہ: Suppliers form میری طرز؛ Customers مشورہ والا
 */
return array (
  'customers' => 
  array (
    'list' => 
    array (
      'title' => 'گاہکوں کی فہرست (Customers List)',
      'summary' => 'وہ صفحہ جہاں سب گاہک قطاروں میں نظر آتے ہیں؛ تلاش، فلٹر، اور ایڈ/ایڈٹ یہاں سے کھلتا ہے۔',
      'article' => 
      array (
        0 => 'Contacts ماڈیول میں Customers صفحہ کھولنے پر سب سے پہلے فہرست نظر آتی ہے۔ یہ وہ جگہ ہے جہاں آپ دکانی/آف لائن گاہک تلاش کرتے ہیں، دیکھتے ہیں، ترمیم کرتے ہیں، یا نیا گاہک شامل کرنے کا بٹن دباتے ہیں۔',
        1 => 'فہرست کے اوپر ٹول بار ہوتا ہے: سرچ باکس، فلٹر، کالمز، اور سبز Add Customer بٹن۔ Add دبانے سے دائیں طرف فارم پینل کھلتا ہے — الگ “Add page” URL نہیں، بلکہ شیئرڈ فارم پینل۔',
        2 => 'ہر قطار میں عموماً نام، نمبر، فون، شہر، بیلنس/حد، اور ایکشنز (View / Edit) ہوتے ہیں۔ آن لائن سٹور سے رجسٹرڈ گاہک الگ چینل سے دکھ سکتے ہیں؛ ایڈمن ان کے کچھ فیلڈز صرف پڑھنے کے لیے رکھتا ہے۔',
      ),
      'fields' => 
      array (
        0 => 
        array (
          'name' => 'search',
          'label' => 'تلاش',
          'note' => 'نام، نمبر، فون وغیرہ سے تلاش',
        ),
        1 => 
        array (
          'name' => 'filter',
          'label' => 'فلٹر',
          'note' => 'فعال/غیر فعال وغیرہ',
        ),
        2 => 
        array (
          'name' => 'columns',
          'label' => 'کالمز',
          'note' => 'کون سے کالم دکھانے ہیں',
        ),
        3 => 
        array (
          'name' => 'Add Customer',
          'label' => 'نیا گاہک',
          'note' => 'فارم پینل کھولتا ہے (create)',
        ),
      ),
      'improvements' => 
      array (
        0 => 
        array (
          'text' => 'فہرست میں بیلنس اور آخری سیل کی تاریخ دکھائیں',
          'suggest' => 'add',
        ),
        1 => 
        array (
          'text' => 'آن لائن / آف لائن الگ فلٹر واضح رکھیں',
          'suggest' => 'keep',
        ),
        2 => 
        array (
          'text' => 'حذف (delete) احتیاط سے — اگر ledger ہو تو روکیں',
          'suggest' => 'add',
        ),
        3 => 
        array (
          'text' => 'ایکسپورٹ CSV (اختیاری)',
          'suggest' => 'optional',
        ),
      ),
      'technical' => 
      array (
        0 => 'صفحہ: /admin/contacts/customers',
        1 => 'Livewire فہرست: ListCustomers',
        2 => 'اجازت: contacts.view؛ Add کے لیے contacts.create',
        3 => 'نیا ریکارڈ: customers جدول + فون contacts',
      ),
      'prompt' => 'نئے Agricart سسٹم میں Contacts → Customers کی فہرست صفحہ بنانا ہے۔

مقصد:
- گاہکوں کی فلیٹ فہرست، سرچ، فلٹر، کالمز، اور Add Customer بٹن۔
- Add/Edit الگ URL نہیں — شیئرڈ فارم پینل کھلے (جیسے موجودہ Agricart)۔

فہرست میں کم از کم دکھائیں:
- گاہک نمبر، ڈسپلے نام، فون، شہر، فعال/غیر فعال، کھلے کریڈٹ/بیلنس اگر دستیاب ہو۔
- قطار ایکشنز: View، Edit۔

بہتری (رکھیں جب تک میں ٹک نہ کر دوں):
- آن لائن/آف لائن فلٹر
- اگر گاہک پر ledger ہو تو delete روکیں
- اختیاری CSV ایکسپورٹ بعد میں

زبان: لیبلز انگریزی UI میں، لیکن کوڈ صاف اور سادہ رکھیں۔ پہلے فہرست + ٹول بار وائرنگ مکمل کریں، فارم الگ بریف میں ہے۔',
      'ui_flow' => 
      array (
        0 => 'Contacts → Customers کھلتے ہی صفحہ بند فہرست (15 فی صفحہ، نئے پہلے) دکھاتا ہے۔',
        1 => 'ٹول بار: سرچ (flex-grow)، Filter dropdown، Columns dropdown، سبز Add Customer۔',
        2 => 'Add Customer → شیئرڈ فارم پینل (create)؛ قطار میں View / Edit اسی پینل کو کھولتے ہیں۔',
        3 => 'Filter: channel (online/offline) + status (active/inactive)؛ Columns: کالم چھپائیں/دکھائیں۔',
        4 => 'Delete قطار سے (تصدیق کے ساتھ) — صرف contacts.delete اجازت پر۔',
      ),
      'functional_requirements' => 
      array (
        0 => 'FR-01: تمام گاہکوں کی صفحہ بند فہرست (online + offline ایک ساتھ)۔',
        1 => 'FR-02: سرچ: customer_number، display_name، name_en/ur، first/last، phone، email، city۔',
        2 => 'FR-03: فلٹر channel اور status۔',
        3 => 'FR-04: کالم toggle (کم از کم name رہے)۔',
        4 => 'FR-05: Add / View / Edit فارم پینل کھولیں۔',
        5 => 'FR-06: Delete (اجازت پر)۔',
      ),
      'validation' => 
      array (
        0 => 'لسٹ صفحہ — کوئی فارم validation نہیں',
        1 => 'Delete: صرف contacts.delete (یا Super Admin)',
        2 => 'خالی سرچ/فلٹر → مکمل فہرست',
      ),
      'database' => 
      array (
        0 => 'جدول customers سے پڑھتا ہے (paginate 15)',
        1 => 'ترتیب: created_at desc پھر id desc',
      ),
      'api' => 
      array (
        0 => 'کوئی عوامی REST نہیں — Livewire ListCustomers',
        1 => 'events: customers-search، customers-filter، customers-clear-filters، customers-column-toggle، customers-saved',
        2 => 'open-form-panel (formKey: customers) سے فارم کھلتا ہے',
      ),
      'permissions' => 
      array (
        0 => 'فہرست/سرچ: contacts.view',
        1 => 'Add: contacts.create؛ Edit: contacts.edit؛ Delete: contacts.delete',
        2 => 'Super Admin کو مکمل رسائی',
      ),
      'edge_cases' => 
      array (
        0 => 'online اور offline گاہک ایک ہی فہرست میں؛ channel فلٹر سے الگ',
        1 => 'delete پر ledger guard نہیں — بہتری میں تجویز',
        2 => 'تمام کالم بند کریں تو name خودکار واپس آ جاتا ہے',
      ),
      'business_rules' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'non_functional_requirements' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'dependencies' => 
      array (
        0 => 'Customers Form — Add/View/Edit فارم پینل',
        1 => 'Sales / POS، Store — گاہک انتخاب',
        2 => 'Reports → Customer Ledger',
        3 => 'Contacts Overview — customers کارڈ',
      ),
    ),
    'form' => 
    array (
      'title' => 'گاہک شامل کریں / ترمیم (Add Customer Form)',
      'summary' => 'فارم پینل میں آف لائن گاہک بنانا: نام، فون، پتہ، شہر، کریڈٹ، تصویر، AI مدد۔',
      'article' => 
      array (
        0 => 'جب آپ Add Customer دباتے ہیں تو دائیں طرف فارم پینل کھلتا ہے۔ آف لائن (دکان) گاہک کے لیے یہی اصل فارم ہے۔ آن لائن گاہک عموماً سٹور سے بنتے ہیں؛ ایڈمن انہیں دیکھ سکتا ہے مگر کئی فیلڈز صرف read-only ہوتے ہیں۔',
        1 => 'سب سے اہم بات: کم از کم ایک درست موبائل نمبر لازمی ہے، اور ڈسپلے نام لازمی ہے۔ گاہک نمبر سسٹم خود نکالتا ہے (خودکار)۔ ای میل اگر خالی ہو تو سسٹم چھپی ہوئی آف لائن ای میل خود لگا دیتا ہے تاکہ ڈیٹا بیس کا unique email ٹوٹے۔',
        2 => 'AI بٹن انگریزی/اردو نام اور پتہ بھرنے میں مدد دے سکتا ہے — پہلے Settings میں AI جڑا ہونا چاہیے۔ Save & Close محفوظ کر کے پینل بند کرتا ہے؛ Save & Add Next اگلا گاہک بنانے کے لیے فارم صاف کرتا ہے۔',
        3 => 'پرانے سسٹم میں کچھ “لیگسی” فیلڈز بھی بھرے جاتے ہیں (first_name، address_line1 وغیرہ) تاکہ آن لائن/آف لائن ایک ہی جدول میں رہیں۔ نئے سسٹم میں آپ انہیں سادہ رکھ سکتے ہیں یا صاف ماڈل بنا سکتے ہیں — نیچے بہتری دیکھیں۔',
      ),
      'fields' => 
      array (
        0 => 
        array (
          'name' => 'customer_number',
          'label' => 'گاہک نمبر',
          'note' => 'خودکار؛ دستی ضروری نہیں',
        ),
        1 => 
        array (
          'name' => 'display_name',
          'label' => 'ڈسپلے نام',
          'note' => 'لازمی',
        ),
        2 => 
        array (
          'name' => 'name_en / name_ur',
          'label' => 'نام انگریزی / اردو',
          'note' => 'اختیاری؛ AI بھر سکتا ہے',
        ),
        3 => 
        array (
          'name' => 'phoneRows',
          'label' => 'موبائل نمبر (ایک یا زیادہ)',
          'note' => 'کم از کم ایک لازمی؛ سب منفرد',
        ),
        4 => 
        array (
          'name' => 'address_quick',
          'label' => 'جلدی پتہ',
          'note' => 'مختصر پتہ',
        ),
        5 => 
        array (
          'name' => 'address_en / address_ur',
          'label' => 'تفصیلی پتہ',
          'note' => 'اختیاری دو لسانی',
        ),
        6 => 
        array (
          'name' => 'city_key / city_en / city_ur',
          'label' => 'شہر',
          'note' => 'پاکستانی شہروں کی فہرست سے',
        ),
        7 => 
        array (
          'name' => 'opening_balance',
          'label' => 'افتتاحی بیلنس',
          'note' => 'اختیاری نمبر',
        ),
        8 => 
        array (
          'name' => 'credit_limit',
          'label' => 'کریڈٹ حد',
          'note' => 'اختیاری',
        ),
        9 => 
        array (
          'name' => 'email',
          'label' => 'ای میل',
          'note' => 'اختیاری؛ خالی ہو تو سسٹم آف لائن ای میل لگاتا ہے',
        ),
        10 => 
        array (
          'name' => 'accepts_marketing',
          'label' => 'مارکیٹنگ قبول',
          'note' => 'چیک باکس',
        ),
        11 => 
        array (
          'name' => 'is_active',
          'label' => 'فعال',
          'note' => 'چیک باکس',
        ),
        12 => 
        array (
          'name' => 'profile photo',
          'label' => 'تصویر',
          'note' => 'اختیاری اپلوڈ',
        ),
        13 => 
        array (
          'name' => 'AI',
          'label' => 'AI بٹن',
          'note' => 'نام/پتہ enrichment',
        ),
      ),
      'improvements' => 
      array (
        0 => 
        array (
          'text' => 'ڈسپلے نام + کم از کم ایک فون رکھیں (ضروری)',
          'suggest' => 'keep',
        ),
        1 => 
        array (
          'text' => 'خودکار گاہک نمبر رکھیں',
          'suggest' => 'keep',
        ),
        2 => 
        array (
          'text' => 'چھپی آف لائن جعلی ای میل کی بجائے email کو حقیقی طور پر اختیاری بنائیں (اسکیما صاف کریں)',
          'suggest' => 'add',
        ),
        3 => 
        array (
          'text' => 'first_name/last_name لیگسی ڈپلیکیٹ کم کریں — ایک نام ماڈل',
          'suggest' => 'add',
        ),
        4 => 
        array (
          'text' => 'CNIC / NTN فیلڈ (اگر B2B چاہیے)',
          'suggest' => 'optional',
        ),
        5 => 
        array (
          'text' => 'WhatsApp نمبر الگ فیلڈ',
          'suggest' => 'optional',
        ),
        6 => 
        array (
          'text' => 'AI رکھیں مگر Save سے پہلے ہمیشہ جائزہ',
          'suggest' => 'keep',
        ),
        7 => 
        array (
          'text' => 'آن لائن گاہک ایڈمن سے “نیا بنائیں” بند رکھیں — صرف سٹور رجسٹریشن',
          'suggest' => 'keep',
        ),
      ),
      'technical' => 
      array (
        0 => 'جدول: customers؛ فون JSON/contacts؛ profile_photo_path',
        1 => 'سروس: CustomerService (validateOffline، nextCustomerNumber)',
        2 => 'AI: CustomerEnrichmentService',
        3 => 'فارم پینل formKey: غالباً customers / contacts pattern',
        4 => 'اجازت: contacts.create / contacts.edit',
      ),
      'prompt' => 'نئے Agricart سسٹم میں Add/Edit Customer فارم بنانا ہے (Contacts ماڈیول، فارم پینل)۔

مقصد (آف لائن / دکان گاہک):
ایک گاہک ریکارڈ بنائیں جسے POS اور Customer Ledger بعد میں استعمال کریں۔

لازمی رویہ:
1) display_name لازمی
2) کم از کم ایک موبائل نمبر لازمی؛ نمبرز منفرد ہوں
3) customer_number خودکار جنریٹ ہو (دستی نہ مانگیں)
4) شہر پاکستان شہر فہرست سے منتخب ہو سکے
5) opening_balance اور credit_limit اختیاری
6) پروفائل تصویر اختیاری
7) AI بٹن: نام/پتہ دو لسانی بھرے، مگر Save الگ ہو
8) Footer: AI → Save & Add Next → Save & Close → Cancel
9) View موڈ میں سب کچھ read-only

پرانے سسٹم سے سبق (بہتری):
- جعلی آف لائن ای میل مجبور نہ کریں اگر ڈیٹا بیس email کو nullable کر سکتے ہو
- first_name/last_name ڈپلیکیٹ لیگسی سے بچیں؛ صاف فیلڈ سیٹ رکھیں
- آن لائن گاہک اس فارم سے create نہ کریں

UI: مربع کونے، Arial، سبز پرائمری — Agricart ڈیزائن لینگویج۔
پہلے فارم + سروس + validation مکمل کریں؛ لسٹ الگ بریف میں ہے۔',
      'ui_flow' => 
      array (
        0 => 'Contacts → Customers فہرست پر Add Customer دبائیں → دائیں شیئرڈ فارم پینل (create) کھلتا ہے۔',
        1 => 'قطار کے View / Edit سے وہی پینل کھلتا ہے؛ View = مکمل read-only۔',
        2 => 'آف لائن (دکان) گاہک یہیں بنتے ہیں؛ آن لائن گاہک سٹور سے آتے ہیں اور admin میں کئی فیلڈ محدود ہوتے ہیں۔',
        3 => 'phoneRows: ایک یا زیادہ موبائل؛ قطار Add/Remove کی جا سکتی ہے (کم از کم ایک رہے)۔',
        4 => 'اختیاری AI بٹن → دو لسانی نام/پتہ بھرتا ہے (فون/بیلنس نہیں)۔',
        5 => 'Footer: AI → Save & Add Next → Save & Close → Cancel؛ کامیابی پر customers-saved + پینل بند۔',
      ),
      'functional_requirements' => 
      array (
        0 => 'FR-01: آف لائن گاہک ماسٹر بنائیں جسے POS اور Customer Ledger استعمال کریں۔',
        1 => 'FR-02: موجودہ گاہک اسی فارم پینل میں دیکھیں یا ترمیم کریں۔',
        2 => 'FR-03: customer_number خودکار منفرد (CUS-#####)۔',
        3 => 'FR-04: دو لسانی نام (name_en/ur) اور پتہ اختیاری۔',
        4 => 'FR-05: کم از کم ایک موبائل لازمی؛ نمبر normalize ہو کر منفرد ہوں۔',
        5 => 'FR-06: شہر پاکستان شہر فہرست سے (city_key → city_en/ur)۔',
        6 => 'FR-07: opening_balance اور credit_limit اختیاری۔',
        7 => 'FR-08: پروفائل تصویر اختیاری۔',
        8 => 'FR-09: AI enrichment صرف متنی نام/پتہ؛ حساس ڈیٹا نہیں۔',
        9 => 'FR-10: Save & Add Next سے مسلسل کئی گاہک۔',
        10 => 'FR-11: آن لائن گاہک admin update میں محدود فیلڈز (auth فیلڈز محفوظ)۔',
      ),
      'validation' => 
      array (
        0 => 'display_name required max 255',
        1 => 'customer_number nullable unique:customers max 32 (خالی → خودکار CUS-#####)',
        2 => 'name_en / name_ur nullable max 255',
        3 => 'address_quick nullable max 500؛ address_en / address_ur nullable max 2000',
        4 => 'city_key nullable max 64؛ city_en / city_ur nullable max 255',
        5 => 'opening_balance nullable numeric (خالی → 0)؛ credit_limit nullable numeric min 0 (خالی → null)',
        6 => 'email nullable email unique:customers؛ خالی → offline.<code>@contacts.agricart.local',
        7 => 'phoneRows required array min 1؛ mobile_number nullable max 30؛ normalize کے بعد کم از کم ایک لازمی',
        8 => 'فون منفرد: اسی فارم میں اور موجودہ ریکارڈز میں (92/0 ہٹا کر آخری 10 ہندسے)',
        9 => 'آن لائن admin update: صرف name/address/city/balance/credit/flags/photo — auth فیلڈز نہیں',
      ),
      'database' => 
      array (
        0 => 'جدول customers (بنیادی): id, customer_number (unique, nullable), first_name, last_name, email (unique), phone(30), password, address_line1/2, city, state, postal_code, country default Pakistan, accepts_marketing, is_active, email_verified_at, remember_token, timestamps',
        1 => 'توسیع (contacts admin): customer_channel (default online), display_name, name_en/ur, address_en/ur, address_quick, city_key/en/ur, opening_balance decimal(15,2) default 0, credit_limit decimal(15,2), contacts JSON, profile_photo_path, ai_status',
        2 => 'انڈیکس: customer_channel, name_en, city_en',
        3 => 'آف لائن legacy: display_name سے first/last؛ address_line1 quick/en سے؛ city city_en/ur سے؛ فون contacts JSON میں + phone پہلا نمبر',
      ),
      'api' => 
      array (
        0 => 'کوئی عوامی REST API نہیں — Livewire فارم پینل (admin session)',
        1 => 'events: customer-form-open، customer-form-action (ai|save_next|save_close)، customers-saved، close-form-panel، customer-form-busy',
        2 => 'سروسز: CustomerService (createOffline/update/validateOffline/validateOnlineAdmin/nextCustomerNumber/normalizePhone/assertPhonesUnique/toFormState)، CustomerEnrichmentService',
        3 => 'formKey: customers؛ شیئرڈ Module Form Panel سے جڑا',
      ),
      'permissions' => 
      array (
        0 => 'نیا ریکارڈ: contacts.create',
        1 => 'ترمیم: contacts.edit؛ Super Admin کو مکمل رسائی',
        2 => 'View موڈ: فارم read-only؛ save/AI غیر فعال',
        3 => 'اجازت نہ ہو تو persist روک دیا جاتا ہے',
      ),
      'edge_cases' => 
      array (
        0 => 'آن لائن گاہک admin سے create نہیں ہوتے؛ update میں first/last name، email، password، channel محفوظ رہتے ہیں',
        1 => 'email خالی → جعلی offline.<code>@contacts.agricart.local لگ جاتا ہے (unique email توڑنے سے بچاؤ)',
        2 => '+92 / 0 / اسپیس والے فون ایک ہی identity میں resolve ہوتے ہیں',
        3 => 'ڈپلیکیٹ فون (اسی فارم یا موجودہ ریکارڈ) → save روک',
        4 => 'opening_balance خالی → 0؛ credit_limit خالی → null',
        5 => 'تصویر بدلنے پر پرانی فائل کامیاب save کے بعد ہٹتی ہے',
        6 => 'موجودہ delete گاہک + تصویر ہٹا دیتا ہے (ledger guard نہیں — بہتری دیکھیں)',
        7 => 'شہر منتخب کرتے ہی city_en/ur خودکار بھر جاتے ہیں',
      ),
      'business_rules' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'non_functional_requirements' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'dependencies' => 
      array (
        0 => 'Sales / POS — سیل پر customer_id',
        1 => 'Store / Online Orders — OnlineSaleOrder belongsTo Customer؛ سٹور authentication بھی یہی ماڈل',
        2 => 'Reports → Customer Ledger — opening balance + sales/payments',
        3 => 'Media — پروفائل تصویر public disk (OptimizedImageStorage)',
        4 => 'AI — CustomerEnrichmentService + AiLog',
        5 => 'Contacts Overview — customers کارڈ (گنتی/لنک)',
        6 => 'Permissions — contacts.* (view/create/edit/delete)',
      ),
    ),
  ),
  'suppliers' => 
  array (
    'list' => 
    array (
      'title' => 'سپلائرز کی فہرست',
      'summary' => 'وینڈرز کی فہرست؛ خریداری اور سپلائر لیجر یہیں سے منتخب ہوتے ہیں۔',
      'article' => 
      array (
        0 => 'Suppliers صفحہ Contacts میں گاہکوں جیسا ہی نمونہ رکھتا ہے: فہرست، سرچ، Add Supplier، فارم پینل۔',
        1 => 'یہ ریکارڈ Purchasing اور Supplier Ledger سے جڑتے ہیں۔ غلط یا ڈپلیکیٹ سپلائر بعد میں ادائیگیوں میں الجھن پیدا کرتا ہے — نام اور فون صاف رکھیں۔',
      ),
      'fields' => 
      array (
        0 => 
        array (
          'name' => 'search',
          'label' => 'تلاش',
          'note' => 'supplier_code, business_name_en/ur, city_en/ur, email',
        ),
        1 => 
        array (
          'name' => 'filter',
          'label' => 'فلٹر',
          'note' => 'status (active/inactive) + city',
        ),
        2 => 
        array (
          'name' => 'columns',
          'label' => 'کالمز',
          'note' => 'code, business, contact, city, type, status, categories, created',
        ),
        3 => 
        array (
          'name' => 'Add Supplier',
          'label' => 'نیا سپلائر',
          'note' => 'فارم پینل (create)',
        ),
        4 => 
        array (
          'name' => 'row actions',
          'label' => 'قطار ایکشنز',
          'note' => 'View / Edit / Delete',
        ),
      ),
      'improvements' => 
      array (
        0 => 
        array (
          'text' => 'سپلائر کوڈ خودکار',
          'suggest' => 'keep',
        ),
        1 => 
        array (
          'text' => 'ٹیکس نمبر / NTN فیلڈ',
          'suggest' => 'optional',
        ),
        2 => 
        array (
          'text' => 'حذف تب روکیں جب purchases ہوں',
          'suggest' => 'add',
        ),
      ),
      'technical' => 
      array (
        0 => 'صفحہ: /admin/contacts/suppliers',
        1 => 'جدول: suppliers',
      ),
      'prompt' => 'نئے سسٹم میں Suppliers فہرست صفحہ بنائیں: سرچ، فلٹر، کالمز، Add Supplier → فارم پینل۔
اگر سپلائر پر خریداری ہو تو delete روکیں۔ UI Agricart شیل جیسا رکھیں۔',
      'ui_flow' => 
      array (
        0 => 'Contacts → Suppliers کھلتے ہی صفحہ بند فہرست (15 فی صفحہ، نئے پہلے)۔',
        1 => 'ٹول بار: سرچ، Filter (status + city)، Columns، سبز Add Supplier۔',
        2 => 'Add Supplier → شیئرڈ فارم پینل (create)؛ قطار View/Edit وہی پینل۔',
        3 => 'Delete قطار سے — صرف contacts.delete اجازت پر۔',
      ),
      'functional_requirements' => 
      array (
        0 => 'FR-01: تمام سپلائرز کی صفحہ بند فہرست + کیٹگریز۔',
        1 => 'FR-02: سرچ code/business/city/email۔',
        2 => 'FR-03: فلٹر status اور city۔',
        3 => 'FR-04: کالم toggle (کم از کم business_name)۔',
        4 => 'FR-05: Add / View / Edit فارم پینل۔',
        5 => 'FR-06: Delete (اجازت پر)۔',
      ),
      'validation' => 
      array (
        0 => 'لسٹ صفحہ — فارم validation نہیں',
        1 => 'Delete: صرف contacts.delete (یا Super Admin)',
        2 => 'خالی سرچ/فلٹر → مکمل فہرست',
      ),
      'database' => 
      array (
        0 => 'جدول suppliers (+ categories relation) سے پڑھتا ہے (paginate 15)',
        1 => 'ترتیب: created_at desc پھر id desc',
      ),
      'api' => 
      array (
        0 => 'کوئی عوامی REST نہیں — Livewire ListSuppliers',
        1 => 'events: suppliers-search، suppliers-filter، suppliers-clear-filters، suppliers-column-toggle، suppliers-saved',
        2 => 'open-form-panel (formKey: suppliers)',
      ),
      'permissions' => 
      array (
        0 => 'فہرست/سرچ: contacts.view',
        1 => 'Add: contacts.create؛ Edit: contacts.edit؛ Delete: contacts.delete',
        2 => 'Super Admin کو مکمل رسائی',
      ),
      'edge_cases' => 
      array (
        0 => 'موجودہ delete سپلائر + فائلیں ہٹاتا ہے؛ خریداری reference guard اس فہرست میں ثابت نہیں (بہتری)',
        1 => 'تمام کالم بند کریں تو business_name واپس آ جاتا ہے',
        2 => 'city فلٹر city_key پر ہے',
      ),
      'business_rules' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'non_functional_requirements' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'dependencies' => 
      array (
        0 => 'Suppliers Form — Add/View/Edit فارم پینل',
        1 => 'Purchasing — خریداری پر سپلائر انتخاب',
        2 => 'Reports → Supplier Ledger',
        3 => 'Catalog / Categories — category_supplier',
        4 => 'Contacts Overview — suppliers کارڈ',
      ),
    ),
    'form' => 
    array (
      'title' => 'سپلائر فارم — شامل کریں / دیکھیں / ترمیم',
      'summary' => 'تین باکس نمونہ: (۱) موجودہ سسٹم — مضمون + UI Flow + FR + فیلڈز + Validation + Database + API + Permissions + Edge Cases؛ (۲) میری بہتری؛ (۳) Ready-to-Code پیغام۔',
      'article' => 
      array (
        0 => 'سوچیں کہ خریداری شروع کرنے سے پہلے سب سے پہلے کس کی ضرورت ہے؟ جس سے سامان آتا ہے — سپلائر۔ یہ فارم اسی شخص یا کمپنی کا ماسٹر کارڈ بناتا ہے۔ Contacts → Suppliers پر Add Supplier دبائیں تو دائیں شیئرڈ فارم پینل کھلتا ہے؛ الگ پورا صفحہ نہیں۔ Create، Edit اور View تینوں اسی پینل میں ہیں۔',
        1 => 'یہ ریکارڈ بعد میں Purchasing کی خریداری، سپلائر لیجر، فنڈ ٹرانسفر کی وینڈر ادائیگی، اور کیٹگری لنک سے جڑتا ہے۔ اس لیے نام، پتہ اور پہلا موبائل صاف ہونا اتنا اہم ہے جتنا گاہک کا فون۔',
        2 => 'آج کے سسٹم میں سپلائر کوڈ خود بنتا ہے (عموماً SUP-#####)۔ کاروباری نام انگریزی یا اردو میں سے کم از کم ایک لازمی ہے۔ پتہ بھی انگریزی یا اردو میں سے کم از کم ایک لازمی ہے۔ ملک ڈیفالٹ پاکستان ہے؛ پاکستان پر شہر فہرست سے چنتے ہیں، دوسرے ملک پر شہر ہاتھ سے لکھتے ہیں۔',
        3 => 'پہلی رابطہ قطار لازمی ہے: نام (انگریزی یا اردو) اور موبائل۔ مزید رابطے، بینک اکاؤنٹس، کیٹگریز، کریڈٹ حد اور افتتاحی بیلنس اختیاری ہیں۔ پورٹل آن کرنے پر نئے ریکارڈ میں ای میل اور پاس ورڈ لازمی ہو جاتے ہیں۔ تصاویر (پروفائل، کارڈ سامنے/پیچھے) اختیاری ہیں۔',
        4 => 'AI بٹن دو لسانی کاروباری نام، پتہ، شہر اور رابطہ نام بھرنے میں مدد دے سکتا ہے — مگر فون نمبر، بینک نمبر، بیلنس، کیٹگری، ای میل یا پاس ورڈ AI خود نہیں لکھتا۔ فوٹر: AI → Save & Add Next → Save & Close → Cancel۔ View موڈ میں سب کچھ صرف پڑھنے کے لیے ہے۔',
      ),
      'fields' => 
      array (
        0 => 
        array (
          'name' => 'supplier_code',
          'label' => 'سپلائر کوڈ',
          'note' => 'خودکار SUP-#####؛ صارف سے نہیں مانگا جاتا',
        ),
        1 => 
        array (
          'name' => 'supplier_type',
          'label' => 'قسم / ٹائپ',
          'note' => 'اختیاری مختصر متن (max 64)',
        ),
        2 => 
        array (
          'name' => 'status',
          'label' => 'حالت',
          'note' => 'active یا inactive؛ نیا ریکارڈ عموماً active',
        ),
        3 => 
        array (
          'name' => 'business_name_en',
          'label' => 'کاروباری نام — انگریزی',
          'note' => 'اگر اردو خالی ہو تو لازمی',
        ),
        4 => 
        array (
          'name' => 'business_name_ur',
          'label' => 'کاروباری نام — اردو',
          'note' => 'اگر انگریزی خالی ہو تو لازمی',
        ),
        5 => 
        array (
          'name' => 'country_code / country_ur',
          'label' => 'ملک',
          'note' => 'کوڈ لازمی؛ ڈیفالٹ PK',
        ),
        6 => 
        array (
          'name' => 'city_key',
          'label' => 'شہر (پاکستان)',
          'note' => 'PK ہو تو فہرست سے لازمی',
        ),
        7 => 
        array (
          'name' => 'city_manual / city_en / city_ur',
          'label' => 'شہر (دیگر ملک)',
          'note' => 'غیر PK پر دستی شہر لازمی',
        ),
        8 => 
        array (
          'name' => 'address_en / address_ur',
          'label' => 'پتہ',
          'note' => 'کم از کم ایک زبان لازمی؛ max 2000',
        ),
        9 => 
        array (
          'name' => 'contactRows[].contact_name_en/ur',
          'label' => 'رابطہ نام',
          'note' => 'پہلی قطار میں EN یا UR لازمی',
        ),
        10 => 
        array (
          'name' => 'contactRows[].mobile_number',
          'label' => 'موبائل',
          'note' => 'پہلی قطار لازمی؛ مزید قطاریں ممکن',
        ),
        11 => 
        array (
          'name' => 'category_ids',
          'label' => 'کیٹگریز',
          'note' => 'اختیاری؛ موجودہ کیٹگریز؛ pivot sync',
        ),
        12 => 
        array (
          'name' => 'bankRows[]',
          'label' => 'بینک حوالہ',
          'note' => 'اختیاری: بینک، ٹائٹل EN/UR، اکاؤنٹ نمبر',
        ),
        13 => 
        array (
          'name' => 'credit_limit',
          'label' => 'کریڈٹ حد',
          'note' => 'اختیاری نمبر',
        ),
        14 => 
        array (
          'name' => 'opening_balance',
          'label' => 'افتتاحی بیلنس',
          'note' => 'اختیاری؛ خالی = 0',
        ),
        15 => 
        array (
          'name' => 'portal_enabled',
          'label' => 'سپلائر پورٹل',
          'note' => 'اختیاری آن/آف',
        ),
        16 => 
        array (
          'name' => 'email',
          'label' => 'ای میل',
          'note' => 'پورٹل + نیا ریکارڈ پر لازمی اور منفرد',
        ),
        17 => 
        array (
          'name' => 'password / password_confirmation',
          'label' => 'پاس ورڈ',
          'note' => 'پورٹل + نیا ریکارڈ پر لازمی؛ کم از کم 8',
        ),
        18 => 
        array (
          'name' => 'profilePhotoUpload',
          'label' => 'پروفائل تصویر',
          'note' => 'اختیاری؛ max 5MB',
        ),
        19 => 
        array (
          'name' => 'cardFrontUpload / cardBackUpload',
          'label' => 'کارڈ تصاویر',
          'note' => 'اختیاری سامنے/پیچھے',
        ),
        20 => 
        array (
          'name' => 'AI + ai_status',
          'label' => 'AI مدد',
          'note' => 'صرف متن؛ حساس نمبرز نہیں',
        ),
        21 => 
        array (
          'name' => 'footer',
          'label' => 'فوٹر بٹن',
          'note' => 'AI → Save & Add Next → Save & Close → Cancel',
        ),
      ),
      'improvements' => 
      array (
        0 => 
        array (
          'text' => 'رکھیں: خودکار کوڈ، دو لسانی نام/پتہ، شہر فہرست، اور پہلا موبائل — بغیر ان کے خریداری ٹوٹ جاتی ہے۔',
          'suggest' => 'keep',
        ),
        1 => 
        array (
          'text' => 'بہتر کریں: افتتاحی بیلنس کو ماسٹر فیلڈ پر گھٹانے کی بجائے لیجر اندراج بنائیں؛ ادائیگی ایک ہی بار Finance سے پوسٹ ہو۔',
          'suggest' => 'add',
        ),
        2 => 
        array (
          'text' => 'بہتر کریں: خریداری/ادائیگی موجود ہو تو حذف بند؛ صرف inactive یا archive۔',
          'suggest' => 'add',
        ),
        3 => 
        array (
          'text' => 'بہتر کریں: پورٹل لاگ اِن کو سپلائر ماسٹر سے الگ صارف ماڈل میں رکھیں تاکہ پاس ورڈ الجھن کم ہو۔',
          'suggest' => 'add',
        ),
        4 => 
        array (
          'text' => 'بہتر کریں: خالی بینک قطاریں محفوظ نہ ہوں؛ اکاؤنٹ نمبر ماسک/آڈٹ کے ساتھ۔',
          'suggest' => 'add',
        ),
        5 => 
        array (
          'text' => 'ہٹائیں / ابھی نہ ڈالیں: NTN یا STRN — ٹیکس رپورٹنگ شروع ہونے تک فارم لمبا کرنے کی ضرورت نہیں۔',
          'suggest' => 'remove',
        ),
        6 => 
        array (
          'text' => 'اختیاری بعد میں: ادائیگی کی مدت (ایام) — جب خریداری پر الگ کریڈٹ پالیسی بنے؛ اب کریڈٹ حد کافی ہے۔',
          'suggest' => 'optional',
        ),
        7 => 
        array (
          'text' => 'رکھیں: AI، مگر Save سے پہلے جائزہ؛ AI فون/بینک/پاس ورڈ کبھی خود نہ لکھے۔',
          'suggest' => 'keep',
        ),
      ),
      'technical' => 
      array (
        0 => 'راستہ: /admin/contacts/suppliers',
        1 => 'Livewire: App\\Livewire\\Suppliers\\Form',
        2 => 'سروس: SupplierService + SupplierEnrichmentService',
        3 => 'جدول: suppliers ، category_supplier',
        4 => 'اجازت: contacts.create / contacts.edit',
        5 => 'formKey: suppliers',
      ),
      'prompt' => 'Agricart ERP — Contacts → Suppliers کا Add/Edit Supplier فارم بالکل موجودہ لائیو سسٹم جیسا بنائیں۔ نئی بہتریاں اس پیغام میں شامل نہیں؛ صرف آج والا رویہ نقل کریں۔

## UI Flow
1. /admin/contacts/suppliers → Add Supplier → شیئرڈ فارم پینل
2. موڈز: create | edit | view (view = read-only)
3. فوٹر: AI → Save & Add Next → Save & Close → Cancel
4. ڈیزائن: مربع کونے، Arial، پرائمری #83B735

## Functional Requirements
FR-01..11 جیسا موجودہ بریف: ماسٹر سپلائر CRUD پینل، auto code، bilingual نام/پتہ، PK سٹی لسٹ، پہلا رابطہ لازمی، اختیاری بینک/کیٹگری/کریڈٹ/بیلنس/پورٹل/تصاویر، AI صرف متن، Save & Add Next۔

## Fields + Validation (لائیو قواعد)
1) supplier_code خودکار SUP-#####
2) supplier_type optional max 64
3) status active|inactive؛ create default active
4) business_name_en/ur — required_without ایک دوسرے؛ max 255
5) country_code required default PK؛ country_ur sync
6) PK → city_key required؛ غیر PK → city_manual required
7) address_en/ur — required_without؛ max 2000
8) contactRows.0: name EN یا UR + mobile required؛ مزید قطاریں optional
9) category_ids optional → category_supplier sync
10) bankRows optional JSON
11) credit_limit / opening_balance nullable numeric؛ blank opening = 0
12) portal create → email unique required + password min 8 + confirmation
13) edit پاس ورڈ صرف نیا داخل کرنے پر
14) images optional max 5MB
15) AI: صرف bilingual business/address/city/contact names؛ کبھی mobile/bank/balances/categories/email/password/portal نہ بدلے
16) ai_status: none|pending|enriched|failed|manual

## Database
- suppliers + category_supplier (اوپر بریف میں کالمز)
- contacts / bank_accounts JSON

## API / Integration
- کوئی عوامی REST نہیں؛ Livewire + SupplierService + SupplierEnrichmentService
- events: supplier-form-open / supplier-form-action

## Permissions
- contacts.create / contacts.edit؛ view = read-only

## Edge Cases
- ملک تبدیل → شہر کلئیر
- AI خالی فیلڈز ہی بھرتا ہے
- referenced supplier hard delete نہیں
- Save & Add Next کے بعد خالی create فارم

قبولیت: منفرد code؛ بغیر پہلا موبائل/نام/پتہ save منع؛ portal create بغیر email/password منع۔
فہرست صفحہ اس پیغام سے باہر ہے۔',
      'ui_flow' => 
      array (
        0 => 'Contacts → Suppliers فہرست پر جائیں۔',
        1 => 'Add Supplier دبائیں → دائیں شیئرڈ فارم پینل کھلتا ہے (الگ فل پیج URL نہیں)۔',
        2 => 'Create: خالی فارم؛ Edit/View: موجودہ سپلائر لوڈ؛ View = سب read-only۔',
        3 => 'اختیاری: AI بٹن → دو لسانی نام/پتہ/شہر/رابطہ نام بھریں (فون/بینک/پاس ورڈ نہیں)۔',
        4 => 'Save & Close = محفوظ + پینل بند؛ Save & Add Next = محفوظ + نیا خالی create؛ Cancel = بغیر محفوظ بند۔',
        5 => 'کامیاب save کے بعد فہرست ریفریش / status پیغام؛ validation fail پر خطرے والا پیغام۔',
      ),
      'functional_requirements' => 
      array (
        0 => 'FR-01: نیا سپلائر ماسٹر ریکارڈ بنائیں جسے بعد میں Purchasing / Supplier Ledger استعمال کرے۔',
        1 => 'FR-02: موجودہ سپلائر دیکھیں یا ترمیم کریں اسی فارم پینل میں۔',
        2 => 'FR-03: supplier_code خودکار منفرد پیدا کریں (SUP-#####)۔',
        3 => 'FR-04: دو لسانی کاروباری نام اور پتہ سپورٹ کریں (کم از کم ایک زبان لازمی)۔',
        4 => 'FR-05: ملک PK پر شہر فہرست؛ غیر PK پر دستی شہر۔',
        5 => 'FR-06: کم از کم ایک رابطہ قطار (نام + موبائل)۔',
        6 => 'FR-07: اختیاری متعدد بینک اکاؤنٹس، کیٹگریز، کریڈٹ حد، افتتاحی بیلنس۔',
        7 => 'FR-08: اختیاری سپلائر پورٹل — create پر ای میل/پاس ورڈ۔',
        8 => 'FR-09: اختیاری پروفائل اور کارڈ تصاویر (max 5MB)۔',
        9 => 'FR-10: AI enrichment صرف متنی فیلڈز؛ حساس ڈیٹا کبھی خود نہ لکھے۔',
        10 => 'FR-11: Save & Add Next سے مسلسل کئی سپلائر شامل کیے جا سکیں۔',
      ),
      'validation' => 
      array (
        0 => 'business_name_en required_without business_name_ur (اور الٹ)؛ max 255',
        1 => 'address_en required_without address_ur (اور الٹ)؛ max 2000',
        2 => 'contactRows.0: contact_name_en یا _ur لازمی؛ mobile_number لازمی max 30',
        3 => 'country_code لازمی max 8؛ status in:active,inactive',
        4 => 'PK → city_key لازمی؛ غیر PK → city_manual لازمی max 255',
        5 => 'supplier_type nullable max 64؛ credit_limit / opening_balance nullable numeric',
        6 => 'email nullable|email|unique:suppliers؛ portal+create پر email/password لازمی (password min 8 same:confirmation)',
        7 => 'edit + portal: پاس ورڈ صرف تب جب صارف نیا دے',
        8 => 'تصاویر: nullable|image|max:5120 (5MB)',
        9 => 'اجازت نہ ہو تو save منع (contacts.create / contacts.edit)',
      ),
      'database' => 
      array (
        0 => 'جدول suppliers: id, supplier_code (unique), supplier_type, status(default active), business_name_en/ur, country_code/ur, city_key/en/ur, address_en/ur, credit_limit decimal(15,2), opening_balance decimal(15,2) default 0, contacts JSON, bank_accounts JSON, portal_enabled, email (unique nullable), password (hashed), profile/card paths, ai_status, timestamps',
        1 => 'جدول category_supplier: supplier_id + category_id (unique جوڑا)؛ cascade on delete',
        2 => 'contacts / bank_accounts JSON arrays میں محفوظ',
        3 => 'انڈیکس: status, city_en, business_name_en',
      ),
      'api' => 
      array (
        0 => 'کوئی عوامی REST API نہیں — صرف Livewire فارم پینل (admin session)',
        1 => 'ایونٹس: supplier-form-open، supplier-form-action (ai | save_next | save_close)',
        2 => 'سروسز: SupplierService (create/update/storeUpload/toFormState)، SupplierEnrichmentService (AI)',
        3 => 'formKey: suppliers؛ شیئرڈ Module Form Panel سے جڑا',
      ),
      'permissions' => 
      array (
        0 => 'نیا ریکارڈ: contacts.create',
        1 => 'ترمیم: contacts.edit',
        2 => 'View موڈ: فارم read-only؛ save/AI غیر فعال',
        3 => 'اجازت نہ ہو تو access denied پیغام، ڈیٹا نہ لکھے',
      ),
      'edge_cases' => 
      array (
        0 => 'ملک بدلنے پر شہر فیلڈز خالی ہو جاتے ہیں — دوبارہ بھرنا پڑتا ہے',
        1 => 'صرف ایک زبان میں نام/پتہ → درست؛ دونوں خالی → save منع',
        2 => 'portal بند ہو تو email/password لازمی نہیں',
        3 => 'AI صرف خالی متنی فیلڈز بھرتا ہے؛ پہلے سے بھری ہوئی ویلیوز اوور رائٹ نہیں',
        4 => 'خالی opening_balance → 0 محفوظ',
        5 => 'Create کے بعد mode edit بن جاتا ہے؛ پاس ورڈ فیلڈز صاف',
        6 => 'Referenced supplier پر hard delete اس فارم کا کام نہیں — inactive استعمال کریں',
        7 => 'کیٹگری سرچ کم از کم 2 حروف کے بعد نتائج دکھاتی ہے',
      ),
      'business_rules' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'non_functional_requirements' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'dependencies' => 
      array (
        0 => 'Contacts — Suppliers فہرست/فارم (یہی ماسٹر سورس)؛ Overview سرچ سے سپلائر کھلتا ہے',
        1 => 'Catalog / Categories — category_supplier pivot؛ فارم سے کیٹگری لنک',
        2 => 'Purchasing → Purchases — ہر خریداری پر supplier_id (لازمی FK)؛ supplier_balance_snapshot؛ انوائس/ادائیگی سے suppliers.opening_balance اپڈیٹ',
        3 => 'Purchasing → Purchase Quotations — quotation پر supplier_id لازمی؛ پرنٹ/فہرست میں سپلائر نام',
        4 => 'Purchasing → Purchase Payment Summary — قطاریں supplier_id سے (یا مینوئل)؛ ایکٹو سپلائرز کی تلاش/شامل',
        5 => 'Purchasing → Goods Received — علیحدہ GRN ماڈیول نہیں؛ purchases.delivery_status (partial/received) سے اسٹاک؛ سپلائر وہی جو خریداری ریکارڈ پر',
        6 => 'Inventory / Stock — received خریداریوں کے ذریعے اسٹاک میں اضافہ؛ سپلائر بالواسطہ (purchase → supplier)',
        7 => 'Finance → Fund Transfers — destination_supplier_id (vendor payment)؛ ادائیگی پر سپلائر opening_balance کم',
        8 => 'Finance → Bank Accounts — bank_account_transactions.supplier_id؛ BankAccountService::paySupplier()',
        9 => 'Finance → Cash / Bank (عمومی) — سپلائر وینڈر ادائیگیوں کا payee',
        10 => 'Reports → Supplier Ledger — خریداری، انوائس ادائیگی، fund transfer vendor payment سے AP موومنٹ',
        11 => 'Reports → Seasonal Purchases — خریداری ڈیٹا (سپلائر کالم اختیاری/پوشیدہ ہو سکتا ہے)',
        12 => 'Reports → Profit & Loss / دیگر — براہِ راست supplier master نہیں؛ خریداری/لاگت کے ذریعے بالواسطہ',
        13 => 'Store / Online → Order Dispatch — online_sale_order_supplier_assignments؛ سپلائر کو اسائن؛ WhatsApp/mail میں primaryMobile() اور email',
        14 => 'Print — purchase.blade.php اور purchase-quotation پرنٹ میں سپلائر نام، پتہ، فون، بیلنس snapshot',
        15 => 'Print — Purchase Payment Summary پرنٹ میں سپلائر/پارٹی کالم',
        16 => 'AI — SupplierEnrichmentService (فارم AI بٹن)؛ AiLogType::SupplierEnrichment لاگ',
        17 => 'Contacts Overview — سپلائرز کارڈ (گنتی/لنک)؛ Dashboard پر علیحدہ سپلائر ویجٹ نہیں',
        18 => 'Permissions — contacts.create / contacts.edit (فارم)؛ purchasing/finance/reports اپنی اجازتیں',
        19 => 'مستقبل / ابھی نہیں — Purchase Returns کا علیحدہ ماڈیول کوڈبیس میں نہیں؛ Re-order / Purchase Planning میں براہِ راست supplier_id لنک ابھی نہیں',
        20 => 'مستقبل — supplier portal_enabled + email/password ماسٹر پر؛ علیحدہ پورٹل لاگ اِن UI اس فارم سے باہر ہو سکتا ہے',
      ),
    ),
  ),
  'overview' => 
  array (
    'page' => 
    array (
      'title' => 'Contacts — جائزہ',
      'summary' => 'گاہک اور سپلائر ماسٹر ڈیٹا کا دروازہ۔',
      'article' => 
      array (
        0 => 'Contacts ماڈیول دو اہم فہرستیں رکھتا ہے: Customers اور Suppliers۔ نیا سسٹم بناتے وقت پہلے یہ ماسٹر ڈیٹا بنائیں، پھر Sales/Purchasing جوڑیں۔',
      ),
      'fields' => 
      array (
      ),
      'improvements' => 
      array (
        0 => 
        array (
          'text' => 'Overview پر صرف کارڈز/لنکس رکھیں، بھاری ویجٹس بعد میں',
          'suggest' => 'keep',
        ),
      ),
      'technical' => 
      array (
        0 => '/admin/contacts/overview',
      ),
      'prompt' => 'Contacts Overview صفحہ: مختصر تعارف + لنکس Customers اور Suppliers کی طرف۔ بھاری ڈیش بورڈ نہ بنائیں۔',
      'ui_flow' => 
      array (
        0 => 'سائیڈبار Contacts → Overview صفحہ کھلتا ہے۔',
        1 => 'Customers اور Suppliers کارڈز (گنتی/فعال) اور ان کے لنک۔',
        2 => 'کارڈ سے متعلقہ فہرست (Customers یا Suppliers) پر جائیں۔',
      ),
      'functional_requirements' => 
      array (
        0 => 'FR-01: Contacts ماڈیول کا داخلی صفحہ۔',
        1 => 'FR-02: Customers اور Suppliers فہرستوں کی طرف رہنمائی۔',
        2 => 'FR-03: مختصر گنتی/فعال کارڈز (ContactsOverviewService)۔',
      ),
      'validation' => 
      array (
        0 => 'Overview — کوئی فارم/validation نہیں',
      ),
      'database' => 
      array (
        0 => 'براہِ راست جدول نہیں؛ customers اور suppliers کی گنتی',
      ),
      'api' => 
      array (
        0 => 'کوئی عوامی REST نہیں — Filament صفحہ',
        1 => 'سروس: ContactsOverviewService (کارڈز)',
      ),
      'permissions' => 
      array (
        0 => 'رسائی: contacts.view',
      ),
      'edge_cases' => 
      array (
        0 => 'گاہک/سپلائر نہ ہوں تو کارڈ 0 دکھاتے ہیں',
      ),
      'business_rules' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'non_functional_requirements' => 
      array (
        0 => '— بعد میں بھریں —',
        1 => '— بعد میں بھریں —',
        2 => '— بعد میں بھریں —',
        3 => '— بعد میں بھریں —',
        4 => '— بعد میں بھریں —',
      ),
      'dependencies' => 
      array (
        0 => 'Customers — گاہک ماسٹر',
        1 => 'Suppliers — سپلائر ماسٹر',
        2 => 'Sales / Store / Purchasing / Finance / Reports — انہی ماسٹرز کے صارف',
      ),
    ),
  ),
);
