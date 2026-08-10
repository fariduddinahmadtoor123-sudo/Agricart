<?php

/**
 * Finance — Urdu Overview خالی؛ AI Prompt انگریزی؛ Notes خالی۔
 */
return [
    'overview' => [
        'page' => [
            'title' => 'Finance — Overview',
            'overview' => [],
            'prompt' => <<<'PROMPT'
Build Finance Overview: links to Categories, Expenses (heads), Payments, Banks, Transfers. Keep it light.
PROMPT,
            'notes' => [],
        ],
    ],

    'expense-categories' => [
        'list' => [
            'title' => 'Expense Categories — List',
            'overview' => [],
            'prompt' => <<<'PROMPT'
Build Finance Expense Categories list + Add: bilingual name EN/UR, status. Block delete when heads exist.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Expense Categories — Form',
            'overview' => [],
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
            'overview' => [],
            'prompt' => <<<'PROMPT'
Expense Heads list: category, name, number EXP-#####, status. Add → form. Do not show Amount column.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Expense Heads — Form',
            'overview' => [],
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
            'overview' => [],
            'prompt' => <<<'PROMPT'
Expense Payments list: date, head, category, amount, wallet. Add opens the payment form.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Expense Payments — Form',
            'overview' => [],
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
