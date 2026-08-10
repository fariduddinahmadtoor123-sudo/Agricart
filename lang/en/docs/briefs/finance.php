<?php

return [
    'overview' => [
        'page' => [
            'title' => 'Finance — Overview',
            'overview' => [
                'Finance overview links the money tree: bank accounts, expense categories → expense heads → expense payments, and fund transfers.',
                'Use cards/links only. Build masters in order: Banks, then Categories → Heads → Payments, then Transfers.',
            ],
            'prompt' => <<<'PROMPT'
Build Finance Overview: links to Categories, Expenses (heads), Payments, Banks, Transfers. Keep it light.
PROMPT,
            'notes' => [],
        ],
    ],

    'expense-categories' => [
        'list' => [
            'title' => 'Expense Categories — List',
            'overview' => [
                'Expense categories are the top buckets (electricity, rent, pay, etc.). Heads cannot exist without a category.',
                'Open Finance → Categories. Add opens the form panel. Block delete when heads still exist.',
            ],
            'prompt' => <<<'PROMPT'
Build Finance Expense Categories list + Add: bilingual name EN/UR, status. Block delete when heads exist.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Expense Categories — Form',
            'overview' => [
                'Create a main expense group: names and status only. No amount or date here — those belong on Payments.',
                'This is level 1 of Category → Head → Payment.',
            ],
            'prompt' => <<<'PROMPT'
Expense Category form: name_en required, name_ur optional, status. Optional AI.
This is level 1 of expenses (Category → Head → Payment). Do not put amount/date on this form.
PROMPT,
            'notes' => [],
        ],
    ],

    'expenses' => [
        'list' => [
            'title' => 'Expense Heads — List',
            'overview' => [
                'Expense heads are accounts inside a category (shop electricity meter, staff salary line, store rent). No money amounts on this list.',
                'Columns show category, name, auto EXP-##### number, and status. Add opens the head form.',
            ],
            'prompt' => <<<'PROMPT'
Expense Heads list: category, name, number EXP-#####, status. Add → form. Do not show Amount column.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Expense Heads — Form',
            'overview' => [
                'Pick an active category, enter the head name, and optionally write Information (meter number, bill id). EXP number is auto.',
                'Saving a head does not cut cash. Real payments happen on the Payments page. Do not put amount/date/cash/bank on this form.',
            ],
            'prompt' => <<<'PROMPT'
Expense Head form (Finance → Expenses):
- Auto EXP number
- Required active category + name_en
- Information textarea (meter/bill notes)
- No amount, date, cash/bank fields
- Optional AI
- Block delete when payments exist
PROMPT,
            'notes' => [],
        ],
    ],

    'expense-payments' => [
        'list' => [
            'title' => 'Expense Payments — List',
            'overview' => [
                'Each row is a real payment (EXPAY). Cash book, bank ledger, and P&L operating expenses are affected from here.',
                'Columns typically show date, head, category, amount, and wallet. Add opens the payment form.',
            ],
            'prompt' => <<<'PROMPT'
Expense Payments list: date, head, category, amount, wallet. Add opens the payment form.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Expense Payments — Form',
            'overview' => [
                'Select an active expense head, enter amount and date (default today), choose Cash or Bank (bank account required when Bank).',
                'Save posts to the ledger. After posting, amount/date stay locked; only notes remain editable. No AI on this form. Block overdraw and delete.',
            ],
            'prompt' => <<<'PROMPT'
Expense Payment form:
- Auto EXPAY number
- Active expense head required
- amount + payment_date (default today)
- Cash or Bank; Bank requires active account
- Save posts ledger OUT/DEBIT
- After save only notes editable; delete forbidden
- No AI
- Prevent balance overdraw
PROMPT,
            'notes' => [],
        ],
    ],
];
