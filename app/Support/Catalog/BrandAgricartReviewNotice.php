<?php

namespace App\Support\Catalog;

/**
 * Editable site-wide Agricart brand-review legal notice (defaults only).
 */
final class BrandAgricartReviewNotice
{
    public static function defaultEnglish(): string
    {
        return 'Important Notice: Brand ratings and reviews published on AgriCart.pk represent our independent editorial opinion based on approximately 27 years of practical industry experience, market observations, and general feedback received from customers, dealers, mechanics, and other industry professionals. These ratings are intended solely to help customers make informed purchasing decisions and are not intended to promote, degrade, or defame any brand. As market conditions, product quality, and user experiences evolve over time, these ratings and reviews may be revised periodically.';
    }

    public static function defaultUrdu(): string
    {
        return 'اہم نوٹ: AgriCart.pk پر شائع کی جانے والی برانڈ ریٹنگ اور ریویو ہمارے تقریباً 27 سالہ عملی تجربے، مارکیٹ کے مشاہدات، صارفین، ڈیلرز، مکینکس اور متعلقہ شعبے سے موصول ہونے والے عمومی فیڈبیک کی بنیاد پر ایک آزاد ادارتی رائے (Editorial Opinion) ہے۔ اس کا مقصد کسی برانڈ کی توہین، تنقیص، تشہیر یا ترجیح دینا نہیں بلکہ صارفین کو بہتر خریداری کے فیصلے میں مدد فراہم کرنا ہے۔ مارکیٹ کے حالات، مصنوعات کے معیار اور صارفین کے تجربات وقت کے ساتھ تبدیل ہو سکتے ہیں، اس لیے یہ ریٹنگز بھی وقتاً فوقتاً اپڈیٹ کی جا سکتی ہیں۔';
    }
}
