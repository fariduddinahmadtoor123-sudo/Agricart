<?php

namespace App\Support\Catalog;

/**
 * Seed / fallback bilingual Pass (high) and Fail (low) suggested review lines.
 * Runtime values live in brand_agricart_review_messages — this class is only the factory default.
 */
final class BrandAgricartReviewMessageDefaults
{
    /**
     * @return array<string, array{high_en: string, high_ur: string, low_en: string, low_ur: string}>
     */
    public static function all(): array
    {
        return [
            'product_quality' => [
                'high_en' => 'Consistently delivers high-quality products.',
                'high_ur' => 'مسلسل اعلیٰ معیار کی مصنوعات فراہم کرتا ہے۔',
                'low_en' => 'Product quality is not consistently maintained.',
                'low_ur' => 'مصنوعات کا معیار ہمیشہ یکساں برقرار نہیں رہتا۔',
            ],
            'value_for_money' => [
                'high_en' => 'Offers excellent value for money.',
                'high_ur' => 'قیمت کے مقابلے میں بہترین معیار فراہم کرتا ہے۔',
                'low_en' => 'Comparable alternatives are available at lower prices.',
                'low_ur' => 'اسی معیار کے متبادل برانڈز نسبتاً کم قیمت پر دستیاب ہیں۔',
            ],
            'market_reputation' => [
                'high_en' => 'Widely trusted across the market.',
                'high_ur' => 'مارکیٹ میں وسیع پیمانے پر قابلِ اعتماد سمجھا جاتا ہے۔',
                'low_en' => 'Market confidence is comparatively limited.',
                'low_ur' => 'مارکیٹ میں اعتماد کا درجہ نسبتاً محدود ہے۔',
            ],
            'durability' => [
                'high_en' => 'Products are built for long-lasting durability.',
                'high_ur' => 'مصنوعات طویل عرصے تک پائیدار کارکردگی فراہم کرتی ہیں۔',
                'low_en' => 'Durability may vary under continuous use.',
                'low_ur' => 'مسلسل استعمال میں پائیداری میں فرق آ سکتا ہے۔',
            ],
            'performance' => [
                'high_en' => 'Consistently delivers reliable performance.',
                'high_ur' => 'مسلسل قابلِ اعتماد کارکردگی فراہم کرتا ہے۔',
                'low_en' => 'Performance may not consistently meet expectations.',
                'low_ur' => 'کارکردگی ہمیشہ توقعات کے مطابق نہیں رہتی۔',
            ],
            'innovation_technology' => [
                'high_en' => 'Regularly introduces modern technologies and innovations.',
                'high_ur' => 'جدید ٹیکنالوجی اور جدت متعارف کرانے میں سرگرم رہتا ہے۔',
                'low_en' => 'Innovation is comparatively limited.',
                'low_ur' => 'جدت اور جدید ٹیکنالوجی کا استعمال نسبتاً محدود ہے۔',
            ],
            'warranty_after_sales' => [
                'high_en' => 'Provides dependable warranty and after-sales support.',
                'high_ur' => 'قابلِ اعتماد وارنٹی اور بعد از فروخت خدمات فراہم کرتا ہے۔',
                'low_en' => 'There is room for improvement in after-sales support.',
                'low_ur' => 'بعد از فروخت خدمات میں مزید بہتری کی گنجائش موجود ہے۔',
            ],
            'spare_parts_availability' => [
                'high_en' => 'Spare parts are readily available.',
                'high_ur' => 'اسپیئر پارٹس آسانی سے دستیاب ہیں۔',
                'low_en' => 'Spare parts may not always be readily available.',
                'low_ur' => 'اسپیئر پارٹس ہر وقت آسانی سے دستیاب نہیں ہوتے۔',
            ],
            'brand_reliability' => [
                'high_en' => 'Consistently maintains customer confidence.',
                'high_ur' => 'مسلسل صارفین کا اعتماد برقرار رکھتا ہے۔',
                'low_en' => 'Reliability may vary across products.',
                'low_ur' => 'مختلف مصنوعات میں اعتماد کا معیار مختلف ہو سکتا ہے۔',
            ],
            'product_availability' => [
                'high_en' => 'Products remain consistently available throughout the year.',
                'high_ur' => 'مصنوعات سال بھر مسلسل دستیاب رہتی ہیں۔',
                'low_en' => 'Product availability becomes limited during peak seasons.',
                'low_ur' => 'مصروف سیزن میں مصنوعات کی دستیابی محدود ہو جاتی ہے۔',
            ],
            'overall_market_acceptance' => [
                'high_en' => 'Highly accepted by customers and dealers.',
                'high_ur' => 'صارفین، ڈیلرز اور مارکیٹ میں وسیع پیمانے پر مقبول ہے۔',
                'low_en' => 'Market acceptance is comparatively limited.',
                'low_ur' => 'مارکیٹ میں مجموعی قبولیت کا درجہ نسبتاً محدود ہے۔',
            ],
        ];
    }

    /**
     * @return array{high_en: string, high_ur: string, low_en: string, low_ur: string}|null
     */
    public static function for(string $criterionKey): ?array
    {
        return self::all()[$criterionKey] ?? null;
    }
}
