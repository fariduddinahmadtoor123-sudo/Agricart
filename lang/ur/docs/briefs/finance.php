<?php

return [
    'expense-categories' => [
        'list' => [
            'title' => 'اخراجات کی کیٹگریز — فہرست',
            'summary' => 'بجلی، کرایہ، Pay جیسی مین کیٹگریز۔',
            'article' => [
                'Finance → Categories پہلی سطح ہے: بڑی بالٹیاں۔ ان کے بغیر expense heads نہیں بنتے۔',
            ],
            'fields' => [
                ['name' => 'Add Category', 'label' => 'نیا', 'note' => 'فارم پینل'],
            ],
            'improvements' => [
                ['text' => 'کیٹگری میں heads کی تعداد دکھائیں', 'suggest' => 'optional'],
            ],
            'technical' => ['expense_categories'],
            'prompt' => 'Finance Expense Categories فہرست + Add: نام EN/UR، status۔ اگر heads ہوں تو delete روکیں۔',
        ],
        'form' => [
            'title' => 'اخراجات کی کیٹگری — فارم',
            'summary' => 'مین گروپ بنانا۔',
            'article' => [
                'صرف نام اور سٹیٹس۔ یہاں رقم یا تاریخ نہیں — وہ Payments پر ہے۔',
            ],
            'fields' => [
                ['name' => 'name_en/ur', 'label' => 'نام', 'note' => 'لازمی'],
                ['name' => 'status', 'label' => 'سٹیٹس', 'note' => 'فعال/غیر فعال'],
            ],
            'improvements' => [
                ['text' => 'کیٹگری کوڈ اختیاری', 'suggest' => 'optional'],
            ],
            'technical' => ['ExpenseCategoryService'],
            'prompt' => <<<'PROMPT'
Expense Category فارم: name_en لازمی، name_ur اختیاری، status۔ AI اختیاری۔
یہ تین سطحی اخراجات کا پہلا درجہ ہے (Category → Head → Payment)۔ یہاں amount/date مت لگائیں۔
PROMPT,
        ],
    ],

    'expenses' => [
        'list' => [
            'title' => 'Expense Heads — فہرست',
            'summary' => 'کیٹگری کے اندر اکاؤنٹس (جیسے دکان بجلی) — بغیر رقم۔',
            'article' => [
                'Expenses صفحہ اب “ہیڈز اونلی” ہے: کون سا میٹر، کون سا سٹاف، کون سا سٹور کرایہ۔ رقم یہاں نہیں۔',
            ],
            'fields' => [],
            'improvements' => [
                ['text' => 'فہرست میں آخری ادائیگی کی تاریخ دکھائیں', 'suggest' => 'add'],
            ],
            'technical' => ['expenses جدول (heads)'],
            'prompt' => 'Expense Heads فہرست: category، نام، نمبر EXP-#####، status۔ Add → فارم۔ Amount کالم مت دکھائیں۔',
        ],
        'form' => [
            'title' => 'Expense Head — فارم',
            'summary' => 'کیٹگری + نام + information باکس۔',
            'article' => [
                'کیٹگری منتخب کریں، نام لکھیں، Information میں میٹر نمبر/بل آئی ڈی لکھیں۔ EXP نمبر خودکار۔ AI نام/information بھر سکتا ہے۔',
                'اسے save کرنے سے کیش نہیں کاٹتا۔ ادائیگی الگ Payments صفحے پر۔',
            ],
            'fields' => [
                ['name' => 'expense_number', 'label' => 'نمبر', 'note' => 'خودکار EXP-#####'],
                ['name' => 'expense_category_id', 'label' => 'کیٹگری', 'note' => 'لازمی'],
                ['name' => 'name_en/ur', 'label' => 'نام', 'note' => 'لازمی EN'],
                ['name' => 'information', 'label' => 'معلومات', 'note' => 'میٹر/بل/نوٹ'],
                ['name' => 'status', 'label' => 'سٹیٹس', 'note' => ''],
            ],
            'improvements' => [
                ['text' => 'amount/date اس فارم پر کبھی واپس نہ لائیں', 'suggest' => 'keep'],
                ['text' => 'payments ہونے پر delete روکیں', 'suggest' => 'keep'],
            ],
            'technical' => ['ExpenseService — no ledger post'],
            'prompt' => <<<'PROMPT'
Expense Head فارم (Finance → Expenses):
- خودکار EXP نمبر
- لازمی active category + name_en
- information ٹیکسٹ ایریا (میٹر وغیرہ)
- کوئی amount، date، cash/bank فیلڈ نہیں
- AI اختیاری
- payments موجود ہوں تو delete منع
PROMPT,
        ],
    ],

    'expense-payments' => [
        'list' => [
            'title' => 'Expense Payments — فہرست',
            'summary' => 'حقیقی ادائیگیاں EXPAY؛ کیش/بینک۔',
            'article' => [
                'یہاں ہر قطار ایک ادائیگی ہے۔ P&L اور کیش بک یہیں سے متاثر ہوتے ہیں۔',
            ],
            'fields' => [],
            'improvements' => [
                ['text' => 'مہینے کے لحاظ سے فلٹر', 'suggest' => 'add'],
            ],
            'technical' => ['expense_payments'],
            'prompt' => 'Expense Payments فہرست: تاریخ، head، category، رقم، wallet۔ Add سے فارم۔',
        ],
        'form' => [
            'title' => 'Expense Payment — فارم',
            'summary' => 'ہیڈ چنیں، رقم، تاریخ، Cash/Bank۔',
            'article' => [
                'فعال expense head منتخب کریں، رقم اور تاریخ دیں، Cash یا Bank۔ Bank ہو تو اکاؤنٹ لازمی۔ Save پر ledger post ہوتا ہے — بعد میں رقم/تاریخ قفل، صرف نوٹس۔ AI نہیں۔',
            ],
            'fields' => [
                ['name' => 'payment_number', 'label' => 'EXPAY', 'note' => 'خودکار'],
                ['name' => 'expense_id', 'label' => 'Head', 'note' => 'لازمی'],
                ['name' => 'amount', 'label' => 'رقم', 'note' => 'لازمی'],
                ['name' => 'payment_date', 'label' => 'تاریخ', 'note' => 'ڈیفالٹ آج'],
                ['name' => 'source_wallet', 'label' => 'Cash/Bank', 'note' => 'لازمی'],
                ['name' => 'bank_account_id', 'label' => 'بینک', 'note' => 'Bank پر لازمی'],
                ['name' => 'notes', 'label' => 'نوٹس', 'note' => 'بعد میں بھی ایڈٹ'],
            ],
            'improvements' => [
                ['text' => 'post کے بعد amount لاک رکھیں', 'suggest' => 'keep'],
                ['text' => 'overdraw سے بچاؤ (lock)', 'suggest' => 'keep'],
                ['text' => 'ریورسل واؤچر بعد میں (ابھی سادہ رکھیں)', 'suggest' => 'optional'],
            ],
            'technical' => [
                'ExpensePaymentService + CashBook/BankAccount',
                'P&L operating expenses',
            ],
            'prompt' => <<<'PROMPT'
Expense Payment فارم:
- خودکار EXPAY نمبر
- active expense head لازمی
- amount + payment_date (آج ڈیفالٹ)
- Cash یا Bank؛ Bank پر active اکاؤنٹ
- Save پر ledger OUT/DEBIT
- بعد میں صرف notes ایڈٹ؛ delete منع
- AI مت لگائیں
- بیلنس overdraw روکیں
PROMPT,
        ],
    ],

    'overview' => [
        'page' => [
            'title' => 'Finance جائزہ',
            'summary' => 'کیش، بینک، اخراجات کا درخت۔',
            'article' => [
                'نیا سسٹم: پہلے Banks، پھر Expense Categories → Heads → Payments، پھر Transfers۔',
            ],
            'fields' => [],
            'improvements' => [],
            'technical' => [],
            'prompt' => 'Finance Overview: لنکس Categories، Expenses، Payments، Banks، Transfers۔',
        ],
    ],
];
